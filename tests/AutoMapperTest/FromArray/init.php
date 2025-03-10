<?php

use AutoMapper\Tests\Fixtures\UserDTO;

return [
    [
        'id' => 1,
        'address' => [
            'city' => 'Toulon',
        ],
        'createdAt' => '1987-04-30T06:00:00Z',
    ],
    UserDTO::class,
    ['classPrefix' => 'CustomDateTime_' , 'dateTimeFormat' => 'U']
];
