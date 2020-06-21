<?php
/**
 * @package    WAF
 * @subpackage WAF\Cache
 */
namespace WAF\Cache;

/**
 * APCU implementation
 *
 * PHP version 7.1
 *
 * @package Cache
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

/**
 * APCU implementation class
 *
 */
class Apcu implements CacheInterface
{
    /**
     * @var int 
     */
    protected $_ttl = 10000000;

    /**
     * Set the TTL for the record
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
        return apcu_fetch($key);
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value)
    {
        apcu_store($key, $value, $this->_ttl);
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        apcu_delete($key);
        return;
    }
}