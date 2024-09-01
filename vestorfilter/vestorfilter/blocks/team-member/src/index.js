/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/#registering-a-block
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Edit from './edit';
import save from './save';

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/#registering-a-block
 */
registerBlockType( 'vestorfilter/team-member', {
	/**
	 * @see https://make.wordpress.org/core/2020/11/18/block-api-version-2/
	 */
	apiVersion: 2,

	/**
	 * This is the display title for your block, which can be translated with `i18n` functions.
	 * The block inserter will show this name.
	 */
	title: __( 'Team Member', 'vestorfilter' ),

	/**
	 * This is a short description for your block, can be translated with `i18n` functions.
	 * It will be shown in the Block Tab in the Settings Sidebar.
	 */
	description: __(
		'Drop this on the page for a styled team member block. Ordering can be randomized using the "Team Member Group" block',
		'vestorfilter'
	),

	/**
	 * Blocks are grouped into categories to help users browse and discover them.
	 * The categories provided by core are `text`, `media`, `design`, `widgets`, and `embed`.
	 */
	category: 'widgets',

	/**
	 * An icon property should be specified to make it easier to identify a block.
	 * These can be any of WordPress’ Dashicons, or a custom svg element.
	 */
	icon: 'smiley',

	attributes: {
		backgroundId: { type: 'number', default: 0 },
		backgroundUrl: { 
			type: 'string', 
			default: '',
			source: 'attribute',
			attribute: 'src',
			selector: '.wp-block-vestorfilter-team-member--image img'
		},
		memberName: { 
			type: 'string',
			source: 'text',
			selector: 'h3'
		},
		memberTitle: {
			type: 'string',
			source: 'text',
			selector: 'p.wp-block-vestorfilter-team-member--subtitle'
		},
		memberPhone: {
			type: 'string',
			source: 'attribute',
			selector: 'a[data-phone]',
			attribute: 'data-phone',
		},
		memberUrl: {
			type: 'string',
			source: 'attribute',
			attribute: 'data-url',
			selector: 'a[data-url]'
		},
		memberEmail: {
			type: 'string',
			source: 'attribute',
			attribute: 'data-email',
			selector: 'a[data-email]'
		},
		memberBio: {
			type: 'string',
			source: 'html',
			selector: '.wp-block-vestorfilter-team-member--bio'
		},
		memberSocial: {
			type: 'string',
			default: '{}',
		}
	},

	/**
	 * Optional block extended support features.
	 */
	supports: {
		// Removes support for an HTML mode.
		html: false,
	},

	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * @see ./save.js
	 */
	save,
} );
