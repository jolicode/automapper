<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Fixtures\Normalizer;

use Symfony\Component\Serializer\Attribute\Groups;

class GroupDummyParent
{
    #[Groups(['a'])]
    private $kevin;
    private $coopTilleuls;

    public function setKevin($kevin)
    {
        $this->kevin = $kevin;
    }

    public function getKevin()
    {
        return $this->kevin;
    }

    public function setCoopTilleuls($coopTilleuls)
    {
        $this->coopTilleuls = $coopTilleuls;
    }

    #[Groups(['a', 'b'])]
    public function getCoopTilleuls()
    {
        return $this->coopTilleuls;
    }
}
