<?php
namespace AppBundle\Controller;

use AppBundle\Event\ContainerStateEvent;
use AppBundle\Exception\ElementNotFoundException;
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
use Swagger\Annotations as OAS;


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
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws ElementNotFoundException
     * @Route("/containers/{containerId}/state", name="update_container_state", methods={"PUT"})
     *
     * @OAS\Put(path="/containers/{containerId}/state",
     *  tags={"containerStates"},
     *  @OAS\Parameter(
     *      name="actionData",
     *      in="body",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="action",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="timeout",
     *              type="integer",
     *          ),
     *          @OAS\Property(
     *              property="force",
     *              type="boolean",
     *          ),
     *          @OAS\Property(
     *              property="stateful",
     *              type="boolean",
     *          ),
     *      ),
     *  ),
     *  @OAS\Parameter(
     *      description="ID von Container",
     *      in="path",
     *      name="containerId",
     *      required=true,
     *      @OAS\Schema(
     *         type="integer"
     *      ),
     *  ),
     *
     *
     *  @OAS\Response(
     *      response=200,
     *      description="Erfolgsmeldung für Container Status Update.",
     *  ),
     * )
     */
    public function updateStateAction(Request $request, $containerId, EntityManagerInterface $em, ContainerStateApi $api)
    {
        $dispatcher = $this->get('sb_event_queue');
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        $data = [
            "action" => $request->get("action"),
            "timeout" => $request->get("timeout") ? : 30,
            "force" => $request->get("force") ? : false,
            "stateful" => $request->get("stateful") ? : false
        ];

        $result = $api->update($container->getHost(), $container, $data);

        switch($request->get("action")){
            case "start":
                $container->setState("starting");
                break;
            case "stop":
                if($container->isEphemeral())
                {
                    $em->remove($container);
                    $em->flush();
                    return new JsonResponse(['message' => 'container delete because ephemeral']);
                }
                $container->setState("stopping");
                break;
            case "restart":
                $container->setState("restarting");
                break;
            default:
                throw new WrongInputException('please use one of the following actions: state, stop, restart');
                break;
        }

        $dispatcher->on(ContainerStateEvent::class, date('Y-m-d H:i:s'), $result->body->metadata->id, $container->getHost(), $container->getId());


        //TODO mögliche Fehler abfangen

        $em->flush();

        return new JsonResponse(['message' => 'update is ongoing']);
    }


    /**
     * Get the current state of the Container by ContainerID
     *
     * @param int $containerId
     * @param ContainerStateApi $api
     * @param EntityManagerInterface $em
     * @return Response
     *
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws ElementNotFoundException
     * @Route("/containers/{containerId}/state", name="show_container_state", methods={"GET"})
     *
     * @OAS\Get(path="/containers/{containerId}/state",
     *  tags={"containerStates"},
     *  @OAS\Parameter(
     *      description="ID von Container",
     *      in="path",
     *      name="containerId",
     *      required=true,
     *      @OAS\Schema(
     *         type="integer"
     *      ),
     *  ),
     *
     *  @OAS\Response(
     *      response=200,
     *      description="Aktueller Status des Contianers"
     *  ),
     * )
     */
    public function showCurrentStateAction(int $containerId, ContainerStateApi $api, EntityManagerInterface $em)
    {
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        $result = $api->actual($container->getHost(), $container);

        $container->setNetwork($result->body->metadata->network);
        $container->setState(mb_strtolower($result->body->metadata->status));

        $em->flush($container);


        return new JsonResponse([
            'state' => $result->body->metadata->status,
            'stateCode' => $result->body->metadata->status_code
        ]);
    }

}