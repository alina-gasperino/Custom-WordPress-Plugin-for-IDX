<?php

namespace VestorFilter\Hooks;

function search_input_field( $value ) {

	$value = filter_var( $value, FILTER_SANITIZE_STRING );

	?>

	<span class="fields">

		<div class="field-wrapper">
	
			<input data-filter-value="search" type="text" autocomplete="off" name="search" value="<?php echo esc_attr( $value ) ?>" placeholder="Enter your search keywords here">

			<button type="button" data-filter-clear="search" <?php if ( empty( $value ) ) echo 'style="display:none"'; ?> class="word-search--trash"><span class="screen-reader-text">Clear Search Terms</span></button>

		</div>

	</span>

	<?php

}

add_filter( 'vestorfilter_filter_toggle_end__search', 'VestorFilter\Hooks\search_input_field' );

function minify_html( $buffer ) {

    $search = array(
        '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
        '/[^\S ]+\</s',     // strip whitespaces before tags, except space
        '/(\s)+/s',         // shorten multiple whitespace sequences
        //'/<!--(.|\s)*?-->/' // Remove HTML comments
    );

    $replace = array(
        '>',
        '<',
        '\\1',
        ''
    );

    $buffer = preg_replace( $search, $replace, $buffer );

    return $buffer;
}
