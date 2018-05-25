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
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Dtc\QueueBundle\Entity\JobArchive;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as OAS;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ImportController extends BaseController
{

    /**
     * @Route("/sync/hosts", name="import_fetch", methods={"GET"})
     *
     * @OAS\Get(path="/sync/hosts",
     *     tags={"import"},
     *     @OAS\Response(
     *          response=202,
     *          description="all import jobs"
     *     ),
     * )
     * @param $hostId
     * @return Response
     * @throws ElementNotFoundException
     */
    public function indexAction()
    {
//        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);
//        if (!$host) {
//            throw new ElementNotFoundException(
//                'No host found for id ' . $hostId
//            );
//        }

        $jobs = $this->getDoctrine()->getRepository(JobArchive::class)->findBy(["workerName" => "import"]);

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($jobs, 'json');
        return new Response($response);
    }

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
     * @param Request $request
     * @param $hostId
     * @param ImportWorker $worker
     * @return JsonResponse
     * @throws ElementNotFoundException
     */
    public function importImages(Request $request, $hostId, ImportWorker $worker)
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
     * @param Request $request
     * @param $hostId
     * @param ImportWorker $worker
     * @return JsonResponse
     * @throws ElementNotFoundException
     */
    public function importContainers(Request $request, $hostId, ImportWorker $worker)
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
}