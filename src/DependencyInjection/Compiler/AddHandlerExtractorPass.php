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
 * Add error factory for handlers
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class AddHandlerExtractorPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $extractorRegistryDefinition = $container->getDefinition('api.handler.extractor_registry');

        foreach ($container->findTaggedServiceIds('api.handler.extractor') as $id => $tags) {
            $definition = $container->getDefinition($id);

            try {
                $class = $definition->getClass();
                $class = $container->getParameterBag()->resolveValue($class);

                $refClass = new \ReflectionClass($class);
                $requiredInterface = 'FivePercent\Component\Api\Handler\Doc\ExtractorInterface';

                if (!$refClass->implementsInterface($requiredInterface)) {
                    throw new \RuntimeException(sprintf(
                        'The handler doc extractor should implement "%s".',
                        $requiredInterface
                    ));
                }

                foreach ($tags as $index => $attributes) {
                    if (empty($attributes['handler'])) {
                        throw new \RuntimeException(sprintf(
                            'Missing required attribute "attribute" for tag "api.handler.extractor" (Tag index: %d).',
                            $index
                        ));
                    }

                    $extractorRegistryDefinition->addMethodCall('addExtractor', [
                        $attributes['handler'],
                        $id
                    ]);
                }

            } catch (\Exception $e) {
                throw new \RuntimeException(sprintf(
                    'Could not compile handler doc extractor with service id "%s".',
                    $id
                ));
            }
        }
    }
}
