<?php
namespace Germania\AuthApiClient;

use Germania\AuthApiClient\Exceptions\AuthApiRequestException;

use Psr\Http\{
    Client\ClientInterface,
    Client\ClientExceptionInterface,
    Message\RequestInterface,
    Message\RequestFactoryInterface,
    Message\StreamFactoryInterface,
};
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Nyholm\Psr7\Factory\Psr17Factory;



class HttpClientAuthApi extends AuthApiAbstract implements AuthApiInterface
{
    use ResponseDecoderTrait;

    /**
     * @var string
     */
    protected $api;

    /**
     * @var \Psr\Http\Client\ClientInterface
     */
    protected $client;

    /**
     * @var \Psr\Http\Message\RequestFactoryInterface
     */
    protected $request_factory;


    /**
     * @var \Psr\Http\Message\StreamFactoryInterface
     */
    protected $stream_factory;


    /**
     * @param string                              $api     AuthApi Base URL
     * @param \Psr\Http\Client\ClientInterface    $client  PSR-18 HTTP Client
     * @param LoggerInterface|null                $logger  Optional: PSR-3 Logger
     */
    public function __construct(string $api, ClientInterface $client, LoggerInterface $logger = null)
    {
        $this->setBaseUrl($api);
        $this->setHttpClient($client);
        $this->setLogger($logger ?: new NullLogger);

        $psr17 = new Psr17Factory;
        $this->setRequestFactory($psr17);
        $this->setStreamFactory($psr17);

    }


    /**
     * @param string $api
     */
    public function setBaseUrl( string $api ) : self
    {
        $this->api = $api;
        return $this;
    }


    /**
     * @param \Psr\Http\Message\RequestFactoryInterface $request_factory PSR-17 Request Factory
     */
    public function setRequestFactory( RequestFactoryInterface $request_factory ) : self
    {
        $this->request_factory = $request_factory;
        return $this;
    }



    /**
     * @param \Psr\Http\Message\StreamFactoryInterface $stream_factory PSR-17 Stream Factory
     */
    public function setStreamFactory( StreamFactoryInterface $stream_factory ) : self
    {
        $this->stream_factory = $stream_factory;
        return $this;
    }


    /**
     * @param \Psr\Http\Client\ClientInterface $client PSR-18 HTTP Client
     */
    public function setHttpClient( ClientInterface $client ) : self
    {
        $this->client = $client;
        return $this;
    }





    /**
     * @inheritDoc
     * @return \Germania\AuthApiClient\AuthToken
     */
    public function getToken(string $username, string $password, bool $refresh = false) : AuthTokenInterface
    {
        $token = $this->login($username, $password);

        if ($refresh):
            $token = $this->refresh($token);
        endif;

        return $token;
    }


    /**
     * @inheritDoc
     * @return \Germania\AuthApiClient\AuthToken
     */
    public function login(string $username, string $password) : AuthTokenInterface
    {
        try {
            $uri = $this->api . "login";

            $body = $this->stream_factory->createStream(http_build_query([
                'username' => $username,
                'password' => $password
            ]));

            $request = $this->request_factory
                            ->createRequest("post", $uri)
                            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                            ->withBody($body);

            $response = $this->client->sendRequest($request);

            $response_decoded = $this->decodeResponse($response);
            // Array with keys
            // - access_token'
            // - token_type'
            // - expires_in'

            $this->logger->log($this->success_loglevel, "AuthToken successfully retrieved");
            return new AuthToken($response_decoded['access_token'], $response_decoded['expires_in']);
        }
        catch (ClientExceptionInterface $e) {
            $msg = $e->getMessage();
            $this->logger->log($this->error_loglevel, $msg);
            throw new AuthApiRequestException($msg, 0, $e);
        }
    }


    /**
     * @inheritDoc
     * @return \Germania\AuthApiClient\AuthToken
     */
    public function refresh(AuthTokenInterface $token) : AuthTokenInterface
    {
        $auth_header = sprintf("Bearer %s", $token->getContent());
        try {
            $uri = $this->api . "refresh";

            $request = $this->request_factory
                            ->createRequest("get", $uri)
                            ->withHeader('Authorization', $auth_header);

            $response = $this->client->sendRequest($request);



            $response_decoded = $this->decodeResponse($response);
            $this->logger->log($this->success_loglevel, "AuthToken successfully refreshed");
            return new AuthToken($response_decoded['access_token'], $response_decoded['expires_in']);
        }
        catch (ClientExceptionInterface $e) {
            $msg = $e->getMessage();
            $this->logger->log($this->error_loglevel, $msg);
            throw new AuthApiRequestException($msg, 0, $e);
        }
    }


}
