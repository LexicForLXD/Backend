<?php

/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 10.05.18
 * Time: 21:06
 */

namespace AppBundle\Worker;


use Symfony\Component\Validator\Validator\ValidatorInterface;
use AppBundle\Service\LxdApi\OperationApi;
use Doctrine\ORM\EntityManagerInterface;
use Dtc\QueueBundle\Model\Worker;

class BackupWorker extends Worker
{
    protected $em;
    protected $validator;
    protected $operationApi;

    /**
     * Undocumented function
     *
     * @param EntityManagerInterface $em
     * @param OperationApi $operationApi
     * @param ValidatorInterface $validator
     */
    public function __construct(EntityManagerInterface $em, OperationApi $operationApi, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->operationApi = $operationApi;
        $this->validator = $validator;
    }
    /**
     * Appends a string to the message of the job.
     * @param string $message
     */
    private function addMessage(string $message)
    {
        $this->getCurrentJob()->setMessage($this->getCurrentJob()->getMessage() . "\n" . $message);
    }

    /**
     * Validates a Object and returns true if error occurs
     * @param  $object
     * @return bool
     */
    private function validation($object)
    {
        $errors = $this->validator->validate($object);

        if (count($errors) > 0) {
            $errorArray = array();
            foreach ($errors as $error) {
                $errorArray[$error->getPropertyPath()] = $error->getMessage();
            }
            $this->addMessage(serialize($errorArray));
            return true;
        }
        return false;
    }

    /**
     * Checks if the response has any errors
     * @param Response $response
     * @return bool
     */
    private function checkForErrors(Response $response)
    {
        if ($response->code !== 202 && $response->code !== 200) {
            $this->addMessage($response->body->error);
            if ($response->body->metadata) {
                if ($response->body->metadata->status_code !== 200 && $response->body->metadata->status_code !== 103) {
                    $this->addMessage($response->body->metadata->err);
                }
            }
            return true;
        }
        return false;
    }
}