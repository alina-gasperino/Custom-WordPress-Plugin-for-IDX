jQuery( function( $ ) {
	$('button[data-action="open-task-notes"]').on( 'click', function (e) {
		$(e.currentTarget).closest('.column-note').toggleClass('notes-expanded');
		if ( $(e.currentTarget).closest('.column-note').hasClass( 'notes-expanded' ) ) {
			$(e.currentTarget).html( 'Collapse' );
		} else {
			$(e.currentTarget).html( 'View' );
		}
	} );
	$('button[data-action="open-task-editor"]').on( 'click', function (e) {
		$(e.currentTarget).closest('.column-note').addClass('editor-open');
		$(e.currentTarget).closest('.column-note').find('div.note-editor').attr('aria-hidden','false');
	} );
	$('button[data-action="cancel-task-notes"]').on( 'click', function (e) {
		$(e.currentTarget).closest('.column-note').removeClass('editor-open');
		$(e.currentTarget).closest('.column-note').find('div.note-editor').attr('aria-hidden','true');
	} );
	$('button[data-action="save-task-notes"]').on( 'click', function (e) {
		$(e.currentTarget).closest('.column-note').removeClass('editor-open');
		$(e.currentTarget).closest('.column-note').find('div.note-editor').attr('aria-hidden','true');

		var $textarea = $(e.currentTarget).closest('.column-note').find('textarea');
		$.post( window.location.href, {
			notes: $textarea.val(),
			edit_note: $textarea.data('nonce'),
			task: $textarea.data('task')
		} );

		$(e.currentTarget).closest('.column-note').find('.note-text').html( $textarea.val() );
	} );
	var $userSelect = jQuery('#agent_select + select[name="user_id"]');
	var $options = $userSelect.find('option');
	var dataOptions = [];
	for(var i = 0; i < $options.length; i ++ ) {
		dataOptions.push( { 
			'agent': $options[i].dataset.agent, 
			'id': $options[i].value, 
			'text': $options[i].innerHTML 
		} );
	}
	$userSelect.html('<option>(Select an agent first)</option>');

	jQuery('#agent_select').on('change', (e) => {
		var value = $(e.currentTarget).val();
		var data = [];
		for(var i = 0; i < dataOptions.length; i ++ ) {
			if ( dataOptions[i].agent == value ) {
				data.push( dataOptions[i] );
			}
		}
		$userSelect.select2( 'destroy' );
		$userSelect.html('');
		$userSelect.select2( { disabled: false, data: data } );
	});
	jQuery('#agent_select + select[name="user_id"]').select2( { disabled: true } );

	$('.remove-lead').on('click', function (e) {
		e.preventDefault();
		if(window.confirm('Do you really want to delete this user/lead?')) {
			let data = {
				'action': 'remove_lead',
				'id': $(this).data('id'),
			}

			$.post('/wp-admin/admin-ajax.php', data, function (response) {
				alert(response);
				location.reload();
			});
		}
	});
} );