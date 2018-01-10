<?php

namespace AppBundle\Controller;


use AppBundle\Service\CorsProxyApi;
use Httpful\Exception\ConnectionErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CorsProxyController extends Controller
{

    /**
     * url = corsproxy?url={url}
     *
     * @Route("/corsproxy", name="cors_proxy", methods={"GET"})
     *
     * @param CorsProxyApi $api
     * @param Request $request
     * @return Response
     *
     * @throws ConnectionErrorException
     */
    public function corsProxy(CorsProxyApi $api, Request $request){
        $url = $request->query->get('url');
        if(!$url){
            return new Response(json_encode(['error' => 'no url provided']));
        }
        $result = $api->getUrl($url);

        return new Response($result);
    }

}