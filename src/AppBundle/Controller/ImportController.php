<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 23.05.18
 * Time: 16:07
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Host;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Worker\ImportWorker;
use Dtc\QueueBundle\Entity\Job;
use Dtc\QueueBundle\Entity\JobArchive;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as OAS;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ImportController extends BaseController
{

    

    /**
     * Import all images from one host.
     *
     * @Route("/sync/hosts/{hostId}/images", name="import_images", methods={"POST"})
     *
     * @OAS\Post(path="/sync/hosts/{hostId}/images",
     *     tags={"import"},
     *     @OAS\Response(
     *          response=202,
     *          description="info if task was started"
     *     ),
     * )
     *
     * @param $hostId
     * @param ImportWorker $worker
     * @return JsonResponse
     * @throws ElementNotFoundException
     */
    public function importImages($hostId, ImportWorker $worker)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No host found for id ' . $hostId
            );
        }

        $worker->later()->importImages($host->getId());

        return new JsonResponse(['message' => 'import started'], 202);
    }

    /**
     * Import all containers from one host.
     *
     * @Route("/sync/hosts/{hostId}/containers", name="import_containers", methods={"POST"})
     *
     * @OAS\Post(path="/sync/hosts/{hostId}/containers",
     *     tags={"import"},
     *     @OAS\Response(
     *          response=202,
     *          description="info if task was started"
     *     ),
     * )
     *
     * @param $hostId
     * @param ImportWorker $worker
     * @return JsonResponse
     * @throws ElementNotFoundException
     */
    public function importContainers($hostId, ImportWorker $worker)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No host found for id ' . $hostId
            );
        }

        $worker->later()->importContainers($host->getId());

        return new JsonResponse(['message' => 'import started'], 202);
    }


    /**
     * Import all storage pools from one host.
     *
     * @Route("/sync/hosts/{hostId}/storage-pools", name="import_storage_pools", methods={"POST"})
     *
     * @OAS\Post(path="/sync/hosts/{hostId}/storage-pools",
     *     tags={"import"},
     *     @OAS\Response(
     *          response=202,
     *          description="info if task was started"
     *     ),
     * )
     *
     * @param $hostId
     * @param ImportWorker $worker
     * @return JsonResponse
     * @throws ElementNotFoundException
     */
    public function importStoragePools($hostId, ImportWorker $worker)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No host found for id ' . $hostId
            );
        }

        $worker->later()->importStoragePools($host->getId());

        return new JsonResponse(['message' => 'import started'], 202);
    }


    /**
     * Import all containers and images from one host.
     *
     * @Route("/sync/hosts/{hostId}", name="import_all", methods={"POST"})
     *
     * @OAS\Post(path="/sync/hosts/{hostId}",
     *     tags={"import"},
     *     @OAS\Response(
     *          response=202,
     *          description="info if task was started"
     *     ),
     * )
     *
     * @param $hostId
     * @param ImportWorker $worker
     * @return JsonResponse
     * @throws ElementNotFoundException
     */
    public function importAll($hostId, ImportWorker $worker)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No host found for id ' . $hostId
            );
        }

        $worker->later()->importAll($host->getId());

        return new JsonResponse(['message' => 'import started'], 200);
    }
}