<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Email {

	private $contents = '';
	private $template = null;
	private $vars = [];
	private $headers = [];
	private $tags = [];

	public function __construct( $template_name = null ) {

		if ( ! empty( $template_name ) ) {
			$this->template = "emails/{$template_name}";
		}

		$from = Settings::get( 'email_from_email' ) ?: get_bloginfo( 'admin_email' );
		$name = Settings::get( 'email_from_name' );
		if ( ! empty( $name ) ) {
			$from = "$name <$from>";
		}

		$this->headers['Content-Type'] = 'text/html; charset=UTF-8';
		$this->headers['From']         = $from;
		$this->headers['Reply-To']     = Settings::get( 'email_replyto' ) ?: get_bloginfo( 'admin_email' );
		

	}

	public function set_template( $template_name ) {

		$this->template = "emails/{$template_name}";

	}

	public function add_section( $contents = [] ) {

		ob_start();

		\VestorFilter\Util\Template::get_part( 'vestorfilter', 'emails/content-section', $contents );

		$this->contents .= ob_get_clean();

	}

	public function set_var( $key, $value ) {
		$this->vars[ $key ] = $value;
	}

	public function set_tags( $tags ) {

		$this->tags = $this->tags + $tags;

	}

	public function set_headers( $values ) {

		foreach( $values as $key => $value ) {
			$this->headers[ $key ] = $value;
		}

	}

	public function send() {

		if ( empty( $this->vars['to'] ) || empty( $this->vars['subject'] ) ) {
			return false;
		}

		$headers = '';
		foreach( $this->headers as $key => $value ) {
			$headers .= "$key: $value\r\n";
		}
        //echo $this->get_html();
        //This log each email address alert is sent to
		$log_file = __DIR__.'/sent.txt';
        $log_load = file_get_contents($log_file);
        $log_time = (new \DateTime('now'))->format('H:i:s d-m-Y');
        $log_data = $log_load."$log_time / {$this->vars['to']}\n";
        $log_save = file_put_contents($log_file, $log_data);

        //echo $log_data;
        //return $log_save;
        return wp_mail( $this->vars['to'], $this->vars['subject'], $this->get_html(), $headers );
//        return wp_mail( "artesym@gmail.com", $this->vars['subject'], $this->get_html(), $headers );
//        return false;

	}

	public function get_html() {

		ob_start();

		$vars = $this->vars;
		$vars['body_contents'] = $this->contents;

		\VestorFilter\Util\Template::get_part( 'vestorfilter', 'emails/header', $vars );

		if ( ! empty( $this->template ) ) {
			\VestorFilter\Util\Template::get_part( 'vestorfilter', $this->template, $vars );
		} else {
			echo $this->contents;
		}

		\VestorFilter\Util\Template::get_part( 'vestorfilter', 'emails/footer', $vars );

		$output = ob_get_clean();

		
		foreach( $this->tags as $key => $value ) {
			$output = str_ireplace( '{{' . $key . '}}', $value, $output );
		}

		return $output;

	}

}
