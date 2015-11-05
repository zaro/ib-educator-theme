<?php

/**
 * Enqueue scripts and styles.
 */
function ibfw_enqueue_scripts() {
	wp_enqueue_style( 'ibfw-admin', IBFW_URL . '/framework/admin.css', array(), '1.0.0' );
}
add_action( 'admin_enqueue_scripts', 'ibfw_enqueue_scripts' );
