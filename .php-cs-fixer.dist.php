<?php

/**
 * PHP CS Fixer configuration.
 *
 * Follows PER-CS2 (PHP Evolving Recommendation) coding style,
 * which is the modern successor to PSR-12.
 */

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'array_indentation' => true,
        'no_whitespace_in_blank_line' => true,
        'no_trailing_whitespace' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
