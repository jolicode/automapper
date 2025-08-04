<?php

declare(strict_types=1);

namespace AutoMapper\Symfony\Bundle\Command;

use AutoMapper\Metadata\Dependency;
use AutoMapper\Metadata\MetadataFactory;
use AutoMapper\Metadata\PropertyMetadata;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DebugMapperCommand extends Command
{
    public function __construct(
        private readonly MetadataFactory $metadataFactory,
    ) {
        parent::__construct('debug:mapper');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Debug Mapper from source to target')
            ->addArgument('source', InputArgument::REQUIRED, 'Source class or "array"')
            ->addArgument('target', InputArgument::REQUIRED, 'Target class or "array"')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var class-string<object>|'array' $source */
        $source = $input->getArgument('source');
        /** @var class-string<object>|'array' $target */
        $target = $input->getArgument('target');

        $metadata = $this->metadataFactory->getGeneratorMetadata($source, $target);

        $style = new SymfonyStyle($input, $output);
        $style->section('Mapper:');

        $style->horizontalTable(
            ['Source', 'Target', 'Classname', 'Check attributes', 'Use constructor', 'Provider'],
            [
                [
                    $metadata->mapperMetadata->source,
                    $metadata->mapperMetadata->target,
                    $metadata->mapperMetadata->className,
                    $metadata->checkAttributes ? 'Yes' : 'No',
                    $metadata->hasConstructor() ? 'Yes' : 'No',
                    $metadata->provider,
                ],
            ]);

        $style->section('Dependencies:');

        $style->table(
            ['Mapper', 'Source', 'Target'],
            array_map(
                fn (Dependency $dependency) => [
                    $dependency->mapperDependency->name,
                    $dependency->mapperDependency->source,
                    $dependency->mapperDependency->target,
                ],
                $metadata->getDependencies()
            )
        );

        $style->section('Used Properties:');

        $style->table(
            [\sprintf('%s -> %s', $source, $target), 'If', 'Transformer', 'Groups', 'MaxDepth'],
            array_map(
                fn (PropertyMetadata $property) => [
                    $property->source->property . ' -> ' . $property->target->property,
                    $property->if,
                    \get_class($property->transformer),
                    $property->disableGroupsCheck ? 'Disabled' : implode(', ', $property->groups ?? []),
                    $property->maxDepth,
                ],
                array_filter($metadata->propertiesMetadata, fn (PropertyMetadata $property) => !$property->ignored)
            )
        );

        $style->section('Not Used Properties:');

        $style->table(
            [\sprintf('%s -> %s', $source, $target), 'Not used reason'],
            array_map(
                fn (PropertyMetadata $property) => [
                    $property->source->property . ' -> ' . $property->target->property,
                    $property->ignoreReason,
                ],
                array_filter($metadata->propertiesMetadata, fn (PropertyMetadata $property) => $property->ignored)
            )
        );

        return Command::SUCCESS;
    }
}
