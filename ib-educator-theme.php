<?php
/*
Plugin Name: Educator Theme Features
Description: The theme shortcodes, custom fields, query vars, endpoints, etc.
Version: 1.4.0
Author: educatorteam
Author URI: http://educatorplugin.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ib-educator-theme
*/

/*
Copyright (C) 2015 http://educatorplugin.com/ - contact@educatorplugin.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

define( 'IBFW_URL', plugins_url( '', __FILE__ ) );

// Initialize custom user flow (registration, etc).
require 'user-flow-init.php';

// Include shortcodes.
require 'shortcodes.php';

if ( is_admin() ) {
	require 'framework/admin.php';
	require_once 'framework/meta.php';
	require_once 'framework/user-meta.php';
}

/**
 * Load text domain.
 */
function educator_theme_plugin_textdomain() {
	load_plugin_textdomain( 'ib-educator-theme', false, 'ib-educator-theme/languages' );

	if ( is_admin() ) {
		require_once 'meta.php';
		require_once 'user-meta.php';
	}
}
add_action( 'plugins_loaded', 'educator_theme_plugin_textdomain' );

/**
 * Add membership query var.
 *
 * @param array $vars
 * @return array
 */
function educator_query_vars( $vars ) {
	$vars[] = 'membership_id';

	if ( ! in_array( 'redirect_to', $vars ) ) {
		$vars[] = 'redirect_to';
	}

	return $vars;
}
add_filter( 'query_vars', 'educator_query_vars' );

/**
 * Register slideshow types.
 *
 * @param array $types
 * @return array
 */
function educator_slideshow_types( $types ) {
	$types['default'] = __( 'Default Slideshow', 'ib-educator-theme' );
	$types['post'] = __( 'Post Slideshow', 'ib-educator-theme' );

	return $types;
}
add_filter( 'ib_slideshow_types', 'educator_slideshow_types' );

/**
 * Register slideshow fields.
 *
 * @param array $fields
 * @return array
 */
function educator_slideshow_fields( $fields ) {
	$fields[] = array(
		'name'  => 'autoscroll',
		'type'  => 'text',
		'label' => __( 'Autoscroll Interval', 'ib-educator-theme' ),
		'class' => 'small-text',
		'description' => __( 'In seconds.', 'ib-educator-theme' ),
	);

	return $fields;
}
add_filter( 'ib_slideshow_fields', 'educator_slideshow_fields' );

/**
 * Register slide fields.
 *
 * @param array $fields
 * @return array
 */
function educator_slide_fields( $fields ) {
	$fields[] = array(
		'name'  => 'title',
		'type'  => 'text',
		'label' => __( 'Title', 'ib-educator-theme' ),
		'class' => 'large-text',
	);
	$fields[] = array(
		'name'  => 'description',
		'type'  => 'textarea',
		'label' => __( 'Description', 'ib-educator-theme' ),
		'class' => 'large-text',
	);
	$fields[] = array(
		'name'  => 'url',
		'type'  => 'text',
		'label' => __( 'URL', 'ib-educator-theme' ),
		'class' => 'large-text',
	);
	$fields[] = array(
		'name'  => 'target',
		'type'  => 'select',
		'label' => __( 'Target', 'ib-educator-theme' ),
		'choices' => array(
			'_self'  => __( 'Self', 'ib-educator-theme' ),
			'_blank' => __( 'Blank', 'ib-educator-theme' ),
		),
	);
	$fields[] = array(
		'name'           => 'caption_pos',
		'type'           => 'select',
		'label'          => __( 'Caption Position', 'ib-educator-theme' ),
		'choices'        => array(
			'left'  => __( 'Left', 'ib-educator-theme' ),
			'right' => __( 'Right', 'ib-educator-theme' ),
		),
		'slideshow_type' => 'default',
	);
	$fields[] = array(
		'name'           => 'caption_style',
		'type'           => 'select',
		'label'          => __( 'Caption Style', 'ib-educator-theme' ),
		'choices'        => array(
			'light' => __( 'Light', 'ib-educator-theme' ),
			'dark'  => __( 'Dark', 'ib-educator-theme' ),
		),
		'slideshow_type' => 'default',
	);
	$fields[] = array(
		'name'           => 'button_text',
		'type'           => 'text',
		'label'          => __( 'Button Text', 'ib-educator-theme' ),
		'slideshow_type' => 'default',
		'class'          => 'large-text',
	);
	$fields[] = array(
		'name'           => 'overlay',
		'type'           => 'checkbox',
		'label'          => __( 'Add Overlay', 'ib-educator-theme' ),
		'slideshow_type' => 'default',
	);

	return $fields;
}
add_filter( 'ib_slideshow_slide_fields', 'educator_slide_fields' );

/**
 * Get the user roles for which the backend is forbidden.
 *
 * @return array
 */
function educator_forbid_backend_roles() {
	return apply_filters( 'edutheme_forbid_backend', array( 'student' ) );
}

/**
 * Disable admin bar for subscribers and students.
 */
function educator_disable_admin_bar() {
	$forbidden_roles = educator_forbid_backend_roles();

	if ( empty( $forbidden_roles ) ) {
		return;
	}

	$user = wp_get_current_user();

	foreach ( $user->roles as $role ) {
		if ( in_array( $role, $forbidden_roles ) ) {
			add_filter( 'show_admin_bar', '__return_false' );

			return;
		}
	}
}
add_action( 'after_setup_theme', 'educator_disable_admin_bar' );

/**
 * Redirect subscribers and students away from admin.
 */
function educator_redirect_admin() {
	// Allow AJAX.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	$forbidden_roles = educator_forbid_backend_roles();

	if ( empty( $forbidden_roles ) ) {
		return;
	}

	$user = wp_get_current_user();

	foreach ( $user->roles as $role ) {
		if ( in_array( $role, $forbidden_roles ) ) {
			wp_redirect( home_url( '/' ) );

			exit;
		}
	}
}
add_action( 'admin_init', 'educator_redirect_admin' );

if ( ! function_exists( 'educator_get_user_profile_photo' ) ) :
/**
 * Get user profile photo.
 *
 * @param int $user_id
 * @param string $size
 * @return false|array
 */
function educator_get_user_profile_photo( $user_id, $size = 'thumbnail' ) {
	$attachment_id = get_user_meta( $user_id, '_educator_photo', true );

	if ( ! $attachment_id ) {
		return false;
	}

	if ( class_exists( 'IB_Retina' ) ) {
		return IB_Retina::get_2x_image_html( $attachment_id, $size );
	}

	return wp_get_attachment_image( $attachment_id, $size );
}
endif;
