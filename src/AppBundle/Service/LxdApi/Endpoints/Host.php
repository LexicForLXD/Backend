<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 11.11.2017
 * Time: 22:53
 */

use AppBundle\Service\Util\ResponseFormat;
use \AppBundle\Service\LxdApi\ApiClient;

class Host extends AbstractEndpoint
{
    protected function getEndpoint()
    {
        return '';
    }

    /**
     * A LXD Host
     * @param ApiClient $client
     */
    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     *  Server configuration and environment information
     *
     * @return object
     */
    public function info()
    {
        return $this->get($this->getEndpoint());
    }

    /**
     * Does the server trust the client
     *
     * @return bool
     */
    public function trusted()
    {
        $info = $this->info();
        return $info['auth'] === 'trusted' ? true : false;
    }
}