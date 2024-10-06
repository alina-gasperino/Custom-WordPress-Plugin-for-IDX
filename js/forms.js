( function( $ ) {

	$( document ).on( 'input change', 'input[data-frmrange]', function( e ) {
		
		let self = e.currentTarget;
		let valueObj = self.parentNode.querySelector( '.frm_range_value' );

		valueObj.innerHTML = self.value;

	} );

	$( document ).on( 'input change', '.frm_form_field input', function () {

		let downPayment = document.getElementById( 'field_downpayment_amt' );
		let salePrice = document.getElementById( 'field_mortgage_amt' );
		let pmi = document.getElementById( 'field_pmi' );

		if ( pmi && downPayment && salePrice ) {

			if ( downPayment.value / salePrice.value < 0.2 && pmi.value == '0' ) {

				pmi.value = '1.5';
				pmi.parentNode.querySelector( '.frm_range_value' ).innerHTML = '1.5';

			}

		}

	} );

	const setupShareUrls = () => {
		
		let currentUrl = window.location
		let newUrlString = currentUrl.protocol + '//' + currentUrl.hostname + currentUrl.pathname;

		let shareLinks = document.querySelectorAll( '[data-form-share]' );
		for ( let sharer of shareLinks ) {

			let $thisForm = $( sharer ).closest( 'form' );
			let fieldValues = [];

			$thisForm.find( '.is-sharable input' ).each( ( index, el ) => {
				fieldValues.push( el.id + '=' + el.value );
			} );

			let shareString = newUrlString + '?' + fieldValues.join( '&' );

			let shareAnchors = sharer.querySelectorAll( 'a' );
			for ( let anchor of shareAnchors ) {
				let replaceWith = anchor.href;
				if ( anchor.dataset.originalUrl ) {
					replaceWith = anchor.dataset.originalUrl;
				} else {
					anchor.dataset.originalUrl = anchor.href;
				}
				replaceWith = replaceWith.replace( 'REPLACE_THIS', encodeURIComponent( shareString ) );
				anchor.href = replaceWith;
			}
			let copyBtn = sharer.querySelector( '[data-copy-url]' );
			let replaceWith = copyBtn.dataset.copyUrl;
			if ( copyBtn.dataset.originalUrl ) {
				replaceWith = copyBtn.dataset.originalUrl;
			} else {
				copyBtn.dataset.originalUrl = copyBtn.dataset.copyUrl;
			}
			replaceWith = replaceWith.replace( 'REPLACE_THIS', shareString );
			copyBtn.dataset.copyUrl = replaceWith;

		}

	};

	setupShareUrls();

	$( document ).on( 'input change', '.frm_form_field input', setupShareUrls );

	const setupPropertyAccordions = ( parentNode ) => {

		if ( ! parentNode ) {
			parentNode = document;
		}

		let ctaH3 = parentNode.querySelector( '.mortgage-calculator .calculator-cta h3' );
		let submitSection;

		if ( ctaH3 ) {
			ctaH3.innerHTML = 'Get Pre-approved';
			submitSection = parentNode.querySelector( '.mortgage-calculator .frm_submit' );
			submitSection.setAttribute( 'aria-hidden', 'true' );
		}

		let sections = parentNode.querySelectorAll( 
			'.mortgage-calculator .calculator-fields, .mortgage-calculator .calculator-adtl, .mortgage-calculator .calculator-cta'
		);
		for ( let section of sections ) {

			section.classList.add( 'collapse' );
			section.classList.add( 'accordion' );
			section.setAttribute( 'aria-hidden', 'true' );

			let sectionH3 = section.querySelector( 'h3' );

			let sectionBtn = document.createElement( 'button' );
			sectionBtn.setAttribute( 'aria-controls', section.id );
			sectionBtn.setAttribute( 'aria-expanded', 'false' );
			sectionBtn.classList.add( 'btn' );
			sectionBtn.classList.add( 'with-icon' );
			sectionBtn.classList.add( 'with-icon__down-caret' );
			sectionBtn.classList.add( 'accordion-toggle' );
			sectionBtn.innerHTML = `<span>${sectionH3.innerHTML}</span>`;
			sectionBtn.type = "button";
			sectionBtn.addEventListener( 'click', (e) => {
				e.preventDefault();
				e.stopPropagation();
				
				a11yToggleExpand( e );
				

				/*let thisSection = document.getElementById( e.currentTarget.getAttribute( 'aria-controls' ) );
				if ( thisSection.classList.contains( 'calculator-cta' ) ) {
					if ( e.currentTarget.getAttribute( 'aria-expanded' ) === "true" ) {
						submitSection.setAttribute( 'aria-hidden', 'false' );
					} else {
						submitSection.setAttribute( 'aria-hidden', 'true' );
					}
				}*/
			} );

			section.parentNode.insertBefore( sectionBtn, section );
			section.addEventListener( 'click', (e) => {
				e.stopPropagation();
			} );

		}

	};

	document.addEventListener( 'vestorfilters|calculator-attached', ( { detail } ) => {
		setupPropertyAccordions( detail );
	} );
	
	const disableSwiping = ( disableTarget ) => {

		disableTarget.dataset.noSwiping = true;

	};

	/*let calcColumn = document.querySelector( '.property-template__calculator' );
	if ( calcColumn ) {
		setupPropertyAccordions( calcColumn );
		let sliders = calcColumn.querySelectorAll( '.frm_range_container' );
		for ( let slider of sliders ) {
			disableSwiping( slider.parentNode );
		}
	}

	document.addEventListener( 'vestorfilters|property-opened', ( { detail } ) => {
		
		let calculator = document.getElementById( 'mortgage-calculator' );
		let placeItShouldGo = detail.querySelector( '.property-template__secondary .column-left' );

		if ( calculator && placeItShouldGo ) {
			if ( placeItShouldGo.querySelector( '#mortgage-calculator') ) {
				return;
			}
			placeItShouldGo.append( calculator );
			calculator.querySelector( 'h3' ).innerHTML = detail.originalCalculator.querySelector('h3').innerHTML;
			let fieldsToSwap = detail.originalCalculator.querySelectorAll( 'input:not([type="hidden"])' );
			//vfDebug( fieldsToSwap );
			for( let field of fieldsToSwap ) {

				let frmField = calculator.querySelector( '#' + field.id );
				if ( frmField ) {
					frmField.value = field.value;
				}
				$( frmField ).trigger("change");
			}
			$( calculator ).find('#field_mortgage_amt,#field_property_tax_value').trigger('change');

			if ( calculator.style.display === 'none' ) {
				calculator.style.display = '';
			}
		}

	} );*/

	/*$("form.mortgage-calculator").on( "keydown", ":input:not(textarea):not(:submit)", function( event ) {
		if ( event.keyCode === 13 ) {
			event.preventDefault();
		}
	});*/

} )( jQuery );
