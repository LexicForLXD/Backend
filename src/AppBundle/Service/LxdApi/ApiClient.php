<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 11.11.2017
 * Time: 22:46
 */
namespace AppBundle\Service\LxdApi;


use GuzzleHttp\Client;



class ApiClient
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $port;

    /**
     * @var string
     */
    private $apiVersion;

    /**
     * @var Client
     */
    private $client;


    public function __construct($host = null)
    {
        $hostname = $host->getIpv4() ?: $host->getIpv6() ?: $host->getDomainName() ?: 'localhost';

        if($hostname == $host->getIpV6()){
            $hostname = '['.$hostname.']';
        }



        $this->port = $host->getPort() ?: '8443';
        $this->apiVersion = '1.0';
        $this->url = 'https://'.$hostname.':'.$this->port.'/'.$this->apiVersion.'/';



        $this->client = new Client([
            'base_uri' => $this->url,
            'defaults' => [
                'headers' => ['Content-Type' => 'application/json'],
                'cert' => ['/etc/letsencryt/live/lxd-api.lleon.de/fullchain.pem'],
                'ssl_key' => ['/etc/letsencryt/live/lxd-api.lleon.de/privkey.pem'],
                'verify' => false
            ]
        ]);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * @param string $apiVersion
     */
    public function setApiVersion(string $apiVersion)
    {
        $this->apiVersion = $apiVersion;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }




}