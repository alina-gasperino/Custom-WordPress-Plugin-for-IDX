<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Agents extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	private static $agents = [];

	private static $filters = [];

	public function install() {

		foreach ( self::$agents as $array_id => $agent ) {
			$filters = $agent->get_meta( 'filters' );
			foreach ( $filters as $filter_key ) {
				self::$filters[ $filter_key ] = $array_id;
			}
		}

		$this->setup_post_type();

		add_filter( 'rwmb_meta_boxes', array( $this, 'setup_meta_boxes' ) );
		add_shortcode( 'agent-roster', array( $this, 'render_agent_roster' ) );

	}

	private function setup_post_type() {

		$labels = array(
			'name'                  => _x( 'Roster', 'Post type general name', 'vestorfilter' ),
			'singular_name'         => _x( 'Roster', 'Post type singular name', 'vestorfilter' ),
			'menu_name'             => _x( 'Roster', 'Admin Menu text', 'vestorfilter' ),
			'name_admin_bar'        => _x( 'Agent in Roster', 'Add New on Toolbar', 'vestorfilter' ),
			'add_new'               => __( 'Add New', 'vestorfilter' ),
			'add_new_item'          => __( 'Add New Agent', 'vestorfilter' ),
			'new_item'              => __( 'New Agent', 'vestorfilter' ),
			'edit_item'             => __( 'Edit Agent', 'vestorfilter' ),
			'view_item'             => __( 'View Agent', 'vestorfilter' ),
			'all_items'             => __( 'All Agents', 'vestorfilter' ),
			'search_items'          => __( 'Search Agents', 'vestorfilter' ),
			'parent_item_colon'     => __( 'Parent Agents:', 'vestorfilter' ),
			'not_found'             => __( 'No agents found.', 'vestorfilter' ),
			'not_found_in_trash'    => __( 'No agents found in Trash.', 'vestorfilter' ),
			'featured_image'        => _x( 'Agent Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'vestorfilter' ),
			'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'vestorfilter' ),
			'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'vestorfilter' ),
			'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'vestorfilter' ),
			'archives'              => _x( 'Agent archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'vestorfilter' ),
			'insert_into_item'      => _x( 'Insert into agent', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'vestorfilter' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this agent', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'vestorfilter' ),
			'filter_items_list'     => _x( 'Filter agents list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'vestorfilter' ),
			'items_list_navigation' => _x( 'Agents list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'vestorfilter' ),
			'items_list'            => _x( 'Agents list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'vestorfilter' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'capabilities'       => array(
				'edit_post'          => 'edit_others_posts',
				'read_post'          => 'edit_others_posts',
				'delete_post'        => 'edit_others_posts',
				'edit_posts'         => 'edit_others_posts',
				'edit_others_posts'  => 'edit_others_posts',
				'delete_posts'       => 'edit_others_posts',
				'publish_posts'      => 'edit_others_posts',
				'read_private_posts' => 'edit_others_posts',
			),
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'excerpt', 'thumbnail' ),
		);

		register_post_type( 'agent', $args );

	}

	public function setup_meta_boxes( $meta_boxes ) {

		$prefix = '_agent_';

		$checkboxes = [];
		foreach ( Filters::get_all() as $key ) {
			$checkboxes[ $key ] = Filters::get_filter_name( $key );
		}

		$meta_boxes[] = array(
			'id'         => 'agentmeta',
			'title'      => esc_html__( 'Agent Properties', 'vestorfilter' ),
			'post_types' => array( 'agent' ),
			'context'    => 'normal',
			'priority'   => 'high',
			'autosave'   => 'false',
			'fields'     => array(
				array(
					'id'   => $prefix . 'show',
					'type' => 'checkbox',
					'name' => esc_html__( 'Show Card On Property Results', 'vestorfilter' ),
					'std'  => true,
				),
				array(
					'id'   => $prefix . 'fname',
					'type' => 'text',
					'name' => esc_html__( 'First Name', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'lname',
					'type' => 'text',
					'name' => esc_html__( 'Last Name', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'email',
					'type' => 'text',
					'name' => esc_html__( 'Email', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'phone',
					'type' => 'text',
					'name' => esc_html__( 'Phone Number', 'vestorfilter' ),
				),
			)
		);
		
		$meta_boxes[] = array(
			'id'         => 'agentmeta_roster',
			'title'      => esc_html__( 'Roster Information', 'vestorfilter' ),
			'post_types' => array( 'agent' ),
			'context'    => 'normal',
			'priority'   => 'high',
			'autosave'   => 'false',
			'fields'     => array(
				array(
					'id'   => $prefix . 'group',
					'type' => 'radio',
					'name' => esc_html__( 'Group', 'vestorfilter' ),
					'options' => [
						'primary' => 'Primary',
						'secondary' => 'Secondary',
					],
				),
				array(
					'id'   => $prefix . 'line2',
					'type' => 'text',
					'name' => esc_html__( 'Title Under Name', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'line3',
					'type' => 'text',
					'name' => esc_html__( 'Tagline Under Name', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'bio',
					'type' => 'wysiwyg',
					'name' => esc_html__( 'Short Bio', 'vestorfilter' ),
					'options' => [
						'tinymce'       => array(
							'toolbar1'      => 'bold,italic,underline,separator,alignleft,aligncenter,alignright,separator,link,unlink,undo,redo',
							'toolbar2'      => '',
							'toolbar3'      => '',
						),
					]
				),
				array(
					'id'   => $prefix . 'url',
					'type' => 'text',
					'name' => esc_html__( 'Website URL', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'social_facebook',
					'type' => 'text',
					'name' => esc_html__( 'Facebook URL', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'social_twitter',
					'type' => 'text',
					'name' => esc_html__( 'Twitter URL', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'social_linkedin',
					'type' => 'text',
					'name' => esc_html__( 'LinkedIn URL', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'social_instagram',
					'type' => 'text',
					'name' => esc_html__( 'Instagram URL', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'social_pinterest',
					'type' => 'text',
					'name' => esc_html__( 'Pinterest URL', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'social_youtube',
					'type' => 'text',
					'name' => esc_html__( 'Youtube URL', 'vestorfilter' ),
				),
			)
		);

		return $meta_boxes;

	}

	public static function get_all( $cards = true ) {
		$return = wp_cache_get( 'all_agents', 'vestorfilter' );
		if ( ! empty( $return ) ) {
			return $return;
		}

		$args = array('post_type' => 'agent', 'posts_per_page' => -1);
		$agents = get_posts( $args );

		$return = [];
		foreach ( $agents as $post ) {
			$return[] = new Agent( $post );
		}

		wp_cache_set( 'all_agents', $return, 'vestorfilter' );

		return $return;

	}

	public static function render_agent_roster( $args = [], $content = '' ) {

		$query_args = [ 
			'post_type'      => 'agent',
			'nopaging'       => true,
			'posts_per_page' => -1,
		];
		if ( ! empty( $args['group'] ) ) {
			$query_args['meta_key']   = '_agent_group';
			$query_args['meta_value'] = $args['group'];
		}
		$group = $args['group'] ?? 'all';
		$class = 'is-group-' . sanitize_title( $group );

		$agents = get_posts( $query_args );

		ob_start();

		echo '<div class="team-bio-list ' . $class . '">';

		foreach( $agents as $agent ) : 
		
			$thumbnail = get_the_post_thumbnail( $agent->ID, 'medium' );
			$line2 = get_post_meta( $agent->ID, '_agent_line2', true );
			if ( $line2 ) {
				$line2 = '<br>' . $line2;
			}
			$line3 = get_post_meta( $agent->ID, '_agent_line3', true );
			$email = get_post_meta( $agent->ID, '_agent_email', true );
			$link  = get_post_meta( $agent->ID, '_agent_url', true );
			if ( $link && strpos( $link, 'http' ) === false ) {
				$link = 'https://' . $link;
			}
			$phone = get_post_meta( $agent->ID, '_agent_phone', true );
			$bio   = get_post_meta( $agent->ID, '_agent_bio', true );

			$networks = [ 'facebook', 'twitter', 'linkedin', 'pinterest', 'youtube', 'instagram' ];

			$order = $group = 'secondary' ? rand( 1, 999999 ) : 1;
		
		?>

		<div class="wp-block-vestorfilter-team-member" style="--order:<?= $order ?>">
			<?php if ( $thumbnail ) : ?>
			<figure class="wp-block-vestorfilter-team-member--image"><?= $thumbnail ?></figure>
			<?php endif; ?>
			<h3 class="wp-block-vestorfilter-team-member--name"><?= $agent->post_title . $line2 ?></h3>
			<?php if ( $line3 ) : ?>
			<p class="wp-block-vestorfilter-team-member--subtitle"><?= $line3 ?></p>
			<?php endif; ?>
			<div class="wp-block-vestorfilter-team-member--icons"><?php if ( $email ) : ?>
				<a class="wp-block-vestorfilter-team-member--email" href="mailto:<?= esc_attr( $email ) ?>" data-email="<?= esc_attr( $email ) ?>"><?= \VestorFilter\Util\Icons::inline('envelope') ?></a>
				<?php endif; if ( $phone ) : ?>
				<a class="wp-block-vestorfilter-team-member--phone" href="tel:<?= esc_attr( $phone ) ?>" data-phone="<?= esc_attr( $phone ) ?>"><?= \VestorFilter\Util\Icons::inline('call') ?></a>
				<?php endif; if ( $link ) : ?>
				<a class="wp-block-vestorfilter-team-member--link" href="<?= esc_url( $link ) ?>" data-url="<?= esc_attr( $link ) ?>"><?= \VestorFilter\Util\Icons::inline('www') ?></a>
				<?php endif; ?><?php foreach ( $networks as $network ) : if ( $data = get_post_meta( $agent->ID, '_agent_social_' . $network, true ) ) : ?>
				<a class="wp-block-vestorfilter-team-member__social" href="<?= esc_url( $data ) ?>" data-url="<?= esc_attr( $data ) ?>"><?= \VestorFilter\Util\Icons::inline( $network ) ?></a>
			<?php endif; endforeach ?></div>
			<?php if ( $bio ) : ?>
			<div class="wp-block-vestorfilter-team-member--bio">
				<?php echo apply_filters( 'the_content', $bio ); ?>
			</div>
			<?php endif; ?>
		</div>

		<?php endforeach;

		echo '</div>';

		return ob_get_clean();

	}

	public static function get_agent_leads( $user_id = null ) {

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if ( empty( $user_id ) ) {
			return [];
		}

		$args = [
			'orderby'    => 'first_name',
			'order'      => 'ASC',
			'number'     => -1,
			'meta_key'   => '_assigned_agent',
			'meta_value' => $user_id,
			''
		];

		$users = new \WP_User_Query( $args );
		$my_leads = $users->get_results();

		return $my_leads;

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Agents', 'init' ) );
