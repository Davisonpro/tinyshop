<?php

declare(strict_types=1);

namespace TinyShop\Services;

use Psr\Log\LoggerInterface;

/**
 * HestiaCP API client for automated custom domain provisioning.
 *
 * Each custom domain is created as a standalone web domain in HestiaCP
 * (not an alias) with its document root pointed to the main app. This
 * allows each domain to have its own Let's Encrypt SSL certificate.
 *
 * @since 1.0.0
 */
final class HestiaCP
{
    private string $apiUrl;
    private string $apiUser;
    private string $apiPassword;
    private string $hestiaDomain;
    private string $hestiaUser;
    private string $serverIp;

    /**
     * @param array<string, string> $config  Hestia config with keys:
     *                                       api_url, api_user, api_password,
     *                                       domain, user, server_ip.
     * @param LoggerInterface       $logger  PSR-3 logger.
     */
    public function __construct(array $config, private readonly LoggerInterface $logger)
    {
        $this->apiUrl      = rtrim($config['api_url'] ?? '', '/');
        $this->apiUser     = $config['api_user'] ?? '';
        $this->apiPassword = $config['api_password'] ?? '';
        $this->hestiaDomain = $config['domain'] ?? '';
        $this->hestiaUser  = $config['user'] ?? '';
        $this->serverIp    = $config['server_ip'] ?? '';
    }

    /**
     * Check whether the HestiaCP integration is configured.
     *
     * @since 1.0.0
     */
    public function isConfigured(): bool
    {
        return $this->apiUrl !== ''
            && $this->apiUser !== ''
            && $this->apiPassword !== ''
            && $this->hestiaDomain !== ''
            && $this->hestiaUser !== '';
    }

    /**
     * Fully provision a custom domain.
     *
     * Steps:
     * 1. Create a DNS zone so our nameservers resolve the domain
     * 2. Restart DNS so the zone is immediately active
     * 3. Add as a standalone web domain in HestiaCP
     * 4. Add www alias, point document root to the main app
     * 5. Restart web/proxy so the vhost is active
     * 6. Issue Let's Encrypt SSL via schedule + immediate processing
     * 7. Restart web/proxy for SSL to take effect
     *
     * @since 1.0.0
     *
     * @param string $domain Custom domain to add (e.g. "myshop.com").
     * @return bool True on success, false on failure.
     */
    public function addDomainAlias(string $domain): bool
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('hestia.not_configured', ['domain' => $domain]);
            return false;
        }

        if (!$this->isValidDomain($domain)) {
            $this->logger->error('hestia.invalid_domain', ['domain' => $domain]);
            return false;
        }

        $wwwAlias = 'www.' . $domain;

        // 1. DNS zone (best-effort, code 4 = already exists)
        if ($this->serverIp !== '') {
            $code = $this->call('v-add-dns-domain', [
                $this->hestiaUser,
                $domain,
                $this->serverIp,
            ]);
            if ($code !== 0 && $code !== 4) {
                $this->logger->warning('hestia.dns_zone_add_failed', [
                    'domain' => $domain, 'return_code' => $code,
                ]);
            }

            // Restart DNS so BIND serves the new zone immediately
            $this->call('v-restart-dns', []);
        }

        // 2. Add as standalone web domain
        $code = $this->call('v-add-web-domain', [
            $this->hestiaUser,
            $domain,
        ]);

        if ($code !== 0 && $code !== 4) {
            $this->logger->error('hestia.add_domain_failed', [
                'domain'      => $domain,
                'return_code' => $code,
            ]);
            return false;
        }

        $this->logger->info('hestia.domain_added', ['domain' => $domain]);

        // 3. Add www alias so SSL covers both bare and www
        $code = $this->call('v-add-web-domain-alias', [
            $this->hestiaUser,
            $domain,
            $wwwAlias,
        ]);
        if ($code !== 0 && $code !== 4) {
            $this->logger->warning('hestia.www_alias_add_failed', [
                'domain' => $domain, 'alias' => $wwwAlias, 'return_code' => $code,
            ]);
        }

        // 4. Point document root to the main app
        $code = $this->call('v-change-web-domain-docroot', [
            $this->hestiaUser,
            $domain,
            $this->hestiaDomain,
        ]);
        if ($code !== 0) {
            $this->logger->warning('hestia.docroot_change_failed', [
                'domain' => $domain, 'return_code' => $code,
            ]);
        }

        // 5. Restart web/proxy so vhost is active for HTTP-01 challenge
        $this->restartServices();
        sleep(3); // Give nginx time to fully reload

        // 6. Issue Let's Encrypt SSL (handles enabling SSL automatically)
        $this->provisionSsl($domain);

        // 7. Force HTTPS redirect
        $this->call('v-add-web-domain-ssl-force', [
            $this->hestiaUser,
            $domain,
        ]);

        // 8. Restart for SSL to take effect
        $this->restartServices();

        return true;
    }

    /**
     * Provision Let's Encrypt SSL for a domain.
     *
     * Tries v-add-letsencrypt-domain with retries (LE validation can
     * be flaky if nginx hasn't fully reloaded). Falls back to the
     * schedule-based approach if direct issuance fails.
     */
    private function provisionSsl(string $domain): void
    {
        // Retry direct issuance up to 3 times with increasing delays
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $result = $this->callWithMessage('v-add-letsencrypt-domain', [
                $this->hestiaUser,
                $domain,
                '',
            ], 120);

            if ($result['code'] === 0) {
                $this->logger->info('hestia.ssl_provisioned', [
                    'domain' => $domain, 'attempt' => $attempt,
                ]);
                return;
            }

            $this->logger->warning('hestia.ssl_attempt_failed', [
                'domain'  => $domain,
                'attempt' => $attempt,
                'code'    => $result['code'],
                'error'   => $result['message'],
            ]);

            // Don't retry if rate-limited (429) — wait won't help
            if (str_contains($result['message'], '429')) {
                $this->logger->warning('hestia.ssl_rate_limited', ['domain' => $domain]);
                break;
            }

            if ($attempt < 3) {
                sleep($attempt * 5);
            }
        }

        // Fallback: schedule for later processing via cron
        $this->logger->info('hestia.ssl_trying_schedule', ['domain' => $domain]);

        $code = $this->call('v-schedule-letsencrypt-domain', [
            $this->hestiaUser,
            $domain,
            '',
        ]);

        if ($code !== 0) {
            $this->logger->warning('hestia.ssl_schedule_failed', [
                'domain' => $domain, 'return_code' => $code,
            ]);
        } else {
            $this->logger->info('hestia.ssl_scheduled', ['domain' => $domain]);
        }
    }

    /**
     * Fully remove a custom domain and all its resources.
     *
     * Steps:
     * 1. Delete the web domain (removes SSL, configs, everything)
     * 2. Delete the DNS zone
     * 3. Restart web/proxy to apply changes
     *
     * @since 1.0.0
     *
     * @param string $domain Custom domain to remove.
     * @return bool True on success.
     */
    public function removeDomainAlias(string $domain): bool
    {
        if (!$this->isConfigured() || !$this->isValidDomain($domain)) {
            return false;
        }

        // 1. Delete web domain entirely (removes SSL, vhost, everything)
        $code = $this->call('v-delete-web-domain', [
            $this->hestiaUser,
            $domain,
        ]);

        if ($code !== 0 && $code !== 3) {
            $this->logger->error('hestia.remove_domain_failed', [
                'domain'      => $domain,
                'return_code' => $code,
            ]);
            return false;
        }

        $this->logger->info('hestia.domain_removed', ['domain' => $domain]);

        // 2. DNS zone (code 3 = doesn't exist)
        $dnsCode = $this->call('v-delete-dns-domain', [
            $this->hestiaUser,
            $domain,
        ]);
        if ($dnsCode !== 0 && $dnsCode !== 3) {
            $this->logger->warning('hestia.dns_zone_remove_failed', [
                'domain' => $domain, 'return_code' => $dnsCode,
            ]);
        }

        // 3. Restart services
        $this->restartServices();

        return true;
    }

    /**
     * Verify that a domain's DNS is pointing to our server.
     *
     * @since 1.0.0
     *
     * @param string $domain Domain to verify.
     * @return bool True if DNS is correctly pointed.
     */
    public function verifyDns(string $domain): bool
    {
        if ($this->serverIp === '' || !$this->isValidDomain($domain)) {
            return true;
        }

        $aRecords = @dns_get_record($domain, DNS_A);
        if ($aRecords) {
            foreach ($aRecords as $record) {
                if (($record['ip'] ?? '') === $this->serverIp) {
                    return true;
                }
            }
        }

        if ($this->hestiaDomain !== '') {
            $nsRecords = @dns_get_record($domain, DNS_NS);
            if ($nsRecords) {
                foreach ($nsRecords as $record) {
                    if (str_ends_with($record['target'] ?? '', '.' . $this->hestiaDomain)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get the nameserver hostnames sellers should point their domains to.
     *
     * @since 1.0.0
     *
     * @return string[] e.g. ["ns1.myduka.link", "ns2.myduka.link", ...]
     */
    public function getNameservers(): array
    {
        if ($this->hestiaDomain === '') {
            return [];
        }

        return [
            'ns1.' . $this->hestiaDomain,
            'ns2.' . $this->hestiaDomain,
            'ns3.' . $this->hestiaDomain,
            'ns4.' . $this->hestiaDomain,
        ];
    }

    /**
     * Create a DNS zone so our nameservers can resolve the custom domain.
     *
     * @since 1.0.0
     *
     * @param string $domain Domain to create a zone for.
     */
    public function prepareDnsZone(string $domain): void
    {
        if ($this->serverIp === '' || !$this->isValidDomain($domain)) {
            return;
        }

        $code = $this->call('v-add-dns-domain', [
            $this->hestiaUser,
            $domain,
            $this->serverIp,
        ]);

        if ($code !== 0 && $code !== 4) {
            $this->logger->warning('hestia.dns_zone_add_failed', [
                'domain'      => $domain,
                'return_code' => $code,
            ]);
        }
    }

    /** Validate domain format to prevent injection into HestiaCP commands. */
    private function isValidDomain(string $domain): bool
    {
        return (bool) preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z]{2,})+$/', $domain);
    }

    /**
     * Restart web server and proxy so config/cert changes take effect.
     */
    private function restartServices(): void
    {
        $this->call('v-restart-web', []);
        $this->call('v-restart-proxy', []);
    }

    /**
     * Execute a HestiaCP API command.
     *
     * @param string   $command HestiaCP CLI command name.
     * @param string[] $args    Positional arguments (arg1, arg2, ...).
     * @param int      $timeout Request timeout in seconds.
     * @return int Return code (0 = success).
     */
    private function call(string $command, array $args, int $timeout = 60): int
    {
        $result = $this->callRaw($command, $args, $timeout);
        if ($result === null) {
            return -1;
        }
        return (int) trim($result);
    }

    /**
     * Execute a HestiaCP API command and return the full response.
     *
     * When called without returncode=yes, HestiaCP returns the error
     * message on failure instead of just the numeric code.
     */
    private function callWithMessage(string $command, array $args, int $timeout = 60): array
    {
        // First get the return code
        $code = $this->call($command, $args, $timeout);

        if ($code === 0) {
            return ['code' => 0, 'message' => ''];
        }

        // Re-run without returncode to get the error message
        $message = $this->callRaw($command, $args, $timeout, false) ?? '';
        return ['code' => $code, 'message' => trim($message)];
    }

    /**
     * Low-level HestiaCP API call.
     */
    private function callRaw(string $command, array $args, int $timeout, bool $returnCode = true): ?string
    {
        $postFields = [
            'user'     => $this->apiUser,
            'password' => $this->apiPassword,
            'cmd'      => $command,
        ];

        if ($returnCode) {
            $postFields['returncode'] = 'yes';
        }

        foreach (array_values($args) as $i => $arg) {
            $postFields['arg' . ($i + 1)] = $arg;
        }

        $ch = curl_init();
        if ($ch === false) {
            $this->logger->error('hestia.curl_init_failed', ['command' => $command]);
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiUrl . '/',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postFields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => $timeout,
        ]);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            $this->logger->error('hestia.curl_error', [
                'command' => $command,
                'error'   => $error,
            ]);
            return null;
        }

        return (string) $result;
    }
}
