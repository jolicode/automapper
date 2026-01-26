<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Extractor\PropertyReadAccessor;
use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Metadata\PropertyMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Expr;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;

trait EvalTransformerTrait
{
    private function createTransformerFunction(TransformerInterface $transformer, ?PropertyMetadata $propertyMapping = null): \Closure
    {
        if (null === $propertyMapping) {
            $propertyMapping = new PropertyMetadata(
                new SourcePropertyMetadata(
                    'dummy',
                    new PropertyReadAccessor('dummy'),
                ),
                new TargetPropertyMetadata(
                    'dummy',
                ),
                $transformer,
            );
        }

        $variableScope = new UniqueVariableScope();
        $inputName = $variableScope->getUniqueName('input');
        $inputExpr = new Expr\Variable($inputName);

        // we give $inputExpr as $targetExpr since we don't use it there and this is needed by TransformerInterface
        [$outputExpr, $stmts] = $transformer->transform($inputExpr, $inputExpr, $propertyMapping, $variableScope, new Expr\Variable('source'));

        $stmts[] = new Stmt\Return_($outputExpr);

        $functionExpr = new Expr\Closure([
            'stmts' => $stmts,
            'params' => [new Param($inputExpr), new Param(new Expr\Variable('context'), new Expr\Array_())],
        ]);

        $printer = new Standard();
        $code = $printer->prettyPrint([new Stmt\Return_($functionExpr)]);

        return eval($code);
    }

    private function evalTransformer(TransformerInterface $transformer, mixed $input, ?PropertyMetadata $propertyMapping = null): mixed
    {
        $function = $this->createTransformerFunction($transformer, $propertyMapping);

        return $function($input);
    }
}
