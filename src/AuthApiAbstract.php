<?php
namespace Germania\AuthApiClient;

use Psr\Log\LoggerAwareTrait;


/**
 * Abstract base class for AuthApi clients,
 * configured to use useful traits such as LoggerAwareTrait and LoglevelTrait.
 */
abstract class AuthApiAbstract implements AuthApiInterface
{
    use LoggerAwareTrait,
        LoglevelTrait;




    /**
     * @inheritDoc
     */
    abstract public function getToken(string $username, string $password, bool $refresh = false) : AuthTokenInterface;

    /**
     * @inheritDoc
     */
    abstract public function login(string $username, string $password) : AuthTokenInterface;


    /**
     * @inheritDoc
     */
    abstract public function refresh(AuthTokenInterface $token) : AuthTokenInterface;
}
