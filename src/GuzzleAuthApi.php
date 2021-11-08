<?php
namespace Germania\AuthApiClient;

use Germania\AuthApiClient\Exceptions\AuthApiRequestException;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;


class GuzzleAuthApi extends AuthApiAbstract implements AuthApiInterface
{

    use ResponseDecoderTrait;

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $guzzle;


    /**
     * @param \GuzzleHttp\ClientInterface $guzzle   Guzzle client
     * @param LoggerInterface|null        $logger   Optional: PSR-3 Logger
     */
    public function __construct(ClientInterface $guzzle, LoggerInterface $logger = null)
    {
        $this->setGuzzleClient($guzzle);
        $this->setLogger($logger ?: new NullLogger);
    }


    /**
     * @param \GuzzleHttp\ClientInterface $guzzle ClientInterface
     */
    public function setGuzzleClient( ClientInterface $guzzle ) : self
    {
        $this->guzzle = $guzzle;
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
            $response = $this->guzzle->post("login", [
                'form_params' => [
                    'username' => $username,
                    'password' => $password
                ]
            ]);

            $response_decoded = $this->decodeResponse($response);
            // Array with keys
            // - access_token'
            // - token_type'
            // - expires_in'

            $this->logger->log($this->success_loglevel, "AuthToken successfully retrieved");
            return new AuthToken($response_decoded['access_token'], $response_decoded['expires_in']);
        }
        catch (GuzzleRequestException $e) {
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
            $response = $this->guzzle->get("refresh", [
                'headers' => array('Authorization' => $auth_header)
            ]);

            $response_decoded = $this->decodeResponse($response);
            $this->logger->log($this->success_loglevel, "AuthToken successfully refreshed");
            return new AuthToken($response_decoded['access_token'], $response_decoded['expires_in']);
        }
        catch (GuzzleRequestException $e) {
            $msg = $e->getMessage();
            $this->logger->log($this->error_loglevel, $msg);
            throw new AuthApiRequestException($msg, 0, $e);
        }
    }


}
