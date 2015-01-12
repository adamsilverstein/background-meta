<?php
/*
Plugin Name: Background-meta
Version: 0.2
Description: Preview meta test for https://core.trac.wordpress.org/ticket/20299
Author: Adam Silverstein
Author URI: wordpress.org
Text Domain: background-meta
Domain Path: /wpbm
*/



Class Background_Meta {

	function __construct(){
		add_action( 'init',                       array( __CLASS__, 'setup_background' ) );
	}

	public function setup_background() {
		add_action( 'save_post',                  array( __CLASS__, 'wpbm_save_meta_box_data' ) );
		add_filter( 'body_class',                 array( __CLASS__, 'wpbm_body_class' ) );
		add_action( 'wp_enqueue_scripts',         array( __CLASS__, '_wpbm_post_styles' ) );
		add_action( 'add_meta_boxes_post',        array( __CLASS__, 'setup_background_meta' ) );
		add_filter( 'wp_post_revision_meta_keys', array( __CLASS__, 'add_meta_keys_to_revision' ) );
	}

	/**
	 * Revision our post meta
	 * @param Array $keys The array of keys to revision.
	 */
	public function add_meta_keys_to_revision( $keys ) {
		$keys[] = '_wpbm_post_style';
		return $keys;
	}
	/**
	 * Add the stylez
	 */
	public function _wpbm_post_styles() {
		$custom_css = "
			.blue {
				background: blue;
			}
			.red {
				background: red;
			}
			.green {
				background: green;
			}
			.orange {
				background: orange;
			}
			";
		wp_add_inline_style( 'twentyfifteen-style', $custom_css );
	}

	/**
	 * Add the body class.
	 */
	public function wpbm_body_class( $classes ) {
		global $post;
		error_log($post->ID);
		$style = get_post_meta( $post->ID, '_wpbm_post_style', true );
		error_log('style ' . $style);
		if ( '' !== $style ) {
			array_push( $classes, $style );
		}

		return $classes;
	}

	/**
	 * Set up the meta box
	 */
	public function setup_background_meta( $post ) {
		add_meta_box(
			'wpbm_meta',
			__( 'Backgroundz', 'wpbm' ),
			array( __CLASS__, 'setup_background_meta_print' ),
			'post',
			'side',
			'high'
		);
	}

	/**
	 * Prints the meta box content.
	 *
	 * @param WP_Post $post The object for the current post/page.
	 */
	public function setup_background_meta_print( $post ) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'wpbm_meta_box', 'wpbm_meta_box_nonce' );

		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		$value = get_post_meta( $post->ID, '_wpbm_post_style', true );
		//error_log($value);

		echo '<label for="_wpbm_post_style">';
		_e( 'Background meta post style', 'wpbm' );
		echo '</label> ';
		$stylez = array(
			''       => 'Choose',
			'blue'   => 'Blue',
			'red'    => 'Red',
			'green'  => 'Green',
			'orange' => 'Orange',
			);
		echo '<select id="_wpbm_post_style" name="_wpbm_post_style">';
		foreach( $stylez as $class => $name ){
			echo sprintf( '<option value="%s" %s>%s</option>',
					esc_attr( $class ),
					( $class == $value ) ? 'selected' : '',
					esc_attr( $name ) );
		}
		echo '</select>';
	}

	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function wpbm_save_meta_box_data( $post_id ) {

//		error_log('wpbm_save_meta_box_data');
//		error_log((defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )?'doing autosave': 'not doing autosave') ;
//		error_log((defined( 'DOING_PREVIEW' ) && DOING_PREVIEW )?'doing preview': 'not doing preview') ;

		// If this is a preview don't save our meta value.
		if ( defined( 'DOING_PREVIEW' ) && DOING_PREVIEW ) {
			return;
		}
		// If this is an autosave don't save our meta value.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
//		error_log('SAVING!');
		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */
		// Check if our nonce is set.
		if ( ! isset( $_POST['wpbm_meta_box_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpbm_meta_box_nonce'], 'wpbm_meta_box' ) ) {
			return;
		}
		error_log('nonce verified');
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		/* OK, it's safe for us to save the data now. */
error_log( 'setting ' . $_POST['_wpbm_post_style'] );

		// Make sure that it is set.
		if ( ! isset( $_POST['_wpbm_post_style'] ) ) {
			return;
		}
error_log( 'setting ' . $_POST['_wpbm_post_style'] );

		// Update the meta field in the database.
		update_post_meta( $post_id, '_wpbm_post_style', sanitize_text_field( $_POST['_wpbm_post_style'] ) );
	}
}

$backgroundmeta = new Background_Meta();