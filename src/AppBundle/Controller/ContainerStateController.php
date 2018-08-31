<?php
namespace AppBundle\Controller;

use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Worker\ContainerStateWorker;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Service\LxdApi\ContainerStateApi;
use AppBundle\Service\LxdApi\OperationApi;

use AppBundle\Entity\Container;

use Doctrine\ORM\EntityManagerInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as OAS;


class ContainerStateController extends BaseController
{
    /**
     * Start, stop or restart a Contaner by ContainerID
     *
     * @param Request $request
     * @param int $containerId
     * @param EntityManagerInterface $em
     * @param ContainerStateWorker $worker
     * @return JsonResponse
     *
     * @throws ElementNotFoundException
     * @throws WrongInputException
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
    public function updateStateAction(Request $request, $containerId, EntityManagerInterface $em, ContainerStateApi $stateApi, OperationApi $opApi)
    {
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



        switch ($request->get("action")) {
            case "start":
                $container->setState("starting");
                break;
            case "stop":
                $container->setState("stopping");
                break;
            case "restart":
                $container->setState("restarting");
                break;
            case "freeze":
                $container->setState("freezing");
                break;
            case "unfreeze":
                $container->setState("unfreezing");
                break;
            default:
                throw new WrongInputException('please use one of the following actions: start, stop, restart, freeze and unfreeze');
                break;
        }
        $em->flush($container);


        $stateOp = $stateApi->update($container, $data);
        $this->checkForErrors($stateOp);

        $stateOpWait = $opApi->getOperationsLinkWithWait($container->getHost(), $stateOp->body->metadata->id);
        $this->checkForErrorsInOps($stateOpWait);



        if ($container->isEphemeral() && $data["action"] === "stop") {
            $em->remove($container);
            $em->flush();
            return new JsonResponse(['message' => 'container delted'], Response::HTTP_OK);
        }

        $stateResult = $stateApi->actual($container);
        $container->setState(mb_strtolower($stateResult->body->metadata->status));
        $container->setNetwork($stateResult->body->metadata->network);
        $em->flush($container);
        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($container, 'json');
        return new Response($response);
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

        $result = $api->actual($container);

        $container->setNetwork($result->body->metadata->network);
        $container->setState(mb_strtolower($result->body->metadata->status));

        $em->flush($container);


        return new JsonResponse([
            'state' => mb_strtolower($result->body->metadata->status),
            'stateCode' => $result->body->metadata->status_code
        ]);
    }

}