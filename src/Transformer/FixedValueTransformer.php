<?php

declare(strict_types=1);

namespace AutoMapper\Transformer;

use AutoMapper\Exception\CompileException;
use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * @internal
 */
final readonly class FixedValueTransformer implements TransformerInterface, AllowNullValueTransformerInterface
{
    private Parser $parser;

    public function __construct(
        private mixed $value,
        ?Parser $parser = null,
    ) {
        $this->parser = $parser ?? (new ParserFactory())->createForHostVersion();
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr $source, ?Expr $existingValue = null): array
    {
        $expr = $this->parser->parse('<?php ' . var_export($this->value, true) . ';')[0] ?? null;

        if ($expr instanceof Stmt\Expression) {
            return [$expr->expr, []];
        }

        throw new CompileException('Cannot create php code from value ' . json_encode($this->value));
    }
}
