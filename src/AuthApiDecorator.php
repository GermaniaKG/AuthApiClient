<?php
namespace Germania\AuthApiClient;


/**
 * Use this abstract class as base class for any decorator.
 */
abstract class AuthApiDecorator extends AuthApiAbstract
{

    /**
     * @var AuthApiInterface
     */
    protected $client;



    /**
     * @param AuthApiInterface $client AuthApi client decoratee
     */
    public function __construct( AuthApiInterface $client )
    {
        $this->setClient( $client );
    }


    /**
     * Sets the AuthApi client decoratee.
     *
     * @param AuthApiInterface $client AuthApi client
     */
    public function setClient( AuthApiInterface $client ) : self
    {
        $this->client = $client;
        return $this;
    }




    /**
     * @inheritDoc
     *
     * Delegates the method call to the AuthApiInterface decoratee.
     */
    public function getToken(string $username, string $password, bool $refresh = false) : AuthTokenInterface
    {
        return $this->client->getToken( $username, $password, $refresh);
    }



    /**
     * @inheritDoc
     *
     * Delegates the method call to the AuthApiInterface decoratee.
     */
    public function login(string $username, string $password) : AuthTokenInterface
    {
        return $this->client->login( $username, $password);
    }


    /**
     * @inheritDoc
     *
     * Delegates the method call to the AuthApiInterface decoratee.
     */
    public function refresh(AuthTokenInterface $token) : AuthTokenInterface
    {
        return $this->client->refresh( $token );
    }
}
