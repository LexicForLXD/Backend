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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImageWorker extends BaseWorker
{
    protected $api;

    /**
     * ImageWorker constructor.
     * @param EntityManagerInterface $em
     * @param ImageApi $api
     * @param OperationApi $operationApi
     */
    public function __construct(EntityManagerInterface $em, ImageApi $api, OperationApi $operationApi, ValidatorInterface $validator)
    {
        parent::__construct($em, $operationApi, $validator);
        $this->api = $api;
    }

    /**
     * @param int $imageId
     * @param $body
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createImage($imageId, $body)
    {
        $image = $this->em->getRepository(Image::class)->find($imageId);
        $imgOp = $this->api->createImage($image->getHost(), $body);
        if ($this->checkForErrors($imgOp)) {
            return;
        }

        $imgOpWait = $this->operationApi->getOperationsLinkWithWait($image->getHost(), $result->body->metadata->id);
        if ($this->checkForErrors($imgOpWait)) {
            return;
        }

        $image->setFingerprint($operationsResponse->body->metadata->metadata->fingerprint);

        //Fetch info from server
        $result = $this->api->getImageByFingerprint($image->getHost(), $image->getFingerprint());
        if ($this->checkForErrors($result)) {
            return;
        }
        $image->setArchitecture($result->body->metadata->architecture);
        $image->setProperties($result->body->metadata->properties);
        $image->setSize($result->body->metadata->size);
        $image->setFilename($result->body->metadata->filename);
        $image->setPublic($result->body->metadata->public);

        $image->setFinished(true);

        if (!$this->validation($image)) {
            $this->em->persist($image);
            $this->em->flush($image);
        }
    }

    public function getName()
    {
        return 'image';
    }

}