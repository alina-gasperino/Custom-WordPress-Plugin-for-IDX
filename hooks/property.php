<?php

namespace VestorFilter;

use VestorFilter\Util\Icons;
?>

	<aside class="popup" id="tour-modal" aria-hidden="true">

		<div class="popup__overlay"></div>

		<div class="popup__inside is-wide">

			<button class="popup__close" aria-controls="tour-modal" aria-expanded="true">
				<?php echo Icons::use( 'action-close' ); ?>
				<span class="screen-reader-text">Close this Window</span>
			</button>

			<div class="popup__contents">
			
				<header>
					<h2>Schedule a Tour</h2>
				</header>

				<?php echo do_shortcode( '[formidable id="schedule-tour" title=false description=false]' ); ?>

			</div>

		</div>

	</aside>

	<aside class="popup" id="contact-modal" aria-hidden="true">

		<div class="popup__overlay"></div>

		<div class="popup__inside is-wide">

			<button class="popup__close" aria-controls="contact-modal" aria-expanded="true">
				<?php echo Icons::use( 'action-close' ); ?>
				<span class="screen-reader-text">Close this Window</span>
			</button>

			<div class="popup__contents">
			
				<header>
					<h2>Contact Agent</h2>
				</header>

				<?php echo do_shortcode( '[formidable id="contact-agent" title=false description=false]' ); ?>

			</div>

		</div>

	</aside>

	<?php