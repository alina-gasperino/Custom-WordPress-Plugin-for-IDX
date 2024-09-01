<?php

namespace VestorFilter\Blocks;

use VestorFilter\Query as Query;
use VestorFilter\Search as Search;
use VestorFilter\Settings as Settings;
use VestorFilter\Filters as Filters;
use VestorFilter\Property as Property;
use VestorFilter\Location;

use \VestorFilter\Data as Data;
use \VestorFilter\Util\Icons as Icons;

class Sample extends \VestorFilter\Util\Singleton {

    /**
     * A pointless bit of self-reference
     *
     * @var object
     */
    public static $instance = null;

    public function install() {

        add_action( 'init', array( $this, 'register' ) );

    }

    function register() {

        add_shortcode( 'vestorfilter-sample', array( $this, 'render' ) );

    }

    static function render( $attrs, $content = '', $tag = null ) {

        $limit = 3;
        if ( isset( $attrs['limit'] ) && ( $attrs['limit'] === 'no' || $attrs['limit'] === 'false' ) ) {
            $limit = -1;
        } elseif ( isset( $attrs['limit'] ) ) {
            $limit = absint( $attrs['limit'] );
        }

        $page = 1;
        if ( isset( $attrs['pagenum'] ) ) {
            $page = max( 1, absint( $attrs['pagenum'] ) );
        } elseif ( ! empty( $_GET['pagenum'] ) ) {
            $page = max( 1, absint( $_GET['pagenum'] ) );
        }

        $per_page = $attrs['pagination'] ?? $limit;
        $per_page = max( 1, absint( $per_page ) );

        $flags = [];

        $query = [
            'filters' => [
                'property-type' => 'sf',
                'status' => 'active',
            ],
        ];
        if ( $limit > 0 ) {
            $query['limit'] = $limit;
        }

        if ( ! empty( $attrs['filters'] ) ) {
            $filters = explode( ',', $attrs['filters'] );
            foreach( $filters as $filter ) {
                list( $key, $value ) = explode( '=', $filter );
                if ( strpos( $value, '+' ) ) {
                    $value = explode( '+', $value );
                }
                $query['filters'][$key] = $value;
            }
        }

        if ( empty( $query['filters']['vf'] ) ) {
            if ( isset( $attrs['sortby'] ) && in_array( $attrs['sortby'], Property::standard_filters(), true ) ) {
                $query['order'] = [ $attrs['sortby'] => strtoupper( $attrs['sort'] ?? '' ) === 'ASC' ? 'ASC' : 'DESC' ];
            } else {
                $query['order'] = [ 'modified' => 'DESC' ];
            }
        } else {
            $flags['vf'] = $query['filters']['vf'];
        }

        if ( isset( $query['filters']['location'] ) && $query['filters']['location'] === 'default' ) {
            $default_location = Settings::get( 'default_location_id' );
            if ( $default_location ) {
                $query['filters']['location'] = is_array( $default_location ) ? implode( ',', $default_location ) : $default_location;
            } else {
                unset( $query['filters']['location'] );
            }
        }

        ob_start();

        try {
            $property_query = new Query( $query, null, false, true );
            $loop = $property_query->get_page_loop( [ 'per_page' => $per_page, 'page' => $page, 'overshoot' => 1 ] );
        } catch( \Exception $e ) {
            $loop = null;
        }

        if(is_front_page())
        {
            echo <<<EOD
                <style>
                .vf-block-results__loop {flex-flow: wrap;}
                @media (max-width: 576px)
                {
                    
                    .vf-block-results:not([data-limit="-1"]) .vf-property-block:not(:nth-child(odd)) {margin-left: 0;}
                }
                </style>
            EOD;
        }

        $classes = [ 'vf-block-results', 'vf-block-sample' ];
        if ( ! empty( $attrs['class'] ) ) {
            $classes = array_merge( $classes, $attrs['class'] );
        }

        echo '<div class="' . implode( ' ', $classes ) . '" data-limit="' . esc_attr( $limit ). '"><div class="vf-block-results__loop">';

        $loop_url = trailingslashit( Settings::get_page_url( 'search' ) );
        if ( ! empty( $query['filters']['location'] ) ) {
            $loop_url .= Location::get_slug( $query['filters']['location'] );
        }
        $loop_url = add_query_arg( $query['filters'], trailingslashit( $loop_url ) );

        while ( $loop && $loop->has_properties() ) {

            $property = $loop->current_property();

            $presets = [];
            if ( empty( $attrs['pagination'] ) ) {
                $presets['property:url'] = add_query_arg( 'property', $property->ID(), $loop_url );
            }

            echo Property::get_cache_html(
                'block-classic',
                $property,
                $presets,
                $flags
            );


            /*\VestorFilter\Util\Template::get_part(
                'vestorfilter',
                'property-block', [
                    'property' => $loop->current_property(),
                    'vf'       => $query['filters']['vf'] ?? null,

                ]
            );*/

            $loop->next();

        }

        echo '</div>';

        if ( ! empty( $attrs['pagination'] ) || empty( $attrs['more'] ) || $attrs['more'] !== 'no' ) {

            echo '<div class="vf-block-results__pagination pagination">';

            if ( empty( $attrs['pagination'] ) ) {

                printf(
                    '<a href="%s" class="btn btn-secondary">%s</a>',
                    $loop_url,
                    __( 'See More and Customize', 'vestorfilter' )
                );

            } else {

                ?>

                <span class="page-count"><?php
                    if ( ! empty( $attrs['pagination-text'] ) ) {
                        printf( esc_html( $attrs['pagination-text'] ), $property_query->total_results() );
                    } else {
                        echo $property_query->results_string();
                    }
                    ?></span>

                <?php

                $this_page = $_SERVER['REQUEST_URI'];
                echo paginate_links( array(
                    'base'     => add_query_arg( 'pagenum', '%#%', $this_page ),
                    'format'   => '',
                    'current'  => max( 1, $page ),
                    'total'    => ceil( $property_query->total_results() / $per_page ),
                    'mid_size' => 2,
                    'end_size' => 2,
                ) );

            }

            echo '</div>';
        }

        echo '</div>';

        return ob_get_clean();

    }


}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Blocks\Sample', 'init' ) );
