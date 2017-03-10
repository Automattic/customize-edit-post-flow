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

	private static $opt = 'post_edit_flow_changeset';

	public static function get_instance() {
		if ( ! isset( self::$instance ) )
			self::$instance = new self;

		return self::$instance;
	}

	private function __construct() {
		add_action( 'customize_register', array( $this, 'customizer_init' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		add_action( 'wp_ajax_post_edit_redirect_save', array( $this, 'save_redirect' ) );
		add_action( 'wp_ajax_post_edit_redirect_delete', array( $this, 'delete_redirect' ) );
	}

	public function delete_redirect() {
		if ( ! current_user_can( 'customize' ) || empty( $_POST['nonce'] ) ) {
			return wp_send_json_error();
		}
		if ( ! wp_verify_nonce( $_POST['nonce'], 'post_edit_flow_redirect_delete' ) ) {
			return wp_send_json_error();
		}

		delete_option( self::$opt );
		return wp_send_json_success();
	}

	public function save_redirect() {
		if ( ! current_user_can( 'customize' ) || empty( $_POST['changeset_flow'] ) ) {
			return wp_send_json_error();
		}

		// validate the changeset UUID
		if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $_POST['changeset_flow'] ) ) {
			return wp_send_json_error();
		}

		update_option( self::$opt, $_POST['changeset_flow'] );
		wp_send_json_success();
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
		wp_enqueue_script( 'edit-post-flow', plugins_url( 'js/customize-edit-post-flow-admin.js', __FILE__ ), array( 'customize-controls', 'wp-util' ), '20170201', true );
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

	private function has_changeset_in_progress() {
		return ( bool ) $this->get_changeset_in_progress();
	}

	private function get_changeset_in_progress() {
		return get_option( self::$opt );
	}

	public function admin_notice() {
		if ( ! current_user_can( 'customize' ) || ! $this->has_changeset_in_progress() ) {
			return;
		}

		$return_link = add_query_arg( 'changeset_uuid', $this->get_changeset_in_progress(), admin_url( 'customize.php' ) );
		$link_text = __( 'Continue customizing your site.' );
		$link = sprintf( '<a href="%s">%s</a>', $return_link, $link_text );

		if ( 'post' === get_current_screen()->base ) {
			$note = __( 'Done editing?' );
		} else {
			$note = __( 'You left some unsaved settings behind.' );
		}
		/* translators: 1. A reminder about your unsaved customizations 2. A link to continue customizing. */
		$format = __( '%1$s %2$s' );

		printf( '<div id="customizer-return" class="notice notice-info is-dismissible"><p>%s</p></div>', sprintf( $format, $note, $link ) );
		wp_enqueue_script( 'edit-post-flow-notice', plugins_url( 'js/customize-edit-post-flow-notice.js', __FILE__ ), array( 'wp-util' ), '20170214', true );
		wp_localize_script( 'edit-post-flow-notice', '_editPostFlowNotice', array(
			'confirm' => __( 'Do you want to discard your unsaved Customizer changes?' ),
			'nonce' => wp_create_nonce( 'post_edit_flow_redirect_delete' ),
		) );
	}

}
