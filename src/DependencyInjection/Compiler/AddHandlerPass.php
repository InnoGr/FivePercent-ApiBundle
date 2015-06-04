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
 * Compile API handlers
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class AddHandlerPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $handlers = [];

    /**
     * @var array
     */
    private $serviceLoaders = [];

    /**
     * @var array
     */
    private $errorServices = [];

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $handlerRegistryDefinition = $container->getDefinition('api.handler_registry');

        foreach ($container->findTaggedServiceIds('api.handler') as $id => $attributes) {
            $attributes = $this->fixAttributes($attributes);

            try {
                if (empty($attributes['key'])) {
                    throw new \RuntimeException('Missing attribute "key".');
                }

                $key = $attributes['key'];
                $definition = $container->getDefinition($id);

                if (isset($this->handlers[$key])) {
                    throw new \RuntimeException(sprintf(
                        'The handler with key "%s" already registered for service "%s". ' .
                        'If you want change handler, please replace service in your service.yml',
                        $key,
                        $this->handlers[$key]
                    ));
                }

                $class = $definition->getClass();
                $class = $container->getParameterBag()->resolveValue($class);

                $refClass = new \ReflectionClass($class);
                $requiredInterface = 'FivePercent\Component\Api\Handler\HandlerInterface';

                if (!$refClass->implementsInterface($requiredInterface)) {
                    throw new \RuntimeException(sprintf(
                        'The API handler should be implemented of "%s" interface.',
                        $requiredInterface
                    ));
                }

                $this->handlers[$key] = $id;

                if (!$definition->isPublic() || $definition->isAbstract()) {
                    $container->getCompiler()->addLogMessage(sprintf(
                        '%s: Attention: can not add API handler with id "%s" to manager. ' .
                        'The handler is abstract or not public.',
                        get_class($this),
                        $id
                    ));
                } else {
                    $handlerRegistryDefinition->addMethodCall('addHandler', [
                        $key,
                        $id
                    ]);
                }

                if (!empty($attributes['service_loader'])) {
                    $this->serviceLoaders[$id] = $attributes['service_loader'];
                }

                if (!empty($attributes['errors'])) {
                    $this->errorServices[$id] = $attributes['errors'];
                }
            } catch (\Exception $e) {
                throw new \RuntimeException(sprintf(
                    'Could not compile API handler with service id "%s".',
                    $id
                ), 0, $e);
            }
        }
    }

    /**
     * Get handlers
     *
     * @return array
     */
    public function getHandlerIds()
    {
        return $this->handlers;
    }

    /**
     * Get handler
     *
     * @param string $key
     *
     * @return string
     */
    public function getHandlerId($key)
    {
        if (isset($this->handlers[$key])) {
            return $this->handlers[$key];
        }

        throw new \RuntimeException(sprintf(
            'Not found handler with key "%s". Available handlers: "%s"',
            $key,
            implode('", "', array_keys($this->handlers))
        ));
    }

    /**
     * Get service id of annotation loader for handler
     *
     * @param string $handlerId
     *
     * @return string
     */
    public function getServiceLoader($handlerId)
    {
        if (isset($this->serviceLoaders[$handlerId])) {
            return $this->serviceLoaders[$handlerId];
        }

        throw new \RuntimeException(sprintf(
            'Not found service loader for handler "%s".',
            $handlerId
        ));
    }

    /**
     * Get service id of error system
     *
     * @param string $handlerId
     *
     * @return string
     */
    public function getErrors($handlerId)
    {
        if (isset($this->errorServices[$handlerId])) {
            return $this->errorServices[$handlerId];
        }

        throw new \RuntimeException(sprintf(
            'Not found error system for handler "%s".',
            $handlerId
        ));
    }

    /**
     * Fix attributes
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function fixAttributes(array $attributes)
    {
        $result = $attributes;

        foreach ($attributes as $attr) {
            $result = array_merge($result, $attr);
        }

        return $result;
    }
}
