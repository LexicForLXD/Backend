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
use Symfony\Component\HttpFoundation\JsonResponse;


class HostController extends Controller
{
    /**
     * @Route("/hosts", name="hosts_index")
     */
    public function index()
    {
        $hosts = $this->getDoctrine()->getRepository(Host::class)->findAll();

        return new JsonResponse($hosts);
    }

}