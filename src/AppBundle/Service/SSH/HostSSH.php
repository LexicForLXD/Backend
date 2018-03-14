<?php

namespace AppBundle\Service\SSH;

use AppBundle\Entity\BackupDestination;
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
     * @param Container $container
     * @param String $fileContent
     * @return null|string|string[]
     */
    public function sendAnacronFile(Container $container, String $fileContent, String $executionTime)
    {
        $host = $container->getHost();
        $hostname = $host->getIpv4() ? : $host->getIpv6() ? : $host->getDomainName() ? : 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $filepath = "/etc/" . $executionTime . "/";

        $session = new Session($configuration, $authentication);

        $exec = $session->getSftp();

        return $exec->write($filepath . $container->getName(), $fileContent);
    }



    /**
     * Make file executeable
     * @param Host $host
     * @param String $fileName absolute filename
     * @return null|string|string[]
     */
    public function makeFileExecuteable(Container $container, String $executionTime)
    {
        $host = $container->getHost();

        $hostname = $host->getIpv4() ? : $host->getIpv6() ? : $host->getDomainName() ? : 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $fileName = "/etc/" . $executionTime . "/" . $container->getName();

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        return $exec->run('chmod +x ' . $fileName);
    }

    /**
     * @param \DateTime $timestamp
     * @param BackupDestination $backupDestination
     * @param string $destinationPath
     * @param string $containerName
     * @param Host $host
     */
    public function restoreBackupForTimestampInTmp(\DateTime $timestamp, BackupDestination $backupDestination, string $destinationPath, string $containerName, Host $host)
    {
        $hostname = $host->getIpv4() ? : $host->getIpv6() ? : $host->getDomainName() ? : 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        $remoteBackupPath = $backupDestination->getDestinationText().$destinationPath;

        $duplicityCommand = 'duplicity restore --no-encryption '.$remoteBackupPath.' --time '.date_format($timestamp, DATE_ISO8601).' --file-to-restore '.$containerName.'.tar.gz /tmp/restore'.$destinationPath.'/'.$containerName.'.tar.gz';

        $exec->run('rm -rf /tmp/restore'.$destinationPath);
        $exec->run('mkdir /tmp/restore'.$destinationPath);
        $result = $exec->run($duplicityCommand);
    }
}