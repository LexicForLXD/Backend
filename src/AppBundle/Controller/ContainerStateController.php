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
     * Startet stopped oder restartet einen Container
     *
     * @param Request $request
     * @param int $containerId
     * @return Response
     *
     * @Route("/containers/{containerId}/state", name="update_container_state", methods={"PUT"})
     *
     * @SWG\Parameter(
     *  description="ID von Container",
     *  format="int64",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *  type="integer"
     * )
     *
     * @SWG\Parameter(
     *  description="Auswahl ob 'start', 'stop', 'restart', 'freeze', 'unfreeze'",
     *  format="int64",
     *  in="body",
     *  name="action",
     *  required=true,
     *  type="string"
     * )
     *
     * @SWG\Parameter(
     *  description="Timeout nachdem die Aktion als gescheitert gilt. Default 30 (Sekunden)",
     *  format="int64",
     *  in="body",
     *  name="timeout",
     *  required=false,
     *  type="integer"
     * )
     *
     * @SWG\Parameter(
     *  description="Force ob bei stop und restart der Befehl erzwungen wird",
     *  format="int64",
     *  in="body",
     *  name="force",
     *  required=false,
     *  type="boolean"
     * )
     *
     * @SWG\Parameter(
     *  description="Stateful, ob der aktuelle Zustand gespeichert wird (seamless restart)",
     *  format="int64",
     *  in="body",
     *  name="force",
     *  required=false,
     *  type="boolean"
     * )
     *
     * @SWG\Response(
     *  response=200,
     *  description="Erfolgsmeldung"
     * )
     *
     * @SWG\Tag(name="containers")
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
     * Zeigt den aktuellen Zustand eines Containers an.
     *
     * @param int $containerId
     * @return Response
     *
     * @Route("/containers/{containerId}/state", name="show_container_state", methods={"GET"})
     *
     * @SWG\Parameter(
     *  description="ID von Container",
     *  format="int64",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *  type="integer"
     * )
     *
     * @SWG\Response(
     *  response=200,
     *  description="Erfolgsmeldung"
     * )
     *
     * @SWG\Tag(name="containers")
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