<?php

namespace AppBundle\Service\Nagios;


use AppBundle\Entity\ContainerStatus;
use AppBundle\Entity\HostStatus;
use Httpful\Request;

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
        $uri = $containerStatus->getNagiosUrl().'/image?host='.$containerStatus->getNagiosName().'&srv='.$containerStatus->getCheckName().'&view=1&source='.$containerStatus->getSourceNumber().'&start='.$timerange;
        $response = Request::get($uri)
            ->authenticateWith($this->username, $this->password)
            ->expectsHtml()
            ->send();

        return $response;
    }

    /**
     * @param HostStatus $hostStatus
     * @param $timerange
     * @return
     */
    public function getNagiosImageForHostTimerange(HostStatus $hostStatus, $timerange){
        $uri = $hostStatus->getNagiosUrl().'/image?host='.$hostStatus->getNagiosName().'&srv='.$hostStatus->getCheckName().'&view=1&source='.$hostStatus->getSourceNumber().'&start='.$timerange;
        $response = Request::get($uri)
            ->authenticateWith($this->username, $this->password)
            ->expectsHtml()
            ->send();

        return $response;
    }
}