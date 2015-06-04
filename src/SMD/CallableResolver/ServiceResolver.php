<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\SMD\CallableResolver;

use FivePercent\Bundle\ApiBundle\SMD\Action\ServiceAction;
use FivePercent\Component\Api\SMD\CallableResolver\BaseCallable;
use FivePercent\Component\Api\SMD\CallableResolver\CallableResolverInterface;
use FivePercent\Component\Reflection\Reflection;
use FivePercent\Component\Exception\UnexpectedTypeException;
use FivePercent\Component\Api\SMD\Action\ActionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolve service callbacks
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class ServiceResolver implements CallableResolverInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Construct
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function isSupported(ActionInterface $action)
    {
        return $action instanceof ServiceAction;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(ActionInterface $action)
    {
        if (!$action instanceof ServiceAction) {
            throw UnexpectedTypeException::create($action, 'FivePercent\Bundle\ApiBundle\SMD\Action\ServiceAction');
        }

        $serviceId = $action->getServiceId();
        $method = $action->getMethod();

        $service = $this->container->get($serviceId);
        $reflectionService = Reflection::loadClassReflection($service);
        $reflectionMethod = $reflectionService->getMethod($method);

        return new BaseCallable($reflectionMethod, $service);
    }
}
