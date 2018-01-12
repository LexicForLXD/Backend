<?php
/**
 * Created by PhpStorm.
 * User: lionf
 * Date: 12.01.2018
 * Time: 10:54
 */

namespace AppBundle\EventListener;

use AppBundle\Entity\Container;
use AppBundle\Event\ContainerCreationEvent;
use AppBundle\Service\LxdApi\ContainerApi;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\VarDumper;

class ContainerCreationListener
{
    protected $em;
    protected $api;

    public function __construct(EntityManager $em, ContainerApi $api)
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
    public function onLxdContainerCreationUpdate(ContainerCreationEvent $event){

        echo "START-UPDATE : ContainerId ".$event->getContainerId()." \n";

        $operationsResponse = $this->api->getOperationsLink($event->getHost(), $event->getOperationId());


        if ($operationsResponse->code != 200) {
            echo "FAILED-UPDATE ".$operationsResponse->code." \n";
            if($operationsResponse->code == 404){
                echo "Operation not found  \n";
            }
            return;
        }

        $operationsResponse = $this->api->getOperationsLinkWithWait($event->getHost(), $event->getOperationId());

        if ($operationsResponse->body->metadata->status_code != 200) {
            echo "FAILED-UPDATE : ".$operationsResponse->body->metadata->err."\n";
            $container = $this->em->getRepository(Container::class)->find($event->getContainerId());
            $container->setState($operationsResponse->body->metadata->err);
            $this->em->persist($container);
            $this->em->flush($container);
            return;
        }

        echo "UPDATING... \n";
        $container = $this->em->getRepository(Container::class)->find($event->getContainerId());

        $container->setState('created');

        $this->em->persist($container);
        $this->em->flush($container);

        echo "FINISH-UPDATE : ContainerId ".$event->getContainerId()."\n";
    }

}