<?php

declare(strict_types=1);

namespace AutoMapper\Tests\AutoMapperTest\SymfonyUId;

use AutoMapper\Tests\AutoMapperBuilder;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

class SymfonyUlidUser
{
    /**
     * @var Ulid
     */
    private $ulid;

    /**
     * @var string
     */
    public $name;

    public function __construct(Ulid $ulid, string $name)
    {
        $this->ulid = $ulid;
        $this->name = $name;
    }

    public function getUlid(): Ulid
    {
        return $this->ulid;
    }
}

class SymfonyUuidUser
{
    /**
     * @var Uuid
     */
    private $uuid;

    /**
     * @var string
     */
    public $name;

    public function __construct(Uuid $uuid, string $name)
    {
        $this->uuid = $uuid;
        $this->name = $name;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }
}

return (function () {
    $autoMapper = AutoMapperBuilder::buildAutoMapper();

    // array -> object
    $data = [
        'ulid' => '01EXE87A54256F05N8P6SB2M9M',
        'name' => 'Grégoire Pineau',
    ];
    /* @var SymfonyUlidUser $user */
    yield 'array-to-object' => $autoMapper->map($data, SymfonyUlidUser::class);

    // object -> array
    $user = new SymfonyUlidUser(new Ulid('01EXE89XR69GERC6GV3J4X38FJ'), 'Grégoire Pineau');
    yield 'object-to-array' => $autoMapper->map($user, 'array');

    // object -> object
    $user = new SymfonyUlidUser(new Ulid('01EXE8A6TNWVCEGMZ36AX8N9MC'), 'Grégoire Pineau');
    /* @var SymfonyUlidUser $newUser */
    yield 'object-to-object' => $autoMapper->map($user, SymfonyUlidUser::class);

    // array -> object // uuid v1
    $data = [
        'uuid' => '0a5411fe-fe79-11ef-9fea-a1a72bed7412',
        'name' => 'Grégoire Pineau',
    ];
    /* @var SymfonyUuidUser $user */
    yield 'array-to-object-v1' => $autoMapper->map($data, SymfonyUuidUser::class);

    // object -> array // uuid v3
    $uuidV3 = Uuid::fromString('42650c8f-f5d0-3b1d-a338-f821651471ff');
    $user = new SymfonyUuidUser($uuidV3, 'Grégoire Pineau');
    yield 'array-to-object-v3' => $autoMapper->map($user, 'array');

    // object -> object // uuid v4
    $uuidV4 = Uuid::fromString('9dbee72c-ebe5-450e-843c-bb06ea7fd4be');
    $user = new SymfonyUuidUser($uuidV4, 'Grégoire Pineau');
    /* @var SymfonyUuidUser $newUser */
    yield 'array-to-object-v4' => $autoMapper->map($user, SymfonyUuidUser::class);
})();
