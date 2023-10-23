<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer;

use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Extractor\ReadAccessor;
use AutoMapper\Generator\UniqueVariableScope;
use AutoMapper\Transformer\TransformerInterface;
use PhpParser\Node\Expr;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;

trait EvalTransformerTrait
{
    private function createTransformerFunction(TransformerInterface $transformer, PropertyMapping $propertyMapping = null): \Closure
    {
        if (null === $propertyMapping) {
            $propertyMapping = new PropertyMapping(
                new ReadAccessor(ReadAccessor::TYPE_PROPERTY, 'dummy'),
                null,
                null,
                $transformer,
                'dummy'
            );
        }

        $variableScope = new UniqueVariableScope();
        $inputName = $variableScope->getUniqueName('input');
        $inputExpr = new Expr\Variable($inputName);

        // we give $inputExpr as $targetExpr since we don't use it there and this is needed by TransformerInterface
        [$outputExpr, $stmts] = $transformer->transform($inputExpr, $inputExpr, $propertyMapping, $variableScope);

        $stmts[] = new Stmt\Return_($outputExpr);

        $functionExpr = new Expr\Closure([
            'stmts' => $stmts,
            'params' => [new Param($inputExpr), new Param(new Expr\Variable('context'), new Expr\Array_())],
        ]);

        $printer = new Standard();
        $code = $printer->prettyPrint([new Stmt\Return_($functionExpr)]);

        return eval($code);
    }

    private function evalTransformer(TransformerInterface $transformer, mixed $input, PropertyMapping $propertyMapping = null): mixed
    {
        $function = $this->createTransformerFunction($transformer, $propertyMapping);

        return $function($input);
    }
}
