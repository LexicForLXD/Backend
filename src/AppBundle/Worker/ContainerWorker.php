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

class ContainerWorker extends Worker
{
    protected $em;
    protected $api;
    protected $stateApi;
    protected $operationApi;
    protected $profileManagerApi;
    protected $sshApi;
    protected $hostApi;

    /**
     * ContainerWorker constructor.
     * @param EntityManagerInterface $em
     * @param ContainerApi $api
     * @param ContainerStateApi $stateApi
     * @param OperationApi $operationApi
     * @param ProfileManagerApi $profileManagerApi
     * @param ScheduleSSH $sshApi
     * @param HostApi $hostApi
     */
    public function __construct(EntityManagerInterface $em, ContainerApi $api, ContainerStateApi $stateApi, OperationApi $operationApi, ProfileManagerApi $profileManagerApi, ScheduleSSH $sshApi, HostApi $hostApi)
    {
        $this->em = $em;
        $this->api = $api;
        $this->hostApi = $hostApi;
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
     * @param int $containerId
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createContainer($containerId)
    {
        $container = $this->em->getRepository(Container::class)->find($containerId);
        $result = $this->api->create($container->getHost(), $container->getBody());

        if ($this->checkForErrors($container, $result)) {
            return;
        }

        $operationsResponse = $this->operationApi->getOperationsLinkWithWait($container->getHost(), $result->body->metadata->id);

        if ($this->checkForErrors($container, $operationsResponse)) {
            return;
        }

//        if ($operationsResponse->body->metadata->status_code != 200) {
//            $container->setError($operationsResponse->body->metadata->err);
//            $this->em->flush($container);
//            return;
//        }

        $container->setState('created');
        $this->em->flush($container);

        $this->fetchInfos($container);
    }

    /**
     * @param int $containerId
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function deleteContainer(int $containerId)
    {
        $container = $this->em->getRepository(Container::class)->find($containerId);

        $result = $this->api->remove($container->getHost(), $container);

        if ($this->checkForErrors($container, $result)) {
            return;
        }

        $operationsResponse = $this->operationApi->getOperationsLinkWithWait($container->getHost(), $result->body->metadata->id);

        $this->checkForErrors($container, $operationsResponse);

        foreach ($container->getBackupSchedules() as $schedule) {
            $this->sshApi->deleteAnacronFile($schedule);
            $schedule->removeContainer($container);
            $this->sshApi->sendAnacronFile($schedule);
            $this->sshApi->makeFileExecuteable($schedule);
        }

        foreach ($container->getProfiles() as $profile) {
            $this->profileManagerApi->disableProfileForContainer($profile, $container);
        }

        $this->em->remove($container);
        $this->em->flush();
    }

    /**
     * @param int $containerId
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function updateContainer($containerId)
    {
        $container = $this->em->getRepository(Container::class)->find($containerId);

        $result = $this->api->update($container->getHost(), $container, $container->getDataBody());

        $this->checkForErrors($container, $result);

        $operationsResponse = $this->operationApi->getOperationsLinkWithWait($container->getHost(), $result->body->metadata->id);

        $this->checkForErrors($container, $operationsResponse);

//        if ($operationsResponse->code != 200) {
//            if ($operationsResponse->body->metadata->status_code != 200) {
//                $container->setError($operationsResponse->body->metadata->err);
//                $this->em->flush($container);
//                return;
//            }
//        }

        $this->fetchInfos($container);
    }

    /**
     * @param int $containerId
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function renameContainer($containerId)
    {
        $container = $this->em->getRepository(Container::class)->find($containerId);

        $result = $this->api->migrate($container->getHost(), $container, $container->getDataBody());

        $this->checkForErrors($container, $result);

        $operationsResponse = $this->operationApi->getOperationsLinkWithWait($container->getHost(), $result->body->metadata->id);

        $this->checkForErrors($container, $operationsResponse);

//        if ($operationsResponse->code != 200) {
//            if ($operationsResponse->body->metadata->status_code != 200) {
//                $container->setError($operationsResponse->body->metadata->err);
//                $this->em->flush($container);
//                return;
//            }
//        }

        if ($operationsResponse->code == 409) {
            $container->setError("The name is already taken.");
            $this->em->flush($container);
        }


        $this->fetchInfos($container);
    }


    /**
     * @param $oldContainerId
     * @param $containerId
     * @param $live
     * @param $containerOnly
     * @param $profiles
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function migrateContainer($oldContainerId, $containerId, $live, $containerOnly, $profiles)
    {
        $oldContainer = $this->em->getRepository(Container::class)->find($oldContainerId);
        $container = $this->em->getRepository(Container::class)->find($containerId);

        foreach ($profiles as $profile) {
            $this->profileManagerApi->enableProfileForContainer($profile, $container);
        }

       $pushResult = $this->api->migrate($oldContainer->getHost(), $oldContainer, [
           "name" => $oldContainer->getName(),
           "migration" => true,
           "live" => $live
       ]);

        $container->setSource([
            "type" => "migration",
            "mode" => "pull",
            "operation" => $this->operationApi->buildUri($oldContainer->getHost(), 'operations/' . $pushResult->body->metadata->id),
            "certificate" => $this->hostApi->getCertificate($oldContainer->getHost()),
            "base-image" => $oldContainer->getImage()->getFingerprint(),
            "container_only" => $containerOnly,
            "live" => $live,
            "secrets" => $pushResult->body->metadata->metadata
        ]);


        $result = $this->api->create($container->getHost(), $container->getBody());

        if ($this->checkForErrors($container, $result)) {
            return;
        }

        $operationsResponse = $this->operationApi->getOperationsLinkWithWait($container->getHost(), $result->body->metadata->id);

        if ($this->checkForErrors($container, $operationsResponse)) {
            return;
        }

        $container->setState('created');
        $this->em->flush($container);

        $this->fetchInfos($container);
    }

    /**
     * @param Container $container
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    private function fetchInfos(Container $container)
    {
        $containerResponse = $this->api->show($container->getHost(), $container->getName());
        $container->setName($containerResponse->body->metadata->name);
        $container->setEphemeral($containerResponse->body->metadata->ephemeral);
        $container->setExpandedConfig($containerResponse->body->metadata->expanded_config);
        $container->setExpandedDevices($containerResponse->body->metadata->expanded_devices);
        $container->setCreatedAt(new \DateTime($containerResponse->body->metadata->created_at));
        $container->setState(strtolower($containerResponse->body->metadata->status));
        $container->setArchitecture($containerResponse->body->metadata->architecture);
        $this->em->flush($container);
    }

    private function checkForErrors(Container $container, Response $response)
    {
        if ($response->code != 202) {
            if ($response->code != 200) {
//                if ($response->body->metadata->status_code != 200) {
//                    $container->setError($response->body->metadata->err);
//
//                }
                return true;

            }
            $container->setError($response->raw_body);
        }
        if (!$response->body->metadata) {
            $container->setError($response->raw_body);
        }
            $this->em->flush($container);
            return false;
        }
    }