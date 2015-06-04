<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add callable resolver to container
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class AddCallableResolverPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $chainCallableDefinition = $container->getDefinition('api.callable_resolver');

        foreach ($container->findTaggedServiceIds('api.callable_resolver') as $id => $attributes) {
            $resolverDefinition = $container->getDefinition($id);
            $class = $resolverDefinition->getClass();

            try {
                $class = $container->getParameterBag()->resolveValue($class);
                $refClass = new \ReflectionClass($class);
                $requiredInterface = 'FivePercent\Component\Api\SMD\CallableResolver\CallableResolverInterface';

                if (!$refClass->implementsInterface($requiredInterface)) {
                    throw new \RuntimeException(sprintf(
                        'The callable resolver should be implemented of "%s" interface.',
                        $requiredInterface
                    ));
                }

                $chainCallableDefinition->addMethodCall('addResolver', [
                    new Reference($id)
                ]);
            } catch (\Exception $e) {
                throw new \RuntimeException(sprintf(
                    'Could not compile callable resolver with service id "%s".',
                    $id
                ), 0, $e);
            }
        }
    }
}
