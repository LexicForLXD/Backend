<?php

namespace AppBundle\Service\Restore;

use AppBundle\Entity\Backup;
use AppBundle\Entity\Host;
use AppBundle\Exception\WrongInputException;

class RestoreService
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

    /**
     * @param Backup $backup
     * @return string | array
     */
    public function getFilesInBackupForTimestamp(Backup $backup)
    {
        $backupDestination = $backup->getDestination();
        $backupSchedule = $backup->getBackupSchedule();

        $remoteBackupPath = $backupDestination->getDestinationText().$backupSchedule->getName();

        $result = shell_exec('duplicity list-current-files --time '.date_format($backup->getTimestamp(), DATE_ISO8601).' '.$remoteBackupPath);

        if(strpos($result, 'Error') !== false){
            return substr($result, strpos($result, 'Error'));
        }

        //Create array with filenames from result - try to find all files ending with .tar.gz

        //TODO File parsing

    }

    /**
     * @param Host $host
     * @param string $containerName
     * @param string $tarball
     * @param Backup $backup
     * @return string
     */
    public function restoreBackupForTimestampInTmp(Host $host, string $containerName, string $tarball, Backup $backup)
    {
        $hostname = $host->getIpv4() ? : $host->getIpv6() ? : $host->getDomainName() ? : 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        $backupDestination = $backup->getDestination();

        $remoteBackupPath = $backupDestination->getDestinationText().$backup->getBackupSchedule()->getName();

        $duplicityCommand = 'duplicity restore --no-encryption '.$remoteBackupPath.' --time '.date_format($backup->getTimestamp(), DATE_ISO8601).' --file-to-restore '.$tarball.' /tmp/restore'.$backup->getBackupSchedule()->getName().'/'.$containerName.'.tar.gz';

        $exec->run('rm -rf /tmp/restore'.$backup->getBackupSchedule()->getName());
        $exec->run('mkdir /tmp/restore'.$backup->getBackupSchedule()->getName());
        $result = $exec->run($duplicityCommand);

        return $result;
    }

    /**
     * @param Host $host
     * @param string $containerName
     * @param Backup $backup
     * @return string
     */
    public function createLXCImageFromTarball(Host $host, string $containerName, Backup $backup) : string
    {
        $hostname = $host->getIpv4() ? : $host->getIpv6() ? : $host->getDomainName() ? : 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        $pathToTarball = '/tmp/restore'.$backup->getBackupSchedule()->getName().'/'.$containerName.'.tar.gz';

        $lxcCommand = 'lxc image import '.$pathToTarball.' --alias '.$containerName;

        $importResult = $exec->run($lxcCommand);
        //Remove tarball after import
        $exec->run('rm -rf '.$pathToTarball);

        return $importResult;
    }

    /**
     * @param Host $host
     * @param string $containerName
     * @return string
     */
    public function restoreContainerFromImage(Host $host, string $containerName) : string
    {
        $hostname = $host->getIpv4() ? : $host->getIpv6() ? : $host->getDomainName() ? : 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();
        return $exec->run('lxc init '.$containerName.' '.$containerName);
    }
}