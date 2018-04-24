<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager as ObjectManager;

/**
 * Class Common
 * @package Optimlight\Bugsnag\Helper
 */
class Common extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface|null
     */
    protected $scopeConfig = null;

    /**
     * Common constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     */
    public function __construct(ScopeConfigInterface $scopeConfig, Context $context)
    {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * @return ObjectManager
     */
    public static function getObjectManager()
    {
        return ObjectManager::getInstance();
    }

    /**
     * @param string $path
     * @param mixed $ifNull
     * @param string $scope
     * @param int|null $scopeCode
     * @return mixed
     */
    public function getConfigValue(
        $path,
        $ifNull = null,
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    )
    {
        $result = $this->scopeConfig->getValue($path, $scope, $scopeCode);
        return is_null($result) ? $ifNull : $result;
    }
}