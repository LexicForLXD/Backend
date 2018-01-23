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
use AppBundle\Event\ContainerDeleteEvent;
use AppBundle\Event\ContainerStateEvent;
use AppBundle\Service\LxdApi\ContainerApi;
use AppBundle\Service\LxdApi\ContainerStateApi;
use AppBundle\Service\LxdApi\OperationApi;
use AppBundle\Service\Profile\ProfileManagerApi;
use Doctrine\ORM\EntityManager;

class ContainerListener
{
    protected $em;
    protected $api;
    protected $stateApi;
    protected $operationApi;

    public function __construct(EntityManager $em, ContainerApi $api, ContainerStateApi $stateApi, OperationApi $operationApi)
    {
        $this->em = $em;
        $this->api = $api;
        $this->stateApi = $stateApi;
        $this->operationApi = $operationApi;
    }

    /**
     * @param ContainerCreationEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function onLxdContainerCreationUpdate(ContainerCreationEvent $event){

        echo "START-CREATION : ContainerId ".$event->getContainerId()." \n";

        echo "CREATING CONTAINER... \n";

        $operationsResponse = $this->api->getOperationsLinkWithWait($event->getHost(), $event->getOperationId());

        if ($operationsResponse->body->metadata->status_code != 200) {
            echo "FAILED-UPDATE : ".$operationsResponse->body->metadata->err."\n";
            $container = $this->em->getRepository(Container::class)->find($event->getContainerId());
            $container->setState($operationsResponse->body->metadata->err);
            $this->em->persist($container);
            $this->em->flush($container);
            return;
        }


        $container = $this->em->getRepository(Container::class)->find($event->getContainerId());

        $container->setState('created');

        $this->em->persist($container);
        $this->em->flush($container);

        echo "FINISH-CREATION : ContainerId ".$event->getContainerId()."\n";
    }


    /**
     * @param ContainerStateEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function onLxdContainerStateUpdate(ContainerStateEvent $event)
    {
        echo "START-STATE-UPDATE: ContainerId ".$event->getContainerId()." \n";


        echo "UPDATING STATE... \n";

        $operationsResponse = $this->stateApi->getOperationsLinkWithWait($event->getHost(), $event->getOperationId());

        if ($operationsResponse->body->metadata->status_code != 200) {
            echo "FAILED-STATE-UPDATE : ".$operationsResponse->body->metadata->err."\n";
            $container = $this->em->getRepository(Container::class)->find($event->getContainerId());
            $container->setState($operationsResponse->body->metadata->err);
            $this->em->persist($container);
            $this->em->flush($container);
            return;
        }

        $container = $this->em->getRepository(Container::class)->find($event->getContainerId());

        $result = $this->stateApi->actual($event->getHost(), $container);


        $container->setState(strtolower($result->body->metadata->status));

        $this->em->persist($container);
        $this->em->flush($container);

        echo "FINISH-STATE-UPDATE : ContainerId ".$event->getContainerId()."\n";
    }

    /**
     * @param ContainerDeleteEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function onLxdContainerDeleteUpdate(ContainerDeleteEvent $event)
    {
        echo "START-CONTAINER-DELETE: ContainerId ".$event->getContainerId()." \n";


        echo "DELETING Container... \n";

        $operationsResponse = $this->operationApi->getOperationsLinkWithWait($event->getHost(), $event->getOperationId());

        if ($operationsResponse->body->metadata->status_code != 200) {
            echo "FAILED-CONTAINER-DELETE : ".$operationsResponse->body->metadata->err."\n";
            $container = $this->em->getRepository(Container::class)->find($event->getContainerId());
            $container->setState($operationsResponse->body->metadata->err);
            $this->em->flush($container);
            return;
        }

        $container = $this->em->getRepository(Container::class)->find($event->getContainerId());


        $this->em->remove($container);
        $this->em->flush($container);

        echo "FINISH-CONTAINER-DELETE : ContainerId ".$event->getContainerId()."\n";
    }

}