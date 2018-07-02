<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Resolver\Build;

use Optimlight\Bugsnag\Model\BuildableInterface;

/**
 * Interface BuildInterface
 * @package Optimlight\Bugsnag\Model\Resolver\Build
 */
interface BuildInterface extends BuildableInterface
{
    /**
     * Destination type (can be FILE, HTTP etc)
     */
    const DESTINATION_TYPE = 'type';

    /**
     * Key for a value for a path to a file / resource which contains build information.
     */
    const DESTINATION_PATH_KEY = 'destination';

    /**
     * Path inside structure of target destination resource which contains generic info about build.
     */
    const NESTED_PATH_INFO_KEY = 'path_info';

    /**
     * Path inside of information's structure of a build.
     */
    const NESTED_PATH_VERSION_KEY = 'path_version';

    /**
     * @return array
     */
    public function getBuildInfo();

    /**
     * @return string
     */
    public function getBuildNumber();

    /**
     * @param array $build
     * @return $this
     */
    public function setBuildInfo(array $build);

    /**
     * @return bool
     */
    public function resolve();

    /**
     * Works like an alias for @see getBuildNumber.
     *
     * @return mixed
     */
    public function __toString();
}
