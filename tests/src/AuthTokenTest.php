<?php
namespace tests;

use Germania\Token\TokenInterface;
use Germania\AuthApiClient\AuthToken;
use Germania\AuthApiClient\AuthTokenInterface;

class AuthTokenTest extends \PHPUnit\Framework\TestCase
{

	/**
	 * @dataProvider provideTokenData
	 */
	public function testSimple( string $content, int $lifetime ) : void
	{

		$sut = new AuthToken($content, $lifetime);
        $this->assertInstanceOf(AuthTokenInterface::class, $sut);
        $this->assertInstanceOf(TokenInterface::class, $sut);

		$this->assertEquals( $content,  $sut->getContent() );
		$this->assertEquals( $content,  $sut->__toString() );
		$this->assertEquals( $lifetime, $sut->getLifeTime() );
	}

	/**
	 * @dataProvider provideTokenData
	 */
	public function testDebugInfo( string $content, int $lifetime  ) : void
	{
		$sut = new AuthToken($content, $lifetime);
		$di = $sut->__debugInfo();
		$this->assertIsArray($di);
		$this->assertArrayHasKey("content", $di);
		$this->assertArrayHasKey("lifetime", $di);
	}



    /**
     * @return mixed[]
     */
	public function provideTokenData() : array
	{
		return array(
			"Just 'foo' and lifetime" => [ "foo", 200 ],
		);
	}
}
