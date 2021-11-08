<img src="https://static.germania-kg.com/logos/ga-logo-2016-web.svgz" width="250px">

------


# AuthApi Client

**Service-side PHP client for retrieving an Auth token from Germania's Authentication API (AuthApi).**



## Installation

Since this a private package, add this to your **composer.json**

```json
{
  "require": {
    "germania-kg/authapi-client": "^2.0"
  }
}
```



## Interfaces and abstracts

### AuthApiInterface

The **AuthApiInterface** provides three public methods:

- **getToken** which requires *username* and *password* and returns an **AuthToken** instance, 
  optionally with long TTL (“refresh”)
- **login** which requires *username* and *password* and returns an **AuthToken** instance, usually with short TTL.
- **refresh** which accepts an **AuthToken** instance and returns a new **AuthToken** instance

```php
<?php
namespace Germania\AuthApiClient;

interface AuthApiInterface
{
    public function getToken(string $username, string $password, bool refresh = false) : AuthTokenInterface;
    public function login(string $username, string $password) : AuthTokenInterface;
    public function refresh(AuthTokenInterface $token) : AuthTokenInterface;
}

```



### AuthApiAbstract

Abstract class **AuthApiAbstract** implements *AuthApiInterface*, so you will have to bring the abstract methods to life. They use `Psr\Log\LoggerAwareTrait` and the `LoglevelTrait` that comes with this package. So with all instances extending from *AuthApiAbstract*, you can:

```php
<?php
class MyClient extends AuthApiAbstract {
  	// Implement abstract methods here
}

$my_client = new MyClient;

// LoggerAwareTrait
$my_client->setLogger( new Monolog );

// LoglevelTrait
$my_client->setErrorLoglevel( \Psr\Log\LogLevel::ERROR) );
$my_client->setSuccessLoglevel( \Psr\Log\LogLevel::INFO) );
$my_client->setErrorLoglevel("error");  
$my_client->setSuccessLoglevel("info");
```



---



## Implementations

### Using Guzzle

The **GuzzleAuthApi** client extends *AuthApiAbstract* and implements *AuthApiInterface*. It uses the *GuzzleHttp Client* to authenticate at Germania's *AuthApi*. The Guzzle client needs to be configured with the AuthAPI's `base_uri`.

```php
use Germania\AuthApiClient\GuzzleAuthApi;

// Setup dependencies
$guzzle = new \GuzzleHttp\Client(['base_uri' => "https://api.test.com/"]);

// Optional with PSR-3 Logger support.
$client = new GuzzleAuthApi( $guzzle);
$client = new GuzzleAuthApi( $guzzle, new Monolog);
```

**Set Guzzle Client**

```php
$guzzle = new \GuzzleHttp\Client( ... );
$client->setGuzzleClient($guzzle);  
```



### Using PSR-18 HTTP client

Class **HttpClientAuthApi** extends abstract *AuthApiDecorator* and thus also implements *AuthApiInterface*. 

The constructor requires the AuthApi *base URL* and a PSR-18 *client* instance. An optional *PSR-3 Logger* may be passed as third parameter.

```php
<?php
use Germania\AuthApiClient\HttpClientAuthApi;  

// Setup dependencies
$base_url = "https://api.test.com/";
$psr_18 = new \GuzzleHttp\Client;

// Optional with PSR-3 Logger support.
$client = new HttpClientAuthApi($base_url, $psr_18);
$client = new HttpClientAuthApi($base_url, $psr_18, new Monolog);
```

**Set base URL**

```php
$client->setBaseUrl("https://api.test.com/");
```

**Set PSR-18 HTTP client**

```php
use GuzzleHttp\Client as Guzzle;
$client->setHttpClient( new Guzzle );
```

**Set PSR-17 Request and Stream factory**

```php
use Nyholm\Psr7\Factory\Psr17Factory;
$client->setRequestFactory( new Psr17Factory );
$client->setStreamFactory( new Psr17Factory );
```





### PSR-6 Cache support

Class **CacheAuthApiDecorator** is a *PSR-6 Cache* decorator for any *AuthApiInterface* instance. It extends abstract *AuthApiDecorator* and thus also implements *AuthApiInterface*. 

The constructor requires *AuthApiInterface* instance and a *PSR-6 CacheItemPool*. An optional *PSR-3 Logger* may be passed as third parameter.

```php
use Germania\AuthApiClient\CacheAuthApiDecorator;
use Germania\AuthApiClient\GuzzleAuthApi;
use GuzzleHttp\Client as Guzzle;

// Setup dependencies
$cache = new \Symfony\Component\Cache\Adapter\FilesystemAdapter;
$inner = new GuzzleAuthApi( new Guzzle );

// Optional with PSR-3 Logger support.
$client = new CacheAuthApiDecorator($inner, $cache);
$client = new CacheAuthApiDecorator($inner, $cache, new Monolog);
```

**Set PSR-6 CacheItemPool**

```php
$cache = new \Symfony\Component\Cache\Adapter\FilesystemAdapter;
$client->setCacheItemPool($cache); 
```



---



## Usage Example

This usage example combines the above *AuthApi* clients.

**The AuthToken's life time is usually quite short;** to obtain one with longer TTL (i.e. *refresh* after *login*), pass a third boolean parameter to the *getToken* method. The new TTL is derived from the token lifetime.

```php
<?php
use Germania\AuthApiClient\CacheAuthApiDecorator;
use Germania\AuthApiClient\GuzzleAuthApi;

// Dependencies
$guzzle = new \GuzzleHttp\Client(['base_uri' => "https://api.test.com/");
$cache  = new \Symfony\Component\Cache\Adapter\FilesystemAdapter;
$logger = new \Monolog\Monolog( ... );
               
// AuthAPi clients                                  
$inner  = new GuzzleAuthApi( $guzzle, $logger);
$client = new CacheAuthApiDecorator($inner, $cache, $logger);

// Retrieve AuthToken, either short-living or long-TTL
$token = $client->getToken("username", "password");  
$token = $client->getToken("username", "password", (bool) "refresh");  

// Work with AuthToken
echo get_class($token);     // \Germania\AuthApiClient\AuthToken
echo $token;                // "somerandomstring"
echo $token->getContent();  // "somerandomstring"  
echo $token->getLifeTime(); // e.g. 60
```







## Exception handling

Any exception thrown by the *AuthApi* client implements **AuthApiExceptionInterface.** So catching `\Germania\AuthApiClient\Exceptions\AuthApiExceptionInterface` usually will be plenty.

Any request-related exception thrown by the *Guzzle* client will be wrapped up in a **AuthApiRequestException**. This exception class extends *\RuntimeException* and implements *AuthApiExceptionInterface*. 

When there's something wrong with the retrieved *AuthApi* response, an **AuthApiResponseException** will be thrown. This exception class extends *\UnexpectedValueException* and implements *AuthApiExceptionInterface*.

```php
<?php
  
use Germania\AuthApiClient\Exceptions\{
  AuthApiRequestException,
  AuthApiResponseException,
  AuthApiExceptionInterface  
};

try {
	$token = $client->getToken("username", "password");  
}
catch (AuthApiRequestException $e) {
  $guzzle_exception = $e->getPrevious(); // Possibly previous Guzzle Exception
}
catch (AuthApiResponseException $e) {
  $json_error = $e->getPrevious(); // Possibly previous JSON error  
  echo $e->getMessage(); // "Access token missing" (or sort of)
}
catch (AuthApiExceptionInterface $e) {
	echo $e->getMessage();
}
```





## The AuthToken

The **AuthToken** and **AuthTokenInterface** rely on Germania's [**germania-kg/token**](https://packagist.org/packages/germania-kg/token) package: 
https://packagist.org/packages/germania-kg/token

### Interface AuthTokenInterface

The `Germania\AuthApiClient\AuthTokenInterface` extends `Germania\Token\TokenInterface` and thus provides these methods inherited from *AuthTokenInterface:*

```php
interface AuthTokenInterface
{
    // Alias for "getContent"
    public function __toString();

    // Returns the token "text".
  	public function getContent() : string;

    // Returns the lifetime in seconds.
    public function getLifeTime() : int;
}
```



### Class AuthToken 

The `Germania\AuthApiClient\AuthToken` implements the `AuthTokenInterface`. It is an extension from `Germania\Token\Token`.

```php
<?php
use Germania\AuthApiClient\AuthToken;

// Pass token string and TTL
$auth_token = new AuthToken( "somerandomstring", 3600);

// Inherited from "Token" class
echo $auth_token;                // "somerandomstring"
echo $auth_token->__toString();  // "somerandomstring"  
echo $auth_token->getContent();  // "somerandomstring"  
echo $auth_token->getLifeTime(); // 3600
```



## Testing

Copy `phpunit.xml.dist` to `phpunit.xml` and fill in your credentials in the `<php>` section.

```bash
$ composer phpunit
```

