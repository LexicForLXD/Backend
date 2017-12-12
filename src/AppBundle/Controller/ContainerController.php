<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\EntityManagerInterface;

use AppBundle\Service\LxdApi\ApiClient;
use AppBundle\Service\LxdApi\Endpoints\Container as ContainerApi;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;



class ContainerController extends Controller
{
    /**
     * Get all saved Containers
     *
     * @Route("/containers", name="containers_all", methods={"GET"})
     *
     * @SWG\Get(path="/containers",
     *     tags={"containers"},
     *      @SWG\Response(
     *          response=200,
     *          description="list of all containers",
     *          ref="$/responses/Json",
     *          @SWG\Schema(
     *              type="array"
     *          ),
     *      )
     * )
     */
    public function indexAction()
    {
        $containers = $this->getDoctrine()->getRepository(Container::class)->findAllJoinedToHost();

        if (!$containers) {
            throw $this->createNotFoundException(
                'No containers found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($containers, 'json');
        return new Response($response);
    }

    /**
     * Get all Containers from one host
     *
     *@Route("/hosts/{hostId}/containers", name="containers_from_host", methods={"GET"})
     *
     *@SWG\Get(path="/hosts/{hostId}/containers",
     * tags={"containers"},
     * @SWG\Response(
     *  response=200,
     *  description="list of containers from one host",
     *  @SWG\Schema(
     *      type="array"
     *  )
     * ),
     *
     * @SWG\Parameter(
     *  description="ID des Hosts",
     *  format="int64",
     *  in="path",
     *  name="hostId",
     *  required=true,
     *  type="integer"
     * ),
     *
     * @SWG\Parameter(
     *  description="Ob die gecacheten Container zurückgegeben werden sollen. Wenn fresh dann gleich true",
     *  format="int64",
     *  in="query",
     *  name="fresh",
     *  type="string"
     * )
     *)
     */
    public function listFormHostAction(Request $request, $hostId)
    {

        $fresh = $request->query->get('fresh');


        if ($fresh == 'true') {
            $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

            if (!$host) {
                throw $this->createNotFoundException(
                    'No host found for id ' . $hostId
                );
            }
            $client = new ApiClient($host);
            $containerApi = new ContainerApi($client);

            $containers = $containerApi->list();

            //TODO in DB aktualisieren

            return containers;
        } else {
            $containers = $this->getDoctrine()->getRepository(Container::class)->findAllByHostJoinedToHost($hostId);

            if (!$containers) {
                throw $this->createNotFoundException(
                    'No containers found'
                );
            }
            return new Response($containers);
        }



    }


    /**
     * Create a new Container on a specific Host
     *
     * @Route("/hosts/{hostId}/containers", name="containers_store", methods={"POST"})
     *
     * @SWG\Post(path="/hosts/{hostId}/containers",
     * tags={"containers"},
         * @SWG\Parameter(
         *  description="how to create a new container",
         *  format="int64",
         *  in="body",
         *  name="containerStoreData",
         *  required=true,
         *  @SWG\Schema(
         *      @SWG\Property(
         *          property="action",
         *          type="string",
         *          enum={"image", "migration", "copy", "none"},
         *          default="none"
         *      ),
         *      @SWG\Property(
         *          property="name",
         *          type="string"
         *      ),
         *      @SWG\Property(
         *          property="architecture",
         *          type="string"
         *      ),
         *  ),
         *),
         *
         * @SWG\Parameter(
         *  description="ID des Hosts",
         *  format="int64",
         *  in="path",
         *  name="hostId",
         *  required=true,
         *  type="integer"
         * ),
         *
         * @SWG\Response(
         *  description="erfolgsmeldung dass der Container erstellt wurde",
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

        $client = new ApiClient($host);
        $containerApi = new ContainerApi($client);
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


                $em->persist($host);
                $em->flush();

                $serializer = $this->get('jms_serializer');
                $response = $serializer->serialize($container, 'json');
                return new Response($response, Response::HTTP_CREATED);

                // return $containerApi->create($data);

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

    }


    /**
     * Get a Container by containerID
     *
     * @Route("/containers/{containerId}", name="containers_show", methods={"GET"})
     *
     *@SWG\Get(path="/containers/{containerId}",
     * tags={"containers"},
     * @SWG\Parameter(
     *  description="ID des Containers",
     *  format="int64",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *  type="integer"
     * ),
     *
     * @SWG\Response(
     *      response=200,
     *      description="show a single container"
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
            $host = $this->getDoctrine()->getRepository(Host::class)->find($container->host->id);

            if (!$host) {
                throw $this->createNotFoundException(
                    'No host found for id ' . $host
                );
            }
            $client = new ApiClient($host);
            $containerApi = new ContainerApi($client);

            $container = $containerApi->show($container->name);

                    //TODO in DB aktualisieren

            return $container;
        }
        return new Response($container);

    }


    /**
     * Deletes a Container by containerID
     *
     * @Route("/containers/{containerId}", name="containers_show", methods={"DELETE"})
     *
     *@SWG\Delete(path="/containers/{containerId}",
     * tags={"containers"},
     * @SWG\Parameter(
     *  description="ID des Containers",
     *  format="int64",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *  type="integer"
     * ),
     *
     * @SWG\Response(
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

        $host = $this->getDoctrine()->getRepository(Host::class)->find($container->host->id);

        if (!$host) {
            throw $this->createNotFoundException(
                'No host found for id ' . $host->getId()
            );
        }

        $em->remove($container);
        $em->flush();

        return $this->json([], 204);


        // $client = new ApiClient($host);
        // $containerApi = new ContainerApi($client);

        //$response = $containerApi->remove($container->name);

        // if ($response->getStatusCode() == 202) {
        //     $em->remove($container);
        //     $em->flush();

        //     return $this->json([], 204);
        // }

        return $this->json(['error' => 'Leider konnte der Container nicht gelöschtwerden.'], 500);
    }






}