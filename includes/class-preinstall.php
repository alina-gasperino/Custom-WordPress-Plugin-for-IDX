<?php

$source_table_name = 'wp_propertysource';

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