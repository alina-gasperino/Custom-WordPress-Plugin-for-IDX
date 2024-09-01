<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
    die();
}

class Templator extends \VestorFilter\Util\Singleton {

    /**
     * A pointless bit of self-reference
     *
     * @var object
     */
    public static $instance = null;

    public static $image_url;

    public function install() {

        if ( defined( 'VF_IMG_URL' ) ) {
            self::$image_url = VF_IMG_URL;
        } else {
            $upload_dir = wp_upload_dir( 'vf' );
            self::$image_url = $upload_dir['url'];
        }

    }

    public static function get_property_image( $photos, $index = 0, $size = 'full' ) {

        if ( ! is_numeric( $index ) || $index >= count( $photos ) ) {
            $index = 0;
        }

        if ( ! $size || $size === 'full' ) {
            $size = 'url';
        }

        $url = $photos[ absint( $index ) ]->$size;
        if ( strpos( $url, '//' ) === false ) {
            $url = self::$image_url . $url;
        }

        return $url;

    }

    public static function filter_html( $template, $cache, $property = null, $flags = [] ) {

        $contents = $template;
        $handlebars = self::find_handlebars( $template );

        foreach( $handlebars as $tag ) {
            $replacement = null;
            extract( $tag );

            switch( $action ) {
                case 'compliance':
                    if ( $value === 'logo' && ! empty( $cache['logo'] ) ) {
                        $replacement = $cache['logo'];
                    }
                    break;
                case 'property':
                    if ( $value === 'photo' && ! is_null( $subset ) ) {
                        $replacement = self::get_property_image( $cache['photos'], $subset ?: 0 );
                    } else if ( $value === 'url' ) {
                        $replacement = $cache['url'] ?? Property::base_url() . $cache['MLSID'] . '/' . $cache['slug'];
                    } else if ( $value === 'id' ) {
                        $replacement = $cache['id'] ?? $cache['ID'] ?? '';
                    }
                    break;
                case 'image':
                    if ( $value ) {
                        $replacement = strpos( $value, '//' ) !== false ? $value : self::$image_url . $value;
                    }
                    break;
                /*case 'vf':
                    if ( $value === 'url' && $subset ) {
                        $url = $cache['url'];
                        $url = add_query_arg( 'vf', $subset, $url );
                        $replacement = $url;
                    }
                    break;*/
                case 'icon':
                    $replacement = sprintf(
                        '<svg class="vf-use-icon vf-use-icon--%1$s"><use xlink:href="#%1$s"></use></svg>',
                        $value
                    );
                    break;
                case 'og':
                    if ( $value === 'url' ) {
                        $replacement = $cache['url'];
                    } else if ( $value === 'image' ) {
                        $replacement = self::get_property_image( $cache['photos'] );
                    }
                    break;
                case 'data':
                    $data = $cache['data'][$value] ?? null;
                    if ( $data ) {
                        if ( $subset === 'dom' ) {
                            $data = ceil( ( time() - $data/100 ) / ( 3600 * 24 ) ) . ' days';
                        }
                        $replacement = $data;
                    }
                    break;
                case 'flags':
                    $class = 'vf-favorite-toggle-btn';
                    $is_favorite = Favorites::is_property_user_favorite( $cache['id'] ?? $cache['ID'] );
                    if ( $is_favorite ) {
                        $class .= ' is-favorite';
                    }
                    $replacement = '<button type="button" data-vestor-favorite="' . ( $cache['id'] ?? $cache['ID'] ?? '' ) . '" class="' . $class . '"><span class="toggle-favorite"></span></button>';
                    if ( $property && ! empty( $flags ) && ! empty( $flags['vf'] ) ) {
                        $value = $property->get_vestorfilter( $flags['vf'] );
                        if ( $value && $value != 'Yes' ) {
                            $replacement .= '<span class="vf-property-block__vf vf-property-block__vf--' . $flags['vf'] . '">' . $value . '</span>';
                        }
                    }
                    break;
                /*case 'actions':
                    if ( actionsTemplate ) {
                        replacement = actionsTemplate + '';
                        //replacement = replacement.replaceAll( /SHARE_URL/g, property.url );
                        //replacement = replacement.replaceAll( /SHARE_ID/g, property.id );
                        //replacement = replacement.replaceAll( /SHARE_IMAGE/g, getPropertyImage( { property } ) );
                        replacement = replaceHandlebars( replacement, property );
                    }
                    break;
                case 'agent-contact':
                    if ( contactTemplate || tourTemplate ) {
                        replacement = ( contactTemplate || '' ) + ( tourTemplate || '' );
                        replacement = replacement.trim();
                        replacement = replaceHandlebars( replacement, property );
                    }
                    break;
                case 'favorite':
                    let favoriteClass = vestorFavorites.currentFavorites.indexOf( property.id ) !== -1 ? ' is-favorite' : '';
                    replacement = `<button type="button" class="vf-property-block__favorite-btn vf-favorite-toggle-btn${favoriteClass}" data-vestor-favorite="${property.id}"><span>Toggle Favorite</span></button>`;
                    break;
                case 'vestorfilter':
                    if ( vf && property.data_cache[vf.filter] ) {
                        let value = ( parseFloat( property.data_cache[vf.filter] ) / 100 ).toFixed(2);
                        if ( value > 999 ) {
                            value = formatPrice( value, true );
                        }
                        let vfLabel = vf.label.replace( '{{value}}', value );
                        replacement = `<span class="vf-property-block__flags--vf vf-property-block__vf">${vfLabel}</span>`;
                    }
                    break;*/
            }

            if ( $replacement ) {
                $contents = str_replace( [ "<!--$source-->", $source ], $replacement, $contents );
            }

        }

        return $contents;

    }

    public static function find_handlebars( $content ) {

        $found = [];

        if ( preg_match_all( '/\{\{.*?\}\}/', $content, $attr_tags, PREG_SET_ORDER ) ) {
            foreach( $attr_tags as $tag ) {
                $interpreted = self::interpret_handlebars( $tag[0] );
                $found[] = $interpreted;
            }
        }

        return $found;

    }

    private static function interpret_handlebars( $handlebar_string ) {

        $source_string = trim( str_replace( ['{{','}}'], '', $handlebar_string ) );

        $attrs = explode( ':', $source_string, 2 );

        $action = $attrs[0];

        if ( count( $attrs ) > 1 ) {
            $value = urldecode( $attrs[1] );
            if ( preg_match( '/(.*?)\[(.*?)\]/', $value, $subset_found ) ) {
                $value = $subset_found[1];
                $subset = $subset_found[2];
            }
        }

        return [
            'action' => $action,
            'value'  => $value ?? null,
            'subset' => $subset ?? null,
            'source' => $handlebar_string
        ];

    }

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Templator', 'init' ) );
