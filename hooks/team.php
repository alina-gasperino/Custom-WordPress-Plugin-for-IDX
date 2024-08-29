<?php

namespace VestorTheme;

use VestorFilter\Util\Icons;


add_action( 'theme__after_footer', 'VestorTheme\team_bio_popup' );

function team_bio_popup() {
	
	?>

	<aside class="popup" id="team-bio" aria-hidden="true">

		<div class="popup__overlay"></div>

		<div class="popup__inside">

			<button class="popup__close" aria-controls="team-bio" aria-expanded="true">
				<?php echo Icons::use( 'action-close' ); ?>
				<span class="screen-reader-text">Close this Window</span>
			</button>

			<div class="popup__contents">
			
				<header>
					<h2>Name of Agent</h2>
				</header>

				<div class="bio-contents"></div>
				
			</div>

		</div>

	</aside>

	<?php
}