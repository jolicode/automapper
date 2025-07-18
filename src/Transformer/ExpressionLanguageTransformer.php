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
final readonly class ExpressionLanguageTransformer implements TransformerInterface, AllowNullValueTransformerInterface
{
    private Parser $parser;

    public function __construct(
        private string $expression,
        ?Parser $parser = null,
    ) {
        $this->parser = $parser ?? (new ParserFactory())->createForHostVersion();
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source, ?Expr $existingValue = null): array
    {
        $expr = $this->parser->parse('<?php ' . $this->expression . ';')[0] ?? null;

        if ($expr instanceof Stmt\Expression) {
            return [$expr->expr, []];
        }

        throw new CompileException('Cannot use callback or create expression language condition from expression "' . $this->expression . "'");
    }
}
