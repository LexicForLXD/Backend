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
        $this->time_elapsed();

        $operationsResponse = $this->api->getOperationsLink($event->getHost(), $event->getOperationId());

        //VarDumper::dump($operationsResponse);

        if ($operationsResponse->code != 200) {
            //return new Response(json_encode($operationsResponse->body));
            echo "FAILED-UPDATE \n";
            return;
        }

        echo "WAITING... \n";
        do{
            $operationsResponse = $this->api->getOperationsLink($event->getHost(), $event->getOperationId());
            sleep(0.5);
        }while ($operationsResponse->body->metadata->status_code == 103);

        if ($operationsResponse->body->metadata->status_code != 200) {
            echo "FAILED-UPDATE : ".$operationsResponse->body->metadata->err."\n";
            $image = $this->em->getRepository(Image::class)->find($event->getImageId());
            $image->setError($operationsResponse->body->metadata->err);
            $this->em->persist($image);
            $this->em->flush($image);
            return;
        }

        echo "UPDATING... \n";
        $image = $this->em->getRepository(Image::class)->find($event->getImageId());

        $image->setFingerprint($operationsResponse->body->metadata->metadata->fingerprint);
        $image->setArchitecture("amd64");
        //TODO Parse architecture
        $image->setSize($operationsResponse->body->metadata->metadata->size);
        $image->setFinish(true);

        $this->em->persist($image);
        $this->em->flush($image);

        echo "FINISH-UPDATE : ImageId ".$event->getImageId().' in '.$this->time_elapsed()."milliseconds \n";
    }

    function time_elapsed()
    {
    }

}