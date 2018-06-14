<?php

namespace AppBundle\Controller;


use AppBundle\Exception\WrongInputException;
use AppBundle\Service\CorsProxyApi;
use Httpful\Exception\ConnectionErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CorsProxyController extends BaseController
{

    /**
     * Proxy to allow the Frontend to access urls without CORS headers
     *
     * url = corsproxy?url={url}
     *
     * @Route("/corsproxy", name="cors_proxy", methods={"GET"})
     *
     * @param CorsProxyApi $api
     * @param Request $request
     * @return Response
     *
     * @throws ConnectionErrorException
     * @throws WrongInputException
     */
    public function corsProxy(CorsProxyApi $api, Request $request){
        $url = $request->query->get('url');
        if(!$url){
            throw new WrongInputException("No URL provided");
        }
        $result = $api->getUrl($url);

        return new Response($result);
    }

}