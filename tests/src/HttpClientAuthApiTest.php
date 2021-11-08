<?php
namespace tests;

use Germania\AuthApiClient\{
    HttpClientAuthApi,
    AuthToken,
    Exceptions\AuthApiExceptionInterface,
    Exceptions\AuthApiRequestException,
    Exceptions\AuthApiResponseException
};

use Nyholm\Psr7\Factory\Psr17Factory;

use Psr\Http\{
    Message\RequestInterface,
    Client\ClientInterface,
    Client\ClientExceptionInterface,
};

use GuzzleHttp\{
    Client,
    Psr7\Response,
    Psr7\HttpFactory,

};

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;



class HttpClientAuthApiTest extends \PHPUnit\Framework\TestCase
{

    use ProphecyTrait;

    /**
     * @var \Nyholm\Psr7\Factory\Psr17Factory
     */
	public $http_factory;


    /**
     * @var \Psr\Http\Client\ClientInterface
     */
    public $psr18_client;


    /**
     * @var string
     */
    public $base_uri;


	public function setUp() : void
	{

        $this->base_uri = $GLOBALS['AUTH_API'];

        $this->http_factory = new Psr17Factory;
		$this->psr18_client = new Client();

		parent::setUp();
	}




	/**
	 * @dataProvider provideValidCredentials
	 */
	public function testValidCredentials(string $user, string $pass) : void
	{
		$sut = new HttpClientAuthApi( $this->base_uri, $this->psr18_client);

		$token = $sut->getToken( $user, $pass );
		$this->assertInstanceOf( AuthToken::class, $token );

		$token = $sut->getToken( $user, $pass, (bool) "refresh" );
		$this->assertInstanceOf( AuthToken::class, $token );
	}



    /**
     * @return array<int, array<int, mixed>>
     */
	public function provideValidCredentials() : array
	{
		return array(
			'Credentials from $GLOBALS' => [ $GLOBALS['AUTH_USER'], $GLOBALS['AUTH_PASS'] ]
		);
	}



	/**
	 * @dataProvider provideInvalidCredentials
	 */
	public function testInvalidCredentials(string $user, string $pass) : void
	{
		$sut = new HttpClientAuthApi( $this->base_uri, $this->psr18_client);

		$this->expectException( AuthApiRequestException::class );
		$this->expectException( AuthApiExceptionInterface::class );
		$sut->getToken( $user, $pass );
	}


    /**
     * @return string[]
     */
	public function provideInvalidCredentials() : array
	{
		return array(
			"Not-existant user" => [ "not", "a user" ]
		);
	}





	public function testExceptions() : void
	{
		// Mock Guzzle
		$client = $this->prophesize( ClientInterface::class );
		$exception = $this->prophesize( ClientExceptionInterface::class );
		$client->sendRequest( Argument::type(RequestInterface::class) )->willThrow( $exception->reveal() );

		// Create SUT
		$sut = new HttpClientAuthApi( $this->base_uri, $client->reveal() );

		$this->expectException( AuthApiRequestException::class );
		$this->expectException( AuthApiExceptionInterface::class );
		$sut->getToken( "foo", "bar" );

	}




	public function testExceptionOnRefresh() : void
	{
		// Mock PSR-18 client to throw ClientException
		$client = $this->prophesize( Client::class );
		$exception = $this->prophesize( ClientExceptionInterface::class );
		$client->sendRequest( Argument::type(RequestInterface::class) )->willThrow( $exception->reveal() );

		// Create SUT
		$sut = new HttpClientAuthApi( $this->base_uri, $client->reveal() );

		$token = $this->prophesize( AuthToken::class );
		$token->getContent()->willReturn( "abc" );
		$token_stub = $token->reveal();

		// Now go
		$this->expectException( AuthApiRequestException::class );
		$this->expectException( AuthApiExceptionInterface::class );
		$sut->refresh( $token_stub );

	}




	/**
	 * @dataProvider provideInvalidTokenStuff
     * @param  mixed $invalid_response_body
	 */
	public function testInvalidTokenResponse( $invalid_response_body )
	{
		// Mock response
		$response = new Response(200, array(), $invalid_response_body);

		// Mock Guzzle
		$client = $this->prophesize( ClientInterface::class );
		$client->sendRequest( Argument::type(RequestInterface::class) )->willReturn( $response );

		// Create SUT
		$sut = new HttpClientAuthApi( $this->base_uri, $client->reveal() );
		$this->expectException( AuthApiResponseException::class );
		$this->expectException( AuthApiExceptionInterface::class );
		$sut->getToken( "foo", "bar" );
	}


    /**
     * @return mixed[]
     */
	public function provideInvalidTokenStuff() : array
	{
		return array(
			"Just a string"                         => [ "Foo bar" ],
			"Bool value"                            => [ false ],
			"Token value, but no expires-in"        => [ json_encode( array('access_token' => "foobar",'expires_in' => null)) ],
			"Both token value and expires-in empty" => [ json_encode( array('access_token' => null,'expires_in' => null)) ],
			"Token empty, but with expires-in"      => [ json_encode( array('access_token' => null,'expires_in' => 100)) ]
		);
	}



}
