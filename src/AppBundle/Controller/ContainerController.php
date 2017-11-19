<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Service\LxdApi\ApiClient;
use AppBundle\Service\LxdApi\Endpoints\Container as ContainerApi;
use AppBundle\Service\LxdApi\Endpoints\ContainerState;
use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;


class ContainerController extends Controller
{
    /**
     * Get all saved Containers
     *
     * @return void
     *
     * @Route("/containers", name="containers_all", methods={"GET"})
     *
     * @SWG\Response(
     *  response=200,
     *  description="list of all host",
     *  @Model(type=Container::class)
     * )
     *
     * @SWG\Tag(name="containers")
     *
     */
    public function indexAction()
    {
        $containers = $this->getDoctrine()->getRepository(Container::class)->findAll();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($containers, 'json');
        return new Response($response);
    }

    /**
     * get Containers from one host
     *
     * @param [String] $host
     * @return void
     *
     * @Route("/hosts/{hostId}/containers", name="containers_from_host", methods={"GET"})
     *
     * @SWG\Response(
     *  response=200,
     *  description="list of containers from one host"
     * )
     * @SWG\Tag(name="containers")
     *
     */
    public function listFormHostAction($hostId)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);


        $hostUrl = $host->getIpv4();


        $client = new ApiClient($hostUrl, $host->getPort());
        $containerApi = new ContainerApi($client);

        $containers = $containerApi->list();

        return new Response($containers);
    }



    public function storeAction (Request $request)
    {

    }


}