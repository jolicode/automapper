<?php

declare(strict_types=1);

namespace AutoMapper\Transformer\PropertyTransformer;

use AutoMapper\Extractor\WriteMutator;
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
        ?Parser $parser = null,
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

        $statements = [];
        $transformExpr = new Expr\MethodCall(
            new Expr\MethodCall(new Expr\PropertyFetch(new Expr\Variable('this'), 'transformerRegistry'), 'getPropertyTransformer', [
                new Arg(new Scalar\String_($this->propertyTransformerId)),
            ]),
            'transform',
            [
                new Arg($input),
                new Arg($source),
                new Arg($context),
            ]
        );

        /*
         * If mutator is type adder and remover, we need to loop over the transformed values and call the adder method for each value.
         *
         * $values = $this->transformers['id']($input, $source, $context);
         * foreach ($values as $value) {
         *     $target->add($value);
         * }
         */
        if ($propertyMapping->target->writeMutator && $propertyMapping->target->writeMutator->type === WriteMutator::TYPE_ADDER_AND_REMOVER) {
            $mappedValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('mappedValue'));

            $statements[] = new Stmt\Expression(new Expr\Assign(
                $mappedValueVar,
                $transformExpr
            ));

            $loopValueVar = new Expr\Variable($uniqueVariableScope->getUniqueName('value'));

            $statements[] = new Stmt\Foreach_($mappedValueVar, $loopValueVar, [
                'stmts' => [
                    new Stmt\Expression($propertyMapping->target->writeMutator->getExpression($target, $loopValueVar)),
                ],
            ]);

            return [new Expr\Variable($uniqueVariableScope->getUniqueName('mappedValues')), $statements];
        }

        /*
         * When using a custom transformer, we need to call the transform method of the custom transformer which has been injected into the mapper.
         *
         * $this->transformers['id']($input, $source, $context)
         */
        return [$transformExpr, []];
    }
}
