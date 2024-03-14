<?php

declare(strict_types=1);

namespace AutoMapper\EventListener;

use AutoMapper\Attribute\MapToContext;
use AutoMapper\Event\PropertyMetadataEvent;
use AutoMapper\Extractor\ReadAccessor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyReadInfo;

final readonly class MapToContextListener
{
    public function __construct(private ReflectionExtractor $extractor)
    {
    }

    public function __invoke(PropertyMetadataEvent $event): void
    {
        if ($event->mapperMetadata->sourceReflectionClass === null) {
            return;
        }

        $readInfo = $this->extractor->getReadInfo($event->mapperMetadata->source, $event->source->name);

        if ($readInfo !== null && ($readInfo->getType() === PropertyReadInfo::TYPE_PROPERTY && $readInfo->getVisibility() === PropertyReadInfo::VISIBILITY_PUBLIC)) {
            return;
        }

        $reflectionClass = $event->mapperMetadata->sourceReflectionClass;
        $camelProp = $this->camelize($event->source->name);
        $readInfo = null;
        $contextParameters = null;

        foreach (ReflectionExtractor::$defaultAccessorPrefixes as $prefix) {
            $methodName = $prefix . $camelProp;

            if (!$reflectionClass->hasMethod($methodName)) {
                continue;
            }

            $reflectionMethod = $reflectionClass->getMethod($methodName);

            if ($reflectionMethod->getModifiers() !== \ReflectionMethod::IS_PUBLIC) {
                continue;
            }

            if ($reflectionMethod->getNumberOfRequiredParameters() === 0) {
                continue;
            }

            $contextParameters = $this->getParametersWithMapToContextAttribute($reflectionMethod);

            if (null !== $contextParameters) {
                $readInfo = new PropertyReadInfo(PropertyReadInfo::TYPE_METHOD, $methodName, PropertyReadInfo::VISIBILITY_PUBLIC, $reflectionMethod->isStatic(), false);
                break;
            }
        }

        if ($readInfo === null || $contextParameters === null) {
            return;
        }

        $event->source->accessor = new ReadAccessor(
            ReadAccessor::TYPE_METHOD,
            $readInfo->getName(),
            $event->mapperMetadata->source,
            PropertyReadInfo::VISIBILITY_PUBLIC !== $readInfo->getVisibility(),
            $event->source->name,
            $contextParameters
        );
    }

    private function camelize(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    /**
     * @return array<string, string>|null
     */
    private function getParametersWithMapToContextAttribute(\ReflectionMethod $method): ?array
    {
        $parameters = [];

        foreach ($method->getParameters() as $parameter) {
            if (!$parameter->getAttributes(MapToContext::class)) {
                return null;
            }

            $attribute = $parameter->getAttributes(MapToContext::class)[0] ?? null;

            if ($attribute === null) {
                return null;
            }

            /** @var MapToContext $attributeInstance */
            $attributeInstance = $attribute->newInstance();

            $parameters[$parameter->getName()] = $attributeInstance->contextName;
        }

        return $parameters;
    }
}
