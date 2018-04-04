<?php

namespace AppBundle\Service\Backup;

use AppBundle\Entity\Backup;
use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Exception\WrongInputException;
use Ssh\Authentication\PublicKeyFile;
use Ssh\Configuration;
use Ssh\Session;

class BackupService
{
    private $ssh_key_location;
    private $ssh_location;
    private $ssh_user;
    private $ssh_passphrase;

    /**
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
        if (!is_readable($this->ssh_key_location) || !is_readable($this->ssh_location)) {
            throw new WrongInputException("Couldn't read the SSH keys");
        }
    }


    public function exportImageToTmp(Host $host, Container $container, Backup $backup, string $fingerprint)
    {
        $hostname = $host->getIpv4() ?: $host->getIpv6() ?: $host->getDomainName() ?: 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        $result = $exec->run('lxc image export ' . $fingerprint . ' /tmp/'.$backup->getManualBackupName().'/'. $container->getName());

        if (strpos($result, 'Error') !== false) {
            return substr($result, strpos($result, 'Error'));
        }

    }

    public function makeTmpBackupFolder(Host $host, Backup $backup)
    {
        $hostname = $host->getIpv4() ?: $host->getIpv6() ?: $host->getDomainName() ?: 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        $result = $exec->run('mkdir /tmp/' . $backup->getManualBackupName());

        if (strpos($result, 'Error') !== false) {
            return substr($result, strpos($result, 'Error'));
        }
    }


    public function makeDuplicityCall(Host $host, Backup $backup)
    {
        $hostname = $host->getIpv4() ?: $host->getIpv6() ?: $host->getDomainName() ?: 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        $result = $exec->run('duplicity --no-encryption /tmp/' . $backup->getManualBackupName() . ' ' . $this->destination->getDestinationText() . $backup->getManualBackupName());

        if (strpos($result, 'Error') !== false) {
            return substr($result, strpos($result, 'Error'));
        }
    }


    public function removeTmpBackupFolder(Host $host, Backup $backup)
    {
        $hostname = $host->getIpv4() ?: $host->getIpv6() ?: $host->getDomainName() ?: 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        $result = $exec->run('rm -rf /tmp/' . $backup->getManualBackupName());

        if (strpos($result, 'Error') !== false) {
            return substr($result, strpos($result, 'Error'));
        }
    }


}