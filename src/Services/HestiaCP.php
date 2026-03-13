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
     * 2. Add as a standalone web domain in HestiaCP
     * 3. Point its document root to the main app
     * 4. Issue a free Let's Encrypt SSL certificate
     * 5. Restart web/proxy so the new cert takes effect
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

        // 3. Point document root to the main app
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

        // 4. Issue Let's Encrypt SSL
        $code = $this->call('v-add-letsencrypt-domain', [
            $this->hestiaUser,
            $domain,
        ]);
        if ($code !== 0) {
            $this->logger->warning('hestia.ssl_provision_failed', [
                'domain' => $domain, 'return_code' => $code,
            ]);
        }

        // 5. Restart web/proxy for cert to take effect
        $this->restartServices();

        return true;
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
        if (!$this->isConfigured()) {
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
        if ($this->serverIp === '') {
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
        if ($this->serverIp === '') {
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
     * @return int Return code (0 = success).
     */
    private function call(string $command, array $args): int
    {
        $postFields = [
            'user'       => $this->apiUser,
            'password'   => $this->apiPassword,
            'returncode' => 'yes',
            'cmd'        => $command,
        ];

        foreach (array_values($args) as $i => $arg) {
            $postFields['arg' . ($i + 1)] = $arg;
        }

        $ch = curl_init();
        if ($ch === false) {
            $this->logger->error('hestia.curl_init_failed', ['command' => $command]);
            return -1;
        }
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiUrl . '/',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postFields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 60,
        ]);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            $this->logger->error('hestia.curl_error', [
                'command' => $command,
                'error'   => $error,
            ]);
            return -1;
        }

        return (int) trim((string) $result);
    }
}
