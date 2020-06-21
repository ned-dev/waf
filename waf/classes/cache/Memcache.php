<?php
/**
 * @package    WAF
 * @subpackage WAF\Cache
 */
namespace WAF\Cache;

/**
 * Memcache implementation
 *
 * PHP version 7.1
 *
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

/**
 * Memcache implementation class
 *
 */
class MemcacheInterface implements CacheInterface
{
    /**
     * Memcached instance
     */
    protected $_memcache;

    /**
     * @var int
     */
    protected $_ttl = 10000000;

    /**
     * Constructor
     *
     */
    public function __construct(\Memcache $memcache)
    {
        $this->_memcache = $memcache;
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
        return $this->_memcache->get($key);
    }

    /**
     * Set method
     *
     * @param string $key   Key
     * @param mixed  $value Value
     */
    public function set($key, $value)
    {
        $this->_memcache->set($key, $value, 0, $this->_ttl);
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
        $this->_memcache->increment($key, $value);
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $this->_memcache->delete($key);
        return;
    }
}