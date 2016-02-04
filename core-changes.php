<?php

add_filter(
	'mime_types',
	function( $mime_types ) {
		$mime_types['webp'] = 'image/webp';

		return $mime_types;
	}
);