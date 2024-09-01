<?php

namespace VestorFilter\Blocks;

use \VestorFilter\Property;
use \VestorFilter\Favorites as VestorFavorites;

use \VestorFilter\Search;
use \VestorFilter\Settings;
use \VestorFilter\Util\Icons;

class Favorites extends \VestorFilter\Util\Singleton {

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

		add_shortcode( 'vestorfilter-favorites', array( $this, 'render' ) );

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

		Search::do_search( [ 
			'friend_favorites' => ( $attrs['list'] ?? '' ) === 'friends' ? 'only' : false, 
			'user' => $user_id, 
			'show_hidden' => false 
		] );

		$property_loop = Search::get_results_loop(
			[
				'per_page' => 60,
				'page'     => Search::current_page_number(),
			]
		);
		$recs = get_user_meta( $user_id, '_agent_recommendation' );
		$recommended_ids = [];
		foreach( $recs as $rec ) {
			$rec = maybe_unserialize( $rec );
			$recommended_ids[ absint( $rec['property'] ) ] = true;
		}

		$friend_recs = VestorFavorites::get_friend_properties( $user_id );
		
		if ( ! empty( $recs ) ) {
			$property_loop->sort( function( $a, $b ) use ( $recommended_ids ) {
				if ( isset( $recommended_ids[ $a->ID() ] ) && isset( $recommended_ids[ $b->ID() ] ) ) {
					return 0;
				}
				if ( isset( $recommended_ids[ $a->ID() ] ) && ! isset( $recommended_ids[ $b->ID() ] ) ) {
					return -1;
				}
				if ( ! isset( $recommended_ids[ $a->ID() ] ) && isset( $recommended_ids[ $b->ID() ] ) ) {
					return 1;
				}
				return 0;
			} );
		}
		
		return self::get_html( $property_loop, $user_id, $recs, ( $attrs['list'] ?? '' === 'friends' ) ? $friend_recs : false );

	}

	static function get_html( $loop, $user_id, $agent_recs = [], $friend_list = false ) {

		ob_start();

		$my_agent_id = get_user_meta( $user_id, '_assigned_agent', true );
		if ( $my_agent_id ) {
			$my_agent = get_user_by( 'id', $my_agent_id );
		}
		
		
		if ( ! empty( $agent_recs ) ) {
			$recommendations = array_column( $agent_recs, 'property' );
		}

		$loop_url = trailingslashit( Settings::get_page_url( 'search' ) );
		$loop_url = add_query_arg( 'favorites', 'user', $loop_url );

		?>

		<?php if ( $loop->has_properties() && ! $friend_list ) : ?>
		<p class="favorites__share">
			<strong>Share This Page:</strong>
			<?php

			$favurl = \VestorFilter\Favorites::get_favorites_url( $user_id );

			$links = \VestorFilter\Social::get_share_links(
				[
					'title'       => 'My Favorite Homes On ' . get_bloginfo( 'name' ),
					'description' => 'My Favorite Homes On ' . get_bloginfo( 'name' ),
					'url'         => $favurl,
				]
			);

			foreach ( $links as $network => $url ) {
				echo $url;
			}

			?>
			<button class="btn btn-slim" data-copy-url="<?php echo esc_url( $favurl ); ?>">Copy URL</button>
		</p>
		<?php elseif ( $loop->has_properties() && $friend_list ) : ?>

		<h3>Suggested by Friends</h3>

		<?php endif; ?>

		<?php if ( $loop->has_properties() ) : ?>

		<div class="vf-block-results vf-block-results--favorites" data-limit="-1">
			<div class="vf-block-results__loop">

			<?php

			while ( $loop->has_properties() ) {

				$property = $loop->current_property();

				$presets = [];
				if ( empty( $attrs['pagination'] ) ) {
					$presets['property:url'] = add_query_arg( 'property', $property->ID(), $loop_url );
				}
				echo Property::get_cache_html( 
					'block-classic',
					$property,
					$presets,
					[
						'friend_recommended' => in_array( $property->ID(), $friend_list ?? [] ),
						'recommended'        => in_array( $property->ID(), $recommendations ?? [] ),
						'agent'              => $my_agent ?? null,
					]
				);

				$loop->next();

			}

			?>

			</div>
		</div>

		<?php elseif ( ! $friend_list && !is_array($friend_list) ) : ?>

			<p>No homes have been saved yet. Click the <?= Icons::inline( 'save' ) ?> when you've found a property you'd like to save or share.</p>

		<?php endif;

		return ob_get_clean();

	}


}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Blocks\Favorites', 'init' ) );
