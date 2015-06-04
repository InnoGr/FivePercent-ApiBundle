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

/**
 * Compile API doc formatters
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class AddDocFormatterPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $formatterManagerDefinition = $container->getDefinition('api.formatter_registry');

        foreach ($container->findTaggedServiceIds('api.formatter') as $id => $tags) {
            $formatterDefinition = $container->getDefinition($id);

            try {
                $class = $formatterDefinition->getClass();
                $class = $container->getParameterBag()->resolveValue($class);

                $refClass = new \ReflectionClass($class);
                $requiredInterface = 'FivePercent\Component\Api\Doc\Formatter\FormatterInterface';

                if (!$refClass->implementsInterface($requiredInterface)) {
                    throw new \RuntimeException(sprintf(
                        'The API Doc Formatter should implement "%s" interface.',
                        $requiredInterface
                    ));
                }

                foreach ($tags as $attributes) {
                    if (empty($attributes['key'])) {
                        throw new \RuntimeException('Missing required attribute "key".');
                    }

                    $formatterManagerDefinition->addMethodCall('addFormatter', [
                        $attributes['key'],
                        $id
                    ]);
                }
            } catch (\Exception $e) {
                throw new \RuntimeException(sprintf(
                    'Could not compile API Doc Formatter with service id "%s".',
                    $id
                ), 0, $e);
            }


        }
    }
}
