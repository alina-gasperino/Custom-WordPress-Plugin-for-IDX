<?php

namespace VestorFilter\Hooks;

use \DrewM\MailChimp\MailChimp as MailChimp;

class MC extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	public function install() {

		add_action( 'vestorfilter_user_created', [ $this, 'subscribe_user' ] );

	}

	public function subscribe_user( $user ) {

		$api_key = \VestorFilter\Settings::get( 'mailchimp_api' );
		$list_id = \VestorFilter\Settings::get( 'mailchimp_listid' );

		if ( empty( $api_key ) || empty( $list_id ) ) {
			return;
		}
		
		include __DIR__ . '/../vendor/autoload.php';
		$MailChimp = new MailChimp( $api_key );

		$results = $MailChimp->post( "lists/$list_id/members", [
			'email_address' => $user->user_email,
			'status'        => 'subscribed',
		] );

		$subscriber_hash = MailChimp::subscriberHash( $user->user_email );

		$fields = [ 'SOURCE' => 'VestorFilter Website' ];
		if ( ! empty( $user->first_name ) ) {
			$fields['FNAME'] = $user->first_name;
		}
		if ( ! empty( $user->last_name ) ) {
			$fields['LNAME'] = $user->last_name;
		}

		$result = $MailChimp->patch( "lists/$list_id/members/$subscriber_hash", [
			'merge_fields' => $fields,
		] );

	}

}

add_action( 'vestorfilter_installed', [ 'VestorFilter\Hooks\MC', 'init' ] );
