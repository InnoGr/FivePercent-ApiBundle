<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\Handler\Doc;

use FivePercent\Bundle\ApiBundle\Exception\ExtractorNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Container registry for handler extractor
 *
 * @author Vitaliy Zhuk <zhuk22052@gmail.com>
 */
class ContainerExtractorRegistry implements ExtractorRegistryInterface
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
     * Construct
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Add extractor
     *
     * @param string $handler
     * @param string $serviceId
     *
     * @return ContainerExtractorRegistry
     */
    public function addExtractor($handler, $serviceId)
    {
        $this->serviceIds[$handler] = $serviceId;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getExtractor($handler)
    {
        if (!isset($this->serviceIds[$handler])) {
            throw new ExtractorNotFoundException(sprintf(
                'Not found extractor for handler "%s".',
                $handler
            ));
        }

        try {
            return $this->container->get($this->serviceIds[$handler]);
        } catch (ServiceNotFoundException $e) {
            throw new ExtractorNotFoundException(sprintf(
                'Not found extractor for handler "%s".',
                $handler
            ), 0, $e);
        }
    }
}
