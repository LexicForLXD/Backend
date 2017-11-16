<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 06.11.2017
 * Time: 19:39
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Host;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;


class HostController extends Controller
{
    /**
     * @Route("/hosts", name="hosts_index", methods={"GET"})
     * @return Response
     *
     * @SWG\Response(
     *     response=200,
     *     description="Zeigt eine Liste aller Hosts an",
     *     @SWG\Schema(
     *          type="array",
     *          @Model(type=Host::class)
     *     )
     * )
     *
     * @SWG\Tag(name="hosts")
     */
    public function indexAction()
    {
        $hosts = $this->getDoctrine()->getRepository(Host::class)->findAll();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($hosts, 'json');
        return new Response($response);
    }


    /**
     * @Route("/hosts", name="hosts_store", methods={"POST"})
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     *
     * @SWG\Response(
     *     response=201,
     *     description="gibt den gespeicherten Host zurück",
     *     @Model(type=Host::class)
     * )
     *
     * @SWG\Parameter(
     *     name="ipv4",
     *     in="body",
     *     required=true,
     *     description="IPv4 Adresse des Hosts",
     *     <@SWG\Schema(type="string")
     * )
     * @SWG\Parameter(
     *     name="ipv6",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(type="string"),
     *     description="IPv6 Adresse des Hosts"
     * )
     * @SWG\Parameter(
     *     name="domain_name",
     *     in="body",
     *     @SWG\Schema(type="string"),
     *     description="FQDN des Hosts"
     * )
     * @SWG\Parameter(
     *     name="name",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(type="string"),
     *     description="Name des Hosts"
     * )
     * @SWG\Parameter(
     *     name="mac",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(type="string"),
     *     description="MAC Adresse des Hosts"
     * )
     * @SWG\Parameter(
     *     name="settings",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(type="string"),
     *     description="Sonstige Settings des Hosts"
     * )
     *
     * @SWG\Tag(name="hosts")
     */
    public function storeAction(Request $request, EntityManagerInterface $em)
    {

        $host = new Host();
        $host->setIpv4($request->request->get('ipv4'));
        $host->setIpv6($request->request->get('ipv6'));
        $host->setDomainName($request->request->get('domain_name'));
        $host->setMac($request->request->get('mac'));
        $host->setName($request->request->get('name'));
        $host->setSettings($request->request->get('settings'));

        $validator = $this->get('validator');
        $errors = $validator->validate($host);

        if (count($errors) > 0) {
            $errorsString = (string)$errors;
            return new Response($errorsString);
        }

        $em->persist($host);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($host, 'json');
        return new Response($response, Response::HTTP_CREATED);
    }

    /**
     * @Route("/hosts/{id}", name="hosts_show", methods={"GET"})
     * @param int $id
     * @return Response
     *
     * @SWG\Parameter(
     *         description="ID von anzuzeigendem Host",
     *         format="int64",
     *         in="path",
     *         name="id",
     *         required=true,
     *         type="integer"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="gibt einen Host zurück",
     *      @Model(type=Host::class)
     * )
     *
     * @SWG\Tag(name="hosts")
     */
    public function showAction($id)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($id);

        if (!$host) {
            throw $this->createNotFoundException(
                'No host found for id ' . $id
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($host, 'json');
        return new Response($response);
    }

    /**
     * @Route("/hosts/{id}", name="hosts_update", methods={"PUT"})
     * @param Request $request
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     * @SWG\Parameter(
     *     description="ID von upzudaten Host",
     *     format="int64",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer"
     * )
     * @SWG\Parameter(
     *     name="ipv4",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(type="string"),
     *     description="IPv4 Adresse des Hosts"
     * )
     * @SWG\Parameter(
     *     name="ipv6",
     *     in="body",
     *     @SWG\Schema(type="string"),
     *     description="IPv6 Adresse des Hosts"
     * )
     * @SWG\Parameter(
     *     name="domain_name",
     *     in="body",
     *     @SWG\Schema(type="string"),
     *     description="FQDN des Hosts"
     * )
     * @SWG\Parameter(
     *     name="name",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(type="string"),
     *     description="Name des Hosts"
     * )
     * @SWG\Parameter(
     *     name="mac",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(type="string"),
     *     description="MAC Adresse des Hosts"
     * )
     * @SWG\Parameter(
     *     name="settings",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(type="string"),
     *     description="Sonstige Settings des Hosts"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="gibt den bearbeiteten Host zurück",
     *     @Model(type=Host::class)
     * )
     *
     * @SWG\Tag(name="hosts")
     */
    public function updateAction(Request $request, $id, EntityManagerInterface $em)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($id);

        if (!$host) {
            throw $this->createNotFoundException(
                'No host found for id ' . $id
            );
        }

        $host->setIpv4($request->request->get('ipv4'));
        $host->setIpv6($request->request->get('ipv6'));
        $host->setDomainName($request->request->get('domain_name'));
        $host->setMac($request->request->get('mac'));
        $host->setName($request->request->get('name'));
        $host->setSettings($request->request->get('settings'));
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($host, 'json');
        return new Response($response);
    }

    /**
     * @Route("/hosts/{id}", name="hosts_delete", methods={"DELETE"})
     * @param $id
     * @param EntityManagerInterface $em
     * @return Response
     *
     * @SWG\Parameter(
     *     description="ID des zu löschenden Host",
     *     format="int64",
     *     in="path",
     *     name="id",
     *     required=true,
     *     type="integer"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="löscht einen Host",
     * )
     *
     * @SWG\Tag(name="hosts")
     */
    public function deleteAction($id, EntityManagerInterface $em)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($id);

        if (!$host) {
            throw $this->createNotFoundException(
                'No host found for id ' . $id
            );
        }

        $em->remove($host);
        $em->flush();

        return $this->json(array('message' => 'erfolgreich gelöscht'));
    }

}