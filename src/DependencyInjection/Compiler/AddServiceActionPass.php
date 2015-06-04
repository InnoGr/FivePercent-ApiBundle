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
use Symfony\Component\DependencyInjection\DefinitionDecorator;

/**
 * Compile API actions
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class AddServiceActionPass implements CompilerPassInterface
{
    /**
     * @var AddHandlerPass
     */
    private $handlerPass;

    /**
     * Construct
     *
     * @param AddHandlerPass $handlerPass
     */
    public function __construct(AddHandlerPass $handlerPass)
    {
        $this->handlerPass = $handlerPass;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('api.action') as $id => $tags) {
            foreach ($tags as $index => $attributes) {
                try {
                    if (empty($attributes['handler'])) {
                        throw new \RuntimeException('Missing required attribute "handler".');
                    }

                    $handlerId = $this->handlerPass->getHandlerId($attributes['handler']);

                    $this->processByAnnotation($container, $id, $handlerId);

                } catch (\Exception $e) {
                    throw new \RuntimeException(sprintf(
                        'Could not compile API action for tag index "%d" and service id "%s".',
                        $index,
                        $id
                    ), 0, $e);
                }
            }
        }
    }

    /**
     * Process by annotation
     *
     * @param ContainerBuilder $container
     * @param string           $id
     * @param string           $handlerId
     */
    private function processByAnnotation(ContainerBuilder $container, $id, $handlerId)
    {
        $serviceLoaderId = $this->handlerPass->getServiceLoader($handlerId);
        $serviceLoaderDefinition= $container->getDefinition($serviceLoaderId);

        $loaderClass = $serviceLoaderDefinition->getClass();
        $loaderClass = $container->getParameterBag()->resolveValue($loaderClass);

        $serviceDefinition = $container->getDefinition($id);
        $serviceClass = $serviceDefinition->getClass();
        $serviceClass = $container->getParameterBag()->resolveValue($serviceClass);

        $refLoaderClass = new \ReflectionClass($loaderClass);
        $requiredLoaderClass = 'FivePercent\Bundle\ApiBundle\SMD\Loader\ServiceAnnotationLoader';

        if (!$refLoaderClass->isSubclassOf($requiredLoaderClass) &&
            $refLoaderClass->getName() !== $requiredLoaderClass
        ) {
            throw new \RuntimeException(sprintf(
                'The service loader for handler "%s" should be implemented of "%s" class.',
                $handlerId,
                $requiredLoaderClass
            ));
        }

        $serviceLoaderDefinition->addMethodCall('addService', [
            $id,
            $serviceClass
        ]);
    }
}
