<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 11.11.2017
 * Time: 22:53
 */
namespace AppBundle\Service\LxdApi\Endpoints;


use AppBundle\Service\Util\ResponseFormat;
use AppBundle\Service\LxdApi\ApiClient;

class Host extends AbstractEndpoint
{
    protected function getEndpoint($urlParam = NULL)
    {
        return '';
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

    public function authenticate($data)
    {
        return $this->post('certificates', $data);
    }
}