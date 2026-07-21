<?php

declare(strict_types=1);

/*
 * Drop this file at:
 *   tests/AutoMapperTest/CollectionWithoutDeepPopulate/map.php
 *
 * Then generate the expected dump:
 *   UPDATE_FIXTURES=1 vendor/bin/phpunit --filter=testAutoMapperFixtures
 *
 * The fixture system in tests/AutoMapperTest.php::testAutoMapperFixtures
 * picks it up automatically.
 *
 * Why it reproduces the bug:
 *   - target has an adder/remover (addItem/removeItem) ->
 *     AbstractArrayTransformer takes the `isAdderRemover()` branch
 *   - source collection has 2 items -> the inner foreach body executes
 *   - no `deep_target_to_populate` -> the existing target items are
 *     not indexed, but $existingvalue is still read inside
 *     withNewContext() before being assigned (root cause)
 *
 * On 10.2.0 / current main this raises:
 *   Warning: Undefined variable $existingvalue
 *
 * After the patch the warning is gone and the dump matches expected.data.
 *
 * To catch the warning automatically in CI, set the PHPUnit config:
 *   <phpunit failOnWarning="true" ...>
 * (already the default in the repo's phpunit.xml.dist).
 */

namespace AutoMapper\Tests\AutoMapperTest\CollectionWithoutDeepPopulate;

use AutoMapper\Attribute\MapFrom;
use AutoMapper\Tests\AutoMapperBuilder;

class SourceItem
{
    public int $id;
    public string $label;
}

class Source
{
    /** @var SourceItem[] */
    public array $items = [];
}

class TargetItem
{
    #[MapFrom(source: SourceItem::class, identifier: true)]
    public int $id;

    public string $label;
}

class Target
{
    /** @var TargetItem[] */
    private array $items = [];

    public function addItem(TargetItem $item): void
    {
        $this->items[] = $item;
    }

    public function removeItem(TargetItem $item): void
    {
        foreach ($this->items as $key => $existing) {
            if ($existing->id === $item->id) {
                unset($this->items[$key]);
                $this->items = array_values($this->items);

                return;
            }
        }
    }
}

return (static function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    $source = new Source();

    $a = new SourceItem();
    $a->id = 1;
    $a->label = 'first';

    $b = new SourceItem();
    $b->id = 2;
    $b->label = 'second';

    $source->items = [$a, $b];

    // No `deep_target_to_populate` and no pre-existing target. Pre-patch:
    // raises Warning: Undefined variable $existingvalue. Post-patch:
    // clean mapping.
    return $autoMapper->map($source, Target::class);
})();
