<?php wp_footer(); ?>
<?php
$compliance_text = get_option('my_idx_options_general')['footer_text'];
?>
<footer class="custom-footer">
<div class="inside">

<div class="website-footer__agency-name" itemscope itemtype="https://schema.org/Brand">

	<h2 itemprop="name" ><a target="_blank" href="https://vestorfilter.com">What is a Vestor Filter &trade; website?</a></h2>

	<p>Vestor Filter&trade; is an intelligent property search platform. Connecting with local MLS systems, Residential and Commercial Real Estate Brokers, the search platform applies smart analytics for the general public home buyer or real estate investor.</p>
	<figure class="site-logo">
		<a target="_blank" href="https://vestorfilter.com"><img src="" /></a>
		<figcaption>Vestor Filter - Power Search Platform for Investment Properties</figcaption>
	</figure>

</div>

<?php if ( ! empty( $compliance_text ) ) : ?>
<div class="website-footer__compliance">

	<p><?php echo $compliance_text; ?></p>
	
</div>
<?php endif; ?>

</div>
</footer>
</body>
</html>
