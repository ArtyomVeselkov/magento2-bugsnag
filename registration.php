<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag;

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Optimlight_Bugsnag',
    __DIR__
);

try {
    \Optimlight\Bugsnag\Boot\Runner::init();
} catch (\Exception $exception) {
    error_log('Unable to initialize Bugsnag runner due to exception: ' . $exception->getMessage());
}
