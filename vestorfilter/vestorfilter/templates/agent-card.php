<div class="vf-panel vf-agent">

	<div class="vf-panel__title">
		<img src="<?php echo $agent->get_image_url(); ?>" class="vf-agents__agent__thumbnail" alt="">
		<?php echo $agent->get_full_name(); ?>
		<?php if ( $info = $agent->get_meta( 'contact_line_2' ) ) : ?>
			<br><?php echo esc_html( $info ); ?>
		<?php endif; ?>
	</div>

	<a class="btn btn-secondary with-icon with-icon__phone" href="tel:<?php echo $agent->get_meta( 'phone' ); ?>">
		<span><?php echo $agent->get_meta( 'phone' ); ?></span></a>

	<!--email_off--><a class="btn btn-secondary with-icon with-icon__email" href="mailto:<?php echo $agent->get_meta( 'email' ); ?>">
		<span>Send Email</span></a><!--/email_off-->

</div>