<?php
/**
 * @package    WAF
 * @subpackage WAF\Cache
 */
namespace WAF\Cache;

/**
 * Memcached implementation
 *
 * PHP version 7.1
 *
 * @package Cache
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

/**
 * Memcached implementation class
 *
 */
class MemcachedInterface implements CacheInterface
{
    /**
     * Memcached instance
     */
    protected $_memcached;

    /**
     * @var int
     */
    protected $_ttl = 10000000;

    /**
     * Constructor
     *
     */
    public function __construct(\Memcached $memcached)
    {
        $this->_memcached = $memcached;
    }

    /**
     * Set the TTL
     *
     * @param int $microseconds
     *
     * @return void
     */
    public function setTtl($microseconds)
    {
        $this->_ttl = $microseconds;
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function get($key)
    {
        return $this->_memcached->get($key);
    }

    /**
     * Set method
     *
     * @param string $key    Key
     * @param mixed  $value  Value
     */
    public function set($key, $value)
    {
        $this->_memcached->set($key, $value, $this->_ttl);
        return;
    }

    /**
     * Memcache increment method
     *
     * @param string $key
     * @param mixed $value
     */
    public function increment($key, $value)
    {
        $this->_memcached->increment($key, $value);
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $this->_memcached->delete($key);
        return;
    }
}