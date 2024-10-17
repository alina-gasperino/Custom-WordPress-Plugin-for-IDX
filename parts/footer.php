<?php 


$compliance_text = \VestorFilter\Settings::get( 'compliance_text' );
$date            = \VestorFilter\Sources::get_last_updated();

if ( is_array( $date ) ) {

	$compliance_text = str_replace( 
		"{{DATE}}", 
		'<span class="compliance-date">' . $date['date'] . '</span>',
			$compliance_text 
	);
	$compliance_text = str_replace( "\n", "<br>", $compliance_text );

}

?>


<div class="inside">

	<div class="website-footer__agency-name" itemscope itemtype="https://schema.org/Brand">

		<h2 itemprop="name" ><a target="_blank" href="https://vestorfilter.com">What is a Vestor Filter &trade; website?</a></h2>

		<p>Vestor Filter&trade; is an intelligent property search platform. Connecting with local MLS systems, Residential and Commercial Real Estate Brokers, the search platform applies smart analytics for the general public home buyer or real estate investor.</p>

		<figure class="site-logo">
			<a target="_blank" href="https://vestorfilter.com"><img src="<?= VestorFilter\Util\Theme::$parent_theme_url . '/dist/images/vf-logo.svg?v=20201116'; ?>" /></a>
			<figcaption>Vestor Filter - Power Search Platform for Investment Properties</figcaption>
		</figure>

	</div>

	<nav aria-label="Site" class="website-footer__more-menu">
		
		<?php

		wp_nav_menu( [
			'theme_location' => 'footer-navigation',
			'depth'          => 1,
			'container'       => false
		] );


		wp_nav_menu( [
			'theme_location' => 'legal-navigation',
			'depth'          => 1,
			'container'       => false
		] );

		?>

	</nav>

	<nav aria-label="Contact" class="website-footer__contact-menu">

		<?php

		wp_nav_menu( [
			'theme_location' => 'contact-navigation',
			'depth'          => 1,
			'container'      => false
		] );

		?>

	</nav>

	<?php if ( ! empty( $compliance_text ) ) : ?>
	<div class="website-footer__compliance">

		<p><?php echo $compliance_text; ?></p>
		
	</div>
	<?php endif; ?>

</div>

<div class="inside website-footer__end">

	<p class="copyleft"><?php echo get_theme_mod( 'copyright_text', 'Copyright &copy; ' . date('Y') . ' Vestor Filter &trade;  All rights reserved.' ); ?></p>

</div>