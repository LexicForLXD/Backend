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
use AppBundle\Service\LxdApi\ContainerApi;
use AppBundle\Service\LxdApi\ImageApi;
use AppBundle\Service\LxdApi\OperationApi;
use Doctrine\ORM\EntityManagerInterface;
use Dtc\QueueBundle\Model\Worker as BaseWorker;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class ImportWorker extends BaseWorker
{

    protected $em;
    protected $imageApi;
    protected $containerApi;
    protected $operationApi;
    protected $validator;

    /**
     * ImageWorker constructor.
     * @param EntityManagerInterface $em
     * @param ImageApi $imageApi
     * @param ContainerApi $containerApi
     * @param OperationApi $operationApi
     * @param ValidatorInterface $validator
     */
    public function __construct(EntityManagerInterface $em, ImageApi $imageApi, ContainerApi $containerApi, OperationApi $operationApi, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->imageApi = $imageApi;
        $this->containerApi = $containerApi;
        $this->operationApi = $operationApi;
        $this->validator = $validator;
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
        $imageList = $imageListResult->body->metadata;

        foreach ($imageList as $item) {
            $imageResult = $this->imageApi->getImageByFingerprint($host, substr($item, 12));

            $image = $this->em->getRepository(Image::class)->findOneBy(["host" => $host->getId(), "fingerprint" => $imageResult->body->metadata->fingerprint]);

            if ($image) {
                break 1;
            }

            $image = new Image();
            $image->setFingerprint($imageResult->body->metadata->fingerprint);
            $image->setProperties((array) $imageResult->body->metadata->properties);
            $image->setPublic($imageResult->body->metadata->public);
            $image->setFilename($imageResult->body->metadata->filename);
            $image->setFinished(true);
            $image->setArchitecture($imageResult->body->metadata->architecture);
            $image->setSize($imageResult->body->metadata->size);
            $image->setHost($host);
            if(!$this->validation($image))
            {
                $this->em->persist($image);
                $this->em->flush();
                $counter++;
            }

            foreach ($imageResult->body->metadata->aliases as $alias)
            {
                $dbAlias = new ImageAlias();
                $dbAlias->setName($alias->name);
                $dbAlias->setDescription($alias->description);
                $dbAlias->setImage($image);
                if(!$this->validation($dbAlias))
                {
                    $this->em->persist($dbAlias);
                    $this->em->flush();
                }

            }
        }
        $this->getCurrentJob()->setMessage($this->getCurrentJob()->getMessage() . " Number of imported images: ". $counter);

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

            if ($container) {
                break;
            }

            $container = new Container();
            $container->setArchitecture($containerResult->body->metadata->architecture);
            $container->setConfig((array) $containerResult->body->metadata->config);
            $container->setDevices((array) $containerResult->body->metadata->devices);
            $container->setEphemeral($containerResult->body->metadata->ephemeral);
//            $container->setProfiles($containerResult->body->metadata->architecture);
            $container->setCreatedAt(new \DateTime($containerResult->body->metadata->created_at));
            $container->setExpandedConfig((array) $containerResult->body->metadata->expanded_config);
            $container->setExpandedDevices((array) $containerResult->body->metadata->expanded_devices);
            $container->setName($containerResult->body->metadata->name);
            $container->setState(mb_strtolower($containerResult->body->metadata->status));
            $container->setHost($host);
            if(!$this->validation($container))
            {
                $this->em->persist($container);
                $this->em->flush();
                $counter++;
            }

        }

        $this->getCurrentJob()->setMessage($this->getCurrentJob()->getMessage() . " Number of imported containers: ". $counter);
    }



    /**
     * Validates a Object and returns true if error occurs
     * @param  $object
     * @return bool
     */
    private function validation($object)
    {
        $errors = $this->validator->validate($object);

        if (count($errors) > 0) {
            $errorArray = array();
            foreach ($errors as $error) {
                $errorArray[$error->getPropertyPath()] = $error->getMessage();
            }
            $this->getCurrentJob()->setMessage($this->getCurrentJob()->getMessage() . serialize($errorArray));
            return true;
        }
        return false;
    }

}