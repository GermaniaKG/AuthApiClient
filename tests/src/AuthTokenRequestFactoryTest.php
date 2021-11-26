<?php
namespace tests;

use Germania\AuthApiClient\AuthTokenRequestFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\RequestFactoryInterface;

class AuthTokenRequestFactoryTest extends \PHPUnit\Framework\TestCase
{

    public function testInstantiation( ) : RequestFactoryInterface
    {
        $inner_request_factory = new \Nyholm\Psr7\Factory\Psr17Factory;
        $token_string = "secret";

        $sut = new AuthTokenRequestFactory($token_string, $inner_request_factory);
        $this->assertInstanceOf(RequestFactoryInterface::class, $sut);

        return $sut;
    }

    /**
     * @depends testInstantiation
     */
    public function testRequestFactory( RequestFactoryInterface $sut ) : void
    {
        $request = $sut->createRequest("GET", "/");

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertTrue($request->hasHeader("Authorization"));

    }

}
