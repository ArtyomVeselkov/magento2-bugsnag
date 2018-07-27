<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Resolver\Build;

/**
 * Class EnvVariable
 * @package Optimlight\Bugsnag\Model\Resolver\Build
 *
 *
 * Example of usage in `env.php`:
 *
 * 'build_class' => '\\Optimlight\\Bugsnag\\Model\\Resolver\\Build\\EnvVariable',
 * 'build_options' =>
 *     array (
 *         'path_version' => 'build_number',
 *         'destination' => array(
 *              'build_number' => 'ENV_BUILD_NUMBER'
 *          ),
 *     ),
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
        $data[self::DESTINATION_TYPE] = 'file';
        $data[self::NESTED_PATH_INFO_KEY] = false;
        parent::__construct($data);
    }

    /**
     * @param string $destination
     * @param string $nestedPath Is not used now.
     * @return bool|array
     */
    public function resolveData($destination, $nestedPath)
    {
        $build = [];
        if (is_array($destination)) {
            foreach ($destination as $key => $envKey) {
                $build[$key] = getenv($envKey, true) ?? getenv($envKey);
            }
        }
        return $build;
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
