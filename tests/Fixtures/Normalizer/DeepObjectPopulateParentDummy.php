<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Normalizer;

class DeepObjectPopulateParentDummy
{
    /**
     * @var DeepObjectPopulateChildDummy|null
     */
    private $child;

    public function setChild(?DeepObjectPopulateChildDummy $child)
    {
        $this->child = $child;
    }

    public function getChild(): ?DeepObjectPopulateChildDummy
    {
        return $this->child;
    }
}
