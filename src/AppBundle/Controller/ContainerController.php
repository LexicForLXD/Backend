<?php
namespace AppBundle\Controller;

use AppBundle\Exception\WrongInputException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\EntityManagerInterface;

use AppBundle\Service\LxdApi\ContainerApi;
use AppBundle\Service\LxdApi\OperationsRelayApi;

use AppBundle\Entity\Container;
use AppBundle\Entity\ContainerStatus;
use AppBundle\Entity\Host;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as OAS;



class ContainerController extends Controller
{
    /**
     * Get all saved Containers
     *
     * @Route("/containers", name="containers_all", methods={"GET"})
     *
     * @OAS\Get(path="/containers",
     *     tags={"containers"},
     *      @OAS\Response(
     *          response=200,
     *          description="List of all containers",
     *          @OAS\JsonContent(ref="#/components/schemas/container"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No containers found",
     *      ),
     * )
     */
    public function indexAction()
    {
        $containers = $this->getDoctrine()->getRepository(Container::class)->findAllJoinedToHost();



        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($containers, 'json');
        return new Response($response);
    }

    /**
     * Get all Containers from one host
     *
     * @Route("/hosts/{hostId}/containers", name="containers_from_host", methods={"GET"})
     *
     * @OAS\Get(path="/hosts/{hostId}/containers?fresh={fresh}",
     *  tags={"containers"},
     *  @OAS\Response(
     *      response=200,
     *      description="List of containers from one host",
     *      @OAS\JsonContent(ref="#/components/schemas/container"),
     *  ),
     *
     *  @OAS\Parameter(
     *      description="ID of the Host",
     *      in="path",
     *      name="hostId",
     *      required=true,
     *      @OAS\Schema(
     *          type="integer"
     *      ),
     *  ),
     *
     *  @OAS\Parameter(
     *      description="true = force collect new data and return them, false = return cached data from the database",
     *      in="query",
     *      name="fresh",
     *      @OAS\Schema(
     *          type="boolean"
     *      ),
     *  ),
     *)
     * @param Request $request
     * @param int $hostId
     * @return Response
     */
    public function listFromHostAction(Request $request, int $hostId, ContainerApi $api)
    {

        $fresh = $request->query->get('fresh');


        if ($fresh == 'true') {
            $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

            if (!$host) {
                throw $this->createNotFoundException(
                    'No host found for id ' . $hostId
                );
            }






            $result = $api->list($host);

            //TODO in DB aktualisieren

            return new Response($result->body);
        } else {
            $containers = $this->getDoctrine()->getRepository(Container::class)->findAllByHostJoinedToHost($hostId);

            if (!$containers) {
                throw $this->createNotFoundException(
                    'No containers found'
                );
            }
            $serializer = $this->get('jms_serializer');
            $response = $serializer->serialize($containers, 'json');
            return new Response($response);
        }



    }


    /**
     * Create a new Container on a specific Host
     *
     * @Route("/hosts/{hostId}/containers", name="containers_store", methods={"POST"})
     *
     * @OAS\Post(path="/hosts/{hostId}/containers",
     * tags={"containers"},
     * @OAS\Parameter(
     *  description="Parameters for the new Container",
     *  in="body",
     *  name="containerData",
     *  required=true,
     *  @OAS\Schema(
     *      @OAS\Property(
     *          property="action",
     *          type="string",
     *          enum={"image", "migration", "copy", "none"},
     *          default="none"
     *      ),
     *      @OAS\Property(
     *          property="name",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="architecture",
     *          type="string"
     *      ),
     *  ),
     * ),
     *
     * @OAS\Parameter(
     *  description="ID of the Host the container should be created on",
     *  in="path",
     *  name="hostId",
     *  required=true,
     *  @OAS\Schema(
     *     type="integer"
     *  ),
     * ),
     *
     * @OAS\Response(
     *  description="The Container was successfully created",
     *  response=201
     * ),
     *
     * @OAS\Response(
     *     description="The Host was not found",
     *     response=404
     * ),
     *
     * @OAS\Response(
     *     description="The input was wrong",
     *     response=400
     * )
     * )
     *
     * @param Request $request
     * @param int $hostId
     * @param EntityManagerInterface $em
     * @param OperationsRelayApi $relayApi
     * @param ContainerApi $api
     * @return Response
     * @throws WrongInputException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function storeAction(Request $request, int $hostId, EntityManagerInterface $em, OperationsRelayApi $relayApi, ContainerApi $api)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw $this->createNotFoundException(
                'No host found for id ' . $hostId
            );
        }

        $type = $request->query->get('type');

        switch ($type) {
            case 'image':
                $data = [
                    "name" => $request->get("name"),
                    "architecture" => $request->get("architecture") ? : 'x86_64',
                    "profiles" => $request->get("profiles") ? : array('default'),
                    "ephermeral" => $request->get("ephermeral") ? : false,
                    "config" => $request->get("config"),
                    "devices" => $request->get("devices"),
                    "source" => [
                        "type" => "image",
                        "mode" => "pull",
                        "server" => $request->get("imageServer") ? : 'https://images.linuxcontainers.org:8443',
                        "protocol" => $request->get("protocol") ? : 'lxd',
                        "alias" => $request->get("alias"),
                        "fingerprint" => $request->get("fingerprint")
                    ]
                ];

                $container = new Container();

                $container->setHost($host);
                $container->setIpv4($request->get("ipv4"));
                $container->setName($request->get("name"));
                $container->setSettings($data);
                // $container->setStatus($containerStatus);
                $container->setState('stopped');

                break;
            case 'migration':
                return new JsonResponse(["message" => "migration"]);
                break;
            case 'copy':
                //TODO make it copy something
                $data = [
                    "name" => $request->get("name"),
                    "architecture" => $request->get("architecture") ? : 'x86_64',
                    "profiles" => $request->get("profiles") ? : array('default'),
                    "ephermeral" => $request->get("ephermeral") ? : false,
                    "config" => $request->get("config"),
                    "devices" => $request->get("devices"),
                ];

                $container = new Container();
                $container->setHost($host);
                $container->setIpv4($request->get("ipv4"));
                $container->setName($request->get("name"));
                $container->setSettings($data);

                return new JsonResponse(["message" => "copy"]);
                break;
            default:
                return new JsonResponse(["message" => "none"]);
        }


        if($errorArray = $this->validation($container))
        {
            throw new WrongInputException($errorArray);
        }

        $result = $api->create($host, $data);

        $operationsResponse = $api->getOperationsLink($host, $result->body->operation);

        if($operationsResponse->code != 200){
            return new Response(json_encode($operationsResponse->body));
        }

        while($operationsResponse->body->metadata->status_code == 103){
            sleep(0.2);
            $operationsResponse = $api->getOperationsLink($host, $result->body->operation);
        }

        if($operationsResponse->body->metadata->status_code != 200){
            return new Response(json_encode($operationsResponse->body));
        }



        $em->persist($container);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($container, 'json');
        return new Response($response, Response::HTTP_CREATED);

    }


    /**
     * Get a Container by containerID
     *
     * @Route("/containers/{containerId}", name="containers_show", methods={"GET"})
     *
     * @OAS\Get(path="/containers/{containerId}",
     * tags={"containers"},
     * @OAS\Parameter(
     *  description="ID of the Container",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *   @OAS\Schema(
     *         type="integer"
     *   ),
     * ),
     *
     * @OAS\Response(
     *      response=200,
     *      description="Returns the informationen of a single Container",
     *      @OAS\JsonContent(ref="#/components/schemas/container"),
     * ),
     *)
     * @param Request $request
     * @param int $containerId
     * @param ContainerApi $api
     * @return Object|Response
     */
    public function showSingleAction(Request $request, int $containerId, ContainerApi $api)
    {
        $fresh = $request->query->get('fresh');

        $container = $this->getDoctrine()->getRepository(Container::class)->findOneByIdJoinedToHost($containerId);

        if (!$container) {
            throw $this->createNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        if ($fresh == 'true') {

            $result = $api->show($container->host, $container->name);

            //TODO in DB aktualisieren

            return new Response($result->body);
        }
        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($container, 'json');
        return new Response($response);

    }


    /**
     * Deletes a Container by containerID
     *
     * @Route("/containers/{containerId}", name="containers_delete", methods={"DELETE"})
     *
     *SWG\Delete(path="/containers/{containerId}",
     * tags={"containers"},
     * SWG\Parameter(
     *  description="ID des Containers",
     *  format="int64",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *  type="integer"
     * ),
     *
     * SWG\Response(
     *      response=200,
     *      description="show a single container"
     * ),
     *)
     * @param int $containerId
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function deleteAction(int $containerId, EntityManagerInterface $em)
    {
        $container = $this->getDoctrine()->getRepository(Container::class)->findOneByIdJoinedToHost($containerId);

        if (!$container) {
            throw $this->createNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        $containerApi = new ContainerApi();
        $response = $containerApi->remove($container->host, $container->name);

        if ($response->getStatusCode() == 202) {
            $em->remove($container);
            $em->flush();

            return $this->json([], 204);
        }

        return $this->json(['error' => 'Leider konnte der Container nicht gelöschtwerden.'], 500);
    }


    /**
     * Validates a Container Object and returns array with errors.
     * @param Container $object
     * @return array|bool
     */
    private function validation(Container $object)
    {
        $validator = $this->get('validator');
        $errors = $validator->validate($object);

        if (count($errors) > 0) {
            $errorArray = array();
            foreach ($errors as $error) {
                $errorArray[$error->getPropertyPath()] = $error->getMessage();
            }
            return $errorArray;
        }
        return false;
    }

}