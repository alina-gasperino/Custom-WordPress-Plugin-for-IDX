!function(e){var t={};function r(a){if(t[a])return t[a].exports;var n=t[a]={i:a,l:!1,exports:{}};return e[a].call(n.exports,n,n.exports,r),n.l=!0,n.exports}r.m=e,r.c=t,r.d=function(e,t,a){r.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:a})},r.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},r.t=function(e,t){if(1&t&&(e=r(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var a=Object.create(null);if(r.r(a),Object.defineProperty(a,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var n in e)r.d(a,n,function(t){return e[t]}.bind(null,n));return a},r.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return r.d(t,"a",t),t},r.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},r.p="",r(r.s=6)}([function(e,t){e.exports=window.wp.element},function(e,t){e.exports=window.wp.blockEditor},function(e,t){e.exports=window.wp.i18n},function(e,t){e.exports=window.wp.components},function(e,t){e.exports=window.wp.blocks},function(e,t,r){},function(e,t,r){"use strict";r.r(t);var a=r(4),n=r(2),l=r(0),c=r(1),o=r(3);function i(e,t){(null==t||t>e.length)&&(t=e.length);for(var r=0,a=new Array(t);r<t;r++)a[r]=e[r];return a}function m(e){var t=e.value;return t&&t.length>0?Object(l.createElement)("p",{className:"wp-block-vestorfilter-team-member--subtitle"},t):null}function s(e){var t=e.values,r=[];if(t.memberEmail&&r.push(Object(l.createElement)("a",{className:"wp-block-vestorfilter-team-member--email",href:"mailto:"+t.memberEmail,"data-email":t.memberEmail},Object(l.createElement)("svg",{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"},Object(l.createElement)("path",{d:"M20,4H4C2.897,4,2,4.897,2,6v12c0,1.103,0.897,2,2,2h16c1.103,0,2-0.897,2-2V6C22,4.897,21.103,4,20,4z M20,6v0.511 l-8,6.223L4,6.512V6H20z M4,18V9.044l7.386,5.745C11.566,14.93,11.783,15,12,15s0.434-0.07,0.614-0.211L20,9.044L20.002,18H4z"})))),t.memberPhone&&r.push(Object(l.createElement)("a",{className:"wp-block-vestorfilter-team-member--phone",href:"tel:"+t.memberPhone,"data-phone":t.memberPhone},'[icon id="call"]')),t.memberUrl){var a=t.memberUrl;0!==a.indexOf("http")&&(a="https://"+a),r.push(Object(l.createElement)("a",{className:"wp-block-vestorfilter-team-member--link",href:a,"data-url":t.memberUrl},Object(l.createElement)("svg",{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"},Object(l.createElement)("path",{d:"M12,2C6.486,2,2,6.486,2,12s4.486,10,10,10s10-4.486,10-10S17.514,2,12,2z M19.931,11h-2.764 c-0.116-2.165-0.73-4.3-1.792-6.243C17.813,5.898,19.582,8.228,19.931,11z M12.53,4.027c1.035,1.364,2.427,3.78,2.627,6.973H9.03 c0.139-2.596,0.994-5.028,2.451-6.974C11.653,4.016,11.825,4,12,4C12.179,4,12.354,4.016,12.53,4.027z M8.688,4.727 C7.704,6.618,7.136,8.762,7.03,11H4.069C4.421,8.204,6.217,5.857,8.688,4.727z M4.069,13h2.974c0.136,2.379,0.665,4.478,1.556,6.23 C6.174,18.084,4.416,15.762,4.069,13z M11.45,19.973C10.049,18.275,9.222,15.896,9.041,13h6.113 c-0.208,2.773-1.117,5.196-2.603,6.972C12.369,19.984,12.187,20,12,20C11.814,20,11.633,19.984,11.45,19.973z M15.461,19.201 c0.955-1.794,1.538-3.901,1.691-6.201h2.778C19.587,15.739,17.854,18.047,15.461,19.201z"}))))}if(t.memberSocial&&t.memberSocial.length>0){var n=JSON.parse(t.memberSocial);for(var c in n)r.push(Object(l.createElement)("a",{className:"wp-block-vestorfilter-team-member__social",href:n[c]},'[icon id="'.concat(c,'"]')))}return r.length>0?Object(l.createElement)("div",{className:"wp-block-vestorfilter-team-member--icons"},r):null}function u(e){var t=e.children;return t&&t.length>0?Object(l.createElement)(l.RawHTML,{className:"wp-block-vestorfilter-team-member--bio"},t):null}r(5),Object(a.registerBlockType)("vestorfilter/team-member",{apiVersion:2,title:Object(n.__)("Team Member","vestorfilter"),description:Object(n.__)('Drop this on the page for a styled team member block. Ordering can be randomized using the "Team Member Group" block',"vestorfilter"),category:"widgets",icon:"smiley",attributes:{backgroundId:{type:"number",default:0},backgroundUrl:{type:"string",default:"",source:"attribute",attribute:"src",selector:".wp-block-vestorfilter-team-member--image img"},memberName:{type:"string",source:"text",selector:"h3"},memberTitle:{type:"string",source:"text",selector:"p.wp-block-vestorfilter-team-member--subtitle"},memberPhone:{type:"string",source:"attribute",selector:"a[data-phone]",attribute:"data-phone"},memberUrl:{type:"string",source:"attribute",attribute:"data-url",selector:"a[data-url]"},memberEmail:{type:"string",source:"attribute",attribute:"data-email",selector:"a[data-email]"},memberBio:{type:"string",source:"html",selector:".wp-block-vestorfilter-team-member--bio"},memberSocial:{type:"string",default:"{}"}},supports:{html:!1},edit:function(e){var t=e.attributes,r=e.setAttributes,a=Object(l.createRef)(),m=JSON.parse(t.memberSocial)||{},s=function(e){var t=e.url;e.sizes&&e.sizes.medium&&(t=e.sizes.medium.url),r({backgroundId:e.id,backgroundUrl:t})},u=function(e){var t,r=e.currentTarget,n=a.current.querySelector("select"),l=function(e,t){var r;if("undefined"==typeof Symbol||null==e[Symbol.iterator]){if(Array.isArray(e)||(r=function(e,t){if(e){if("string"==typeof e)return i(e,void 0);var r=Object.prototype.toString.call(e).slice(8,-1);return"Object"===r&&e.constructor&&(r=e.constructor.name),"Map"===r||"Set"===r?Array.from(e):"Arguments"===r||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?i(e,void 0):void 0}}(e))||t&&e&&"number"==typeof e.length){r&&(e=r);var a=0,n=function(){};return{s:n,n:function(){return a>=e.length?{done:!0}:{done:!1,value:e[a++]}},e:function(e){throw e},f:n}}throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}var l,c=!0,o=!1;return{s:function(){r=e[Symbol.iterator]()},n:function(){var e=r.next();return c=e.done,e},e:function(e){o=!0,l=e},f:function(){try{c||null==r.return||r.return()}finally{if(o)throw l}}}}(n.querySelectorAll("option"));try{for(l.s();!(t=l.n()).done;){var c=t.value;c.value===r.dataset.network?(c.disabled=!1,c.selected=!0):c.disabled=!0}}catch(e){l.e(e)}finally{l.f()}n.value=r.dataset.network,a.current.querySelector("input").value=r.dataset.href,a.current.classList.add("is-visible")};return t.backgroundId?Object(l.createElement)(l.Fragment,null,Object(l.createElement)(l.Fragment,null,Object(l.createElement)(c.BlockControls,null,Object(l.createElement)(o.Toolbar,null,Object(l.createElement)(c.MediaUpload,{value:t.backgroundId||"",allowedTypes:["image"],onSelect:s,render:function(e){return Object(l.createElement)(o.Button,{className:"components-toolbar__control",label:Object(n.__)("Edit media","vestorfilter"),icon:"format-image",onClick:e.open})}})))),Object(l.createElement)("div",Object(c.useBlockProps)(),Object(l.createElement)("div",{className:"left-controls"},Object(l.createElement)(c.MediaUpload,{value:t.backgroundId||"",allowedTypes:["image"],onSelect:s,render:function(e){return Object(l.createElement)("img",{className:"wp-block-vestorfilter-team-member--image",src:t.backgroundUrl,onClick:e.open})}}),Object(l.createElement)((function(e){var t=e.values,r=[];for(var a in t)r.push(Object(l.createElement)(o.Button,{className:"is-pressed edit-social-media",onClick:u,"data-network":a,"data-href":t[a]},a," ",Object(l.createElement)("span",{class:"dashicons dashicons-edit"})));return 0===r.length?null:r}),{values:m}),Object(l.createElement)(o.Button,{className:"add-social-media",onClick:function(){for(var e in m){var t=a.current.querySelector('option[value="'.concat(e,'"]'));t&&(t.disabled=!0)}a.current.querySelector("select").value="",a.current.querySelector("input").value="",a.current.classList.add("is-visible"),a.current.addEventListener("click",(function(e){e.currentTarget===e.target&&e.currentTarget.classList.remove("is-visible")}))}},Object(n.__)("Add Social Media","vestorfilter"))),Object(l.createElement)("div",{className:"social-controls-popup",ref:a},Object(l.createElement)("select",{className:"components-select-control__input"},Object(l.createElement)("option",{value:""},"(Select Media Site)"),Object(l.createElement)("option",{value:"facebook"},"Facebook"),Object(l.createElement)("option",{value:"linkedin"},"LinkedIn"),Object(l.createElement)("option",{value:"instagram"},"Instagram"),Object(l.createElement)("option",{value:"pinterest"},"Pinterest"),Object(l.createElement)("option",{value:"twitter"},"Twitter"),Object(l.createElement)("option",{value:"youtube"},"Youtube")),Object(l.createElement)("input",{className:"components-text-control__input",placeholder:Object(n.__)("Paste URL","vestorfilter")}),Object(l.createElement)("button",{className:"components-button is-pressed",type:"button",onClick:function(){var e=a.current.querySelector("select"),t=a.current.querySelector("input");""===t.value||0===t.value.length?m[e.value]&&delete m[e.value]:m[e.value]=t.value,r({memberSocial:JSON.stringify(m)}),a.current.classList.remove("is-visible")}},"Save")),Object(l.createElement)(c.PlainText,{className:"wp-block-vestorfilter-team-member--name",value:t.memberName,onChange:function(e){return r({memberName:e})},placeholder:Object(n.__)("Enter team member name here","vestorfilter")}),Object(l.createElement)(c.PlainText,{className:"wp-block-vestorfilter-team-member--subtitle",value:t.memberTitle,onChange:function(e){return r({memberTitle:e})},placeholder:Object(n.__)("Enter subtitle here","vestorfilter")}),Object(l.createElement)(c.PlainText,{className:"wp-block-vestorfilter-team-member--phone",value:t.memberPhone,onChange:function(e){return r({memberPhone:e})},placeholder:Object(n.__)("Team member phone number","vestorfilter")}),Object(l.createElement)(c.PlainText,{className:"wp-block-vestorfilter-team-member--link",value:t.memberUrl,onChange:function(e){return r({memberUrl:e})},placeholder:Object(n.__)("Team member website URL","vestorfilter")}),Object(l.createElement)(c.PlainText,{className:"wp-block-vestorfilter-team-member--email",value:t.memberEmail,onChange:function(e){return r({memberEmail:e})},placeholder:Object(n.__)("Team member email address","vestorfilter")}),Object(l.createElement)(c.RichText,{className:"wp-block-vestorfilter-team-member--bio",value:t.memberBio,onChange:function(e){return r({memberBio:e})},placeholder:Object(n.__)("Team member bio","vestorfilter"),tagName:"div",multiline:"p"}))):Object(l.createElement)("div",Object(c.useBlockProps)(),Object(l.createElement)(c.MediaPlaceholder,{onSelect:s,className:"wp-block-vestorfilter-team-member--placeholder",labels:{title:"Team member image"}}))},save:function(e){var t=e.attributes;return Object(l.createElement)("div",c.useBlockProps.save(),Object(l.createElement)("figure",{className:"wp-block-vestorfilter-team-member--image"},Object(l.createElement)("img",{src:t.backgroundUrl||""})),Object(l.createElement)("h3",{className:"wp-block-vestorfilter-team-member--name"},t.memberName),Object(l.createElement)(m,{value:t.memberTitle}),Object(l.createElement)(s,{values:t}),Object(l.createElement)(u,null,t.memberBio))}})}]);