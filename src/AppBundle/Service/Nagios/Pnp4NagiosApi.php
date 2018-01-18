<?php

namespace AppBundle\Service\Nagios;


use AppBundle\Entity\ContainerStatus;
use Httpful\Request;
use Symfony\Component\VarDumper\VarDumper;

class Pnp4NagiosApi
{
    private $username;
    private $password;

    public function __construct($nagiosUsername, $nagiosPassword)
    {
        $this->username = $nagiosUsername;
        $this->password = $nagiosPassword;
    }

    /**
     * @param ContainerStatus $containerStatus
     * @param $timerange
     * @return
     */
    public function getNagiosImageForContainerTimerange(ContainerStatus $containerStatus, $timerange){
        $uri = $containerStatus->getNagiosUrl().'/image?host='.$containerStatus->getNagiosName().'&srv=check_http&view=1&source=0&start='.$timerange;
        $response = Request::get($uri)
            ->authenticateWith($this->username, $this->password)
            ->expectsHtml()
            ->send();

        return $response;
    }
}