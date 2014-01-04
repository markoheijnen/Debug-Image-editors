<?php
/*
Plugin Name: Debug Image Editors
Plugin URI: http://wordpress.org/extend/plugins/default-to-gd
Description: Creates an page in the back-end to test the behavior of all the image editors
Author: Marko Heijnen
Version: 1.0
Author URI: http://www.markoheijnen.com
*/

class Debug_Image_Editor {
	private $image_editor = false;
	private $file;

	public function __construct() {
		$this->file = dirname( __FILE__ ) . '/amsterdam.jpg';

		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'wp_image_editors', array( $this, 'get_image_editor' ), 1000 );
	}

	public function init() {
		$image_editors = $this->image_editors();

		echo '<table><tr>';

		foreach( $image_editors as $image_editor ) {
			$this->set_image_editor( $image_editor );

			echo '<td>';

			echo '<h1>' . $image_editor . '</h1>';

			$methods = get_class_methods( __CLASS__ );

			foreach( $methods as $method ) {
				if( strpos( $method, 'example' ) !== false ) {
					echo '<h2>' . $method . '</h2>';

					$data = call_user_func_array( array( $this, $method ), array( $image_editor ) );

					if( ! is_wp_error( $data ) ) {
						echo '<img src="' . plugins_url( $data['file'], __FILE__ ) . '" />';
					}

					echo '<pre style="white-space: pre-wrap; word-wrap:break-word;">';
					var_dump( $data );
					echo '</pre>';

					echo '<div style="height: 30px; clear: both;"></div>';
				}
			}

			echo '</td>';
		}

		die();
	}

	private function image_editors() {
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

		$implementations = apply_filters( 'wp_image_editors',
			array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );

		$editors = array();

		foreach ( $implementations as $implementation ) {
			if ( ! call_user_func( array( $implementation, 'test' ), array() ) )
				continue;

			$editors[] = $implementation;
		}

		return $editors;
	}


	private function set_image_editor( $image_editor ) {
		$this->image_editor = $image_editor;
	}

	public function get_image_editor( $image_editors ) {
		if ( $this->image_editor )
			return array( $this->image_editor );

		return $image_editors;
	}


	function example1( $method ) {
		$editor = wp_get_image_editor( $this->file );

		if( ! is_wp_error( $editor ) ) {
			$editor->resize( 300, 300, true );

			return $editor->save( dirname( __FILE__ ) . '/' . $method . '-test1.jpg' ); // Save the file as /path/to/image-100x100.jpeg
		}

		return $editor;
	}

	function example2( $method ) {
		$editor = wp_get_image_editor( $this->file );

		if( ! is_wp_error( $editor ) ) {
			// $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs
			$editor->crop( 0, 0, 300, 300, 300, 300, false );

			return $editor->save( dirname( __FILE__ ) . '/' . $method . '-test2.jpg'  ); // Save the file as /path/to/image-100x100.jpeg
		}

		return $editor;
	}

	function example3( $method ) {
		$editor = wp_get_image_editor( $this->file );

		if( ! is_wp_error( $editor ) ) {
			$editor->flip( false, true );

			$editor->rotate( 90 );

			$editor->resize( 0, 512 );

			return $editor->save( dirname( __FILE__ ) . '/' . $method . '-test3.jpg'  ); // Save the file as /path/to/image-100x100.jpeg
		}

		return $editor;
	}

	/*
	 * Broken example for imagick till 3.7.
	 * 0.7 is the goldenrule (selfclaimed) to never have black boundaries
	 */
	function example4( $method ) {
		$editor = wp_get_image_editor( $this->file );

		if( ! is_wp_error( $editor ) ) {
			$original = $editor->get_size();

			$editor->flip( false, true );
			$editor->rotate( 30 );

			$size = $editor->get_size();

			$editor->crop(
				( $size['width'] - ( $original['width'] * 0.7 ) ) / 2,
				( $size['height'] - ( $original['height'] * 0.7 ) ) / 2,
				$original['width'] * 0.7,
				$original['height'] * 0.7,
				null,
				null,
				false
			);
			
			$editor->resize( 300, 300, true );

			$info = pathinfo( $this->file );

			return $editor->save( dirname( __FILE__ ) . '/' . $method . '-test4.jpg'  ); // Save the file as /path/to/image-100x100.jpeg
		}

		return $editor;
	}

}

$debug_image_editor = new Debug_Image_Editor;
