<section class="property-template__tour datatable" id="tour-property-card">

	<header class="vf-panel__title">
		<h3>Schedule a Tour</h3>
	</header>

	<div class="tour-form">

		<ul name="date" class="tour-form__date">
			<?php $dates = [ 'today', 'tomorrow', '+2 days' ]; $count = 0; ?>
			<?php foreach ( $dates as $date ): $datetime = strtotime( $date ); $count ++; ?>
			<li>
				<input <?php checked( $count, 1 ) ?> id="tour-date--<?php echo $date; ?>" type="radio" name="tour-date" value="<?php echo date( 'Y-m-d', $datetime ); ?>">
				<label for="tour-date--<?php echo $date; ?>">
					<span class="dow"><?php echo date( 'l', $datetime ); ?></span>
					<span class="dom"><?php echo date( 'j', $datetime ); ?></span>
					<span class="month"><?php echo date( 'F', $datetime ); ?></span>
				</label>
			</li>
			<?php endforeach; ?>
		</ul>

		<div class="form-toggle tour-form__location">
			<input checked id="toggle-tour-location--human" type="radio" name="tour-location" value="0">
			<label for="toggle-tour-location--human">In Person</label>
		</div>

		<div class="form-toggle tour-form__location">
			<input id="toggle-tour-location--virtual" type="radio" name="tour-location" value="1">
			<label for="toggle-tour-location--virtual">Virtual Q&A</label>
		</div>

		<button type="button" data-vestor-tour class="btn btn-secondary">Request Tour</button>

	</div>

</section>