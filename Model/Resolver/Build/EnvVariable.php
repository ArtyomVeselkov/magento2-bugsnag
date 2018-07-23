<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Resolver\Build;

/**
 * Class EnvVariable
 * @package Optimlight\Bugsnag\Model\Resolver\Build
 */
class EnvVariable extends BuildAbstract
{
    /**
     * EnvVariable constructor.
     * @param array $data
     */
    public function __construct(
        array $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * @param string $destination
     * @param string $nestedPath Is not used now.
     * @return bool|array
     */
    public function resolveData($destination, $nestedPath)
    {
        return getenv($destination, true) ?? getenv($destination);
    }

    /**
     * @param mixed ...$arguments
     * @return BuildAbstract|static
     */
    public static function getInstance(...$arguments)
    {
        return new static(...$arguments);
    }
}
