<?php

$finder = (new PhpCsFixer\Finder())
    ->exclude(__DIR__ . '/../../tests/cache')
    ->in(__DIR__ . '/../../src')
    ->in(__DIR__ . '/../../tests')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->registerCustomFixers((new PhpCsFixerCustomFixers\Fixers()))
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'one'],
        'yoda_style' => false,
        'native_constant_invocation' => false,
        'no_superfluous_phpdoc_tags' => [
            'remove_inheritdoc' => false,
        ],
        'nullable_type_declaration_for_default_null_value' => false,
        'declare_strict_types' => true,
        'no_trailing_comma_in_singleline' => false,
        'function_declaration' => ['trailing_comma_single_line' => true],
        'phpdoc_to_comment' => ['allow_before_return_statement' => true],
        PhpCsFixerCustomFixers\Fixer\MultilinePromotedPropertiesFixer::name() => true,
    ])
    ->setFinder($finder)
;
