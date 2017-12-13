<?php


namespace AppBundle\Service\LxdApi\Util;

class UriBuilder
{
    public static function build($host, $endpoint, $apiVersion = null)
    {
        $hostname = $host->getIpv4() ?: $host->getIpv6() ?: $host->getDomainName() ?: 'localhost';

        $port = $host->getPort() ?: '8443';
        $apiVersion = $apiVersion ?: '1.0';
        $url = 'https://'.$hostname.':'.$this->port.'/'.$this->apiVersion.'/'.$endpoint;

        return $url;
    }
}