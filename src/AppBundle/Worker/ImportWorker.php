<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 23.05.18
 * Time: 16:10
 */

namespace AppBundle\Worker;

use AppBundle\Entity\Image;
use AppBundle\Entity\Host;
use AppBundle\Service\LxdApi\ImageApi;
use AppBundle\Service\LxdApi\OperationApi;
use Doctrine\ORM\EntityManagerInterface;
use Dtc\QueueBundle\Model\Worker as BaseWorker;


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
                break;
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

            //TODO Import alias as well
        }
        $this->getCurrentJob()->setMessage($this->getCurrentJob()->getMessage() . " Number of imported images: ". $counter);

    }
}