<?php

declare(strict_types=1);

namespace AutoMapper\Tests\Bundle\Resources\App\Controller;

use AutoMapper\AutoMapperInterface;
use AutoMapper\Tests\Bundle\Resources\App\Entity\FooMapTo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private AutoMapperInterface $autoMapper,
        #[Autowire('@serializer.normalizer.object')]
        private NormalizerInterface $serializer,
    ) {
    }

    #[Route('/')]
    public function __invoke(Request $request): Response
    {
        $output = [];
        $data = new FooMapTo('value');

        for ($i = 0; $i < 1; ++$i) {
            if ($request->query->has('serializer')) {
                $output[] = $this->serializer->normalize($data, 'json');
            } else {
                $output[] = $this->autoMapper->map($data, 'array');
            }
        }

        return new JsonResponse($output);
    }
}
