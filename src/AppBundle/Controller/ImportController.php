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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as OAS;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


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
}