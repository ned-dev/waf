<?php
/**
 * @package    WAF
 * @subpackage WAF\Cache
 */
namespace WAF\Cache;

/**
 * APC implementation
 *
 * PHP version 7.1
 *
 * @package Cache
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

/**
 * APC implementation class
 *
 */
class Apc implements CacheInterface
{
    /**
     * @var int 
     */
    protected $_ttl = 10000000;

    /**
     * Set the ttl
     *
     * @param int $microseconds
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
        return apc_fetch($key);
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value)
    {
        apc_store($key, $value, $this->_ttl);
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        apc_delete($key);
        return;
    }
}