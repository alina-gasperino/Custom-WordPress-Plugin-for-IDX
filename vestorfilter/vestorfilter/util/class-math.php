<?php

namespace VestorFilter\Util;

use VestorFilter\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Math {

	public static function conv_base( $numberInput, $inputSet, $to = true )
	{
		$toBaseInput = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
		if ( is_string( $to ) ) {
			$fromBaseInput = $inputSet;
			$toBaseInput = $to;
		} elseif ( $to === true ) {
			$fromBaseInput = $inputSet;
		} elseif ( $to === false ) {
			$fromBaseInput = $toBaseInput;
			$toBaseInput = $inputSet;
		} else {
			return $numberInput;
		}

		if ( $fromBaseInput === $toBaseInput ) {
			return $numberInput;
		}

		$fromBase 	= str_split($fromBaseInput,1);
		$toBase 	= str_split($toBaseInput,1);
		$number 	= str_split($numberInput,1);
		$fromLen	= strlen($fromBaseInput);
		$toLen		= strlen($toBaseInput);
		$numberLen	= strlen($numberInput);
		$retval		= '';

		if ( $toBaseInput == '0123456789' ) {
			$retval = 0;
			for ($i = 1;$i <= $numberLen; $i++)
				$retval = bcadd($retval, bcmul(array_search($number[$i-1], $fromBase),bcpow($fromLen,$numberLen-$i)));
			return $retval;
		}

		$base10 = self::conv_base( $numberInput, $fromBaseInput, '0123456789' );

		if ( $base10 < strlen( $toBaseInput ) ) {
			return $toBase[$base10];
		}
		while( $base10 !== '0' ) {
			$retval = $toBase[ bcmod( $base10, $toLen ) ] . $retval;
			$base10 = bcdiv( $base10, $toLen, 0 );
		}
		return $retval;
	}


}

