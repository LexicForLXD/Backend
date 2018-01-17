<?php

namespace AppBundle\Service\SSH;

use AppBundle\Entity\Host;
use Ssh\Authentication\PublicKeyFile;
use Ssh\Configuration;
use Ssh\Session;

class HostSSH
{
    private $privateKey;
    private $publicKey;
    private $username;
    private $passphrase;

    public function __construct($privateKey, $publicKey, $username, $passphrase)
    {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
        $this->username = $username;
        $this->passphrase = $passphrase;
    }

    public function getLogFileFromHost(Host $host, String $logpath){
        $hostname = $host->getIpv4() ?: $host->getIpv6() ?: $host->getDomainName() ?: 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->username, $this->publicKey, $this->privateKey, $this->passphrase);

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        return $exec->run('cat '.$logpath);
    }
}