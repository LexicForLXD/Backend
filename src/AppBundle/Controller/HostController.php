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
use Symfony\Component\HttpFoundation\JsonResponse;
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
     *          type="array"
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
     *     description="gibt den neu gespeicherten Host zurück"
     * )
     *
     * @SWG\Parameter(
     *  name="hostStoreData",
     *  in="body",
     *  required=true,
     *  @SWG\Schema(
     *      @SWG\Property(
     *          property="ipv4",
     *          type="string"
     *      ),
     *      @SWG\Property(
     *          property="ipv6",
     *          type="string"
     *      ),
     *      @SWG\Property(
     *          property="domainName",
     *          type="string"
     *      ),
     *      @SWG\Property(
     *          property="name",
     *          type="string"
     *      ),
     *      @SWG\Property(
     *          property="mac",
     *          type="string"
     *      ),
     *      @SWG\Property(
     *          property="settings",
     *          type="string"
     *      ),
     *      @SWG\Property(
     *          property="port",
     *          type="integer"
     *      )
     *  )
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
        $host->setPort($request->request->get('port'));
        $host->setSettings($request->request->get('settings'));

        if($errorArray = $this->validation($host))
        {
            return new JsonResponse(['errors' => $errorArray], 400);
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
     *          parameter="id",
     *         required=true,
     *         type="integer"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="gibt einen Host zurück"
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
     *
     * @SWG\Parameter(
     *  name="hostUpdateData",
     *  in="body",
     *  required=true,
     *  @SWG\Schema(
     *      @SWG\Property(
     *          property="ipv4",
     *          type="string"
     *      ),
     *      @SWG\Property(
     *          property="ipv6",
     *          type="string"
     *      ),
     *      @SWG\Property(
     *          property="domainName",
     *          type="string"
     *      ),
     *      @SWG\Property(
     *          property="name",
     *          type="string"
     *      ),
     *      @SWG\Property(
     *          property="mac",
     *          type="string"
     *      ),
     *      @SWG\Property(
     *          property="settings",
     *          type="string"
     *      ),
     *      @SWG\Property(
     *          property="port",
     *          type="integer"
     *      )
     *  )
     * )
     *
     * @SWG\Response(
     *  response=200,
     *  description="Erfolgsmeldung,dass der Host erfolgreich geupdated wurde"
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
        $host->setPort($request->request->get('port'));
        $host->setSettings($request->request->get('settings'));

        if($errorArray = $this->validation($host))
        {
            return new JsonResponse(['errors' => $errorArray], 400);
        }

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
     *     description="löscht einen Host"
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

        return $this->json([], 204);
    }

    /**
     * @Route("/hosts/{id}", name="hosts_authorize", methods={"POST"})
     *
     * push the client certificate to server
     *
     * @param Request $request
     * @param [integer] $id
     * @return void
     *
     * @SWG\Parameter(
     *  description="ID des Host",
     *  format="int64",
     *  in="path",
     *  name="id",
     *  required=true,
     *  type="integer"
     * )
     *
     * @SWG\Parameter(
     *  description="password of lxd host",
     *  format="int64",
     *  in="body",
     *  name="password",
     *  required=true,
     *  @SWG\Schema(@SWG\Property(type="string", property="password")),
     * )
     *
     * @SWG\Response(
     *  response = 200,
     *  description="erfolgsmeldung dass Host erfolgreich authorisiert"
     * )
     *
     * @SWG\Tag(name = "hosts")
     */
    public function authorizeAction(Request $request, $id)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($id);

        if (!$host) {
            throw $this->createNotFoundException(
                'No host found for id ' . $id
            );
        }

        $client = new ApiClient($host);
        $hostApi = new \AppBundle\Service\LxdApi\Endpoints\Host($client);

        $data = [
            "type" => "client",
            "password" => $request->get("password")
        ];

        return $hostApi->authenticate($data);

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