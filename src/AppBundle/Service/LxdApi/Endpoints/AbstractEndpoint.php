<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 11.11.2017
 * Time: 22:53
 */
 namespace AppBundle\Service\LxdApi\Endpoints;
 

use GuzzleHttp\Psr7\Request;
use AppBundle\Service\Util\ResponseFormat;
use \AppBundle\Service\LxdApi\ApiClient;

abstract class AbstractEndpoint
{

    protected $client;


    public function __construct(ApiClient $apiClient)
    {
        $this->client = $apiClient->getClient();
    }

    abstract protected function getEndpoint();
    /**
     * Send a GET request with query parameters.
     *
     * @param string $path           Request path.
     * @param array  $parameters     GET parameters.
     *
     * @return array|string
     */
    protected function get($path, array $parameters = [])
    {
        $request = new Request(
            'GET',
            $path,
            ['query' => $parameters]
        );

        $response = $this->client->send($request);
        return ResponseFormat::getContent($response);
    }
    /**
     * Send a POST request with JSON-encoded data.
     *
     * @param string        $path           Request path.
     * @param array|string  $data           POST data to be JSON encoded.
     * @param array         $parameters     POST parameters.
     *
     * @return array|string
     */
    protected function post($path, $data = [], array $parameters = [])
    {
        $request = new Request(
            'POST',
            $path,
            [
                'query' => $parameters,
                'body' => $this->createJsonBody($data)
            ]
        );

        $response = $this->client->send($request);
        return ResponseFormat::getContent($response);
    }
    /**
     * Send a PUT request with JSON-encoded data.
     *
     * @param string        $path           Request path.
     * @param array|string  $data           POST data to be JSON encoded.
     * @param array         $parameters     POST parameters.
     *
     * @return array|string
     */
    protected function put($path, $data = [], array $parameters = [])
    {
        $request = new Request(
            'PUT',
            $path,
            [
                'query' => $parameters,
                'body' => $this->createJsonBody($data)
            ]
        );

        $response = $this->client->send($request);
        return ResponseFormat::getContent($response);
    }
    /**
     * Send a PATCH request with JSON-encoded data.
     *
     * @param string        $path           Request path.
     * @param array|string  $data           POST data to be JSON encoded.
     * @param array         $parameters     POST parameters.
     *
     * @return array|string
     */
    protected function patch($path, $data = [], array $parameters = [])
    {
        $request = new Request(
            'PATCH',
            $path,
            [
                'query' => $parameters,
                'body' => $this->createJsonBody($data)
            ]
        );

        $response = $this->client->send($request);
        return ResponseFormat::getContent($response);
    }
    /**
     * Send a DELETE request with query parameters.
     *
     * @param string $path           Request path.
     * @param array  $parameters     GET parameters.
     *
     * @return array|string
     */
    protected function delete($path, array $parameters = [])
    {
        $request = new Request(
            'PATCH',
            $path,
            [
                'query' => $parameters,
            ]
        );

        $response = $this->client->send($request);
        return ResponseFormat::getContent($response);
    }


    /**
     * Create a JSON encoded version of an array.
     *
     * @param array|string $data Request data
     *
     * @return null|string
     */
    protected function createJsonBody($data)
    {
        if (is_array($data)) {
            return (count($data) === 0) ? null : json_encode($data, empty($data) ? JSON_FORCE_OBJECT : 0);
        } else {
            return $data;
        }
    }
}