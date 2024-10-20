<?php
	namespace VestorFilter;
	use VestorFilter\Util;
?>
<section class="property-template__actions">

	<div class="property-template__share btn" target="_blank">
		<span><?php esc_html_e( 'Share', 'vestorfilters' ); ?></span>
		<?php 
		
		$share_btns = \VestorFilter\Social::get_share_links( [
			'url'   => '{{ og:url }}',
			'image' => '{{ og:image }}',
		] );

		$share_list = '<li>' . implode( '</li><li>', $share_btns ) . '</li>';
		$share_list .= '<li><button title="Copy to Clipboard" type="button" class="btn btn-link btn-link--copy" data-copy-url="{{ property:url }}">' . use_icon( 'share-copy') . '</button></li>';


		?>
		<ul class="menu share-icons"><?php echo $share_list; ?></ul>
	</div>

	<button data-vestor-favorite="{{ property:id }}" class="btn datatable__half has-icon btn-primary btn-favorite" target="_blank">
		<span class="screen-reader-text"><?php esc_html_e( 'Save', 'vestorfilters' ); ?></span>
	</button>
</section>
<section class="property-template__quick-share">
	<ul class="menu share-icons"><?php echo $share_list; ?></ul>
</section>