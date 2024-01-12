<?php

declare(strict_types=1);

namespace AutoMapper\CustomTransformer;

use AutoMapper\Extractor\AstExtractor;
use AutoMapper\Extractor\ReadAccessor;
use PhpParser\Builder;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;

final readonly class CustomTransformerGenerator
{
    private AstExtractor $astExtractor;

    public function __construct(
        private CustomTransformersRegistry $customTransformerRegistry,
        AstExtractor|null $astExtractor = null
    ) {
        $this->astExtractor = $astExtractor ?? new AstExtractor();
    }

    /**
     * @return class-string<CustomTransformerInterface>
     */
    public function generateMapToCustomTransformer(
        string $source,
        string $target,
        string $sourceProperty,
        string $targetProperty,
        ReadAccessor $readAccessor
    ): string {
        $transformerClass = strtr('MapTo_Transformer_{source}_{target}_{sourceProperty}_{targetProperty}', [
            '{source}' => str_replace('\\', '_', $source),
            '{target}' => str_replace('\\', '_', $target),
            '{sourceProperty}' => $sourceProperty,
            '{targetProperty}' => $targetProperty,
        ]);

        $file = __DIR__ . "/{$transformerClass}.php";

        if (class_exists($transformerClass)) {
            $this->customTransformerRegistry->addCustomTransformer(new $transformerClass());

            return $transformerClass;
        }

        if (!file_exists($file)) {
            $class = (new Builder\Class_($transformerClass))
                ->makeFinal()
                ->makeReadonly()
                ->implement(CustomPropertyTransformerInterface::class)
                ->addStmt($this->generateSupportsStatement($source, $target, $targetProperty))
                ->addStmt($this->generateTransformStatement($readAccessor, $source))
                ->getNode();

            $code = "<?php\n" . (new Standard())->prettyPrint([$class]);
            if (!file_exists(__DIR__ . "/generated_transformers")) {
                mkdir(__DIR__ . "/generated_transformers");
            }
            file_put_contents($file = __DIR__ . "/generated_transformers/{$transformerClass}.php", $code);
        }

        require_once $file;

        $this->customTransformerRegistry->addCustomTransformer(new $transformerClass());

        return $transformerClass;
    }

    /**
     * public function supports(string $source, string $target, string $propertyName): bool
     * {
     *     return $source === [source type] && $target === [target type] && $propertyName === [target property];
     * }.
     */
    private function generateSupportsStatement(string $source, string $target, string $targetProperty): Stmt\ClassMethod
    {
        $supportsMethodInInterface = $this->astExtractor
            ->extractClassLike(CustomPropertyTransformerInterface::class)
            ->getMethod('supports') ?? throw new \LogicException('Cannot find "supports" method in interface ' . CustomPropertyTransformerInterface::class);

        return (new Builder\Method('supports'))
            ->makePublic()
            ->setReturnType($supportsMethodInInterface->getReturnType())
            ->addParams($supportsMethodInInterface->getParams())
            ->addStmt(
                new Stmt\Return_(
                    new Expr\BinaryOp\BooleanAnd(
                        new Expr\BinaryOp\Identical(
                            $supportsMethodInInterface->getParams()[0]->var,
                            new String_($source)
                        ),
                        new Expr\BinaryOp\BooleanAnd(
                            new Expr\BinaryOp\Identical(
                                $supportsMethodInInterface->getParams()[1]->var,
                                new String_($target)
                            ),
                            new Expr\BinaryOp\Identical(
                                $supportsMethodInInterface->getParams()[2]->var,
                                new String_($targetProperty)
                            ),
                        )
                    )
                )
            )
            ->getNode();
    }

    /**
     * public function transform(object|array $source): mixed
     * {
     *     return $source->[sourceProperty accessor];
     * }.
     */
    private function generateTransformStatement(ReadAccessor $readAccessor, string $source): Stmt\ClassMethod
    {
        $transformMethodInInterface = $this->astExtractor
            ->extractClassLike(CustomTransformerInterface::class)
            ->getMethod('transform') ?? throw new \LogicException('Cannot find "transform" method in interface ' . CustomTransformerInterface::class);

        return (new Builder\Method('transform'))
            ->makePublic()
            ->setReturnType($transformMethodInInterface->getReturnType())
            ->setDocComment(
                <<<PHPDOC
                /**
                 * @param $source \$source
                 */
                PHPDOC
            )
            ->addParams($transformMethodInInterface->getParams())
            ->addStmt(
                new Stmt\Return_(
                    $readAccessor->getExpression($transformMethodInInterface->getParams()[0]->var)
                )
            )
            ->getNode();
    }
}
