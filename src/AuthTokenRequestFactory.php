<?php
namespace Germania\AuthApiClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\RequestFactoryInterface;

class AuthTokenRequestFactory implements RequestFactoryInterface
{

    /**
     * @var Psr\Http\Message\RequestFactoryInterface
     */
    public $request_factory;

    /**
     * @var string
     */
    public $auth_token;


    public function __construct(string $auth_token, RequestFactoryInterface $request_factory)
    {
        $this->auth_token = $auth_token;
        $this->request_factory = $request_factory;
    }


    public function createRequest( $method, $url) : RequestInterface
    {
        $request = $this->request_factory->createRequest( $method, $url);

        $auth_header = sprintf("Bearer %s", $this->auth_token);
        return $request->withHeader('Authorization', $auth_header);
    }

}

