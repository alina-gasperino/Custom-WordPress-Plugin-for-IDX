<?php

$source_table_name = 'wp_propertysource';
$table_name = 'wp_locationcache';

$prop_table_name  = 'wp_propertycache';
$meta_table_name  = 'wp_propertycache_meta';
$text_table_name  = 'wp_propertycache_text';
$addr_table_name  = 'wp_propertycache_address';
$addrpart_table_name  = 'wp_propertycache_address_part';

$cache_table_name  = 'wp_propertycache_cache';
$results_table_name  = 'wp_propertycache_results';


$tax_table_name   = 'wp_propertycache_taxonomy';
$index_table_name = 'wp_propertycache_index';
$value_table_name = 'wp_propertycache_value';

$data_table_name  = 'wp_propertycache_data';

$photo_table_name = 'wp_propertycache_photo';

function find_taxonomy_by( $field, $value ) {

    global $vfdb, $tax_table_name;

    $results = wp_cache_get( "tax_lookup_$field--$value", 'vestorfilter' );
    if ( $results !== false ) {
        return $results;
    }

    if ( $field !== 'name' && $field !== 'slug' ) {
        return false;
    }

    $taxtable = $tax_table_name;
    $query = $vfdb->prepare( "SELECT * FROM $taxtable WHERE $field = %s", $value );

    $results = $vfdb->get_row( $query );
    if ( ! empty( $results ) ) {
        wp_cache_set( "tax_lookup_$field--$value", $results, 'vestorfilter' );
    }

    return $results;

}

function get_index_values( $taxonomy_slug, $order = '' ) {

    global $vfdb, $value_table_name;
    if ( is_object( $taxonomy_slug ) ) {
        $taxonomy = $taxonomy_slug;
        $taxonomy_slug = $taxonomy->slug;
    }

    $value = wp_cache_get( "index_query_{$taxonomy_slug}_{$order}", 'vestorfilter' );

    if ( ! empty( $value ) ) {
        return $value;
    }

    if ( empty( $taxonomy ) ) {
        $taxonomy = find_taxonomy_by( 'slug', $taxonomy_slug );
        if ( empty( $taxonomy ) ) {
            return false;
        }
    }

    $query = 'SELECT * FROM ' . $value_table_name . ' WHERE `taxonomy` = %d';

    if ( ! empty( $order ) ) {
        $query .= ' ORDER BY ' . $order;
    }
    $vars = $vfdb->get_results( $vfdb->prepare(
        $query,
        $taxonomy->ID
    ) );

    if ( ! empty( $vars ) ) {
        wp_cache_set( "index_query_{$taxonomy_slug}_{$order}", $vars, 'vestorfilter' );
    }

    return $vars;

}

function get( $location_id ) {
    global $vfdb, $table_name;

    $data = wp_cache_get( 'location_data__' . $location_id, 'vestorfilter' );

    if ( ! empty( $data ) ) {
        return $data;
    }

    $data = $vfdb->get_row( $vfdb->prepare(
        'SELECT * FROM ' . $table_name . ' WHERE `ID` = %s',
        $location_id
    ) );

    wp_cache_set( 'location_data__' . $location_id, $data, 'vestorfilter' );

    return $data;

}

function get_slug( $location ) {

    if ( is_numeric( $location ) ) {
        $location = get( $location );
    }
    if ( ! is_object( $location ) || empty( $location ) ) {
        return '';
    }

    if ( ! empty( $location->slug ) ) {
        $slug = $location->slug;
    } else {
        $slug = sanitize_title( $location->value );
    }
    if ( ! empty( $location->type ) ) {
        $slug = sanitize_title( $location->type ) . '/' . $slug;
    }

    return $slug;

}

function get_all_data( $type = '', $sort = '', $duplicates = false ) {
    global $vfdb, $table_name;

    $data = wp_cache_get( "location_data-$type-$sort", 'vestorfilter' );

    if ( ! empty( $data ) ) {
        return $data;
    }

    $query = 'SELECT * FROM ' . $table_name . ' WHERE `count` > 0 ';
    if ( $type ) {
        $query .= $vfdb->prepare( ' AND `type` = %s', $type );
    }

    if ( empty( $duplicates ) ) {
        $query .= ' AND `duplicate_of` IS NULL';
    }
    if ( $sort ) {
        $query .= ' ORDER BY ' . $sort;
    }

    $data = $vfdb->get_results( $query );


    foreach( $data as &$location ) {
        if ( $slug = get_slug( $location ) ) {
            $location->url = $slug;
        }
        $location = apply_filters( 'vestorfilter_location_data_item', $location, $type, $sort );
    }

    wp_cache_set( "location_data-$type-$sort", $data, 'vestorfilter' );

    return $data;

}

function get_locations() {
    $location_options = array();
    $location_types_allowed = apply_filters( 'vestorfilter_allowed_default_locations', [ 'city', 'county', 'zip' ] );
    foreach( $location_types_allowed as $type ) {
        $locations = get_all_data( $type );
        foreach( $locations as $locale ) {
            $location_options[ $locale->ID ] = $locale->value;
        }
    }
    
    return $location_options;
}