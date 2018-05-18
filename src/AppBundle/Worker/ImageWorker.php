<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 10.05.18
 * Time: 22:23
 */

namespace AppBundle\Worker;


use AppBundle\Entity\Image;
use AppBundle\Service\LxdApi\ImageApi;
use AppBundle\Service\LxdApi\OperationApi;
use Doctrine\ORM\EntityManagerInterface;
use Dtc\QueueBundle\Model\Worker as BaseWorker;

class ImageWorker extends BaseWorker
{
    protected $em;
    protected $api;
    protected $operationApi;

    /**
     * ImageWorker constructor.
     * @param EntityManagerInterface $em
     * @param ImageApi $api
     * @param OperationApi $operationApi
     */
    public function __construct(EntityManagerInterface $em, ImageApi $api, OperationApi $operationApi)
    {
        $this->em = $em;
        $this->api = $api;
        $this->operationApi = $operationApi;
    }

    /**
     * @param int $imageId
     * @param $body
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createImage($imageId, $body)
    {
        $image = $this->getDoctrine()->getRepository(Image::class)->find($imageId);
        $result = $this->api->createImage($image->getHost(), $body);

        if ($result->code != 202) {
            $image->setError($result->body->error);
        }
        if ($result->body->metadata->status_code == 400) {
            $image->setError($result->body->error);
        }

        $operationsResponse = $this->operationApi->getOperationsLinkWithWait($image->getHost(), $result->body->metadata->id);

        if ($operationsResponse->body->metadata->status_code != 200) {
            $image->setError($operationsResponse->body->metadata->err);
            $this->em->persist($image);
            $this->em->flush($image);
            return;
        }

        $image->setFingerprint($operationsResponse->body->metadata->metadata->fingerprint);

        //Parse architecture
        $result = $this->api->getImageByFingerprint($image->getHost(), $image->getFingerprint());
        $image->setArchitecture($result->body->metadata->architecture);
        $image->setSize($operationsResponse->body->metadata->metadata->size);
        $image->setFinished(true);

        $this->em->persist($image);
        $this->em->flush($image);
    }

    public function getName()
    {
        return 'image';
    }
}