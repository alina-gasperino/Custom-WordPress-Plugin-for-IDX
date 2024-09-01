/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

import {
    useBlockProps,
    InspectorControls,
} from '@wordpress/block-editor';

import {
    Fragment,
    createRef,
	useState,
	setState
} from '@wordpress/element';

import {
    ToggleControl,
    RangeControl,
    Toolbar,
    TextControl,
} from '@wordpress/components';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {

    const autoloadFromURL = ( { pastedUrl } ) => {

        if ( pastedUrl.length === 0 ) {
            return;
        }

        // https://vh.test/?l=r2u6q07e&g=3UfPv0AqSpc
        try {
            pastedUrl = new URL( pastedUrl );
        } catch( e ) {
            pastedUrl = false;
        }

        if ( ! pastedUrl || ! pastedUrl.searchParams.has( 'l' ) ) {
            alert( "Please enter a valid url generated from the Map share function." );
            return;
        }

        let fetchUrl = new URL( window.location.protocol + window.location.host + '/wp-json/vestorfilter/v1/search/get_shortlink_params' );
        fetchUrl.searchParams.set( 'l', pastedUrl.searchParams.get( 'l' ) );
        if ( pastedUrl.searchParams.has( 'g' ) ) {
            fetchUrl.searchParams.set( 'g', pastedUrl.searchParams.get( 'g' ) );
        }

        fetch( fetchUrl.toString(), {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        } )
        .then( response => {
            if ( ! response.ok ) {
                throw new Error( 'Network response was not ok' );
            }
            return response.json();
        } )
        .then( data => {

            //console.log( data.filters );

            let params = new URLSearchParams();
            for( let key in data.filters ) {
                //console.log( key );
                if ( data.filters.hasOwnProperty( key ) ) {
                    params.set( key, data.filters[key] );
                }
            }

            setAttributes( {
                filterParams: params.toString(),
                centerLat:    data.lat + '',
                centerLon:    data.lon + '',
                zoom:         parseInt( data.zoom )
            } );

        } )
        .catch( error => {
            alert( "There was an error retrieving this search, please ensure the URL is correct and try again." );
        } );

    }

	const ZoomControl = () => {
		//const [ zoom ] = useState( 12 );
		return (
			<RangeControl
				className="wp-block-vestorfilter-map__zoom"
				label={ __( 'Zoom level', 'vestorfilter' ) }
				value={ Number( attributes.zoom ) }
				onChange={ ( zoom ) => {
					setAttributes( { zoom } );
				} }
				min={ 8 }
				max={ 20 }
				step={ 1 }
				help={ __( 'A smaller number shows a wider area, larger is zoomed in', 'vestorfilter' ) }
			/>
		);
	}

    return (
        <Fragment>
            <Fragment>
                <InspectorControls key="setting">
                    <div id="vestorfilter-map-controls" className="wp-block-vestorfilter-map__sidebar">
                        <fieldset>
                            <legend className="screen-reader-text">
                                { __( 'Layout settings', 'vestorfilter' ) }
                            </legend>
                            <RangeControl
                                className="wp-block-vestorfilter-map__height-control"
                                label="Block height (% Ratio)"
                                value={ Number( attributes.height ?? 100 ) }
                                onChange={ ( height ) => setAttributes( { height } ) }
                                min={ 50 }
                                max={ 150 }
                                step={ 5 }
                            />
                        </fieldset>
                    </div>
                </InspectorControls>
            </Fragment>
            <div { ...useBlockProps() }>
                <TextControl
                    className={ "wp-block-vestorfilter-map__pasted-url" }
                    onChange={ ( pastedUrl ) => autoloadFromURL( { pastedUrl } ) }
                    placeholder={ __( 'Paste a URL copied from a map', 'vestorfilter' ) }
                />
                <p className="wp-block-vestorfilter-map__text">Or enter details manually...</p>
                <div className="wp-block-vestorfilter-map__fields">
                    <div className="wp-block-vestorfilter-map__field-row wp-block-vestorfilter-map__field-row--filters">
                        <label>Filters</label>
                        <TextControl
                            className={ "wp-block-vestorfilter-map__filter-params" }
                            value={ attributes.filterParams }
                            onChange={ ( filterParams ) => setAttributes( { filterParams } ) }
                            placeholder={ __( 'Enter query parameters here', 'vestorfilter' ) }
                        />
                    </div>
                    <div className="wp-block-vestorfilter-map__field-row wp-block-vestorfilter-map__field-row--center">
                        <label>Map center</label>
                        <TextControl
                            className={ "wp-block-vestorfilter-map__center-lat" }
                            value={ attributes.centerLat }
                            type="number"
                            step="0.00001"
                            onChange={ ( centerLat ) => setAttributes( { centerLat } ) }
                            placeholder={ __( 'Latitude', 'vestorfilter' ) }
                        />
                    </div>
                    <div className="wp-block-vestorfilter-map__field-row wp-block-vestorfilter-map__field-row--center">
                        <label>Map center</label>
                        <TextControl
                            className={ "wp-block-vestorfilter-map__center-lon" }
                            value={ attributes.centerLon }
                            type="number"
                            step="0.00001"
                            onChange={ ( centerLon ) => setAttributes( { centerLon } ) }
                            placeholder={ __( 'Longitude', 'vestorfilter' ) }
                        />
                    </div>
                    <div className="wp-block-vestorfilter-map__field-row wp-block-vestorfilter-map__field-row--zoom">
                        <ZoomControl />
                    </div>
                    <div className="wp-block-vestorfilter-map__field-row wp-block-vestorfilter-map__field-row--labels">
                        <ToggleControl
                            label="Show price labels"
                            className={ "wp-block-vestorfilter-map__labels" }
                            onChange={ ( value ) => {
                                setAttributes( { labels: value } );
                            } }
                            checked={ attributes.labels ?? false }
                        />
                    </div>
                </div>
            </div>
        </Fragment>
    );
}
