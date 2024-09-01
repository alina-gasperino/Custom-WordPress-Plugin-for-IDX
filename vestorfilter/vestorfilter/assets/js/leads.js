/* global wp, jQuery */
/**
 * File customizer.js.
 *
 * Theme Customizer enhancements for a better user experience.
 *
 * Contains handlers to make Theme Customizer preview reload changes asynchronously.
 */

( function( $ ) {

    $('.send').on('click', function (e) {
        e.preventDefault();
        let data = {
            'action': 'remove_lead',
            'id': $(this).data('id'),
        }

        $.post('/wp-admin/admin-ajax.php', data, function (response) {
            alert(response);
        });

    });

}( jQuery ) );
