<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model;

/**
 * Interface BuildableInterface
 * @package Optimlight\Bugsnag\Model
 */
interface BuildableInterface
{
    /**
     * Function which works like a factory. As this class can be invoked before Magento's FrameWork is launched,
     * each class inherited from @see BuilderInterface should implement this function as an alternative to be
     * created via Magento's ObjectManager.
     *
     * Otherwise false should be returned.
     *
     * @return BuildableInterface|bool
     */
    public static function getInstance();
}
