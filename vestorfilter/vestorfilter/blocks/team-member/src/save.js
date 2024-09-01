/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

import {
	RawHTML
} from '@wordpress/element';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-block-editor/#useBlockProps
 */
import { useBlockProps } from '@wordpress/block-editor';

function MemberSubtitle( { value } ) {

	if ( value && value.length > 0 ) {
		return (
			<p className={ "wp-block-vestorfilter-team-member--subtitle" }>{ value }</p>
		);
	} else {
		return null;
	}

};

function MemberIcons( { values } ) {

	let icons = [];

	if ( values.memberEmail ) {
		icons.push( (
			<a className={ "wp-block-vestorfilter-team-member--email" } href={ 'mailto:' + values.memberEmail } data-email={ values.memberEmail }><svg xmlns={ "http://www.w3.org/2000/svg" } viewBox={ "0 0 24 24" }><path d={ "M20,4H4C2.897,4,2,4.897,2,6v12c0,1.103,0.897,2,2,2h16c1.103,0,2-0.897,2-2V6C22,4.897,21.103,4,20,4z M20,6v0.511 l-8,6.223L4,6.512V6H20z M4,18V9.044l7.386,5.745C11.566,14.93,11.783,15,12,15s0.434-0.07,0.614-0.211L20,9.044L20.002,18H4z" }></path></svg></a>
		) );
	}

	if ( values.memberPhone ) {
		icons.push( (
			<a className={ "wp-block-vestorfilter-team-member--phone" } href={ 'tel:' + values.memberPhone } data-phone={ values.memberPhone }>{ `[icon id="call"]` }</a>
		) );
	}

	if ( values.memberUrl ) {
		let cleanedUrl = values.memberUrl;
		if ( cleanedUrl.indexOf( 'http' ) !== 0 ) {
			cleanedUrl = 'https://' + cleanedUrl;
		}
		icons.push( (
			<a className={ "wp-block-vestorfilter-team-member--link" } href={ cleanedUrl } data-url={ values.memberUrl }><svg xmlns={ "http://www.w3.org/2000/svg" } viewBox={ "0 0 24 24" }><path d={ "M12,2C6.486,2,2,6.486,2,12s4.486,10,10,10s10-4.486,10-10S17.514,2,12,2z M19.931,11h-2.764 c-0.116-2.165-0.73-4.3-1.792-6.243C17.813,5.898,19.582,8.228,19.931,11z M12.53,4.027c1.035,1.364,2.427,3.78,2.627,6.973H9.03 c0.139-2.596,0.994-5.028,2.451-6.974C11.653,4.016,11.825,4,12,4C12.179,4,12.354,4.016,12.53,4.027z M8.688,4.727 C7.704,6.618,7.136,8.762,7.03,11H4.069C4.421,8.204,6.217,5.857,8.688,4.727z M4.069,13h2.974c0.136,2.379,0.665,4.478,1.556,6.23 C6.174,18.084,4.416,15.762,4.069,13z M11.45,19.973C10.049,18.275,9.222,15.896,9.041,13h6.113 c-0.208,2.773-1.117,5.196-2.603,6.972C12.369,19.984,12.187,20,12,20C11.814,20,11.633,19.984,11.45,19.973z M15.461,19.201 c0.955-1.794,1.538-3.901,1.691-6.201h2.778C19.587,15.739,17.854,18.047,15.461,19.201z" }></path></svg></a>
		) );
	}

	if ( values.memberSocial && values.memberSocial.length > 0 ) {
		let memberSocial = JSON.parse( values.memberSocial );
		for ( let network in memberSocial ) {
			icons.push( (
				<a className={ "wp-block-vestorfilter-team-member__social" } href={ memberSocial[network] }>{ `[icon id="${network}"]` }</a>
			) );
		}
	}

	if ( icons.length > 0 ) {
		return (
			<div className={ "wp-block-vestorfilter-team-member--icons" }>
				{ icons }
			</div>
		);
	} else {
		return null;
	}

};

function MemberBio( { children } ) {
	
	if ( children && children.length > 0 ) {
		return (
			<RawHTML className={ "wp-block-vestorfilter-team-member--bio" }>
				{ children }
			</RawHTML>
		);
	} else {
		return null;
	}
	

};

export default function save( { attributes } ) {
	return (
		<div { ...useBlockProps.save() }>
			<figure className={ "wp-block-vestorfilter-team-member--image" }><img src={ attributes.backgroundUrl || '' } /></figure>
			<h3 className={ "wp-block-vestorfilter-team-member--name" }>{ attributes.memberName }</h3>
			<MemberSubtitle value={ attributes.memberTitle } />
			<MemberIcons values={ attributes } />
			<MemberBio>{ attributes.memberBio }</MemberBio>
		</div>
	);
}
