<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class RETS extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	public function install() {


	}


}

add_action( 'vestorfilter_installed', array( 'VestorFilter\RETS', 'init' ) );
