<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Network;
use AppBundle\Exception\ElementNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NetworkController extends Controller
{
    /**
     * @Route("/networks", name="get_all_networks", methods={"GET"})
     * @throws ElementNotFoundException
     */
    public function getAllNetworks(){
        $networks = $this->getDoctrine()->getRepository(Network::class)->findAll();

        if(!$networks){
            throw new ElementNotFoundException(
                'No Networks found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($networks, 'json');
        return new Response($response);
    }

    /**
     * @Route("/networks/{networkID}", name="get_single_networks", methods={"GET"})
     * @param $networkID
     * @return Response
     * @throws ElementNotFoundException
     */
    public function getSingleNetwork($networkID){
        $network = $this->getDoctrine()->getRepository(Network::class)->find($networkID);

        if(!$network){
            throw new ElementNotFoundException(
                'No Network for '.$networkID.' found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($network, 'json');
        return new Response($response);
    }

    public function createNetwork(){

    }

    public function deleteNetwork(){

    }
}