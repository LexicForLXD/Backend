<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Service\LxdApi\ApiClient;
use AppBundle\Service\LxdApi\Endpoints\ContainerState as ContainerStateApi;

use AppBundle\Entity\Container;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;


class ContainerStateController extends Controller
{
    /**
     * Start, stop or restart a Contaner by ContainerID
     *
     * @param Request $request
     * @param int $containerId
     * @return Response
     *
     * @Route("/containers/{containerId}/state", name="update_container_state", methods={"PUT"})
     *
     *@SWG\Put(path="/containers/{containerId}/state",
     *tags={"containerStates"},
     * @SWG\Parameter(
     *  name="actionData",
     *  in="body",
     *  required=true,
     *  @SWG\Schema(
     *      @SWG\Property(
     *          property="action",
     *          type="string"
     *      ),
     *       @SWG\Property(
     *          property="timeout",
     *          type="integer"
     *      ),
     *       @SWG\Property(
     *          property="force",
     *          type="boolean"
     *      ),
     *       @SWG\Property(
     *          property="stateful",
     *          type="boolean"
     *      ),
     *  ),
     * ),
     * @SWG\Parameter(
     *  description="ID von Container",
     *  format="int64",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *  type="integer"
     * ),
     *
     *
     * @SWG\Response(
     *  response=200,
     *  description="Erfolgsmeldung für Container Status Update."
     * ),
     *)
     */
    public function updateStateAction(Request $request, $containerId)
    {
        $container = $this->getDoctrine()->getRepository(Container::class)->findOneByIdJoinedToHost($containerId);

        if (!$container) {
            throw $this->createNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        $client = new ApiClient($container->host);
        $containerApi = new ContainerStateApi($client);

        $data = [
            "action" => $request->get("action"),
            "timeout" => $request->get("timeout") ? : 30,
            "force" => $request->get("force") ? : false,
            "stateful" => $request->get("stateful") ? : false
        ];

        return $containerApi->update($container->name, $data);
    }


    /**
     * Get the current state of the Container by ContainerID
     *
     * @param int $containerId
     * @return Response
     *
     * @Route("/containers/{containerId}/state", name="show_container_state", methods={"GET"})
     *
     *@SWG\Get(path="/containers/{containerId}/state",
     *tags={"containerStates"},
     * @SWG\Parameter(
     *  description="ID von Container",
     *  format="int64",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *  type="integer"
     * ),
     *
     * @SWG\Response(
     *  response=200,
     *  description="Aktueller Status des Contianers"
     * ),
     *)
     */
    public function showCurrentStateAction($containerId)
    {
        $container = $this->getDoctrine()->getRepository(Container::class)->findOneByIdJoinedToHost($containerId);

        if (!$container) {
            throw $this->createNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        $client = new ApiClient($container->host);
        $containerApi = new ContainerStateApi($client);

        return $containerApi->actual($container->name);
    }

}