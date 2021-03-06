<?php

namespace AppBundle\Service\Restore;

use AppBundle\Entity\Backup;
use AppBundle\Entity\Host;
use AppBundle\Exception\WrongInputException;
use Ssh\Authentication\PublicKeyFile;
use Ssh\Configuration;
use Ssh\Session;

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
     * @param Host $host
     * @return string | array
     */
    public function getFilesInBackupForTimestamp(Backup $backup, Host $host)
    {
        $backupDestination = $backup->getDestination();

        $backupName = $this->getBackupName($backup);

        if($backupName == null){
            return "Backup object is invalid, backupSchedule and manualBackupName is missing.";
        }

        $remoteBackupPath = $backupDestination->getDestinationText().$backupName;

        $hostname = $host->getIpv4() ? : $host->getIpv6() ? : $host->getDomainName() ? : 'localhost';
        $configuration = new Configuration($hostname);
        $authentication = new PublicKeyFile($this->ssh_user, $this->ssh_location, $this->ssh_key_location, $this->ssh_passphrase);

        $session = new Session($configuration, $authentication);

        $exec = $session->getExec();

        $result = $exec->run('duplicity list-current-files --time '.date_format($backup->getTimestamp(), DATE_ATOM).' '.$remoteBackupPath.' 2>&1 &');

        if(strpos($result, 'Error') !== false){
            return substr($result, strpos($result, 'Error'));
        }

        //Second query with grep
        $result = $exec->run('duplicity list-current-files --time '.date_format($backup->getTimestamp(), DATE_ATOM).' '.$remoteBackupPath.' | grep .tar.gz');

        //Create array with filenames from result - try to find all files ending with .tar.gz
        $numberOfTarballs = substr_count($result, '.tar.gz');

        $tarballs = array();
        for($i=0; $i<$numberOfTarballs; $i++){
            //Find .tar.gz
            $end = strpos($result, '.tar.gz');
            //Find space before tarball name
            $start = strrpos($result, ' ', $end-strlen($result))+1;

            $name = substr($result, $start, $end-$start+7);
            $tarballs[] = $name;

            //Remove found tarball name from string
            $result = str_replace($name, ' ', $result);
        }

        return $tarballs;
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

        $backupName = $this->getBackupName($backup);

        if($backupName == null){
            return "Error - Backup object is invalid, backupSchedule and manualBackupName is missing.";
        }

        $remoteBackupPath = $backupDestination->getDestinationText().$backupName;

        $duplicityCommand = 'duplicity restore --no-encryption '.$remoteBackupPath.' --time '.date_format($backup->getTimestamp(), DATE_ATOM).' --file-to-restore '.$tarball.' /tmp/restore'.$backupName.'/'.$containerName.'.tar.gz';

        $exec->run('rm -rf /tmp/restore'.$backupName);
        $exec->run('mkdir /tmp/restore'.$backupName);
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

        $backupName = $this->getBackupName($backup);

        if($backupName == null){
            return "Error - Backup object is invalid, backupSchedule and manualBackupName is missing.";
        }

        $pathToTarball = '/tmp/restore'.$backupName.'/'.$containerName.'.tar.gz';

        $lxcCommand = '/snap/bin/lxc image import '.$pathToTarball.' --alias '.$containerName;

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
        return $exec->run('/snap/bin/lxc init '.$containerName.' '.$containerName);
    }

    private function getBackupName(Backup $backup){
        //Check if backupSchedule or manualBackupName is set
        $backupName = $backup->getManualBackupName();
        if($backup->getBackupSchedule() != null){
            $backupName = $backup->getBackupSchedule()->getName();
        }
        return $backupName;
    }
}