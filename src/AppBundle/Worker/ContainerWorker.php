<?php

namespace AppBundle\Worker;

use AppBundle\Entity\Container;
use AppBundle\Exception\WrongInputException;
use AppBundle\Service\LxdApi\ContainerApi;
use AppBundle\Service\LxdApi\ContainerStateApi;
use AppBundle\Service\LxdApi\OperationApi;
use AppBundle\Service\Profile\ProfileManagerApi;
use AppBundle\Service\SSH\ScheduleSSH;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class ContainerWorker extends \Dtc\QueueBundle\Model\Worker
{
    protected $em;
    protected $api;
    protected $stateApi;
    protected $operationApi;
    protected $profileManagerApi;
    protected $sshApi;

    /**
     * ContainerWorker constructor.
     * @param EntityManager $em
     * @param ContainerApi $api
     * @param ContainerStateApi $stateApi
     * @param OperationApi $operationApi
     * @param ProfileManagerApi $profileManagerApi
     * @param ScheduleSSH $sshApi
     */
    public function __construct(EntityManagerInterface $em, ContainerApi $api, ContainerStateApi $stateApi, OperationApi $operationApi, ProfileManagerApi $profileManagerApi, ScheduleSSH $sshApi)
    {
        $this->em = $em;
        $this->api = $api;
        $this->stateApi = $stateApi;
        $this->operationApi = $operationApi;
        $this->profileManagerApi = $profileManagerApi;
        $this->sshApi = $sshApi;
    }


    public function getName()
    {
        return "container";
    }

    /**
     * @param Container $container
     * @throws WrongInputException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createContainer(Container $container)
    {
        $result = $this->api->create($container->getHost(), $container->getDataBody());

        if ($result->code != 202) {
            throw new WrongInputException($result->raw_body);
        }
        if ($result->body->metadata->status_code == 400) {
            throw new WrongInputException($result->raw_body);
        }

        $operationsResponse = $this->api->getOperationsLinkWithWait($container->getHost(), $result->body->metadata->id);

        if ($operationsResponse->body->metadata->status_code != 200) {
            echo "FAILED-UPDATE : " . $operationsResponse->body->metadata->err . "\n";
            $container->setError($operationsResponse->body->metadata->err);
            $this->em->flush($container);
            return;
        }

        $container->setState('created');

        $this->fetchInfos($container);
    }

    public function deleteContainer(Container $container)
    {

    }

    public function updateContainer(Container $container)
    {

    }

    /**
     * @param Container $container
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    private function fetchInfos(Container $container)
    {
        $containerResponse = $this->api->show($container->getHost(), $container->getName());
        $container->setExpandedConfig($containerResponse->body->metadata->expanded_config);
        $container->setExpandedDevices($containerResponse->body->metadata->expanded_devices);
        $container->setCreatedAt(new \DateTime($containerResponse->body->metadata->created_at));
        $container->setState(strtolower($containerResponse->body->metadata->status));
        $container->setArchitecture($containerResponse->body->metadata->architecture);
        $this->em->flush($container);
    }
}