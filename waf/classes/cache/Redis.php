<?php
/**
 * @package    WAF
 * @subpackage WAF\Cache
 */
namespace WAF\Cache;

/**
 * Redis implementation via Predis package
 *
 * PHP version 7.1
 *
 * @package Cache
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

/**
 * Redis implementation class
 *
 */
class Redis implements CacheInterface
{
    /**
     * RedisClient instance
     */
    protected $_redis_client;

    public function __construct(\Predis\Client $_redis_client)
    {
        $this->_redis_client = $_redis_client;
    }

    /**
     * {@inheritDoc}
     */
    public function get($key)
    {
        return $this->_redis_client->get($key);
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value)
    {
        $this->_redis_client->set($key, $value, 'PX', 3600);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $this->_redis_client->del($key);
        return;
    }
}