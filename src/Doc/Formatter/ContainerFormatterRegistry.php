<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\Doc\Formatter;

use FivePercent\Component\Api\Doc\Formatter\FormatterRegistryInterface;
use FivePercent\Component\Api\Doc\FormatterNotFoundException;
use FivePercent\Component\Api\Handler\Doc\Handler\Handler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Container formatter registry. Get formatter from service container
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class ContainerFormatterRegistry implements FormatterRegistryInterface
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
     * @var array|\FivePercent\Component\Api\Doc\Formatter\FormatterInterface[]
     */
    private $formatters = [];

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
     * Add formatter
     *
     * @param string $key
     * @param string $serviceId
     *
     * @return ContainerFormatterRegistry
     */
    public function addFormatter($key, $serviceId)
    {
        $this->serviceIds[$key] = $serviceId;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormatter($key)
    {
        if (isset($this->formatters[$key])) {
            return $this->formatters[$key];
        }

        try {
            $formatter = $this->container->get($this->serviceIds[$key]);
        } catch (ServiceNotFoundException $e) {
            throw new FormatterNotFoundException(sprintf(
                'Not found formatter with key "%s".',
                $key
            ), 0, $e);
        }

        $this->formatters[$key] = $formatter;

        return $formatter;
    }

    /**
     * {@inheritDoc}
     */
    public function hasFormatter($key)
    {
        return isset($this->serviceIds[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function render(Handler $handler, $format)
    {
        return $this->getFormatter($format)->render($handler);
    }
}
