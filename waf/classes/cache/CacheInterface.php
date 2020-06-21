<?php
/**
 * @package    WAF
 * @subpackage WAF\Cache
 */
namespace WAF\Cache;

/**
 * Interface describing a in-memory caching mechanism
 *
 * PHP version 7.1
 *
 * @package Cache
 * @author  Ned Andonov <neoplovdiv@gmail.com>
 */

/**
 * Interface describing a in-memory caching mechanism
 *
 */
interface CacheInterface
{
    /**
     * Get method
     *
     * @param string $key
     * @return int
     */
    public function get($key);

    /**
     * Set method
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value);

    /**
     * Delete method
     *
     * @param string $key
     *
     * @return void
     */
    public function delete($key);
}