<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\EntityManagerInterface;

use AppBundle\Service\LxdApi\Endpoints\Container as ContainerApi;

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
     *@Route("/hosts/{hostId}/containers", name="containers_from_host", methods={"GET"})
     *
     *@OAS\Get(path="/hosts/{hostId}/containers?fresh={fresh}",
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
     */
    public function listFromHostAction(Request $request, $hostId)
    {

        $fresh = $request->query->get('fresh');


        if ($fresh == 'true') {
            $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

            if (!$host) {
                throw $this->createNotFoundException(
                    'No host found for id ' . $hostId
                );
            }


            $containerApi = new ContainerApi();



            $containers = $containerApi->list($host);

            //TODO in DB aktualisieren

            return containers;
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
         *),
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
     * )
     *
     */
    public function storeAction(Request $request, $hostId, EntityManagerInterface $em)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw $this->createNotFoundException(
                'No host found for id ' . $hostId
            );
        }

        // $containerStatus = new ContainerStatus;
        // $containerStatus->setState('stopped');

        // $em->persist($containerStatus);
        // $em->flush();


        // $client = new ApiClient($host);
        // $containerApi = new ContainerApi($client);
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


                if($errorArray = $this->validation($container))
                {
                    return new JsonResponse(['errors' => $errorArray], 400);
                }

                break;
            case 'migration':
                return new JsonResponse(["message" => "migration"]);
                break;
            case 'copy':
                return new JsonResponse(["message" => "copy"]);
                break;
            default:
                return new JsonResponse(["message" => "none"]);
        }

        $containerApi = new \AppBundle\Service\LxdApi\Endpoints\ContainerApi();
        $response = $containerApi->create($host, $data);

        //TODO Mögliche Fehler abfangen async

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
     *@OAS\Get(path="/containers/{containerId}",
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
     *
     */
    public function showSingleAction(Request $request, $containerId)
    {
        $fresh = $request->query->get('fresh');

        $container = $this->getDoctrine()->getRepository(Container::class)->findOneByIdJoinedToHost($containerId);

        if (!$container) {
            throw $this->createNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        if ($fresh == 'true') {


            $containerApi = new ContainerApi();

            $container = $containerApi->show($container->host, $container->name);

            //TODO in DB aktualisieren

            return $container;
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
     *
     */
    public function deleteAction($containerId, EntityManagerInterface $em)
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




    private function validation($object)
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