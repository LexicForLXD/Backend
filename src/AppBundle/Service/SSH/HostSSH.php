<?php

namespace AppBundle\Service\SSH;

use AppBundle\Entity\Host;
use AppBundle\Exception\WrongInputException;
use Ssh\Authentication\PublicKeyFile;
use Ssh\Configuration;
use Ssh\Session;
use AppBundle\Entity\BackupSchedule;

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
        if (!is_readable($this->ssh_key_location) || !is_readable($this->ssh_location)) {
            throw new WrongInputException("Couldn't read the SSH keys");
        }
    }

    /**
     * Receive the content of a file specified via path
     * @param Host $host
     * @param String $logpath
     * @return null|string|string[]
     */
    public function getLogFileFromHost(Host $host, String $logpath)
    {
        $hostname = $host->getIpv4() ? : $host->getIpv6() ? : $host->getDomainName() ? : 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        return $exec->run('cat ' . $logpath);
    }



    /**
     * Sends the file to the respective anacron dir
     * @param BackupSchedule $backupSchedule
     * @param String $webhookUrl url which should be called in the bash script
     * @return null|string|string[]
     */
    public function sendAnacronFile(BackupSchedule $backupSchedule, String $webhookUrl)
    {
        $host->$backupSchedule->getContainers()[0]->getHost();
        $hostname = $host->getIpv4() ? : $host->getIpv6() ? : $host->getDomainName() ? : 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $fileName = "/etc/" . $backupSchedule->getExecutionTime() . "/" . $backupSchedule->getName();

        $session = new Session($configuration, $authentication);

        $exec = $session->getSftp();

        return $exec->write($fileName, $backupSchedule->getShellCommands($webhookUrl));
    }



    /**
     * Make file executeable
     * @param BackupSchedule $backupSchedule
     * @return null|string|string[]
     */
    public function makeFileExecuteable(BackupSchedule $backupSchedule)
    {
        $host->$backupSchedule->getContainers()[0]->getHost();
        $hostname = $host->getIpv4() ? : $host->getIpv6() ? : $host->getDomainName() ? : 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $fileName = "/etc/" . $backupSchedule->getExecutionTime() . "/" . $backupSchedule->getName();

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        return $exec->run('chmod +x ' . $fileName);
    }

    /**
     * Delete the anacron file on Host
     *
     * @param BackupSchedule $backupSchedule
     * @return boolean|string
     */
    public function deleteAnacronFile(BackupSchedule $backupSchedule)
    {
        $host->$backupSchedule->getContainers()[0]->getHost();
        $hostname = $host->getIpv4() ? : $host->getIpv6() ? : $host->getDomainName() ? : 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $filename = "/etc/" . $backupSchedule->getExecutionTime() . "/" . $backupSchedule->getName();

        $session = new Session($configuration, $authentication);

        $exec = $session->getSftp();

        if ($exec->exists($filename)) {
            return $exec->unlink($filename);
        }
        return "File does not exist";
    }
}