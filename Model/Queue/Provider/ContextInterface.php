<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue\Provider;

interface ContextInterface
{
    /**
     * @param $name
     * @param array $options
     * @return void
     */
    public function initContext($name, array $options);

    /**
     * @param string $name
     * @return object
     */
    public function getContext($name);
}
