<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Block\Track;

use Optimlight\Bugsnag\Boot\{Runner, ExceptionHandler};
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Interface InterfaceScript
 * @package Optimlight\Bugsnag\Block\Track
 */
interface InterfaceScript extends ArgumentInterface
{
    /**
     * @return string
     */
    public function render();
}
