<?php

namespace AppBundle\EventListener;


use AppBundle\Entity\Image;
use AppBundle\Event\ImageCreationEvent;
use AppBundle\Service\LxdApi\ImageApi;
use Doctrine\ORM\EntityManager;

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
     */
    public function onLxdImageCreationUpdater(ImageCreationEvent $event){

        $image = $this->em->getRepository(Image::class)->find($event->getImageId());

        $image->setSize(1500);

        $this->em->persist($image);
        $this->em->flush();

//        $operationsResponse = $this->api->getOperationsLink($event->getHostId(), $result->body->operation);
//
//        if ($operationsResponse->code != 200) {
//            return new Response(json_encode($operationsResponse->body));
//        }
//
//        while ($operationsResponse->body->metadata->status_code == 103) {
//            sleep(0.2);
//            $operationsResponse = $api->getOperationsLink($host, $result->body->operation);
//        }
//
//        if ($operationsResponse->body->metadata->status_code != 200) {
//            return new Response(json_encode($operationsResponse->body));
//        }
//
//        $image->setFingerprint($operationsResponse->body->metadata->metadata->fingerprint);
//        $image->setArchitecture("amd64");
//        //TODO Parse architecture
//        $image->setSize($operationsResponse->body->metadata->metadata->size);
        log("TEST");
    }

}