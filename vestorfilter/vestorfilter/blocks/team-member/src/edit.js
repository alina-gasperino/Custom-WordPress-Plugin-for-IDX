/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-block-editor/#useBlockProps
 */
import { 
	useBlockProps,
	BlockControls,
	MediaUpload,
	MediaPlaceholder,
	PlainText,
	RichText
} from '@wordpress/block-editor';

import {
	Fragment,
	createRef
} from '@wordpress/element';

import {
	Button,
	PanelBody,
	ToggleControl,
	Toolbar
} from '@wordpress/components';

import './editor.scss';

function ImageOrPlaceholder( { src, onSelect } ) {

	if ( src && src.length > 0 ) {

		return (
			<img className="wp-block-team-member--image" src={ src } />
		);

	} else {

		return (
			<MediaPlaceholder 
				onSelect={ onSelect }
				className="wp-block-vestorfilter-team-member--image" 
				labels={ { title: 'Team member image' } 
			} />
		);

	}

}

export default function Edit( { attributes, setAttributes } ) {

	let socialPopupRef = createRef();

	let memberSocial = JSON.parse( attributes.memberSocial ) || {};

	const setMedia = ( media ) => {
		let url = media.url;
		if ( media.sizes && media.sizes.medium ) {
			url = media.sizes.medium.url;
		}
		setAttributes( {
			backgroundId: media.id,
			backgroundUrl: url,
		} );
	};

	const addSocial = () => {

		for ( let selected in memberSocial ) {
			let option = socialPopupRef.current.querySelector( `option[value="${selected}"]` );
			if ( option ) {
				option.disabled = true;
			}
		}

		let select = socialPopupRef.current.querySelector( `select` );
		select.value = '';

		let input = socialPopupRef.current.querySelector( `input` );
		input.value = '';

		socialPopupRef.current.classList.add( 'is-visible' );

		socialPopupRef.current.addEventListener( 'click', ( e ) => {
			if ( e.currentTarget === e.target ) {
				e.currentTarget.classList.remove( 'is-visible' );
			}
		} )

	};

	const saveSocial = () => {

		let select = socialPopupRef.current.querySelector( `select` );
		let input = socialPopupRef.current.querySelector( `input` );
		
		if ( input.value === '' || input.value.length === 0 ) {
			if ( memberSocial[ select.value ] ) {
				delete memberSocial[ select.value ];
			}
		} else {
			memberSocial[ select.value ] = input.value;
		}

		setAttributes( { memberSocial: JSON.stringify( memberSocial ) } );

		socialPopupRef.current.classList.remove( 'is-visible' );

	};

	const editSocial = ( e ) => {

		let thisButton = e.currentTarget;

		let select = socialPopupRef.current.querySelector( `select` );
		let selectOptions = select.querySelectorAll( `option` );

		for ( let option of selectOptions ) {
			if ( option.value === thisButton.dataset.network ) {
				option.disabled = false;
				option.selected = true;
			} else {
				option.disabled = true;
			}
		}

		select.value = thisButton.dataset.network;

		let input = socialPopupRef.current.querySelector( `input` );
		input.value = thisButton.dataset.href;

		socialPopupRef.current.classList.add( 'is-visible' );

	};
		
	function SocialMediaLinks( { values } ) {

		let buttons = [];
		for ( let network in values ) {
			buttons.push( (
				<Button 
					className={ "is-pressed edit-social-media" }
					onClick={ editSocial }
					data-network={ network }
					data-href={ values[network] }
				>{ network } <span class="dashicons dashicons-edit"></span></Button>
			) )
		}
		if ( buttons.length === 0 ) {
			return null;
		}
		return buttons;
	}

	if ( ! attributes.backgroundId ) {
		return (
			<div { ...useBlockProps() }>
				<MediaPlaceholder 
					onSelect={ setMedia }
					className="wp-block-vestorfilter-team-member--placeholder" 
					labels={ { title: 'Team member image' } 
				} />
			</div>
		);
	}

	return (
		<Fragment>
			<Fragment>
				<BlockControls>
					<Toolbar>
						<MediaUpload 
							value={ attributes.backgroundId || '' }
							allowedTypes={ [ 'image' ] }
							onSelect={ setMedia }
							render={ ( renderProps ) => (
								<Button 
									className={ "components-toolbar__control" }
									label={ __( 'Edit media', 'vestorfilter' ) }
									icon={ 'format-image' }
									onClick={ renderProps.open }
								/>
							) }
						/>
					</Toolbar>
				</BlockControls>
			</Fragment>
			<div { ...useBlockProps() }>
				<div className={ "left-controls" }>
					<MediaUpload 
						value={ attributes.backgroundId || '' }
						allowedTypes={ [ 'image' ] }
						onSelect={ setMedia }
						render={ ( renderProps ) => (
							<img 
								className="wp-block-vestorfilter-team-member--image" 
								src={ attributes.backgroundUrl } 
								onClick={ renderProps.open }
							/>
						) }
					/>
					<SocialMediaLinks values={ memberSocial } />
					<Button 
						className={ "add-social-media" }
						onClick={ addSocial }
					>{ __( 'Add Social Media', 'vestorfilter' ) }</Button>
				</div>
				<div className={ "social-controls-popup" } ref={ socialPopupRef }>
					<select className={ "components-select-control__input" }>
						<option value="">(Select Media Site)</option>
						<option value="facebook">Facebook</option>
						<option value="linkedin">LinkedIn</option>
						<option value="instagram">Instagram</option>
						<option value="pinterest">Pinterest</option>
						<option value="twitter">Twitter</option>
						<option value="youtube">Youtube</option>
					</select>
					<input className={ "components-text-control__input" } placeholder={ __( 'Paste URL', 'vestorfilter' ) } />
					<button 
						className={ "components-button is-pressed" }
						type="button"
						onClick={ saveSocial }
					>Save</button>
				</div>
				<PlainText
					className={ "wp-block-vestorfilter-team-member--name" }
					value={ attributes.memberName }
					onChange={ ( memberName ) => setAttributes( { memberName } ) }
					placeholder={ __( 'Enter team member name here', 'vestorfilter' ) }
				/>
				<PlainText
					className={ "wp-block-vestorfilter-team-member--subtitle" }
					value={ attributes.memberTitle }
					onChange={ ( memberTitle ) => setAttributes( { memberTitle } ) }
					placeholder={ __( 'Enter subtitle here', 'vestorfilter' ) }
				/>
				<PlainText
					className={ "wp-block-vestorfilter-team-member--phone" }
					value={ attributes.memberPhone }
					onChange={ ( memberPhone ) => setAttributes( { memberPhone } ) }
					placeholder={ __( 'Team member phone number', 'vestorfilter' ) }
				/>
				<PlainText
					className={ "wp-block-vestorfilter-team-member--link" }
					value={ attributes.memberUrl }
					onChange={ ( memberUrl ) => setAttributes( { memberUrl } ) }
					placeholder={ __( 'Team member website URL', 'vestorfilter' ) }
				/>
				<PlainText
					className={ "wp-block-vestorfilter-team-member--email" }
					value={ attributes.memberEmail }
					onChange={ ( memberEmail ) => setAttributes( { memberEmail } ) }
					placeholder={ __( 'Team member email address', 'vestorfilter' ) }
				/>
				<RichText
					className={ "wp-block-vestorfilter-team-member--bio" }
					value={ attributes.memberBio }
					onChange={ ( memberBio ) => setAttributes( { memberBio } ) }
					placeholder={ __( 'Team member bio', 'vestorfilter' ) }
					tagName="div"
					multiline="p"
				/>
			</div>
		</Fragment>
	);
}
