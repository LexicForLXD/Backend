<?php
namespace AppBundle\Service\LxdApi;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Service\LxdApi\Util\HttpHelper;
use Httpful\Request;


class ContainerExecApi extends HttpHelper
{
    protected function getEndpoint($urlParam = NULL)
    {
        return 'containers/'.$urlParam.'/exec';
    }


    public function __construct($cert_location, $cert_key_location, $cert_passphrase)
    {
        parent::__construct($cert_location, $cert_key_location, $cert_passphrase);
        $this->init();
    }


    /**
     * Executes a command on the container
     *
     * @param Host $host
     * @param Container $container
     * @param array $data
     * @return void
     */
    public function execCommand(Container $container, $data)
    {
        $uri = $this->buildUri($container->getHost(), $this->getEndpoint($container->getName()));
        return Request::post($uri, $data)->send();
    }


    public function exec(Container $container, array $command, $environment = "{}", $recordOutput = true, $waitForWebsocket = false, $interactive = false, $width = 80, $height = 25)
    {

        $data = [
            "command" => $command,
            "environment" => $environment,
            "wait-for-websocket" => $waitForWebsocket,
            "record-output" => $recordOutput,
            "interactive" => $interactive,
            "width" => $width,
            "height" => $height
        ];

        return $this->execCommand($container, $data);
    }
}
