<?php

namespace VestorFilter;

?>
<aside class="vf-panel vf-collapse vf-agents on-right" id="vf-agent-panel">

	<?php

	$agents = Agents::get_all();
	if ( empty( $active_filter ) ) {
		$active_filter = Search::get_filter_value( 'vf' );
		
	}

	if ( ! empty( $active_filter ) ) {
		$filter_name = esc_html( Filters::get_filter_name( $active_filter ) );
	}

	?>

	<button aria-expanded="false" aria-controls="vf-agent-panel" class="vf-collapse__toggle vf-agents__toggle">
		<?php echo Util\Icons::inline( 'call' ); ?>
		<div class="vf-agents__heading">
			<span class="vf-agents__heading--pretitle">Agent Advice</span>
			<?php if ( ! empty( $filter_name ) ) : ?>
			<span class="vf-agents__heading--title"><?= ucwords( $filter_name ) ?></span>
			<?php endif; ?>
		</div>
	</button>

	<div class="inside">

		<button aria-expanded="false" aria-controls="vf-agent-panel" class="vf-search__nav close ">
			<span class="icon">&times;</span>
			<span>Close</span>
		</button>

		<ul class="menu">

		<?php

		$count = 0;

		$selected = rand( 1, count($agents) );

		foreach ( $agents as $agent ) {

			$count ++;

			$agent_filters = $agent->get_meta( 'filters' );
			$visible = ( $count === $selected );

			?>

			<li class="<?php echo $visible ? 'is-visible' : 'is-hidden'; ?>"
				data-filters="">

				<figure class="vf-agents__agent">

					<figcaption>
						<strong><?php echo esc_html( $agent->get_full_name() ); ?></strong>
						<span><?php echo esc_html( $agent->get_meta( 'contact_line_2' ) ); ?></span>
					</figcaption>

					<img src="<?php echo $agent->get_image_url( 'thumbnail' ); ?>" class="vf-agents__agent--thumbnail" alt="">

				</figure>

				<blockquote class="vf-agents__filter-desc"><h3><?php

				if ( ! empty( $active_filter ) && $visible ) {
					echo ucwords( $filter_name );
				}
					
				?></h3><p><?php

				if ( ! empty( $active_filter ) && $visible ) {
					echo esc_html( Filters::get_filter_description( $active_filter ) );
				}

				?></p></blockquote>

				<figure class="vf-agents__agent-logo">

					<?php

					$custom_logo_id = Settings::get( 'compliance_logo' );
					
					if ( $custom_logo_id ) {
						echo wp_get_attachment_image( $custom_logo_id[0] , 'brand-logo', false );
					}

					?>

					<figcaption><?php echo str_replace( "\n", "<br>", esc_html( $agent->get_meta( 'agent_info' ) ) ) ?></figcaption>

				</figure>

				<a class="btn btn-secondary with-icon with-icon__phone" href="tel:<?php echo $agent->get_meta( 'phone' ); ?>">
						<span>Call</span></a>

				<!--email_off--><a class="btn btn-secondary with-icon with-icon__email" target="_blank" href="mailto:<?php echo $agent->get_meta( 'email' ); ?>">
					<span>Email</span></a><!--/email_off-->

			</li>

		<?php } //foreach ?>

		</ul>

	</div>

	<span class="spacer"></span>

</aside>