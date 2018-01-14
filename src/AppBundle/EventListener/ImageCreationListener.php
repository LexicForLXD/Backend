<?php

namespace AppBundle\EventListener;


use AppBundle\Entity\Image;
use AppBundle\Event\ImageCreationEvent;
use AppBundle\Service\LxdApi\ImageApi;
use Doctrine\ORM\EntityManager;
use Symfony\Component\VarDumper\VarDumper;

class ImageCreationListener
{
    protected $em;
    protected $api;

    public function __construct(EntityManager $em, ImageApi $api)
    {
        $this->em = $em;
        $this->api = $api;
    }

    /**
     * @param ImageCreationEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function onLxdImageCreationUpdate(ImageCreationEvent $event){

        echo "START-UPDATE : ImageId ".$event->getImageId()." \n";
        echo "UPDATING... \n";

        $operationsResponse = $this->api->getOperationsLinkWithWait($event->getHost(), $event->getOperationId());

        if ($operationsResponse->body->metadata->status_code != 200) {
            echo "FAILED-UPDATE : ".$operationsResponse->body->metadata->err."\n";
            $image = $this->em->getRepository(Image::class)->find($event->getImageId());
            $image->setError($operationsResponse->body->metadata->err);
            $this->em->persist($image);
            $this->em->flush($image);
            return;
        }

        $image = $this->em->getRepository(Image::class)->find($event->getImageId());

        $image->setFingerprint($operationsResponse->body->metadata->metadata->fingerprint);
        //Parse architecture
        $result = $this->api->getImageByFingerprint($event->getHost(), $image->getFingerprint());
        $image->setArchitecture($result->body->metadata->architecture);
        $image->setSize($operationsResponse->body->metadata->metadata->size);
        $image->setFinished(true);

        $this->em->persist($image);
        $this->em->flush($image);

        echo "FINISH-UPDATE : ImageId ".$event->getImageId()."\n";
    }

}