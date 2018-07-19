<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue\Integrator\Guzzle;

use GuzzleHttp\Psr7 as GuzzlePsr7;
use Psr\Http\Message\RequestInterface;

/**
 * Class RequestSerializer
 * @package Optimlight\Bugsnag\Model\Queue\Integrator\Guzzle
 */
class RequestSerializer
{
    /**
     * @param RequestInterface $request
     * @return string
     */
    public function serialize(RequestInterface $request)
    {
        if ('https' === $request->getUri()->getScheme()) {
            $request = $request->withRequestTarget((string)$request->getUri());
        }
        return GuzzlePsr7\str($request);
    }

    /**
     * @param $string
     * @return RequestInterface
     */
    public function unserialize($string)
    {
        return GuzzlePsr7\parse_request($string);
    }
}