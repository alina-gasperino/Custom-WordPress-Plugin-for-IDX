<?php

namespace VestorFilter\Util;

use PlaidPowered\Util as Util;
use \Exception;

class CLI {

	public $args = [];

	public $loop_func = [];
	public $setup_func = [];
	public $end_func = [];

	public $loop_env = [];
	
	private $total, $index, $current_page = 0, $page_size = 1000, $output = true;
	private $email_to = null;

	private $final_output = '';

	public function __construct( $args ) {

		if ( count( $args ) < 2 ) {
			// show helper flags
			return;
		}

		for( $i = 1; $i < count( $args ); $i++ ) {
			$arg = explode( '=', $args[$i], 2 );
			$key = trim( $arg[0], '-' );
			if ( count( $arg ) === 1 ) {
				$value = $key;
			} else {
				$value = trim( $arg[1], "'\" " );
				$value = stripslashes( $value );
			}
			$this->args[ $key ] = $value;
		}

		if ( ! empty( $this->args['email'] ) ) {
			$this->output = false;
			$this->email_to = sanitize_email( $this->args['email'] );
		}
		if ( ! empty( $this->args['page-size'] ) ) {
			$this->page_size = absint( $this->args['page-size'] );
		}
		if ( ! empty( $this->args['page'] ) ) {
			$this->current_page = absint( $this->args['page'] - 1 );
		}

		/*
		try {

			$output = apply_filters( 'VestorFilter\execute_' . $function, '', self::$args );
			echo $output;

		} catch ( Exception $e ) {

			if ( ! empty( self::$args['debug'] ) ) {
				var_export( $e->getTrace() );
				echo "\r\n";
			}
			
			App::log( 
				'error', 
				$e->getMessage(), 
				LogLevel::ERROR, 
				$e->getTrace() 
			);
			echo $e->getMessage();
			

		}
		echo "\r\n";
		echo "\r\n";
		*/

	}

	public function add_loop_function( $method, int $priority = 10 ) {

		if ( ! is_callable( $method ) ) {
			throw new Exception( "Method passed to loop hook is not callable." );
		}

		if ( ! isset( $this->loop_func[ $priority ] ) ) {
			$this->loop_func[ $priority ] = [];
		}

		$this->loop_func[ $priority ][] = $method;

	}

	public function add_setup_function( $method, int $priority = 10 ) {

		if ( ! is_callable( $method ) ) {
			throw new Exception( "Method passed to setup hook is not callable." );
		}

		if ( ! isset( $this->setup_func[ $priority ] ) ) {
			$this->setup_func[ $priority ] = [];
		}

		$this->setup_func[ $priority ][] = $method;

	}

	public function add_end_function( $method, int $priority = 10 ) {

		if ( ! is_callable( $method ) ) {
			throw new Exception( "Method passed to setup hook is not callable." );
		}

		if ( ! isset( $this->setup_func[ $priority ] ) ) {
			$this->end_func[ $priority ] = [];
		}

		$this->end_func[ $priority ][] = $method;

	}

	public function set_page_size( $new_size ) {
		$this->page_size = $new_size;
	}

	public function set_total( $new_total ) {
		$this->total = $new_total;
	}

	public function start() {

		ksort( $this->setup_func );
		foreach( $this->setup_func as $methods ) {
			foreach( $methods as $method ) {
				$this->loop_env = call_user_func( $method, $this->args, $this->loop_env, $this );
			}
		}
		if ( isset( $this->loop_env['data'] ) ) {
			$data = $this->loop_env['data'];
			unset( $this->loop_env['data'] );
			if ( empty( $this->total ) ) {
				$this->total = count( $data );
			}
		} else {
			throw new Exception( "No data to process." );
		}

		$total_pages = ceil( $this->total / $this->page_size );
		if ( $this->output ) {
			echo "\r\n{$this->total} records found. Running {$total_pages} batches of {$this->page_size}.\n";
		}
		for( $page = $this->current_page; $page < $total_pages; $page ++ ) {
			$start = $this->current_page * $this->page_size;
			$end   = ( $this->current_page + 1 ) * $this->page_size - 1;
			if ( $end > $this->total ) {
				$end = $this->total;
			}
			if ( $this->output ) {
				echo "\r\033[1KRunning page " . ( $page + 1 ) . ", index $start - $end: ";
			}
			$exit = $this->batch_page( array_slice( $data, $start, $this->page_size, false ) );
			$this->current_page ++;

			if ( $exit ) {
				if ( $exit === 'time' && $this->output ) {
					echo "\r\nEnded early due to time limit.\n";
				}
				break;
			}
		}

		foreach( $this->end_func as $methods ) {
			foreach( $methods as $method ) {
				$this->loop_env = call_user_func( $method, $data, $this->args, $this->loop_env, $this );
			}
		}

		if ( $this->output ) {
			echo "\r\033[1KFinished processing {$this->total} records.\n";
			if ( ! empty( $this->loop_env['output'] ) ) {
				echo "\r";
				echo $this->loop_env['output'];
				echo "\r\n";
			}
		}

	}

	public function batch_page( $data ) {

		$index = 0;
		$total = count( $data );
		foreach( $data as $row ) {
			//$start = time();
			$index ++;
			foreach( $this->loop_func as $methods ) {
				foreach( $methods as $method ) {
					$this->loop_env = call_user_func( $method, $row, $this->args, $this->loop_env, $this );
				}
			}
			if ( $this->output ) {
				if ( ! empty( $output ) ) {
					echo ( "\e[" . strlen( $output ) . 'D' );
				}
				$output = floor( $index * 100 / $total ) . "% ($index/$total)";
				echo $output;
			}

			if ( ! empty( $this->loop_env['runtime_limit'] ) && time() > $this->loop_env['runtime_limit'] ) {
				return 'time';
			}
			/*if ( ! empty( $this->loop_env['rate_limit'] ) && time() < $start + $this->loop_env['rate_limit'] ) {
				@time_sleep_until( $start + $this->loop_env['rate_limit'] );
			}*/
		}

		return false;
	}

}
