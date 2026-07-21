<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Transformer\ApiPlatform;

use ApiPlatform\Metadata\ResourceClassResolverInterface;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Transformer\ApiPlatform\JsonLdObjectToIdTransformer;
use AutoMapper\Transformer\ApiPlatform\JsonLdObjectToIdTransformerFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\TypeInfo\Type;

class JsonLdObjectToIdTransformerFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(ResourceClassResolverInterface::class)) {
            self::markTestSkipped('API Platform is not installed.');
        }
    }

    private function createFactory(): JsonLdObjectToIdTransformerFactory
    {
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        $classMetadata = $this->createMock(ClassMetadataInterface::class);
        $classMetadata->method('getAttributesMetadata')->willReturn([]);

        $classMetadataFactory = $this->createMock(ClassMetadataFactoryInterface::class);
        $classMetadataFactory->method('getMetadataFor')->willReturn($classMetadata);

        return new JsonLdObjectToIdTransformerFactory($resourceClassResolver, $classMetadataFactory);
    }

    /**
     * @return iterable<string, array{Type, bool}>
     */
    public static function provideTargetTypes(): iterable
    {
        // the transformer may output a nested array (groups) or an IRI, so it only applies to targets
        // able to hold both, not to concrete scalar/object properties
        yield 'mixed target is supported' => [Type::mixed(), true];
        yield 'array target is supported' => [Type::array(), true];
        yield 'string target is not supported' => [Type::string(), false];
        yield 'int target is not supported' => [Type::int(), false];
        yield 'object target is not supported' => [Type::object(\stdClass::class), false];
    }

    #[DataProvider('provideTargetTypes')]
    public function testTargetTypeGating(Type $targetType, bool $supported): void
    {
        $factory = $this->createFactory();

        $source = new SourcePropertyMetadata('foo', type: Type::object(\stdClass::class));
        $target = new TargetPropertyMetadata('foo', type: $targetType);
        $mapperMetadata = $this->getMockBuilder(MapperMetadata::class)->disableOriginalConstructor()->getMock();

        $transformer = $factory->getTransformer($source, $target, $mapperMetadata);

        if ($supported) {
            self::assertInstanceOf(JsonLdObjectToIdTransformer::class, $transformer);
        } else {
            self::assertNull($transformer);
        }
    }
}
