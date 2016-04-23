<?php
/*
Plugin Name: Ink Contact Form 7
Plugin URI: https://github.com/inklink/ink-contact-form-7
Description: Stores Contact Form 7 messages under CPT. Only load CSS/JS on pages having the shortcode
Author: inklink
Author URI: https://github.com/inklink
Version: 0.3

GitHub Plugin URI: https://github.com/inklink/Ink-contact-form-7
*/

!defined( 'ABSPATH' ) && die;
define( 'INK_CONTACT_FORM_7_VERSION', '0.3' );
define( 'INK_CONTACT_FORM_7_URL', plugin_dir_url( __FILE__ ) );
define( 'INK_CONTACT_FORM_7_DIR', plugin_dir_path( __FILE__ ) );
define( 'INK_CONTACT_FORM_7_BASENAME', plugin_basename( __FILE__ ) );

class Ink_Contact_Form_7 {

	public static function factory() {
		static $instance = null;
		if ( ! ( $instance instanceof self ) ) {
			$instance = new self;
			$instance->setup_actions();
		}
		return $instance;
	}

	protected function setup_actions() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		add_action( 'wpcf7_before_send_mail', array( $this, 'wpcf7_before_send_mail' ) );
	}

	public function init() {
		if ( !class_exists( 'WPCF7_Submission' ) ) { return; }
		register_post_type( 'wpcf7_wpce_item', array(
				'labels' => array(
					'name' => __( 'WPCF7 Items', 'Ink-contact-form-7' ),
					'singular_name' => __( 'WPCF7 Items', 'Ink-contact-form-7' ),
					'menu_name'  => __( 'View Submissions', 'Ink-contact-form-7' ) ),
				'public' => false,
				'exclude_from_search' => true,
				'publicly_queryable' => false,
				'show_ui' => true,
				'can_export' => false,
				'show_in_menu' => 'wpcf7',
				'show_in_admin_bar' => false,
				'show_in_nav_menus' => false,
				'rewrite' => false,
				'query_var' => false )
		);
	}


	public function wpcf7_before_send_mail( $wpcf7 ) {
		if ( $submission = WPCF7_Submission::get_instance() ) {
			$data = new stdClass;
			$data->posted_data    = $submission->get_posted_data();
			$data->remote_ip      = $submission->get_meta( 'remote_ip' );
			$data->user_agent     = $submission->get_meta( 'user_agent' );
			$data->url            = $submission->get_meta( 'url' );
			$data->timestamp      = $submission->get_meta( 'timestamp' );
			$data->unit_tag       = $submission->get_meta( 'unit_tag' );
			$data->title          = $wpcf7->title();
			$data->id             = $wpcf7->id();
			$mail                 = $wpcf7->prop( 'mail' );
			$data->recipient      = $mail['recipient'];
			$data->subject        = $mail['subject'];
			$data->body           = $mail['body'];
			// $data->uploaded_files = $submission->uploaded_files();
			$json = json_encode( $data, JSON_PRETTY_PRINT );
			$post_id = wp_insert_post( array(
					'post_type' => 'wpcf7_wpce_item',
					'post_status' => 'pending',
					'post_title' => $data->title,
					'post_content' => trim( $json )
				) );
		}
	}

	public function template_redirect() {
		if ( class_exists( 'WPCF7_ContactForm' ) ) {
			if ( is_singular() ) {
				global $post;
				// $post = get_post();
				if ( has_shortcode( $post->post_content, 'contact-form-7' ) ) {
					return;
				}
			}
			add_filter( 'wpcf7_load_js', '__return_false' );
			add_filter( 'wpcf7_load_css', '__return_false' );
		}
	}

	public function __construct() { }

	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'core-plugin' ), '0.1' );
	}

	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'core-plugin' ), '0.1' );
	}

};

function Ink_Contact_Form_7() {
	return Ink_Contact_Form_7::factory();
}

Ink_Contact_Form_7();
