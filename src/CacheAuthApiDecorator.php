<?php
namespace Germania\AuthApiClient;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CacheAuthApiDecorator extends AuthApiDecorator
{


    /**
     * Hash algorith to create a key with.
     * Defaults to "sha256" as it creates a hash of 64 chars length.
     *
     * @var string
     */
    protected $cache_key_hash_algo = "sha256";


    /**
     * @var CacheItemPoolInterface
     */
    protected $cache_itempool;



    /**
     * @param \Germania\AuthApiClient\AuthApiInterface  $client  AuthApi client decoratee
     * @param \Psr\Cache\CacheItemPoolInterface         $cache   PSR-6 Cache
     * @param \Psr\Log\LoggerInterface|null             $logger  Optional: PSR-3 Logger
     */
    public function __construct( AuthApiInterface $client, CacheItemPoolInterface $cache, LoggerInterface $logger = null )
    {
        parent::__construct( $client );
        $this->setCacheItemPool( $cache );
        $this->setLogger($logger ?: new NullLogger);
    }



    /**
     * @param \Psr\Cache\CacheItemPoolInterface $cache PSR-6 CacheItem Pool
     */
    public function setCacheItemPool( CacheItemPoolInterface $cache  ) : self
    {
        $this->cache_itempool = $cache;
        return $this;
    }



    /**
     * @inheritDoc
     *
     * Uses PSR-6 Cache to store tokens.
     */
    public function getToken(string $username, string $password, bool $refresh = false) : AuthTokenInterface
    {
        $cache_key = $this->makeCacheKey($username, $password );
        $cache_item = $this->cache_itempool->getItem($cache_key);


        if ($cache_item->isHit()) {
            $this->logger->log($this->success_loglevel, "AuthToken found in cache");
            $token = $cache_item->get();
            return $token;
        }

        $this->logger->debug("AuthToken not found or stale, delete cache item.");
        $this->cache_itempool->deleteItem($cache_key);

        $token = $this->client->getToken($username, $password, $refresh);

        $token_ttl = $token->getLifeTime();
        $this->logger->log($this->success_loglevel, "Store AuthToken in cache", [
            'ttl' => $token_ttl
        ]);

        $cache_item->set($token);
        $cache_item->expiresAfter($token_ttl);
        $this->cache_itempool->save($cache_item);

        return $token;
    }



    /**
     * Create a PSR-6 compliant cache key (hex characters).
     *
     * @param  string $username Username
     * @param  string $password Password
     * @return string
     */
    protected function makeCacheKey(string $username, string $password) : string
    {
        return hash($this->cache_key_hash_algo, $username . $password, false );
    }

}
