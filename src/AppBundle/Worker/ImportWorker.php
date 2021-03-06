<?php

/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 23.05.18
 * Time: 16:10
 */

namespace AppBundle\Worker;

use AppBundle\Entity\Container;
use AppBundle\Entity\Image;
use AppBundle\Entity\Host;
use AppBundle\Entity\ImageAlias;
use AppBundle\Entity\StoragePool;
use AppBundle\Entity\Profile;
use AppBundle\Service\LxdApi\ContainerApi;
use AppBundle\Service\LxdApi\StorageApi;
use AppBundle\Service\LxdApi\ImageApi;
use AppBundle\Service\LxdApi\ProfileApi;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use AppBundle\Service\LxdApi\OperationApi;
use Doctrine\ORM\EntityManagerInterface;


class ImportWorker extends BaseWorker
{

    protected $imageApi;
    protected $containerApi;
    protected $storageApi;
    protected $profileApi;

    /**
     * ImageWorker constructor.
     * @param EntityManagerInterface $em
     * @param ImageApi $imageApi
     * @param ContainerApi $containerApi
     * @param OperationApi $operationApi
     * @param ValidatorInterface $validator
     */
    public function __construct(EntityManagerInterface $em, ImageApi $imageApi, ContainerApi $containerApi, StorageApi $storageApi, OperationApi $operationApi, ProfileApi $profileApi, ValidatorInterface $validator)
    {
        parent::__construct($em, $operationApi, $validator);

        $this->imageApi = $imageApi;
        $this->containerApi = $containerApi;
        $this->storageApi = $storageApi;
        $this->profileApi = $profileApi;
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
        $imageListResult = $this->imageApi->listImages($host);
        $counter = 0;
        $counterSkipped = 0;
        $imageList = $imageListResult->body->metadata;
        foreach ($imageList as $item) {
            $imageResult = $this->imageApi->getImageByFingerprint($host, substr($item, 12));

            $image = $this->em->getRepository(Image::class)->findOneBy(["host" => $host->getId(), "fingerprint" => $imageResult->body->metadata->fingerprint]);

            if ($image) {
                $counterSkipped++;
            } else {
                $image = new Image();
                $image->setFingerprint($imageResult->body->metadata->fingerprint);
                $image->setProperties($imageResult->body->metadata->properties);
                $image->setPublic($imageResult->body->metadata->public);
                $image->setFilename($imageResult->body->metadata->filename);
                $image->setFinished(true);
                $image->setArchitecture($imageResult->body->metadata->architecture);
                $image->setSize($imageResult->body->metadata->size);
                $image->setHost($host);
                if (!$this->validation($image)) {
                    $this->em->persist($image);
                    $this->em->flush();
                    $counter++;
                }

                foreach ($imageResult->body->metadata->aliases as $alias) {
                    $dbAlias = new ImageAlias();
                    $dbAlias->setName($alias->name);
                    $dbAlias->setDescription($alias->description);
                    $dbAlias->setImage($image);
                    if (!$this->validation($dbAlias)) {
                        $this->em->persist($dbAlias);
                        $this->em->flush();
                    }

                }
            }

        }
        $this->addMessage("Number of imported images: " . $counter);
    }


    /**
     * @param int $hostId
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function importContainers(int $hostId)
    {
        $host = $this->em->getRepository(Host::class)->find($hostId);
        $counter = 0;
        $containerListResult = $this->containerApi->list($host);
        $containerList = $containerListResult->body->metadata;

        foreach ($containerList as $item) {
            $containerResult = $this->containerApi->show($host, substr($item, 16));

            $container = $this->em->getRepository(Container::class)->findOneBy(["host" => $host->getId(), "name" => $containerResult->body->metadata->name]);

            if (!$container) {
                $container = new Container();
                $container->setArchitecture($containerResult->body->metadata->architecture);
                $container->setConfig($containerResult->body->metadata->config);
                $container->setDevices($containerResult->body->metadata->devices);
                $container->setEphemeral($containerResult->body->metadata->ephemeral);
                $container->setCreatedAt(new \DateTime($containerResult->body->metadata->created_at));
                $container->setExpandedConfig($containerResult->body->metadata->expanded_config);
                $container->setExpandedDevices($containerResult->body->metadata->expanded_devices);
                $container->setName($containerResult->body->metadata->name);
                $container->setState(mb_strtolower($containerResult->body->metadata->status));
                $container->setHost($host);

                if ($config = (array)$containerResult->body->metadata->config) {
                    $image = $this->em->getRepository(Image::class)->findOneBy(["host" => $host->getId(), "fingerprint" => $config["volatile.base_image"]]);
                    if (!$image) {
                        $this->addMessage("base image not found for container " . $container->getName());
                    } else {
                        $container->setImage($image);
                    }
                }

                if ($root = (array)$containerResult->body->metadata->expanded_devices->root) {
                    $storagePool = $this->em->getRepository(StoragePool::class)->findOneBy(["name" => $root["pool"], "host" => $host->getId()]);
                    if (!$storagePool) {
                        $this->addMessage("Storage-pool " . $root["pool"] . " was not found");
                    } else {
                        $container->setStoragePool($storagePool);
                    }
                }


                if ($profiles = (array)$containerResult->body->metadata->profiles) {
                    $profiles = $this->em->getRepository(Profile::class)->findBy(["name" => $profiles]);
                    if (!$profiles) {
                        $this->addMessage("no profiles for container " . $container->getName() . " found.");
                    } else {
                        foreach ($profiles as $profile) {
                            $container->addProfile($profile);
                        }
                    }

                }

                if (!$this->validation($container)) {
                    $this->em->persist($container);
                    $this->em->flush();
                    $counter++;
                }
            }
        }

        $this->addMessage(" Number of imported containers: " . $counter);
    }


    /**
     * @param int $hostId
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function importStoragePools(int $hostId)
    {
        $host = $this->em->getRepository(Host::class)->find($hostId);
        $counter = 0;
        $storagePoolListResult = $this->storageApi->list($host);
        $storagePoolList = $storagePoolListResult->body->metadata;

        foreach ($storagePoolList as $item) {
            $storagePoolResult = $this->storageApi->show($host, substr($item, 19));

            $storagePool = $this->em->getRepository(StoragePool::class)->findOneBy(["host" => $host->getId(), "name" => $storagePoolResult->body->metadata->name]);

            if (!$storagePool) {
                $storagePool = new StoragePool();
                $storagePool->setName($storagePoolResult->body->metadata->name);
                $storagePool->setConfig($storagePoolResult->body->metadata->config);
                $storagePool->setDriver($storagePoolResult->body->metadata->driver);
                $storagePool->setHost($host);

                if (!$this->validation($storagePool)) {
                    $this->em->persist($storagePool);
                    $this->em->flush();
                    $counter++;
                }
            }

        }
        $this->addMessage(" Number of imported storagePools: " . $counter);
    }


    /**
     * @param int $hostId
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function importProfiles(int $hostId)
    {
        $host = $this->em->getRepository(Host::class)->find($hostId);
        $counter = 0;
        $profileListResult = $this->profileApi->list($host);
        $profileList = $profileListResult->body->metadata;

        foreach ($profileList as $item) {
            $profileResult = $this->profileApi->show($host, substr($item, 14));

            $profile = $this->em->getRepository(Profile::class)->findOneBy(["name" => $profileResult->body->metadata->name]);

            if (!$profile) {
                $profile = new Profile();
                $profile->setName($profileResult->body->metadata->name);
                $profile->setDescription($profileResult->body->metadata->description);
                $profile->setConfig($profileResult->body->metadata->config);
                $profile->setDevices($profileResult->body->metadata->devices);
                $profile->addHost($host);

                if (!$this->validation($profile)) {
                    $this->em->persist($profile);
                    $this->em->flush();
                    $counter++;
                }
            }
        }
        $this->addMessage(" Number of imported profiles: " . $counter);
    }



    /**
     * Import all Entities
     *
     * @param int $hostId
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function importAll(int $hostId)
    {
        $this->importImages($hostId);
        $this->importStoragePools($hostId);
        $this->importProfiles($hostId);
        $this->importContainers($hostId);
    }
}
