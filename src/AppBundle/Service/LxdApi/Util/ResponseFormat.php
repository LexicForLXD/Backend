<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 11.11.2017
 * Time: 23:43
 */
namespace AppBundle\Service\LxdApi\Util;

use GuzzleHttp\Psr7\Response;

class ResponseFormat
{
    /**
     * @param Response $response
     *
     * @return array|string
     */
    public static function getContent(Response $response)
    {
        $body = $response->getBody()->__toString();
        if (strpos($response->getHeaderLine('Content-Type'), 'application/json') === 0) {
            $content = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if ($response->getStatusCode() >= 100 && $response->getStatusCode() <= 111) {
                    return $content;
                }
                return $content['metadata'];
            }
        }
        return $body;
    }

    /**
     * Get the value for a single header
     * @param Response $response
     * @param string $name
     *
     * @return string|null
     */
    public static function getHeader(Response $response, $name)
    {
        $headers = $response->getHeader($name);
        return array_shift($headers);
    }
}