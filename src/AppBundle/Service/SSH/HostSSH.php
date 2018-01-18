<?php

namespace AppBundle\Service\SSH;

use AppBundle\Entity\Host;
use AppBundle\Exception\WrongInputException;
use Ssh\Authentication\PublicKeyFile;
use Ssh\Configuration;
use Ssh\Session;

class HostSSH
{
    private $ssh_key_location;
    private $ssh_location;
    private $ssh_user;
    private $ssh_passphrase;

    /**
     * HostSSH constructor.
     * @param $ssh_key_location
     * @param $ssh_location
     * @param $ssh_user
     * @param $ssh_passphrase
     * @throws WrongInputException
     */
    public function __construct(String $ssh_key_location, String $ssh_location, String $ssh_user, $ssh_passphrase)
    {
        $this->ssh_key_location = $ssh_key_location;
        $this->ssh_location = $ssh_location;
        $this->ssh_user = $ssh_user;
        $this->ssh_passphrase = $ssh_passphrase;
        if(!is_readable($this->ssh_key_location) || !is_readable($this->ssh_location)){
            throw new WrongInputException("Couldn't read the SSH keys");
        }
    }

    public function getLogFileFromHost(Host $host, String $logpath){
        $hostname = $host->getIpv4() ?: $host->getIpv6() ?: $host->getDomainName() ?: 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        return $exec->run('cat '.$logpath);
    }
}