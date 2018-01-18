<?php
/**
 * LiteSpeed Object Cache Library
 *
 * @since  1.8
 */

if ( ! defined( 'WPINC' ) ) {
	die ;
}

require_once dirname( __FILE__ ) . '/object.class.php' ;

/**
 * Sets up Object Cache Global and assigns it.
 *
 * @since 1.8
 */
function wp_cache_init()
{
	$GLOBALS['wp_object_cache'] = WP_Object_Cache::get_instance();
}

/**
 * Retrieves the cache contents from the cache by key and group.
 *
 * @since 1.8
 */
function wp_cache_get( $key, $group = '', $force = false, &$found = null )
{
	global $wp_object_cache ;

	return $wp_object_cache->get( $key, $group, $force, $found ) ;
}

/**
 * Saves the data to the cache.
 *
 * @since 1.8
 */
function wp_cache_set( $key, $data, $group = '', $expire = 0 )
{
	global $wp_object_cache ;

	return $wp_object_cache->set( $key, $data, $group, $expire ) ;
}

/**
 * Adds data to the cache, if the cache key doesn't already exist.
 *
 * @since 1.8
 */
function wp_cache_add( $key, $data, $group = '', $expire = 0 )
{
	global $wp_object_cache ;

	return $wp_object_cache->add( $key, $data, $group, $expire ) ;
}

/**
 * Replaces the contents of the cache with new data.
 *
 * @since 1.8
 */
function wp_cache_replace( $key, $data, $group = '', $expire = 0 )
{
	global $wp_object_cache ;

	return $wp_object_cache->replace( $key, $data, $group, $expire ) ;
}

/**
 * Increment numeric cache item's value
 *
 * @since 1.8
 */
function wp_cache_incr( $key, $offset = 1, $group = '' )
{
	global $wp_object_cache ;

	return $wp_object_cache->incr_desr( $key, $offset, $group ) ;
}

/**
 * Decrements numeric cache item's value.
 *
 * @since 1.8
 */
function wp_cache_decr( $key, $offset = 1, $group = '' )
{
	global $wp_object_cache ;

	return $wp_object_cache->incr_desr( $key, $offset, $group, false ) ;
}

/**
 * Removes the cache contents matching key and group.
 *
 * @since 1.8
 */
function wp_cache_delete( $key, $group = '' )
{
	global $wp_object_cache ;

	return $wp_object_cache->delete( $key, $group ) ;
}

/**
 * Removes all cache items.
 *
 * @since 1.8
 */
function wp_cache_flush()
{
	global $wp_object_cache ;

	return $wp_object_cache->flush() ;
}

/**
 * Adds a group or set of groups to the list of global groups.
 *
 * @since 1.8
 */
function wp_cache_add_global_groups( $groups )
{
	global $wp_object_cache ;

	$wp_object_cache->add_global_groups( $groups ) ;
}

/**
 * Adds a group or set of groups to the list of non-persistent groups.
 *
 * @since 1.8
 */
function wp_cache_add_non_persistent_groups( $groups )
{
	global $wp_object_cache ;

	$wp_object_cache->add_non_persistent_groups( $groups ) ;
}

/**
 * Switches the internal blog ID.
 *
 * This changes the blog id used to create keys in blog specific groups.
 *
 * @since 1.8
 *
 * @see WP_Object_Cache::switch_to_blog()
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int $blog_id Site ID.
 */
function wp_cache_switch_to_blog( $blog_id ) {
	global $wp_object_cache ;

	$wp_object_cache->switch_to_blog( $blog_id ) ;
}

/**
 * Closes the cache.
 *
 * @since 1.8
 */
function wp_cache_close()
{
	return true ;
}



class WP_Object_Cache
{
	private static $_instance ;

	private $_object_cache ;

	private $_cache = array() ;
	private $_cache_404 = array() ;

	private $cache_total = 0 ;
	private $count_hit_incall = 0 ;
	private $count_hit = 0 ;
	private $count_miss_incall = 0 ;
	private $count_miss = 0 ;
	private $count_set = 0 ;

	private $blog_prefix ;

	/**
	 * Init
	 *
	 * @since  1.8
	 * @access private
	 */
	private function __construct()
	{
		$this->_object_cache = LiteSpeed_Cache_Object::get_instance() ;

		$this->multisite = is_multisite() ;
		$this->blog_prefix = $this->multisite ? get_current_blog_id() . ':' : '' ;

	}

	/**
	 * Output debug info
	 *
	 * @since  1.8
	 * @access public
	 */
	public function debug()
	{
		$log = ' [total] ' . $this->cache_total
			. ' [hit_incall] ' . $this->count_hit_incall
			. ' [hit] ' . $this->count_hit
			. ' [miss_incall] ' . $this->count_miss_incall
			. ' [miss] ' . $this->count_miss
			. ' [set] ' . $this->count_set ;

		return $log ;
	}

	/**
	 * Get from cache
	 *
	 * @since  1.8
	 * @access public
	 */
	public function get( $key, $group = 'default', $force = false, &$found = null )
	{
		$final_key = $this->_key( $key, $group ) ;
// error_log('');
// error_log("oc: get \t\t\t[key] " . $final_key . ( $force ? "\t\t\t [forced] " : '' ) );
		$found = false ;
		$found_in_oc = false ;
		$cache_val = false ;
		if ( array_key_exists( $final_key, $this->_cache ) && ! $force ) {
			$found = true ;
			$cache_val = $this->_cache[ $final_key ] ;
			$this->count_hit_incall ++ ;
		}
		elseif ( ! array_key_exists( $final_key, $this->_cache_404 ) && ! $this->_object_cache->is_non_persistent( $group ) ) {
			$v = $this->_object_cache->get( $final_key ) ;

			// To be compatible with false val
			if ( is_array( $v ) && array_key_exists( 'data', $v ) ) {
				$this->count_hit ++ ;
				$found = true ;
				$found_in_oc = true ;
				$cache_val = $v[ 'data' ] ;
			}
			else { // Can't find key, cache it to 404
// error_log("oc: add404\t\t\t[key] " . $final_key ) ;
				$this->_cache_404[ $final_key ] = 1 ;
				$this->count_miss ++ ;
			}
		}
		else {
			$this->count_miss_incall ++ ;
		}

		if ( is_object( $cache_val ) ) {
			$cache_val = clone $cache_val ;
		}

		if ( $found_in_oc ) {
			$this->_cache[ $final_key ] = $cache_val ;
		}

		$this->cache_total ++ ;

		return $cache_val ;
	}

	/**
	 * Set to cache
	 *
	 * @since  1.8
	 * @access public
	 */
	public function set( $key, $data, $group = 'default', $expire = 0 )
	{
		$final_key = $this->_key( $key, $group ) ;

		if ( is_object( $data ) ) {
			$data = clone $data ;
		}
// error_log("oc: set \t\t\t[key] " . $final_key ) ;
		$this->_cache[ $final_key ] = $data ;

		if( array_key_exists( $final_key, $this->_cache_404 ) ) {
// error_log("oc: unset404\t\t\t[key] " . $final_key ) ;
			unset( $this->_cache_404[ $final_key ] ) ;
		}

		if ( ! $this->_object_cache->is_non_persistent( $group ) ) {
			$this->_object_cache->set( $final_key, array( 'data' => $data ), $expire ) ;
			$this->count_set ++ ;
		}

		return true ;
	}

	/**
	 * Adds data to the cache if it doesn't already exist.
	 *
	 * @since 1.8
	 * @access public
	 */
	public function add( $key, $data, $group = 'default', $expire = 0 )
	{
		if ( wp_suspend_cache_addition() ) {
			return false ;
		}

		$final_key = $this->_key( $key, $group ) ;

		if ( array_key_exists( $final_key, $this->_cache ) ) {
			return false ;
		}

		return $this->set( $key, $data, $group, $expire ) ;
	}

	/**
	 * Replace cache if the cache key exists.
	 *
	 * @since 1.8
	 * @access public
	 */
	public function replace( $key, $data, $group = 'default', $expire = 0 )
	{
		$final_key = $this->_key( $key, $group ) ;

		if ( ! array_key_exists( $final_key, $this->_cache ) ) {
			return false ;
		}

		return $this->set( $key, $data, $group, $expire ) ;
	}

	/**
	 * Increments numeric cache item's value.
	 *
	 * @since 1.8
	 * @access public
	 */
	public function incr_desr( $key, $offset = 1, $group = 'default', $incr = true )
	{
		$cache_val = $this->get( $key, $group ) ;

		if ( $cache_val === false ) {
			return false ;
		}

		if ( ! is_numeric( $cache_val ) ) {
			$cache_val = 0 ;
		}

		$offset = (int) $offset ;

		if ( $incr ) {
			$cache_val += $offset ;
		}
		else {
			$cache_val -= $offset ;
		}

		if ( $cache_val < 0 ) {
			$cache_val = 0 ;
		}

		$this->set( $key, $cache_val, $group ) ;

		return $cache_val ;
	}

	/**
	 * Delete cache
	 *
	 * @since 1.8
	 * @access public
	 */
	public function delete( $key, $group = 'default' )
	{

		$final_key = $this->_key( $key, $group ) ;

		if ( ! array_key_exists( $final_key, $this->_cache ) ) {
			return false ;
		}
// error_log("oc: delete \t\t\t[key] " . $final_key ) ;
		unset( $this->_cache[ $final_key ] ) ;

		if ( $this->_object_cache->is_non_persistent( $group ) ) {
			return false ;
		}

		return $this->_object_cache->delete( $final_key ) ;
	}

	/**
	 * Clear all cached data
	 *
	 * @since 1.8
	 * @access public
	 */
	public function flush()
	{
		$this->_cache = array() ;
		$this->_cache_404 = array() ;
// error_log("oc: flush " ) ;

		$this->_object_cache->flush() ;

		return true ;
	}

	/**
	 * Add global groups
	 *
	 * @since 1.8
	 * @access public
	 */
	public function add_global_groups( $groups )
	{
		$this->_object_cache->add_global_groups( $groups ) ;
	}

	/**
	 * Add non persistent groups
	 *
	 * @since 1.8
	 * @access public
	 */
	public function add_non_persistent_groups( $groups )
	{
		$this->_object_cache->add_non_persistent_groups( $groups ) ;
	}

	/**
	 * Get the final key
	 *
	 * @since 1.8
	 * @access private
	 */
	private function _key( $key, $group = 'default' )
	{
		$prefix = $this->_object_cache->is_global( $group ) ? '' : $this->blog_prefix ;

		return $prefix . $group . '.' . $key ;
	}

	/**
	 * Switches the internal blog ID.
	 *
	 * This changes the blog ID used to create keys in blog specific groups.
	 *
	 * @since 1.8
	 *
	 * @param int $blog_id Blog ID.
	 */
	public function switch_to_blog( $blog_id ) {
		$blog_id = (int) $blog_id ;
		$this->blog_prefix = $this->multisite ? $blog_id . ':' : '' ;
	}

	/**
	 * Get the current instance object.
	 *
	 * @since 1.8
	 * @access public
	 * @return Current class instance.
	 */
	public static function get_instance()
	{
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self() ;
		}

		return self::$_instance ;
	}
}