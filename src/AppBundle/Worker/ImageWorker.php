<?php

/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 10.05.18
 * Time: 22:23
 */

namespace AppBundle\Worker;


use AppBundle\Entity\Image;
use AppBundle\Service\LxdApi\ImageApi;
use AppBundle\Service\LxdApi\OperationApi;
use Doctrine\ORM\EntityManagerInterface;
use Dtc\QueueBundle\Model\Worker as BaseWorker;

class ImageWorker extends BaseWorker
{
    protected $em;
    protected $api;
    protected $operationApi;

    /**
     * ImageWorker constructor.
     * @param EntityManagerInterface $em
     * @param ImageApi $api
     * @param OperationApi $operationApi
     */
    public function __construct(EntityManagerInterface $em, ImageApi $api, OperationApi $operationApi)
    {
        $this->em = $em;
        $this->api = $api;
        $this->operationApi = $operationApi;
    }

    /**
     * @param int $imageId
     * @param $body
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createImage($imageId, $body)
    {
        $image = $this->em->getRepository(Image::class)->find($imageId);
        $result = $this->api->createImage($image->getHost(), $body);

        if ($result->code != 202) {
            $this->em->remove($image);
            $this->addMessage($result->body->error);

            if ($result->body->metadata) {
                if ($result->body->metadata->status_code == 400) {
                    $this->addMessage($result->body->metadata->err);
                }
            }
            $this->em->flush();
            return;
        }

        $operationsResponse = $this->operationApi->getOperationsLinkWithWait($image->getHost(), $result->body->metadata->id);

        if ($operationsResponse->code != 200) {
            $this->em->remove($image);
            $this->addMessage($operationsResponse->body->error);
            if ($operationsResponse->body->metadata) {
                if ($operationsResponse->body->metadata->status_code != 200) {
                    $this->addMessage($operationsResponse->body->metadata->err);
                }
            }
            $this->em->flush();
            return;
        }

        $image->setFingerprint($operationsResponse->body->metadata->metadata->fingerprint);

        //Fetch info from server
        $result = $this->api->getImageByFingerprint($image->getHost(), $image->getFingerprint());
        $image->setArchitecture($result->body->metadata->architecture);
        $image->setProperties($result->body->metadata->properties);
        $image->setSize($result->body->metadata->size);
        $image->setFilename($result->body->metadata->filename);
        $image->setPublic($result->body->metadata->public);

        $image->setFinished(true);

        $this->em->persist($image);
        $this->em->flush($image);
    }

    public function getName()
    {
        return 'image';
    }


    /**
     * Appends a string to the message of the job.
     * @param string $message
     */
    private function addMessage(string $message)
    {
        $this->getCurrentJob()->setMessage($this->getCurrentJob()->getMessage() . "\n" . $message);
    }

}