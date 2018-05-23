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
use Dtc\QueueBundle\Model\Worker;
use Httpful\Response;

class ContainerStateWorker extends Worker
{
    protected $em;
    protected $api;
    protected $stateApi;
    protected $operationApi;


    /**
     * ContainerWorker constructor.
     * @param EntityManagerInterface $em
     * @param ContainerApi $api
     * @param ContainerStateApi $stateApi
     * @param OperationApi $operationApi
     */
    public function __construct(EntityManagerInterface $em, ContainerApi $api, ContainerStateApi $stateApi, OperationApi $operationApi)
    {
        $this->em = $em;
        $this->api = $api;
        $this->stateApi = $stateApi;
        $this->operationApi = $operationApi;
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
        $result = $this->stateApi->update($container, $data);
        if ($this->checkForErrors($container, $result)) {
            return;
        }
        $operationsResponse = $this->operationApi->getOperationsLinkWithWait($container->getHost(), $result->body->metadata->id);

        if ($this->checkForErrors($container, $operationsResponse)) {
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


    /**
     * @param Container $container
     * @param Response $response
     * @return bool
     */
    private function checkForErrors(Container $container, Response $response)
    {

        if ($response->code !== 202 && $response->code !== 200)
        {
            if($response->body->metadata)
            {
                if ($response->body->metadata->status_code !== 200 && $response->body->metadata->status_code !== 103) {
                    $container->setError($response->body->metadata->err);
                }
            } else
            {
                $container->setError($response->raw_body);
            }
            $this->em->flush($container);
            $this->getCurrentJob()->setMessage("error");
            return true;
        }
        return false;
    }
}