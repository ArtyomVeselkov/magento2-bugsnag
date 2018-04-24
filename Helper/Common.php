<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager as ObjectManager;

/**
 * Class Common
 * @package Optimlight\Bugsnag\Helper
 */
class Common extends AbstractHelper
{
    /**
     * @return ObjectManager
     */
    public static function getObjectManager()
    {
        return ObjectManager::getInstance();
    }
}