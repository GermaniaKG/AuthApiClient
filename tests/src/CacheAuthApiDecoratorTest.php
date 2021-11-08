<?php
namespace tests;

use Germania\AuthApiClient\{
    CacheAuthApiDecorator,
    AuthApiInterface,
    AuthToken,
    AuthTokenInterface,
    Exceptions\AuthApiExceptionInterface,
    Exceptions\AuthApiRequestException,
    Exceptions\AuthApiResponseException
};

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LogLevel;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Symfony\Component\Cache\Adapter\PdoAdapter;

class CacheAuthApiDecoratorTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    public $symfony_cache;

    public function setUp() : void
    {
        $this->symfony_cache = new PdoAdapter("sqlite::memory:");
        parent::setUp();
    }


    public function testInstantiation() : void
    {
        $client_mock = $this->prophesize( AuthApiInterface::class );
        $client = $client_mock->reveal();

        $pool_mock = $this->prophesize(CacheItemPoolInterface::class);
        $pool = $pool_mock->reveal();

        $sut = new CacheAuthApiDecorator($client, $pool);
        $sut->setErrorLoglevel( LogLevel::ERROR );
        $sut->setSuccessLoglevel( LogLevel::INFO );

        $this->assertInstanceOf(AuthApiInterface::class, $sut);
    }




    /**
     * @dataProvider provideGetTokenArguments
     */
    public function testTokenIsInMockCache( string $username, string $password, bool $refresh) : void
    {
        $token_content = "foobar";
        $token_lifetime = 60;

        $token = new AuthToken($token_content, $token_lifetime);

        // Mock Cache pool and CacheItem which IS in cache
        $item_mock = $this->prophesize( CacheItemInterface::class );
        $item_mock->isHit()->willReturn( true );
        $item_mock->get()->willReturn( $token );

        $pool_mock = $this->prophesize(CacheItemPoolInterface::class);
        $pool_mock->getItem( Argument::type("string") )->willReturn( $item_mock->reveal() );
        $pool = $pool_mock->reveal();

        $client_mock = $this->prophesize(AuthApiInterface::class);
        $client = $client_mock->reveal();

        $sut = new CacheAuthApiDecorator($client, $pool);
        $result_token = $sut->getToken($username, $password, $refresh);

        $this->assertInstanceOf( AuthTokenInterface::class, $result_token);
        $this->assertEquals( $result_token->getContent(), $token_content);
    }


    /**
     * @dataProvider provideGetTokenArguments
     */
    public function testTokenIsNotInMockCache( string $username, string $password, bool $refresh) : void
    {
        $token_content = "foobar";
        $token_lifetime = 60;

        $token = new AuthToken($token_content, $token_lifetime);

        // Mock Cache pool and CacheItem which IS in cache
        $item_mock = $this->prophesize( CacheItemInterface::class );
        $item_mock->isHit()->willReturn( false );
        $item_mock->set( Argument::exact($token))->shouldBeCalled();
        $item_mock->expiresAfter( Argument::exact($token_lifetime))->shouldBeCalled();
        $item = $item_mock->reveal();

        $pool_mock = $this->prophesize(CacheItemPoolInterface::class);
        $pool_mock->deleteItem( Argument::type("string") )->shouldBeCalled();
        $pool_mock->getItem( Argument::type("string") )->willReturn( $item );
        $pool_mock->save( Argument::exact($item) )->shouldBeCalled();
        $pool = $pool_mock->reveal();

        $client_mock = $this->prophesize(AuthApiInterface::class);
        $client_mock->getToken(Argument::exact($username), Argument::exact($password), Argument::exact($refresh))->willReturn( $token );
        $client = $client_mock->reveal();

        $sut = new CacheAuthApiDecorator($client, $pool);
        $result_token = $sut->getToken($username, $password, $refresh);

        $this->assertInstanceOf( AuthTokenInterface::class, $result_token);
        $this->assertEquals( $result_token->getContent(), $token_content);
    }



    /**
     * @dataProvider provideGetTokenArguments
     */
    public function testTokenIsNotInSymfonyCache( string $username, string $password, bool $refresh) : void
    {
        $token_content = "foobar";
        $token_lifetime = 60;

        $token = new AuthToken($token_content, $token_lifetime);

        $this->symfony_cache->clear();

        $client_mock = $this->prophesize(AuthApiInterface::class);
        $client_mock->getToken(Argument::exact($username), Argument::exact($password), Argument::exact($refresh))->willReturn( $token );
        $client = $client_mock->reveal();

        $sut = new CacheAuthApiDecorator($client, $this->symfony_cache);
        $result_token = $sut->getToken($username, $password, $refresh);

        $this->assertInstanceOf( AuthTokenInterface::class, $result_token);
        $this->assertEquals( $result_token->getContent(), $token_content);
    }



    /**
     * @return mixed[]
     */
    public function provideGetTokenArguments() : array
    {
        return array(
            "Credentials and refresh" => [ "username", "password", true ],
            "Credentials, no refresh" => [ "username", "password", false ],
        );
    }

}
