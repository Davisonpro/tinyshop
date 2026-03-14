<?php

declare(strict_types=1);

namespace TinyShop\Services\Importers;

/**
 * Cleans and preprocesses free-form product text before AI parsing.
 *
 * Minimal processing — the heavy lifting is done by AI.
 * This just normalizes whitespace, removes junk lines, and trims input.
 *
 * @since 1.0.0
 */
final class BulkTextParser
{
    /**
     * Clean raw text input for AI processing.
     *
     * @param string $text Raw user input.
     * @return string Cleaned text ready for AI parsing.
     */
    public function clean(string $text): string
    {
        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Split into lines, trim each, remove empties and junk
        $lines = array_filter(
            array_map('trim', explode("\n", $text)),
            fn(string $line): bool => $line !== '' && !$this->isJunkLine($line)
        );

        if (empty($lines)) {
            return '';
        }

        return implode("\n", $lines);
    }

    /**
     * Check if a line is visual junk (separators, decorative characters).
     */
    private function isJunkLine(string $line): bool
    {
        // Separator lines: ---, ===, ***, ###, ___
        if (preg_match('/^[-=_#*~]{3,}$/', $line)) {
            return true;
        }

        // Purely numeric lines with no context (e.g. line numbers)
        if (preg_match('/^\d{1,2}\.$/', $line)) {
            return true;
        }

        return false;
    }
}
