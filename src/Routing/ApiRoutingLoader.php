<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads routing for servers
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class ApiRoutingLoader extends Loader
{
    /**
     * @var array
     */
    private $paths = [];

    /**
     * Add path
     *
     * @param string $key
     * @param string $host
     * @param string $path
     * @param array  $schemes
     */
    public function addPath($key, $host, $path, array $schemes = [])
    {
        $this->paths[$key] = [
            'host' => $host,
            'path' => $path,
            'schemes' => $schemes
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load($resource, $type = null)
    {
        $routes = new RouteCollection();

        foreach ($this->paths as $key => $pathInfo) {
            $defaults = [
                '_controller' => 'api.controller:handle',
                'serverKey' => $key
            ];

            $route = new Route(
                $pathInfo['path'],
                $defaults,
                [],
                [],
                $pathInfo['host'],
                $pathInfo['schemes']
            );

            $routes->add('api_handle_' . $key, $route);
        }

        return $routes;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api' == $type;
    }
}
