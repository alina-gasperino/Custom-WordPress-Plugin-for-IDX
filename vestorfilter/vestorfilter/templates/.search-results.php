<?php

use VestorFilter\Search as VestorSearch;
use VestorFilter\Settings as Settings;
use \VestorFilter\Util\Icons as Icons;

wp_enqueue_script( 'swipe' );
//wp_enqueue_script( 'vestorhouse-property' );
wp_enqueue_script( 'vestorhouse-favorites' );

$active = absint( $_GET['active'] ?? 1 );

if ( ! isset( $recommended ) ) {
	$recommended = [];
}
if ( ! isset( $agent ) ) {
	$agent = null;
}

?>

<div class="vf-search__results" 
	data-current-property="<?php echo esc_attr( $active ); ?>" 
	data-vf-results 
	data-page="<?php echo esc_attr( VestorSearch::current_page_number() ) ?>">

	<?php if ( ! empty( $spacers ) ) : ?>

	<div class="vf-panel vf-empty is-spacer"></div>
	<div class="vf-panel vf-empty is-spacer"></div>

	<?php endif; ?>

	<?php if ( ! $loop->has_properties() ) : ?>

		<div class="vf-property vf-panel no-results">

			<div class="inside">
				<p><?php if ( isset( $no_results_msg ) ) : ?>
					<?php echo $no_results_msg; ?>
				<?php else: ?>
					No listings could be found that match your search parameters.<br><br>
					<?php if ( empty( $hide_reset ) ) : ?>
					<button class="btn btn-primary reset" type="button" data-search-action="reset">
						<span>Reset Search</span>
					</button>
					<?php endif; ?>
				<?php endif; ?></p>

			</div>

			<div class="spacer"></div>

		</div>

	<?php endif; ?>

	<?php $count = 0; while ( $loop->has_properties() ) : $count ++; ?>

		<?php 

		$property = $loop->current_property();

		if ( ! empty( $friend_list ) ) {
			$friend = get_user_meta( $friend_list, '_friend_favorite_' . $property->ID(), true );
		}
		
		\VestorFilter\Util\Template::get_part(
			'vestorfilter',
			'property-block',
			[
				'property'   => $property,
				'is_active'  => $count === $active,
				'vf'         => $_GET['vf'] ?? false,
				'agent'      => in_array( $property->ID(), $recommended ) ? $agent : null,
				'friend'     => ! empty( $friend ) ? get_user_by( 'id', $friend ) : null,
				'user'       => $friend_list ?? null,
			]
		);
		?>

	<?php $loop->next(); endwhile; ?>

</div>

<?php if ( empty( $hide_reset ) ) : ?>
<div class="vf-search__pagination pagination" data-vf-pagination>

	<button class="btn btn-primary reset" type="button" data-search-action="reset">
		<span>Reset Search</span>
	</button>

	<span class="page-count"><?php printf( apply_filters( 'vestorfilter_search_results_count', esc_html__( VestorSearch::default_results_count(), 'vestorfilter' ) ), VestorSearch::total_results() ); ?></span>

	<?php

	$search_page = Settings::get_page_template( 'search_page' );
	if ( ! empty( $search_page ) && $search_url = get_permalink( $search_page ) ) {
		echo paginate_links( array(
			'base'     => add_query_arg( 'pagenum', '%#%', $search_url ),
			'format'   => '',
			'current'  => max( 1, VestorSearch::current_page_number() ),
			'total'    => VestorSearch::number_of_results_pages( absint( $_GET['per_page'] ?? 18 ) ?: 18 ),
			'mid_size' => 2,
			'end_size' => 2,
		) );

	}

	?>

</div>
<?php endif; ?>