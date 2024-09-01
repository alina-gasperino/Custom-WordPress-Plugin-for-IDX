<?php

namespace VestorFilter;

use Twilio\Rest\Client;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Onboard extends \VestorFilter\Util\Singleton {

	public static $instance = null;

	private static $sms_client = null;

	public function install() {

		add_action( 'vestorfilter_user_created', [ $this, 'send_welcome_email' ] );

		add_filter( 'cron_schedules', array( $this, 'digest_schedule' ) );
		add_action( 'vf_send_sms_digest', array( $this, 'send_digest' ) );
		
		if ( ! wp_next_scheduled( 'vf_send_sms_digest' ) ) {
			wp_schedule_event( time(), 'sms_digest_int', 'vf_send_sms_digest' );
		}

		add_action( 'vestorfilter_user_created', [ $this, 'setup_sms' ] );

	}

	public function digest_schedule( $schedules ) {

		$schedules['sms_digest_int'] = array(
			'interval' => 60 * 5,
			'display'  => esc_html__( 'Every 5 minutes' ), 
		);
		return $schedules;

	}

	public function send_welcome_email( $user ) {

		$email = self::make_welcome_email( $user );

		$email->send();

	}

	public static function make_welcome_email( $user ) {

		$email = new Email( 'welcome' );
		$email->set_var( 'header_title', 'Welcome' );
		$email->set_var( 'address', Settings::get( 'email_footer_address' ) );
		$email->set_var( 'footer_text', Settings::get( 'email_footer_text_welcome' ) );
		$email->set_var( 'unsubscribe_url', get_bloginfo( 'url' ) . '/favorites/' );
		$email->set_var( 'subject', 'I think you’ll like this.' );
		$email->set_var( 'to', $user->user_email );
		$email->set_var( 'name', $user->first_name ?: $user->display_name );

		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$image = wp_get_attachment_image_src( $custom_logo_id , 'brand-logo' );
			$email->set_var( 'logo', $image[0] );
		}

		return $email;

	}

	public static function init_twilio() {

		if ( ! defined( 'TWILIO_SID' ) || ! defined( 'TWILIO_TOKEN' ) ) {
			self::$sms_client = false;
			return;
		}

		if ( ! empty( self::$sms_client ) ) {
			return self::$sms_client;
		}

		require_once Plugin::$plugin_path . '/vendor/autoload.php';

		self::$sms_client = new Client( TWILIO_SID, TWILIO_TOKEN );

	}

	public static function send_onboard( $user_id, $step ) {

		$message = Settings::get( 'onboard_sms_' . $step );
		if ( empty( $message ) ) {
			return false;
		}
		$phone = get_user_meta( $user_id, '_twilio_number', true );
		if ( empty( $phone ) ) {
			return false;
		}

		$user = get_user_by( 'id', $user_id );

		$message = str_replace( '{NAME}', $user ? ($user->first_name ?: $user->display_name) : '', $message );

		return self::send_message( $message, $phone );

	}

	public static function send_message( $contents, $to ) {

		$twilio_sms = Settings::get( 'twilio_sms' );
		if ( empty( $twilio_sms ) && defined( 'TWILIO_NUMBER' ) ) {
			$twilio_sms = TWILIO_NUMBER;
		}
		if ( empty( $twilio_sms ) || empty( self::$sms_client ) ) {
			return;
		}

		$to = preg_replace( '/[^0-9]/', '', $to );
		if ( strlen( $to ) < 10 ) {
			return false;
		}
		if ( strlen( $to ) === 10 ) {
			$to = '1' . $to;
		}
		$to = '+' . $to;

		$signature = Settings::get( 'onboard_sms_signature' );
		if ( ! empty( $signature ) ) {
			$signature = "\n-" . $signature;
		}

		return self::$sms_client->messages->create(
			// Where to send a text message (your cell phone?)
			$to,
			array(
				'from' => $twilio_sms,
				'body' => $contents . $signature,
			)
		);

	}

	public static function get_messages( $since = null ) {

		if ( ! defined( 'TWILIO_NUMBER' ) || empty( self::$sms_client ) ) {
			return;
		}

		$params = [
			'to' => TWILIO_NUMBER,
		];

		if ( $since ) {
			$params['dateSent'] = new \DateTime( date( 'c', $since ) );
		}

		$fetched = self::$sms_client->messages->read($params, 1000);
		$compiled = [];
		foreach( $fetched as $message ) {
			if ( ! isset( $compiled[ $message->from ] ) ) {
				$compiled[ $message->from ] = [];
			}
			$sent = $message->dateSent->format( 'U' );
			$message->dateSent->setTimezone( new \DateTimeZone( get_option('timezone_string') ) );
			$compiled[ $message->from ][ $sent ] = [
				'body' => $message->body,
				'sent' => $message->dateSent->format( 'c' ),
			];

		}

		foreach( $compiled as $number => $formatted ) {
			$users = get_users(array(
				'meta_key' => '_twilio_number',
				'meta_value' => $number,
			) );
			foreach( $users as $user ) {
				$messages = get_user_meta( $user->ID, '_twilio_messages', true ) ?: [];
				$messages = $messages + $formatted;
				update_user_meta( $user->ID, '_twilio_messages', $messages );
				foreach( $messages as $message ) {
					if ( strpos( $message['body'], 'STOP' ) !== false ) {
						delete_user_meta( $user->ID, '_onboard_step' );
						add_user_meta( $user->ID, '_sms_blocked', 'yes' );
						delete_user_meta( $user->ID, '_sms_consent', 'yes' );
					}
				}
			}
			
		}

		update_option( '_last_fetched_twilio', time() );

		return $compiled;

	}

	public static function get_sms_digest_email( $all_messages ) {

		$to = Settings::get( 'onboard_forward_email' );
		if ( empty( $to ) ) {
			return;
		}

		$email = new Email( 'messages' );
		$email->set_var( 'header_title', 'SMS Replies' );
		$email->set_var( 'subject', 'SMS Replies' );
		$email->set_var( 'to', $to );

		foreach( $all_messages as $from => $messages ) {

			$users = get_users( [
				'meta_key' => '_twilio_number',
				'meta_value' => $from,
			] );
			$user = ! empty( $users ) ? reset( $users ) : null;

			$contents = [];
			if ( $user ) {
				$contents[] = '<a href="' . add_query_arg( [ 'page' => 'leads', 'user' => $user->ID ], admin_url( 'admin.php' ) ) . '">View User in Agent Dashboard</a>';
			}
			foreach( $messages as $message ) {
				$date = new \DateTime( $message['sent'] );
				$contents[] = '<strong>' . $date->format( 'F d, Y - h:i:s a') . "</strong>\n" . $message['body'];
			}

			$email->add_section( [
				'title'      => $user ? $from . ' - ' . $user->user_email : $from,
				'content'    => implode( "\n\n", $contents ),
			] );

		}

		return $email;

	}

	public function send_digest() {

		self::init_twilio();

		$since = get_option( '_last_fetched_twilio', 0 );
		$messages = self::get_messages( $since );
		if ( ! empty( $messages ) ) {
			$email = self::get_sms_digest_email( $messages );
			$email->send();
		}

		if ( ! wp_next_scheduled( 'vf_send_sms_digest' ) ) {
			wp_schedule_event( time(), 'sms_digest_int', 'vf_send_sms_digest' );
		}

	}

	public function setup_sms( $user ) {

		$phone = get_user_meta( $user->ID, '_twilio_number', true );
		$consent = get_user_meta( $user->ID, '_sms_consent', true );
		if( empty( $consent ) || $consent[0] !== 'yes' ) {
			return;
		}
		
		if ( empty( $phone ) ) {
			return;
		}

		update_user_meta( $user->ID, '_onboard_step', 'ready' );

		$now = new \DateTime( "now", new \DateTimeZone( get_option('timezone_string') ) );
		if ( $now->format('H') >= 22 ) {
			wp_schedule_single_event( strtotime( '8:00 am tomorrow UTC' ) - $now->getOffset(), 'vf_send_next_sms', [ $user->ID, 0 ] );
			//wp_schedule_single_event( strtotime( '8:10 am tomorrow' ) + $now->getOffset(), 'vf_send_next_sms', [ 'user' => $user->ID ] );
		} else if ( $now->format('H') < 8 ) {
			wp_schedule_single_event( strtotime( '8:00 am UTC' ) - $now->getOffset(), 'vf_send_next_sms', [ $user->ID, 0 ] );
			//wp_schedule_single_event( strtotime( '8:10 am' ) + $now->getOffset(), 'vf_send_next_sms', [ 'user' => $user->ID ] );
		} else {
			wp_schedule_single_event( time() + 180, 'vf_send_next_sms', [ $user->ID, 0 ] );
			//wp_schedule_single_event( time() + 600, 'vf_send_next_sms', [ 'user' => $user->ID ] );
		}

		//wp_schedule_single_event( time() + 180, 'vf_send_next_sms', [ 'user' => $user->ID ] );
		//wp_schedule_single_event( time() + 600, 'vf_send_next_sms', [ 'user' => $user->ID ] );
		

	}

	public function send_next_sms( $user_id ) {
		
		if ( empty( $user_id ) ) {
			return;
		}

		$consent = get_user_meta( $user_id, '_sms_consent', true );
		if( empty( $consent ) || $consent[0] !== 'yes' ) {
			return;
		}

		$blocked = get_user_meta( $user_id, '_sms_blocked', true );
		if ( ! empty( $blocked ) ) {
			return;
		}

		$lead_status = strtolower( get_user_meta( $user_id, '_lead_tag', true ) );
		if ( $lead_status === 'hot' || $lead_status === 'warm' ) {
			update_user_meta( $user_id, '_onboard_step', 5 );
			return;
		}

		$phone = get_user_meta( $user_id, '_twilio_number', true );
		if ( empty( $phone ) ) {
			delete_user_meta( $user_id, '_onboard_step' );
			return;
		}

		$level = get_user_meta( $user_id, '_onboard_step', true );
		
		if ( empty( $level ) ) {
			
			return;
		}

		$level = $level === 'ready' ? 1 : absint( $level ) + 1;
		if ( $level > 4 ) {
			delete_user_meta( $user_id, '_onboard_step' );
			return;
		}
		
		\VestorFilter\Onboard::init_twilio();
		$response = \VestorFilter\Onboard::send_onboard( $user_id, $level );

		update_user_meta( $user_id, '_onboard_step', $level );

		if ( $level === 1 ) {
			wp_schedule_single_event( time() + 600, 'vf_send_next_sms', [ $user_id, $level ] );
		}
		if ( $level === 2 ) {
			wp_schedule_single_event( time() + 3*24*3600, 'vf_send_next_sms', [ $user_id, $level ] );
		}
		if ( $level === 3 ) {
			wp_schedule_single_event( time() + 4*24*3600, 'vf_send_next_sms', [ $user_id, $level ] );
		}

	}

}

add_action( 'vf_send_next_sms', '\VestorFilter\send_next_onboard_sms' );
function send_next_onboard_sms( $user ) {

	//var_dump( $args );
	//echo 'hi mom';
	//exit;

	\VestorFilter\Onboard::$instance->send_next_sms( $user );

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Onboard', 'init' ) );
