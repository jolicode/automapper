<?php

declare(strict_types=1);

namespace AutoMapper\Symfony;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final readonly class ExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @param ServiceProviderInterface<\Closure> $functions
     */
    public function __construct(
        private ServiceProviderInterface $functions
    ) {
    }

    public function getFunctions(): array
    {
        $functions = [];

        foreach ($this->functions->getProvidedServices() as $function => $type) {
            $functions[] = new ExpressionFunction(
                $function,
                static fn (...$args) => sprintf('($this->expressionLanguageProvider->get(%s)(%s))', var_export($function, true), implode(', ', $args)),
                fn ($values, ...$args) => $this->get($function)(...$args)
            );
        }

        return $functions;
    }

    public function get(string $function): callable
    {
        return $this->functions->get($function);
    }
}
