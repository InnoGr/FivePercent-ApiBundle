<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\Server;

use FivePercent\Component\Api\Server\Exception\ServerNotFoundException;
use FivePercent\Component\Api\Server\ServerRegistryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Container server manager. Get the servers from container.
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class ContainerServerRegistry implements ServerRegistryInterface
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
    private $servers = [];

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
     * Add server by service id
     *
     * @param string $key
     * @param string $id
     *
     * @return ContainerServerRegistry
     */
    public function addServer($key, $id)
    {
        $this->serviceIds[$key] = $id;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getServer($key)
    {
        if (isset($this->servers[$key])) {
            return $this->servers[$key];
        }

        if (!isset($this->serviceIds[$key])) {
            throw ServerNotFoundException::create($key);
        }

        try {
            $server = $this->container->get($this->serviceIds[$key]);
        } catch (ServiceNotFoundException $e) {
            throw ServerNotFoundException::create($key, 0, $e);
        }

        if ($server instanceof ContainerAwareInterface) {
            $server->setContainer($this->container);
        }

        $this->servers[$key] = $server;

        return $server;
    }
}
