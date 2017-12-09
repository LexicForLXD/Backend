<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Service\LxdApi\ApiClient;
use AppBundle\Service\LxdApi\Endpoints\Container as ContainerApi;
use AppBundle\Service\LxdApi\Endpoints\ContainerState;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;



class ContainerController extends Controller
{
    /**
     * Get all saved Containers
     *
     * @Route("/containers", name="containers_all", methods={"GET"})
     *
     * @SWG\Response(
     *      response=200,
     *      description="list of all containers",
     *      ref="$/responses/Json",
     *      @SWG\Schema(
     *          type="array"
     *      ),
     * )
     *
     * @SWG\Tag(name="containers")
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
     * get Containers from one host
     *

     *
     * @Route("/hosts/{hostId}/containers", name="containers_from_host", methods={"GET"})
     *
     * @SWG\Response(
     *  response=200,
     *  description="list of containers from one host",
     *  @SWG\Schema(
     *      type="array"
     *  )
     * )
     *
     * @SWG\Parameter(
     *  description="ID des Hosts",
     *  format="int64",
     *  in="path",
     *  name="hostId",
     *  required=true,
     *  type="integer"
     * )
     *
     *
     * @SWG\Tag(name="containers")
     *
     */
    public function listFormHostAction($hostId)
    {

        $containers = $this->getDoctrine()->getRepository(Container::class)->findAllByHostJoinedToHost($hostId);

        if (!$containers) {
            throw $this->createNotFoundException(
                'No containers found'
            );
        }

        // $client = new ApiClient($host);
        // $containerApi = new ContainerApi($client);

        // $containers = $containerApi->list();

        return new Response($containers);
    }


    /**
     * Undocumented function
     *
     * @Route("/hosts/{hostId}/containers", name="containers_store", methods={"POST"})
     *
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
     *      )
     *  )
     *
     *
     * )
     *
     * @SWG\Parameter(
     *  description="ID des Hosts",
     *  format="int64",
     *  in="path",
     *  name="hostId",
     *  required=true,
     *  type="integer"
     * )
     *
     * @SWG\Response(
     *  description="erfolgsmeldung dass der Container erstellt wurde",
     *  response=201
     * )
     *
     * @SWG\Tag(name="containers")
     *
     */
    public function storeAction(Request $request, $hostId)
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

        switch($type)
        {
            case 'image':
                $data = [
                    "name" => $request->get("name"),
                    "architecture" => $request->get("architecture") ?: 'x86_64',
                    "profiles" => $request->get("profiles") ?: array('default'),
                    "ephermeral" => $request->get("ephermeral") ?: false,
                    "config" => $request->get("config"),
                    "devices" => $request->get("devices"),
                    "source" => [
                        "type" => "image",
                        "mode" => "pull",
                        "server" => $request->get("imageServer") ?: 'https://images.linuxcontainers.org:8443',
                        "protocol" => $request->get("protocol") ?: 'lxd',
                        "alias" => $request->get("alias"),
                        "fingerprint" => $request->get("fingerprint")
                    ]
                ];

                $container = new Container();

                return $containerApi->create($data);

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

        return new JsonResponse(["message" => "blas"]);
    }


    /**
     * Returns a container with host
     *
     * @Route("/containers/{containerId}", name="containers_show", methods={"GET"})
     *
     *
     * @SWG\Parameter(
     *  description="ID des Containers",
     *  format="int64",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *  type="integer"
     * )
     *
     * @SWG\Response(
     *      response=200,
     *      description="show a single container"
     * )
     *
     * @SWG\Tag(name="containers")
     *

     */
    public function showSingleAction($containerId)
    {
        $containers = $this->getDoctrine()->getRepository(Container::class)->findOneByIdJoinedToHost($containerId);

        if (!$containers) {
            throw $this->createNotFoundException(
                'No container found for id ' .$containerId
            );
        }

        return new Response($containers);
    }






}