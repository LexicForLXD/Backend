<?php


namespace AppBundle\Service\LxdApi\Util;

use Httpful\Request;

class HttpHelper
{
    public static function buildUri($host, $endpoint, $apiVersion = null)
    {
        $hostname = $host->getIpv4() ?: $host->getIpv6() ?: $host->getDomainName() ?: 'localhost';

        $port = $host->getPort() ?: '8443';
        $apiVersion = $apiVersion ?: '1.0';
        $url = 'https://'.$hostname.':'.$this->port.'/'.$this->apiVersion.'/'.$endpoint;

        return $url;
    }

    public static function init()
    {

        if($this->hasParameter('cert_passphrase'))
        {
            $template = Request::init()
            ->sendsJson()    // Send application/x-www-form-urlencoded
            ->withoutStrictSsl()        // Ease up on some of the SSL checks
            ->expectsJson()             // Expect JSON responses
            ->authenticateWithCert($this->getParameter('cert_location'), $this->getParameter('cert_key_location'), $this->getParameter('cert_passphrase')); //uses cert from parameters.yml
        } else {
            $template = Request::init()
            ->sendsJson()    // Send application/x-www-form-urlencoded
            ->withoutStrictSsl()        // Ease up on some of the SSL checks
            ->expectsJson()             // Expect JSON responses
            ->authenticateWithCert($this->getParameter('cert_location'), $this->getParameter('cert_key_location')); //uses cert from parameters.yml
        }

        Request::ini($template);
    }
}