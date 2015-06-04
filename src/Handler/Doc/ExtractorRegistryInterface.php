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

/**
 * All extractor registry should implement this interface
 *
 * @author Vitaliy Zhuk <zhuk22052gmail.com>
 */
interface ExtractorRegistryInterface
{
    /**
     * Get extractor for handler
     *
     * @param string $handler
     *
     * @return \FivePercent\Component\Api\Handler\Doc\ExtractorInterface
     */
    public function getExtractor($handler);
}
