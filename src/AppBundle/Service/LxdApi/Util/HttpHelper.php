<?php


namespace AppBundle\Service\LxdApi\Util;

use Httpful\Request;

class HttpHelper
{
    private $cert_location;
    private $cert_key_location;
    private $cert_passphrase;

    public function __construct($cert_location, $cert_key_location, $cert_passphrase)
    {
        $this->cert_location = $cert_location;
        $this->cert_key_location = $cert_key_location;
        $this->cert_passphrase = $cert_passphrase;
    }


    public function buildUri($host, $endpoint, $apiVersion = null)
    {
        $hostname = $host->getIpv4() ?: $host->getIpv6() ?: $host->getDomainName() ?: 'localhost';

        $port = $host->getPort() ?: '8443';
        $apiVersion = $apiVersion ?: '1.0';
        $url = 'https://'.$hostname.':'.$port.'/'.$apiVersion.'/'.$endpoint;

        return $url;
    }

    public function init()
    {

        if($this->cert_passphrase != NULL)
        {
            $template = Request::init()
            ->sendsJson()    // Send application/x-www-form-urlencoded
            ->withoutStrictSsl()        // Ease up on some of the SSL checks
            ->expectsJson()             // Expect JSON responses
            ->authenticateWithCert($this->cert_location, $this->cert_key_location, $this->cert_passphrase); //uses cert from parameters.yml
        } else {
            $template = Request::init()
            ->sendsJson()    // Send application/x-www-form-urlencoded
            ->withoutStrictSsl()        // Ease up on some of the SSL checks
            ->expectsJson()             // Expect JSON responses
            ->authenticateWithCert($this->cert_location, $this->cert_key_location);
        }

        Request::ini($template);
    }
}