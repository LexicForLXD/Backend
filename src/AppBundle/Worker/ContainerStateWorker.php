<?php

namespace AppBundle\Worker;

use AppBundle\Entity\Container;
use AppBundle\Service\LxdApi\ContainerApi;
use AppBundle\Service\LxdApi\ContainerStateApi;
use AppBundle\Service\LxdApi\HostApi;
use AppBundle\Service\LxdApi\OperationApi;
use AppBundle\Service\Profile\ProfileManagerApi;
use AppBundle\Service\SSH\ScheduleSSH;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class ContainerStateWorker extends BaseWorker
{
    protected $api;
    protected $stateApi;


    /**
     * ContainerWorker constructor.
     * @param EntityManagerInterface $em
     * @param ContainerApi $api
     * @param ContainerStateApi $stateApi
     * @param OperationApi $operationApi
     */
    public function __construct(EntityManagerInterface $em, ContainerApi $api, ContainerStateApi $stateApi, OperationApi $operationApi, ValidatorInterface $validator)
    {
        parent::__construct($em, $operationApi, $validator);
        $this->api = $api;
        $this->stateApi = $stateApi;
    }


    public function getName()
    {
        return "containerState";
    }

    /**
     * @param int $containerId
     * @param $data
     * @return void
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function updateState($containerId, $data)
    {
        $container = $this->em->getRepository(Container::class)->find($containerId);
        $stateOp = $this->stateApi->update($container, $data);
        if ($this->checkForErrors($stateOp)) {
            return;
        }
        $stateOpWait = $this->operationApi->getOperationsLinkWithWait($container->getHost(), $stateOp->body->metadata->id);

        if ($this->checkForErrors($stateOpWait)) {
            return;
        }


        if ($container->isEphemeral() && $data["action"] === "stop") {
            $this->em->remove($container);
            $this->em->flush();
            return;
        }

        $this->em->refresh($container);
        $stateResult = $this->stateApi->actual($container);
        $container->setState(mb_strtolower($stateResult->body->metadata->status));
        $container->setNetwork($stateResult->body->metadata->network);
        $this->em->flush($container);
    }


}