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
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add error factory for handlers
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class AddErrorFactoryPass implements CompilerPassInterface
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
        foreach ($container->findTaggedServiceIds('api.error') as $id => $tags) {
            foreach ($tags as $index => $attributes) {
                try {
                    if (empty($attributes['handler'])) {
                        throw new \RuntimeException('Missing required attribute "handler"');
                    }

                    $handlerId = $this->handlerPass->getHandlerId($attributes['handler']);
                    $errorId = $this->handlerPass->getErrors($handlerId);

                    $errorDefinition = $container->getDefinition($errorId);
                    $factoryDefinition = $container->getDefinition($id);

                    $class = $factoryDefinition->getClass();
                    $class = $container->getParameterBag()->resolveValue($class);

                    $refClass = new \ReflectionClass($class);
                    $requiredInterface = 'FivePercent\Component\Error\ErrorFactoryInterface';

                    if (!$refClass->implementsInterface($requiredInterface)) {
                        throw new \RuntimeException(sprintf(
                            'The error factory should implement "%s" interface.',
                            $requiredInterface
                        ));
                    }

                    $errorDefinition->addMethodCall('addFactory', [
                        new Reference($id)
                    ]);

                } catch (\Exception $e) {
                    throw new \RuntimeException(sprintf(
                        'Could not compile error factory for tag index "%d" and service id "%s".',
                        $index,
                        $id
                    ), 0, $e);
                }
            }
        }
    }
}
