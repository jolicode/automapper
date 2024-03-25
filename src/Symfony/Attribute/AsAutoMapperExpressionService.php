<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Attribute;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Service tag to autoconfigure auto mapper expression services.
 *
 * You can tag a service:
 *
 *     #[AsAutoMapperExpressionService('foo')]
 *     class SomeFooService
 *     {
 *         public function bar(DummyObject $object): string
 *         {
 *             // ...
 *         }
 *
 *         public function isOk(): bool
 *         {
 *             // ...
 *         }
 *
 * Then you can use the tagged service in the transformer or check property of MapTo / MapFrom attribute
 *
 *     class DummyObject
 *     {
 *        #[MapTo('array', transformer: 'service("foo").bar(source)', if: 'service("foo").isOk()')]
 *        public string $foo;
 *     }
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsAutoMapperExpressionService extends AutoconfigureTag
{
    /**
     * @param string|null $alias    The alias of the service to use it in routing condition expressions
     * @param int         $priority Defines a priority that allows the routing condition service to override a service with the same alias
     */
    public function __construct(
        ?string $alias = null,
        int $priority = 0,
    ) {
        parent::__construct('automapper.expression_service', ['alias' => $alias, 'priority' => $priority]);
    }
}
