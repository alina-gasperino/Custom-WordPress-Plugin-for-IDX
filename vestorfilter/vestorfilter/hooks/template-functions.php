<?php

namespace VestorFilter;

function use_icon( $icon_id ) {
	return '<svg class="vf-use-icon vf-use-icon--' . $icon_id . '"><use xlink:href="#' . $icon_id . '"></use></svg>';
}