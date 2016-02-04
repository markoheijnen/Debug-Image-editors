<?php
/*
Plugin Name: Debug Image Editors
Plugin URI: http://wordpress.org/extend/plugins/default-to-gd
Description: Creates an page in the back-end to test the behavior of all the image editors
Author: Marko Heijnen
Version: 1.0
Author URI: http://www.markoheijnen.com
*/

include 'core-changes.php';

class Debug_Image_Editor {
	private $image_editor = false;
	private $file;
	private $storage_dir;

	public function __construct() {
		register_deactivation_hook( __FILE__, array( $this, 'on_deactivation' ) );

		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_filter( 'wp_image_editors', array( $this, 'get_image_editor' ), 1000 );
	}


	public function on_deactivation() {
		$upload_dir  = wp_upload_dir();
		$storage_dir = $upload_dir['basedir'] . '/debug-image-editors/';

		$files = glob( $storage_dir . '*', GLOB_MARK );

		foreach ( $files as $file ) {
			unlink( $file ); 
		} 

		rmdir( $storage_dir );
	}


	public function add_admin_page() {
		add_management_page( 'Debug Image Editors', 'Debug Image Editors', 'manage_options', 'debug-image-editors', array( $this, 'show_images' ) );
	}

	public function show_images() {
		$upload_dir        = wp_upload_dir();
		$this->file        = dirname( __FILE__ ) . '/amsterdam.jpg';
		$this->storage_dir = $upload_dir['basedir'] . '/debug-image-editors';
		$storage_url       = $upload_dir['baseurl'] . '/debug-image-editors';

		if( ! is_dir( $this->storage_dir ) ) {
			wp_mkdir_p( $this->storage_dir );
		}

		echo '<div class="wrap">';

		echo '<h2>Debug Image Editors</h2>';

		echo '<div class="tool-box">';
		echo '<h3 class="title">Current image editor</h3>';
		echo _wp_image_editor_choose();
		echo '</div>';


		echo '<table><tr>';

		$image_editors = $this->image_editors();

		foreach( $image_editors as $image_editor ) {
			$this->set_image_editor( $image_editor );

			echo '<td style="vertical-align: top;">';

			echo '<h1>' . $image_editor . '</h1>';

			$methods = get_class_methods( __CLASS__ );

			foreach( $methods as $method ) {
				if( strpos( $method, 'example' ) !== false ) {
					echo '<h2>' . $method . '</h2>';

					$file = $this->storage_dir . '/' . $image_editor . '-' . $method . '.jpg';

					if( ! file_exists( $file ) || ( time() - filemtime( $file ) >= DAY_IN_SECONDS ) ) {
						$data = call_user_func_array( array( $this, $method ), array( $image_editor ) );

						if( ! is_wp_error( $data ) ) {
							echo '<img src="' . $storage_url . '/' . $data['file'] . '" />';
						}
						else {
							echo '<pre style="white-space: pre-wrap; word-wrap:break-word;">';
							var_dump( $data );
							echo '</pre>';
						}
						
					}
					else {
						echo '<img src="' . $storage_url . '/' . $image_editor . '-' . $method . '.jpg" />';
					}
				}
			}

			echo '</td>';
		}

		echo '</tr></table>';

		echo '</div>';
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

			return $editor->save( $this->storage_dir . '/' . $method . '-example1.jpg' ); // Save the file as /path/to/image-100x100.jpeg
		}

		return $editor;
	}

	function example2( $method ) {
		$editor = wp_get_image_editor( $this->file );

		if( ! is_wp_error( $editor ) ) {
			// $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs
			$editor->crop( 0, 0, 300, 300, 300, 300, false );

			return $editor->save( $this->storage_dir . '/' . $method . '-example2.jpg'  ); // Save the file as /path/to/image-100x100.jpeg
		}

		return $editor;
	}

	function example3( $method ) {
		$editor = wp_get_image_editor( $this->file );

		if( ! is_wp_error( $editor ) ) {
			$editor->flip( false, true );

			$editor->rotate( 90 );

			$editor->resize( 0, 512 );

			return $editor->save( $this->storage_dir . '/' . $method . '-example3.jpg'  ); // Save the file as /path/to/image-100x100.jpeg
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

			return $editor->save( $this->storage_dir . '/' . $method . '-example4.jpg'  ); // Save the file as /path/to/image-100x100.jpeg
		}

		return $editor;
	}

	/*
	 * WebP!
	 */
	function example5( $method ) {
		$editor = wp_get_image_editor( $this->file );

		if( ! is_wp_error( $editor ) ) {
			$editor->resize( 300, 300, true );

			return $editor->save( $this->storage_dir . '/' . $method . '-example5.webp' );
		}

		return $editor;
	}

}

if( is_admin() ) {
	$debug_image_editor = new Debug_Image_Editor;
}