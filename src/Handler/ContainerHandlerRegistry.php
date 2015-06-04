<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\Handler;

use FivePercent\Component\Api\Exception\HandlerNotFoundException;
use FivePercent\Component\Api\Handler\HandlerRegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Container handler manager. Get handler form Service Container.
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class ContainerHandlerRegistry implements HandlerRegistryInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $serviceIds = [];

    /**
     * @var array
     */
    private $handlers = [];

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
     * Add handler
     *
     * @param string $key
     * @param string $serviceId
     *
     * @return ContainerHandlerRegistry
     */
    public function addHandler($key, $serviceId)
    {
        $this->serviceIds[$key] = $serviceId;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getHandler($key)
    {
        if (isset($this->handlers[$key])) {
            return $this->handlers[$key];
        }

        if (empty($this->serviceIds[$key])) {
            throw new HandlerNotFoundException(sprintf(
                'Not found handler with key "%s".',
                $key
            ));
        }

        $serviceId = $this->serviceIds[$key];

        try {
            $handler = $this->container->get($serviceId);
        } catch (ServiceNotFoundException $e) {
            throw new HandlerNotFoundException(sprintf(
                'Not found handler with key "%s".',
                $key
            ), 0, $e);
        }

        $this->handlers[$key] = $handler;

        return $handler;
    }

    /**
     * {@inheritDoc}
     */
    public function getHandlerKeys()
    {
        return array_keys($this->serviceIds);
    }
}
