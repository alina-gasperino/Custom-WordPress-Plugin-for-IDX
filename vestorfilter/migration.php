<?php

add_filter( 'vf_property_source_object_id', 'maybe_use_old_meta_structure', 10, 2 );

function maybe_use_old_meta_structure( $post_id, $data ) {

	if ( $post_id === 647 && $data->modified < 1656357757 ) {
		return 352;
	}

	return $post_id;

}
