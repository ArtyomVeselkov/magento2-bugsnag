<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 * Try to register the error handler right after Magento registered its handler (with fallback).
 */

use Optimlight\Bugsnag\Boot\Runner;

try {
    $handler = Runner::getExceptionsHandler();
    if ($handler && $handler->isActive()) {
        $handler->prepareCards();
        $handler->registerAllHandlers();
    }
} catch (\Exception $exception) {
    error_log('Unable to initialize Bugsnag runner due to exception: ' . $exception->getMessage());
}

return [];
