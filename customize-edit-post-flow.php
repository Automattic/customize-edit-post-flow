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

	public function post_edit_notice() {
		if ( ! isset( $_GET['return_uuid'] ) ) {
			return;
		}
		$post_object = get_post_type_object( get_post()->post_type );
		$message = sprintf( __( 'After you finish editing this %s, you can return to customizing your site\'s appearance.'  ), $post_object->labels->singular_name );
		echo '<div class="notice notice-warning is-dismissible"><p>' . $message . '</p></div>';
	}

}
