<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Loop {

	private $properties = [];

	private $index = 0;

	public function __construct( $data ) {

		$this->properties = $data;
		$this->reset();

	}

	public function reset() {
		$this->index = 0;
	}

	public function has_properties() {

		return $this->index < count( $this->properties );

	}

	public function next() {

		$this->index += 1;

	}

	public function sort( $sort_function ) {

		usort( $this->properties, $sort_function );

	}

	public function current_property() {

		$property = $this->properties[ $this->index ];

		if ( ! is_object( $property ) ) {
			$property = new Property( $property );
			$this->properties[ $this->index ] = $property;
		}

		return $property;

	}

}
