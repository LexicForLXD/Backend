<?php

/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 06.11.2017
 * Time: 19:39
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Host;
use AppBundle\Exception\ElementNotFoundException;
use Httpful\Exception\ConnectionErrorException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\LxdApi\HostApi;
use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;


class HostController extends BaseController
{
    /**
     * Get a list of all saved Hosts
     *
     * @Route("/hosts", name="hosts_index", methods={"GET"})
     * @return Response
     *
     * @throws ElementNotFoundException
     */
    public function indexAction()
    {
        $hosts = $this->getDoctrine()->getRepository(Host::class)->findAll();

        if (!$hosts) {
            throw new ElementNotFoundException(
                'No Hosts found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($hosts, 'json');
        return new Response($response);
    }


    /**
     * Create a new Host
     *
     * @Route("/hosts", name="hosts_store", methods={"POST"})
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param HostApi $api
     * @return Response
     *
     * @throws WrongInputExceptionArray
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function storeAction(Request $request, EntityManagerInterface $em, HostApi $api)
    {

        $host = new Host();
        $host->setIpv4($request->request->get('ipv4'));
        $host->setIpv6($request->request->get('ipv6'));
        $host->setDomainName($request->request->get('domainName'));
        $host->setMac($request->request->get('mac'));
        $host->setName($request->request->get('name'));
        $host->setPort($request->request->get('port'));
        $host->setSettings($request->request->get('settings'));


        $host->setAuthenticated(false);

        $this->validation($host);

        $em->persist($host);
        $em->flush();

        try {
            $authenticated = $api->trusted($host);
        } catch (ConnectionErrorException $e) {
            $authenticated = false;
        }


        if ($authenticated) {
            $host->setAuthenticated(true);
        } elseif ($request->get('password')) {
            $data = [
                "type" => "client",
                "name" => "LEXIC_",
                "password" => $request->get('password')
            ];

            $result = $api->authenticate($host, $data);

            if ($result->code == 201) {
                $host->setAuthenticated(true);
            }

        }

        $em->persist($host);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($host, 'json');
        return new Response($response, Response::HTTP_CREATED);
    }

    /**
     * Get a Host by hostID
     * @Route("/hosts/{hostId}", name="hosts_show", methods={"GET"})
     * @param int $hostId
     * @return Response
     * @throws ElementNotFoundException
     */
    public function showAction($hostId)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No host found for id ' . $hostId
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($host, 'json');
        return new Response($response);
    }

    /**
     * Update a Host by hostID
     * @Route("/hosts/{hostId}", name="hosts_update", methods={"PUT"})
     * @param Request $request
     * @param int $hostId
     * @param EntityManagerInterface $em
     * @param HostApi $api
     * @return Response
     *
     * @throws ConnectionErrorException
     * @throws ElementNotFoundException
     * @throws WrongInputExceptionArray
     */
    public function updateAction(Request $request, $hostId, EntityManagerInterface $em, HostApi $api)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No host found for id ' . $hostId
            );
        }


        $host->setIpv4($request->request->get('ipv4'));
        $host->setIpv6($request->request->get('ipv6'));
        $host->setDomainName($request->request->get('domainName'));
        $host->setMac($request->request->get('mac'));
        $host->setName($request->request->get('name'));
        $host->setPort($request->request->get('port'));
        $host->setSettings($request->request->get('settings'));


        if (!$host->isAuthenticated()) {
            if ($request->request->has("password")) {
                $data = [
                    "type" => "client",
                    "name" => "LEXIC_",
                    "password" => $request->get('password')
                ];

                $result = $api->authenticate($host, $data);

                if ($result->code == 201) {
                    $host->setAuthenticated(true);
                }
            }
        }


        $this->validation($host);


        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($host, 'json');
        return new Response($response);
    }

    /**
     * Delete a Host by hostID
     *
     * @Route("/hosts/{hostId}", name="hosts_delete", methods={"DELETE"})
     * @param int $hostId
     * @param EntityManagerInterface $em
     * @return Response
     *
     * @throws ElementNotFoundException
     * @throws WrongInputException
     */
    public function deleteAction(int $hostId, EntityManagerInterface $em)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No host found for id ' . $hostId
            );
        }

        if ($host->hasAnything()) {
            throw new WrongInputException('Host has an association with one or more of the following: images, containers, profiles');
        }

        $em->remove($host);
        $em->flush();

        return $this->json([], 204);
    }

    /**
     * Authorize the Backend to Access the Hosts LXD API
     *
     * @Route("/hosts/{hostId}/authorization", name="hosts_authorize", methods={"POST"})
     *
     * push the client certificate to server
     *
     * @param Request $request
     * @param [integer] $hostId
     * @param HostApi $api
     * @param EntityManagerInterface $em
     * @return Response
     *
     * @throws ConnectionErrorException
     * @throws ElementNotFoundException
     * @throws WrongInputException
     */
    public function authorizeAction(Request $request, $hostId, HostApi $api, EntityManagerInterface $em)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No host found for id ' . $hostId
            );
        }

        if ($api->trusted($host)) {
            $host->setAuthenticated(true);
        } else {
            $data = [
                "type" => "client",
                "name" => "LEXIC",
                "password" => $request->get("password")
            ];

            $result = $api->authenticate($host, $data);

            if ($result->code != 201) {
                $host->setAuthenticated(false);
                throw new WrongInputException($result->body->error);
            } else {
                $host->setAuthenticated(true);
            }
        }

        $em->flush();
        return new JsonResponse(['message' => 'authentication successful']);

    }
}