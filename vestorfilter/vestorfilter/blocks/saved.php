<?php

namespace VestorFilter\Blocks;

use VestorFilter\Filters as Filters;
use VestorFilter\Search as Search;
use VestorFilter\Settings as Settings;
use VestorFilter\Favorites as Favorites;
use VestorFilter\Data as Data;
use VestorFilter\Location as Location;
use \VestorFilter\Util\Icons as Icons;
use \VestorFilter\Util\Template as Template;


class SavedSearches extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	private $user_id;

	public function install() {

		add_action( 'init', array( $this, 'register' ) );

	}

	function register() {

		add_shortcode( 'vestorfilter-saved', array( $this, 'render' ) );

	}

	static function render( $attrs, $content = '', $tag = null ) {

		if ( empty( $attrs['user'] ) ) {
			$user_slug = get_query_var( 'user_slug' );
			if ( empty( $user_slug ) ) {
				$user_id = get_current_user_id();
			} else {
				$user_id = \VestorFilter\Favorites::find_user_from_slug( $user_slug );
			}
		} else {
			$user_id = absint( $attrs['user'] );
		}

		if ( empty( $user_id ) ) {
			return;
		}

		$search_queries = Favorites::get_searches( $user_id );
		foreach( $search_queries as $hash => &$query ) {
			$query = json_decode( $query );
			$query->hash = $hash;
		}

		usort( $search_queries, function ( $a, $b ) {
			if ( isset( $a->added_by ) && isset( $b->added_by ) ) {
				return 0;
			}
			if ( isset( $a->added_by ) && ! isset( $b->added_by ) ) {
				return -1;
			}
			if ( ! isset( $a->added_by ) && isset( $b->added_by ) ) {
				return 1;
			}
			return 0;
		} );

		return self::get_html( $search_queries, $user_id );

	}

	static function get_search_label_html( $query ) {

		$all_filters = Data::get_allowed_filters();
		$url = Settings::get_page_url( 'search' );
		$text = [];
		$plain_text = [];

		if ( ! empty( $query->location ) ) {
			if ( ! is_numeric( $query->location ) && strpos( $query->location, ',' ) === false ) {
				$location_name = 'Custom map';
				if ( ! empty( $query->map_user ) && strpos( $query->location, '[' ) === false ) {
					$query->location = "{$query->map_user}[$query->location]";
				}
			} elseif ( strpos( $query->location, ',' ) !== false ) {
				$location = Location::get( $query->location );
				if ( $location ) {
					$url .= Location::get_slug( $location ) . '/';
				}
			}
		}

		foreach ( $query as $key => $value ) {
			if ( $key === 'vf' ) {
				$label = 'VestorFilter&trade;';
				$formatted = Filters::get_filter_name( $value );
			} elseif ( $key === 'location' && ! empty( $location_name ) ) {
				$formatted = 'Custom Map';
				$label = 'Location';
				$value = strpos( $value, '[' ) === false ? get_current_user_id() . '[' . $value . ']' : $value;
			} elseif ( isset( $all_filters[ $key ]['label'] ) ) {
				$label = $all_filters[ $key ]['label'];
				$formatted = Data::get_filter_value( $all_filters[ $key ], $value, $key );
			} else {
				$label = null;
			}

			if ( ! empty( $label ) && ! empty( $formatted ) ) {
				$text[] = sprintf( 
					'<span class="search-query-list__filter">%s%s</span>',
					'<span class="label">' . $label . '</span>',
					'<span class="value">' . esc_html( $formatted ) . '</span>'
				);
				$plain_text[] = $formatted;
				$url = add_query_arg( $key, $value, $url );
			}
		}
		$text = implode( '', $text );
		$plain_text = implode( ', ', $plain_text );

		return [ 'label' => $text, 'url' => $url, 'plain' => $plain_text ];

	}

	static function get_html( $queries, $user_id ) {

		ob_start();


		$subscriptions = Favorites::get_subscriptions( $user_id );

		$suboptions = [ 
//			'0' => 'Immediate',
			'1' => 'Daily',
			'7' => 'Weekly',
			'30' => 'Monthly',
			'never' => 'Never',
		];

		?>

		<div class="website-content__search-queries">

			<ul class="search-query-list"><?php 

			foreach( $queries as $properties ) {

				extract( self::get_search_label_html( $properties ) );

				$hash = $properties->hash;
				$hash_value = $subscriptions[ $hash ] ?? 'never';
				$nonce = wp_create_nonce( $hash );

				$can_edit = $user_id === get_current_user_id() || current_user_can( 'see_leads' );

			?><li class="search-query-list__saved<?php if ( ! empty( $properties->dynamic ) ) echo ' is-dynamic' ?><?php if ( isset( $properties->added_by ) ) echo ' added-by-agent' ?> <?php if ( $can_edit ) echo 'current-user' ?>">
				<?php if ( empty( $properties->dynamic ) ) : ?>
				<a href="<?= esc_url( $url ); ?>">View Properties</a>
				<?php endif; if ( ! empty( $properties->dynamic ) ) : ?>
					<div class="search-query-list__saved--name label-only">
						<strong>Dynamic Smart Search</strong>
					</div>
				<?php elseif ( $can_edit ) : ?>
					<div class="search-query-list__saved--name">
						<input data-user="<?= $user_id ?>" data-hash="<?= $hash; ?>" data-search-name="<?= $nonce; ?>" value="<?= esc_attr( $properties->name ?? '' ); ?>" name="name-<?= $hash; ?>" id="name-<?= $hash; ?>" placeholder="Name this search">
						<?= Icons::inline( 'bxs-save' ) ?>
					</div>
				<?php endif; ?>
				<div class="search-query-list__saved--label">
					<?= $label; ?>
				</div>
				<?php if ( $can_edit ) : ?>
					<?php if ( empty( $properties->dynamic ) ) : ?>
					<button data-user="<?= $user_id ?>" data-trash="<?= $hash; ?>" data-vestor-save="<?= $nonce ?>" class="search-query-list__saved--trash">
						<?php echo Icons::inline( 'trash' ); ?>
						<span class="screen-reader-text">Delete this Search</span>
					</button>
					<?php endif; ?>
					<div class="search-query-list__saved--emails">
						<span class="label">Emails:</span>
						<?php foreach( $suboptions as $value => $label ) : ?>
						<input data-user="<?= $user_id ?>" value="<?= esc_attr( $value ); ?>" data-hash="<?= $hash; ?>" data-vestor-subscribe="<?= $nonce ?>" name="subscription-<?= $hash; ?>" type="radio" id="subscription-<?= $hash; ?>--<?= $value ?>" <?php checked( $value, $hash_value ); ?>>
						<label for="subscription-<?= $hash; ?>--<?= $value ?>"><?= $label; ?></label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ( isset( $properties->added_by ) ) : ?>
				<div class="search-query-list__saved--recommendation">
					Recommended By Your Agent
				</div>
				<?php endif; ?>
				<?php if ( ! empty( $properties->dynamic ) ) : ?>
				<p class="search-query-list__saved--description">
					Our Dynamic Smart Search criteria will learn your preferences and adjust to your desires. Simply save all the homes on this site that you like (click the heart buttons) and our system will auto-send you matching properties. The more homes you save, the better the Dynamic Smart Search will perform.
				</p>
				<?php endif; ?>
			</li><?php 

			}

			?></ul>

			<p class="search-query-list__notice">
			Your saved home search criteria shows up here.<br>
			Hit the heart <?= Icons::inline( 'save' ) ?> button on the search bar to save criteria.<br>
			Once you save a search you can choose how often you'd like to be notified.
			</p>

		</div>

		<?php

		return ob_get_clean();

	}


}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Blocks\SavedSearches', 'init' ) );
