<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Host;
use AppBundle\Entity\Network;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;
use AppBundle\Service\LxdApi\NetworkApi;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
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

    /**
     * @Route("/hosts/{hostID}/networks", name="create_network_on_host", methods={"POST"})
     *
     * @param Request $request
     * @param $hostID
     * @param NetworkApi $networkApi
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws WrongInputExceptionArray
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createNetwork(Request $request, $hostID, NetworkApi $networkApi){
        $network = new Network();

        if($request->request->has('name')) {
            $network->setName($request->request->get('name'));
        }
        if($request->request->has('description')) {
            $network->setDescription($request->request->get('description'));
        }
        if($request->request->has('config')) {
            $network->setConfig($request->request->get('config'));
        }
        if($request->request->has('type')) {
            $network->setType($request->request->get('type'));
        }

        if ($errorArray = $this->validation($network)) {
            throw new WrongInputExceptionArray($errorArray);
        }

        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostID);
        if(!$host){
            throw new ElementNotFoundException(
                'No Host for '.$hostID.' found'
            );
        }

        $result = $networkApi->createNetwork($host, $network);
        if($result->code != 201){
            throw new WrongInputException("LXD Error - ".$result->body->error);
        }

        $result = $networkApi->getSingleNetwork($host, $network->getName());
        if($result->code != 200){
            throw new WrongInputException("LXD Error - ".$result->body->error);
        }

        $network->setManaged($result->body->metadata->managed);
        $network->setStatus($result->body->metadata->status);
        $network->setLocations($result->body->metadata->locations);

        $host->addLXDNetwork($network);

        $em = $this->getDoctrine()->getManager();
        $em->persist($host);
        $em->persist($network);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($network, 'json');
        return new Response($response, Response::HTTP_CREATED);
    }

    public function deleteNetwork(){

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