<?php
namespace Germania\AuthApiClient;

interface AuthApiInterface
{


    /**
     * Returns the AuthToken for the given username and password.
     *
     * @param  string $username  Username
     * @param  string $password  Password
     * @param  bool   $refresh   Optional: Wether to obtain longer TTL via "refresh"
     *
     * @return \Germania\AuthApiClient\AuthTokenInterface
     */
    public function getToken(string $username, string $password, bool $refresh = false) : AuthTokenInterface;


    /**
     * Performs the login process at Germania's AuthAPI
     * and returns the (short-lifetime) AuthToken.
     *
     * @param  string $username  Username
     * @param  string $password  Password
     *
     * @return  \Germania\AuthApiClient\AuthTokenInterface
     *
     * @throws  \Germania\AuthApiClient\Exceptions\AuthApiExceptionInterface
     */
    public function login(string $username, string $password) : AuthTokenInterface;


    /**
     * Refreshes (prolongs) the AuthToken by asking AuthAPI again.
     *
     * @param   \Germania\AuthApiClient\AuthTokenInterface $token Old token
     *
     * @return  \Germania\AuthApiClient\AuthTokenInterface
     *
     * @throws  \Germania\AuthApiClient\Exceptions\AuthApiRequestException
     */
    public function refresh(AuthTokenInterface $token) : AuthTokenInterface;
}
