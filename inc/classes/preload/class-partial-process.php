<?php
namespace WP_Rocket\Preload;

/**
 * Extends the background process class for the partial preload background process.
 *
 * @since 3.2
 * @author Remy Perona
 *
 * @see WP_Background_Process
 */
class Partial_Process extends Process {

	/**
	 * Specific action identifier for partial preload
	 *
	 * @since 3.2
	 * @var string
	 */
	protected $action = 'partial_preload';

	/**
	 * Preload the URL provided by $item.
	 *
	 * @since  3.2
	 * @since  3.6 $item can be an array.
	 * @author Remy Perona
	 *
	 * @param  array|string $item {
	 *     The item to preload: an array containing the following values.
	 *     A string is allowed for backward compatibility (for the URL).
	 *
	 *     @type string $url    The URL to preload.
	 *     @type bool   $mobile True when we want to send a "mobile" user agent with the request. Optional.
	 * }
	 * @return bool False.
	 */
	protected function task( $item ) {
		return $this->maybe_preload( $item );
	}
}
