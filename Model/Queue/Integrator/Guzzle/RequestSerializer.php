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
     * @var bool
     */
    private $canGzip = false;

    /**
     * RequestSerializer constructor.
     */
    public function __construct()
    {
        if (extension_loaded('zlib')) {
            $this->canGzip = true;
        }
    }

    /**
     * @param RequestInterface $request
     * @return string
     */
    public function serialize(RequestInterface $request)
    {
        if ('https' === $request->getUri()->getScheme()) {
            $request = $request->withRequestTarget((string)$request->getUri());
        }
        return $this->compress(GuzzlePsr7\str($request));
    }

    /**
     * @param $string
     * @return RequestInterface
     */
    public function unserialize($string)
    {
        return GuzzlePsr7\parse_request($this->uncompress($string));
    }

    /**
     * @param $string
     * @return string
     */
    private function compress($string)
    {
        if ($this->canGzip && 8192 < strlen($string)) {
            $string = bin2hex(zlib_encode($string, ZLIB_ENCODING_DEFLATE));
        }
        return $string;
    }

    /**
     * @param $string
     * @return string
     */
    private function uncompress($string)
    {
        if ($this->canGzip && ctype_xdigit($string)) {
            $string = hex2bin($string);
            if (PHP_VERSION_ID >= 50400) {
                $string = zlib_decode($string);
            } else {
                // work around issue with gzuncompress & co that do not work with all gzip checksums
                $string = file_get_contents(
                    'compress.zlib://data:application/octet-stream;base64,' . base64_encode($string)
                );
            }
        }
        return $string;
    }
}