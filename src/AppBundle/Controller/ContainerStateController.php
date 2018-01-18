<?php
namespace AppBundle\Controller;

use AppBundle\Exception\WrongInputException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Service\LxdApi\ContainerStateApi;

use AppBundle\Entity\Container;
use AppBundle\Entity\ContainerStatus;

use Doctrine\ORM\EntityManagerInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;


class ContainerStateController extends Controller
{
    /**
     * Start, stop or restart a Contaner by ContainerID
     *
     * @param Request $request
     * @param int $containerId
     * @param EntityManagerInterface $em
     * @param ContainerStateApi $api
     * @return JsonResponse
     *
     * @throws WrongInputException
     * @Route("/containers/{containerId}/state", name="update_container_state", methods={"PUT"})
     *
     *SWG\Put(path="/containers/{containerId}/state",
     *tags={"containerStates"},
     * SWG\Parameter(
     *  name="actionData",
     *  in="body",
     *  required=true,
     *  SWG\Schema(
     *      SWG\Property(
     *          property="action",
     *          type="string"
     *      ),
     *       SWG\Property(
     *          property="timeout",
     *          type="integer"
     *      ),
     *       SWG\Property(
     *          property="force",
     *          type="boolean"
     *      ),
     *       SWG\Property(
     *          property="stateful",
     *          type="boolean"
     *      ),
     *  ),
     * ),
     * SWG\Parameter(
     *  description="ID von Container",
     *  format="int64",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *  type="integer"
     * ),
     *
     *
     * SWG\Response(
     *  response=200,
     *  description="Erfolgsmeldung für Container Status Update."
     * ),
     *)
     */
    public function updateStateAction(Request $request, $containerId, EntityManagerInterface $em, ContainerStateApi $api)
    {
        $container = $this->getDoctrine()->getRepository(Container::class)->findOneById($containerId);

        if (!$container) {
            throw $this->createNotFoundException(
                'No container found for id ' . $containerId
            );
        }


        switch($request->get("action")){
            case "start":
                $container->setState("running");
                break;
            case "stop":
                $container->setState("stopped");
                break;
            case "restart":
                $container->setState("running");
                break;
            default:
                throw new WrongInputException('please use one of the following actions: state, stop, restart');
                break;
        }


        $data = [
            "action" => $request->get("action"),
            "timeout" => $request->get("timeout") ? : 30,
            "force" => $request->get("force") ? : false,
            "stateful" => $request->get("stateful") ? : false
        ];

        $result = $api->update($container->getHost(), $container, $data);

        if($result->code != 200){
            return new JsonResponse(["error" => $result->body]);
        }

        //TODO mögliche Fehler abfangen

        $em->flush();

        return new JsonResponse(['message' => 'success']);
    }


    /**
     * Get the current state of the Container by ContainerID
     *
     * @param int $containerId
     * @return Response
     *
     * @Route("/containers/{containerId}/state", name="show_container_state", methods={"GET"})
     *
     *SWG\Get(path="/containers/{containerId}/state",
     *tags={"containerStates"},
     * SWG\Parameter(
     *  description="ID von Container",
     *  format="int64",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *  type="integer"
     * ),
     *
     * SWG\Response(
     *  response=200,
     *  description="Aktueller Status des Contianers"
     * ),
     *)
     */
    public function showCurrentStateAction(int $containerId, ContainerStateApi $api)
    {
        $container = $this->getDoctrine()->getRepository(Container::class)->findOneById($containerId);

        if (!$container) {
            throw $this->createNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        $result = $api->actual($container->getHost(), $container);


        return new JsonResponse([
            'state' => $result->body->metadata->status,
            'stateCode' => $result->body->metadata->status_code
        ]);
    }

}