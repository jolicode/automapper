<?php

declare(strict_types=1);

namespace AutoMapper\CustomTransformer;

use AutoMapper\Attribute\PropertyAttribute;
use AutoMapper\Extractor\AstExtractor;
use AutoMapper\Extractor\PropertyMapping;
use AutoMapper\Extractor\ReadAccessor;
use PhpParser\Builder;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;

final readonly class CustomTransformerGenerator
{
    private AstExtractor $astExtractor;

    private array $transformers;

    public function __construct(
        private CustomTransformersRegistry $customTransformerRegistry,
        AstExtractor|null $astExtractor = null
    ) {
        $this->astExtractor = $astExtractor ?? new AstExtractor();

        $this->transformers = [new MapToAttributeCustomTransformerGenerator()];
    }

    /**
     * @return class-string<CustomTransformerInterface>
     */
    public function generateMapToCustomTransformer(PropertyMapping $propertyMapping, PropertyAttribute $propertyAttribute, string $transformerClass): void
    {
        if (class_exists($transformerClass)) {
            return;
        }

        $transformerGenerator = $this->getTransformerGenerator($propertyMapping, $propertyAttribute);

        $source = $propertyMapping->mapperMetadata->getSource();
        $target = $propertyMapping->mapperMetadata->getTarget();

        $file = __DIR__ . "/{$transformerClass}.php";

        // todo use EvalLoader / FileLoader
        if (!file_exists($file)) {
            $class = (new Builder\Class_($transformerClass))
                ->makeFinal()
                ->makeReadonly()
                ->implement(CustomPropertyTransformerInterface::class)
                ->addStmt($transformerGenerator->generateSupportsStatement($propertyMapping, $propertyAttribute))
                ->addStmt($transformerGenerator->generateTransformStatement($propertyMapping, $propertyAttribute))
                ->getNode();

            $code = "<?php\n" . (new Standard())->prettyPrint([$class]);
            if (!file_exists(__DIR__ . "/generated_transformers")) {
                mkdir(__DIR__ . "/generated_transformers");
            }
            file_put_contents($file = __DIR__ . "/generated_transformers/{$transformerClass}.php", $code);
        }

        require_once $file;

        $this->customTransformerRegistry->addCustomTransformer(new $transformerClass());
    }

    private function getTransformerGenerator(PropertyMapping $propertyMapping, PropertyAttribute $propertyAttribute): AttributeCustomTransformerGenerator
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($propertyMapping, $propertyAttribute)) {
                return $transformer;
            }
        }

        throw new \LogicException('Cannot find transformer.');
    }
}
