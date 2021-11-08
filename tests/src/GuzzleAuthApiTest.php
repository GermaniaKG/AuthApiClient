<?php
namespace tests;

use Germania\AuthApiClient\{
    GuzzleAuthApi,
    AuthToken,
    Exceptions\AuthApiExceptionInterface,
    Exceptions\AuthApiRequestException,
    Exceptions\AuthApiResponseException
};

use GuzzleHttp\{
    Client,
    Exception\ClientException,
    Exception\RequestException,
    Psr7\Response
};

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;



class GuzzleAuthApiTest extends \PHPUnit\Framework\TestCase
{

    use ProphecyTrait;

	public $guzzle;

	public function setUp() : void
	{
		$this->guzzle = new Client([
		    'base_uri' => $GLOBALS['AUTH_API']
		]);

		parent::setUp();
	}




	/**
	 * @dataProvider provideValidCredentials
	 */
	public function testValidCredentials(string $user, string $pass) : void
	{
		$sut = new GuzzleAuthApi( $this->guzzle );
		$token = $sut->getToken( $user, $pass );
		$this->assertInstanceOf( AuthToken::class, $token );

		$token = $sut->getToken( $user, $pass, (bool) "refresh" );
		$this->assertInstanceOf( AuthToken::class, $token );
	}



    /**
     * @return string[]
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
		$sut = new GuzzleAuthApi( $this->guzzle );

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
		$client = $this->prophesize( Client::class );
		$exception = $this->prophesize( ClientException::class );
		$client->post( Argument::type("string"), Argument::type("array") )->willThrow( $exception->reveal() );

		// Create SUT
		$sut = new GuzzleAuthApi( $client->reveal() );

		$this->expectException( AuthApiRequestException::class );
		$this->expectException( AuthApiExceptionInterface::class );
		$sut->getToken( "foo", "bar" );

	}




	public function testExceptionOnRefresh() : void
	{
		// Mock Guzzle to throw RequestException
		$client = $this->prophesize( Client::class );
		$exception = $this->prophesize( RequestException::class );
		$client->get( Argument::type("string"), Argument::type("array") )->willThrow( $exception->reveal() );

		// Create SUT
		$sut = new GuzzleAuthApi( $client->reveal() );

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
	public function testInvalidTokenResponse( $invalid_response_body ) : void
	{
		// Mock response
		$response = new Response(200, array(), $invalid_response_body);

		// Mock Guzzle
		$client = $this->prophesize( Client::class );
		$client->post( Argument::type("string"), Argument::type("array") )->willReturn( $response );

		// Create SUT
		$sut = new GuzzleAuthApi( $client->reveal() );
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
            "Both token value and expires-in empty" => [ json_encode( array('access_token' => null,'expires_in'     => null)) ],
            "Token empty, but with expires-in"      => [ json_encode( array('access_token' => null,'expires_in'     => 100)) ]
		);
	}



}
