<?php

namespace VestorFilter;

function use_icon( $icon_id ) {
	$icon =  file_get_contents(plugin_dir_url( __DIR__ ) . '/util/dist/icons/' .$icon_id .'.svg');
	return $icon;
}