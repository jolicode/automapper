<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle;

use AutoMapper\Tests\Bundle\Resources\App\Entity\AddressDTO;
use AutoMapper\Tests\Bundle\Resources\App\Entity\Order;
use Money\Currency;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

class NormalizerTest extends WebTestCase
{
    protected function setUp(): void
    {
        static::$class = null;
        $_SERVER['KERNEL_DIR'] = __DIR__ . '/Resources/App';
        $_SERVER['KERNEL_CLASS'] = 'AutoMapper\Tests\Bundle\Resources\App\AppKernel';
        $_SERVER['APP_DEBUG'] = false;

        (new Filesystem())->remove(__DIR__ . '/Resources/var/cache/test');

        self::bootKernel();
    }

    /**
     * This method needs to be the first in this test class, more details about why here: https://github.com/janephp/janephp/pull/734#discussion_r1247921885.
     *
     * @see Resources/App/config.yml
     */
    public function testOrderNormalizer(): void
    {
        $serializer = self::getContainer()->get('serializer');
        $order = new Order();
        $order->id = 1;
        $order->price = new Money(100, new Currency('USD'));

        $serializer->normalize($order);

        $this->assertEquals([
            'id' => 1,
            'price' => 100,
            'currency' => 'USD',
            'from_order_normalizer' => 'yes',
        ], $serializer->normalize($order));
    }

    /**
     * This method needs to be the first in this test class, more details about why here: https://github.com/janephp/janephp/pull/734#discussion_r1247921885.
     *
     * @see Resources/App/config.yml
     */
    public function testOrderDenormalizer(): void
    {
        $serializer = self::getContainer()->get('serializer');
        $data = [
            'id' => 1,
            'price' => [
                'amount' => 100,
                'currency' => [
                    'code' => 'USD',
                ],
            ],
        ];

        $order = $serializer->denormalize($data, Order::class);

        self::assertInstanceOf(Order::class, $order);
        self::assertSame(1, $order->id);
        self::assertSame('100', $order->price->getAmount());
        self::assertSame('USD', $order->price->getCurrency()->getCode());
    }

    public function testAddressDTONormalizer(): void
    {
        $serializer = self::getContainer()->get('serializer');
        $address = new AddressDTO();
        $address->city = 'city';

        $normalized = $serializer->normalize($address);
        $denormalized = $serializer->denormalize($normalized, AddressDTO::class);

        self::assertEquals($address, $denormalized);
    }
}
