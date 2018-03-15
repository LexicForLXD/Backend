<?php

namespace AppBundle\Service\Restore;

use AppBundle\Entity\Backup;
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
}