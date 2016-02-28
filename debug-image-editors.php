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


	public function get_image_editor( $image_editors ) {
		if ( $this->image_editor ) {
			return array( $this->image_editor );
		}

		return $image_editors;
	}

	private function set_image_editor( $image_editor ) {
		$this->image_editor = $image_editor;
	}





	public function show_images() {
		$upload_dir        = wp_upload_dir();
		$this->file        = dirname( __FILE__ ) . '/images/amsterdam.jpg';
		$this->storage_dir = $upload_dir['basedir'] . '/debug-image-editors';
		$this->storage_url = $upload_dir['baseurl'] . '/debug-image-editors';

		if( ! is_dir( $this->storage_dir ) ) {
			wp_mkdir_p( $this->storage_dir );
		}

		echo '<div class="wrap">';

		echo '<h2>Debug Image Editors</h2>';

		echo '<div class="tool-box">';
		echo '<h3 class="title">Current image editor</h3>';
		echo _wp_image_editor_choose();
		echo '</div>';


		echo '<table style="margin-top: 20px; width:100%">';

		$image_editors = $this->image_editors();
		$methods       = get_class_methods( __CLASS__ );
		$amount        = count( $image_editors );
		$width         = 100 / $amount;


		echo '<tr>';
		foreach( $image_editors as $image_editor ) {
			echo '<td style="width:' . $width . '%">';
			echo '<h1>' . $image_editor . '</h1>';
			echo '</td>';
		}
		echo '</tr>';


		foreach ( $methods as $method ) {
			if ( strpos( $method, 'example' ) === false ) {
				continue;
			}

			echo '<tr><td colspan="' . $amount . '"><h2>' . $method . '</h2></td></tr>';

			echo '<tr>';
			foreach( $image_editors as $image_editor ) {
				echo '<td style="vertical-align: top;">';
				$this->set_image_editor( $image_editor );
				$file = call_user_func( array( $this, $method ) );

				if( ! is_wp_error( $file ) ) {
					echo '<img src="' . $this->storage_url . '/' . $file . '" style="max-width:100%" />';
				}
				else {
					echo '<pre style="white-space: pre-wrap; word-wrap:break-word;">';
					var_dump( $file );
					echo '</pre>';
				}
				echo '</td>';
			}
			echo '</tr>';
		}

		echo '</table>';

		echo '</div>';
	}


	//
	// TEST CASES
	//

	public function example1() {
		$file = $this->image_editor . '-example1.jpg';

		if ( $this->is_file_cached( $this->storage_dir . '/' . $file ) ) {
			return $file;
		}

		$editor = wp_get_image_editor( $this->file );

		if( ! is_wp_error( $editor ) ) {
			$editor->resize( 300, 300, true );

			return $editor->save( $this->storage_dir . '/' . $file )['file']; // Save the file as /path/to/image-100x100.jpeg
		}

		return $editor;
	}

	public function example2() {
		$file = $this->image_editor . '-example2.jpg';

		if ( $this->is_file_cached( $this->storage_dir . '/' . $file ) ) {
			return $file;
		}

		$editor = wp_get_image_editor( $this->file );

		if( ! is_wp_error( $editor ) ) {
			// $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs
			$editor->crop( 0, 0, 300, 300, 300, 300, false );

			return $editor->save( $this->storage_dir . '/' . $file )['file']; // Save the file as /path/to/image-100x100.jpeg
		}

		return $editor;
	}

	public function example3() {
		$file = $this->image_editor . '-example3.jpg';

		if ( $this->is_file_cached( $this->storage_dir . '/' . $file ) ) {
			return $file;
		}

		$editor = wp_get_image_editor( $this->file );

		if( ! is_wp_error( $editor ) ) {
			$editor->flip( false, true );

			$editor->rotate( 90 );

			$editor->resize( 0, 512 );

			return $editor->save( $this->storage_dir . '/' . $file )['file']; // Save the file as /path/to/image-100x100.jpeg
		}

		return $editor;
	}

	/*
	 * Broken example for imagick till 3.7.
	 * 0.7 is the goldenrule (selfclaimed) to never have black boundaries
	 */
	public function example4() {
		$file = $this->image_editor . '-example4.jpg';

		if ( $this->is_file_cached( $this->storage_dir . '/' . $file ) ) {
			return $file;
		}

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

			return $editor->save( $this->storage_dir . '/' . $file )['file']; // Save the file as /path/to/image-100x100.jpeg
		}

		return $editor;
	}

	/*
	 * WebP!
	 */
	public function example5() {
		$file = $this->image_editor . '-example5.webp';

		if ( $this->is_file_cached( $this->storage_dir . '/' . $file ) ) {
			return $file;
		}

		if ( $this->is_file_cached( $this->storage_dir . '/' . $this->image_editor . '-example5.jpg' ) ) {
			return new WP_Error( 'failed_test', __('File got saved as JPG') );
		}

		$editor = wp_get_image_editor( $this->file );

		if( ! is_wp_error( $editor ) ) {
			$editor->resize( 300, 300, true );

			$image_data = $editor->save( $this->storage_dir . '/' . $file );

			if ( is_wp_error( $image_data ) ) {
				return $image_data;
			}
			
			if ( 'image/jpeg' == $image_data['mime-type'] ) {
				return new WP_Error( 'failed_test', __('File got saved as JPG') );
			}

			return $image_data['file'];
		}

		return $editor;
	}

	/*
	 * Animation!
	 */
	public function example6() {
		$file = $this->image_editor . '-example6.gif';

		if ( $this->is_file_cached( $this->storage_dir . '/' . $file ) ) {
			return $file;
		}

		$editor = wp_get_image_editor( dirname( __FILE__ ) . '/images/giphy.gif' );

		if ( ! is_wp_error( $editor ) ) {
			$editor->resize( 400, 250, true );

			$image_data = $editor->save( $this->storage_dir . '/' . $file );

			if ( is_wp_error( $image_data ) ) {
				return $image_data;
			}

			return $image_data['file'];
		}

		return $editor;
	}



	//
	// Helper Methods
	//

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

	private function is_file_cached( $path ) {
		if( ! file_exists( $path ) || ( time() - filemtime( $path ) >= DAY_IN_SECONDS ) ) {
			return false;
		}

		return true;
	}

}

if( is_admin() ) {
	$debug_image_editor = new Debug_Image_Editor;
}