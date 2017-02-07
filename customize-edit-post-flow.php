<?php
/**
* Plugin Name: Customize Edit Post Flow
* Description: Supporting post editing in the Customizer with a tolerable flow
* Plugin URI: http://automattic.com/
* Author: Automattic
* Author URI: http://automattic.com
* Version: 0.1
* License: GPL2
*/

/*
Copyright (C) 2017  Matt Wiebe  wiebe@automattic.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'plugins_loaded', array( 'WP_Customize_Post_Edit_Flow', 'get_instance' ) );

class WP_Customize_Post_Edit_Flow {

	private static $instance = null;

	public static function get_instance() {
		if ( ! isset( self::$instance ) )
			self::$instance = new self;

		return self::$instance;
	}

	private function __construct() {
		add_action( 'customize_register', array( $this, 'customizer_init' ) );
		add_action( 'edit_form_top', array( $this, 'post_edit_notice' ) );
		add_filter( 'redirect_post_location', array( $this, 'maybe_add_return_to_redirect' ) );
		add_action( 'edit_form_top', array( $this, 'maybe_add_hidden_field' ) );
	}

	public function maybe_add_hidden_field() {
		if ( ! isset( $_GET['customizer_return'] ) || ! $this->is_customizer_url( $_GET['customizer_return'] ) ) {
			return;
		}

		printf( '<input type="hidden" name="%s" value="%s" />', 'customizer_return', esc_attr( $_GET['customizer_return'] ) );
	}

	public function maybe_add_return_to_redirect( $location ) {
		if ( isset( $_POST['customizer_return'] ) ) {
			// todo verify _return_to_customizer_url
			$location = add_query_arg( 'customizer_return', $_POST['customizer_return'], $location );
		}
		return $location;
	}

	public function customizer_init() {
		add_action( 'customize_preview_init', array( $this, 'init' ), 11 );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'controls_script' ) );
	}

	public function init() {
		remove_filter( 'get_edit_post_link', '__return_empty_string' );
		wp_enqueue_script( 'edit-post-flow', plugins_url( 'js/customize-edit-post-flow.js', __FILE__ ), array( 'customize-preview' ), '20170201', true );
	}

	public function controls_script() {
		wp_enqueue_script( 'edit-post-flow', plugins_url( 'js/customize-edit-post-flow-admin.js', __FILE__ ), array( 'customize-controls' ), '20170201', true );
	}

	private function did_get_refered_from_customizer() {
		if ( empty( $_SERVER['HTTP_REFERER'] ) ) {
			return false;
		}

		return $this->is_customizer_url( $_SERVER['HTTP_REFERER'] );
	}

	private function has_customizer_return() {
		if ( empty( $_REQUEST['customizer_return'] ) ) {
			return false;
		}

		return $this->is_customizer_url( $_REQUEST['customizer_return'] );
	}

	private function is_customizer_url( $url ) {
		$parsed = parse_url( $url );

		if ( empty( $parsed['path'] ) || empty( $parsed['scheme'] || empty( $parsed['host'] ) ) ) {
			return false;
		}

		return admin_url( 'customize.php' ) === sprintf( '%s://%s%s', $parsed['scheme'], $parsed['host'], $parsed['path'] );
	}

	public function post_edit_notice() {
		// If no return URL, bail
		if ( ! $this->has_customizer_return() ) {
			return;
		}

		$post_object = get_post_type_object( get_post()->post_type );

		// If we just came from the Customizer, show a notice of state
		if ( $this->did_get_refered_from_customizer() ) {
			$message = sprintf( __( 'After you finish editing this %s, you can return to customizing your site\'s appearance.'  ), $post_object->labels->singular_name );
		} else {
			// otherwise, we're ready to go back.
			$message = sprintf( __( 'You edited your %s!' ), $post_object->labels->singular_name );
			$message .= sprintf( ' <a href="%s">%s</a>', esc_url( $_REQUEST['customizer_return'] ), __( 'Continue customizing your site.' ) );
		}
		echo '<div class="notice notice-warning is-dismissible"><p>' . $message . '</p></div>';
	}

}
