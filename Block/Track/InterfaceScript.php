<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Block\Track;

use Magento\Framework\Data\CollectionDataSourceInterface;

/**
 * Interface InterfaceScript
 * @package Optimlight\Bugsnag\Block\Track
 *
 * CollectionDataSourceInterface extends @see \Magento\Framework\View\Element\Block\ArgumentInterface
 *   starting from Magento 2.2.x
 */
interface InterfaceScript extends CollectionDataSourceInterface
{
    /**
     * @return string
     */
    public function render();
}
