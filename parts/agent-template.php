<?php

$agents = \VestorFilter\Agents::get_all();

$agent  = $agents[ rand( 0, count( $agents ) - 1 ) ];

$image =  $agent->get_image_url( 'thumbnail' );

?><section class="property-template__agent datatable" id="contact-agent-card">

	<header class="vf-panel__title">
		<h3>Contact Agent</h3>
	</header>

	<figure class="property-template__agent--inside">

	<?php if ( $image ) : ?><span class="property-template__agent--photo"><img src="<?php echo $image ?>" class="vf-agents__agent__thumbnail" alt=""></span><?php endif ?>

		<span class="property-template__agent--logo">
		<?php

		$custom_logo_id = get_theme_mod( 'custom_logo' );

		if ( $custom_logo_id ) {
			echo wp_get_attachment_image( $custom_logo_id, 'brand-logo', false );
		}

		?>
		</span>

		<figcaption>
			<?php echo $agent->get_full_name(); ?>
			<?php if ( $info = $agent->get_meta( 'contact_line_2' ) ) : ?>
				<br><?php echo esc_html( $info ); ?>
			<?php endif; ?>
		</figcaption>

	</figure>

	<div class="property-template__agent--contact">

		<button class="btn btn-primary" aria-expanded="false" data-agent="<?php echo esc_attr( $agent->get_full_name() ); ?>" aria-controls="contact-modal"><span>Contact Me</span></button>

	</div>

	<a class="btn btn-secondary with-icon with-icon__phone" href="tel:<?php echo $agent->get_meta( 'phone' ); ?>">
			<span>Call</span></a>

	<!--email_off-->
	<a class="btn btn-secondary with-icon with-icon__email" href="mailto:<?php echo $agent->get_meta( 'email' ); ?>">
		<span>Email</span></a><!--/email_off-->

</section>