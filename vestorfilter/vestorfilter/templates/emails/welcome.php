<!--[if (gte mso 9)|(IE)]>
<table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
<tr>
<td align="center" valign="top" width="600">
<![endif]-->
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;">

	<tr><td style="font-family: Open Sans, Helvetica, Arial, sans-serif;">
    
	<p><?= $name ?>,</p>

	<p>Welcome to <a href="<?= esc_url( get_bloginfo( 'url' ) ) ?>"><?php bloginfo( 'title' ) ?></a>. We’re thrilled you’ve joined us.</p>

	<p>Honestly, we believe this is the most powerful home search website on the market.</p>

	<h3>Collaboration Made Simple</h3>
	<p>Any homes or criteria you save (hit the heart buttons) on your dashboard can be easily shared with friends and family, simply copy the link or hit any social media button. Every home and criteria you save, we can see. Not only that, but we can collaborate with you in real-time and add suggested homes and criteria to your dashboard - if you’d like us to.</p>

	<h3>Smart Home Search</h3>
	<p><em>Looking for something hard to find?</em> Don’t forget to use the custom keyword search. Type in any word and our site will bring up the matching homes. <em>Looking for deals?</em> Try out every one of our Vestor Filter&trade; smart overlays. You truly can find the needle in the haystack, the deal everyone has been missing. <em>Need a lot of land?</em> Try our best price per acre filter. <em>Need a lot of room?</em> Try our additional living quarters or best price per bedroom filters.</p>

	<h3>No Need to Unsubscribe - You are in Control</h3>
	<p>We are only sending this one welcome email after registration. If you’d like to be kept informed of new homes as they hit the market that match your criteria - it’s simple! Click the heart button on the main filter bar to save your criteria. Then on your dashboard select how often you’d like to get updated on that matching criteria: immediately, weekly, or monthly. Start and stop the property alerts as you like.

	<h3>Contact Us</h3>
	<p>Another set of eyes on your home search can help. We are here to use every tool at hand to help your best matching home, plus we have access to homes that are not on the market yet, coming-soon homes only members of our local MLS can see.</p>

	</td></tr>

	<?php if ( ! empty( $footer_text ) ) : ?>
    <tr>
        <td align="center" valign="top" style="padding: 25px 0 0 0; font-family: Open Sans, Helvetica, Arial, sans-serif;">
			<hr>
            <table cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:500px">		
                <tr>
                    <td align="center">
                        <?= apply_filters( 'the_content', $footer_text ) ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
	<?php endif; ?>

</table>