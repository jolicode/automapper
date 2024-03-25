<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\PropertyTransformer;

use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Transformer\AllowNullValueTransformerInterface;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * @internal
 */
final readonly class PropertyTransformer implements TransformerInterface, AllowNullValueTransformerInterface
{
    private Parser $parser;

    /**
     * @param array<mixed> $extraContext
     */
    public function __construct(
        private string $propertyTransformerId,
        private array $extraContext = [],
        Parser $parser = null,
    ) {
        $this->parser = $parser ?? (new ParserFactory())->createForHostVersion();
    }

    public function transform(Expr $input, Expr $target, PropertyMetadata $propertyMapping, UniqueVariableScope $uniqueVariableScope, Expr\Variable $source): array
    {
        $context = new Expr\Variable('context');

        if ($this->extraContext) {
            $expr = $this->parser->parse('<?php ' . var_export($this->extraContext, true) . ';')[0] ?? null;

            if ($expr instanceof Stmt\Expression) {
                $context = new Expr\BinaryOp\Plus(
                    $context,
                    $expr->expr
                );
            }
        }

        /*
         * When using a custom transformer, we need to call the transform method of the custom transformer which has been injected into the mapper.
         *
         * $this->transformers['id']($input, $source, $context)
         */
        return [new Expr\MethodCall(
            new Expr\MethodCall(new Expr\PropertyFetch(new Expr\Variable('this'), 'transformerRegistry'), 'getPropertyTransformer', [
                new Arg(new Scalar\String_($this->propertyTransformerId)),
            ]),
            'transform',
            [
                new Arg($input),
                new Arg($source),
                new Arg($context),
            ]
        ), []];
    }
}
