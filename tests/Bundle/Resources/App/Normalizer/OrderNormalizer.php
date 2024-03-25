<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Normalizer;

use AutoMapper\Tests\Bundle\Resources\App\Entity\Order;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class OrderNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /** @param Order $object */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return [
            'id' => $object->id,
            'price' => $object->price->getAmount(),
            'currency' => $object->price->getCurrency()->getCode(),
            'from_order_normalizer' => 'yes',
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if ($data instanceof Order) {
            return true;
        }

        return false;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Order::class => false];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $order = new Order();
        $order->id = 'from_order_denormalizer';
        $order->price = new Money($data['price'], new Currency($data['currency']));

        return $order;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === Order::class;
    }
}
