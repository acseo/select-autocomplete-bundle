<?php

declare(strict_types=1);

namespace Acseo\SelectAutocomplete\DependencyInjection\Compiler;

use Acseo\SelectAutocomplete\Doctrine\DataProviderCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProviderExtensionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(DataProviderCollection::class);
        $taggedServices = $container->findTaggedServiceIds('select_autocomplete.doctrine.providers');

        foreach (array_keys($taggedServices) as $id) {
            $definition->addMethodCall('addProvider', [new Reference($id)]);
        }
    }
}
