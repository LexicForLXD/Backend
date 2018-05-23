<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 23.05.18
 * Time: 16:10
 */

namespace AppBundle\Worker;

use AppBundle\Entity\Image;
use AppBundle\Entity\Host;
use AppBundle\Service\LxdApi\ImageApi;
use AppBundle\Service\LxdApi\OperationApi;
use Doctrine\ORM\EntityManagerInterface;
use Dtc\QueueBundle\Model\Worker as BaseWorker;


class ImportWorker extends BaseWorker
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

    public function getName()
    {
        return 'import';
    }

    /**
     * @param int $hostId
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function importImages(int $hostId)
    {
        $host = $this->em->getRepository(Host::class)->find($hostId);
        $imageListResult = $this->api->listImages($host);

        $imageList = $imageListResult->body->metadata;

        foreach ($imageList as $item) {
            $imageResult = $this->api->getImageByFingerprint($host, ltrim($item, "/1.0/images/"));

            $image = new Image();
            $image->setFingerprint($imageResult->body->metadata->fingerprint);
            $image->setProperties($imageResult->body->metadata->properties);
            $image->setPublic($imageResult->body->metadata->public);
            $image->setFilename($imageResult->body->metadata->filename);
            $image->setFinished(true);
            $image->setArchitecture($imageResult->body->metadata->architecture);
            $image->setSize($imageResult->body->metadata->size);
            $image->setHost($host);
            $this->em->persist($image);
            $this->em->flush();
        }
    }
}