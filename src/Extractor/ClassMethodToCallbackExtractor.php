<?php

declare(strict_types=1);

namespace AutoMapper\Extractor;

use AutoMapper\Exception\InvalidArgumentException;
use AutoMapper\Exception\LogicException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Param;

/**
 * Extracts the code of the given method from a given class and wraps it inside a closure, in order to inject it
 * in the generated mappers.
 *
 * @author Nicolas Philippe <nikophil@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 *
 * @internal
 */
final readonly class ClassMethodToCallbackExtractor
{
    private AstExtractor $astExtractor;

    public function __construct(AstExtractor|null $astExtractor = null)
    {
        $this->astExtractor = $astExtractor ?? new AstExtractor();
    }

    /**
     * @param class-string $class
     * @param Arg[]        $inputParameters
     */
    public function extract(string $class, string $method, array $inputParameters): Expr
    {
        $classStatement = $this->astExtractor->extractClassLike($class, resolveImports: true);

        $classMethod = $classStatement->getMethod($method) ?? throw new LogicException("Cannot find method \"{$method}()\" in class \"{$class}\".");

        if (\count($inputParameters) !== \count($classMethod->getParams())) {
            throw new InvalidArgumentException("Input parameters and method parameters in class \"{$class}\" do not match.");
        }

        $closureParameters = [];
        foreach ($classMethod->getParams() as $parameter) {
            if ($parameter->var instanceof Expr\Variable) {
                $closureParameters[] = new Param(new Expr\Variable($parameter->var->name), type: $parameter->type);
            }
        }

        return new Expr\FuncCall(
            new Expr\Closure([
                'stmts' => $classMethod->stmts,
                'params' => $closureParameters,
                'returnType' => $classMethod->returnType,
            ]),
            $inputParameters,
        );
    }
}
