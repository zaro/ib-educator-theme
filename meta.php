<?php

if ( ! defined( 'ABSPATH' ) ) exit();

// Initialize custom fields.
IBFW_Meta::init();

// Theme settings meta box.
IBFW_Meta::add_meta_box( array(
	'id'     => 'educator_theme_meta',
	'title'  => __( 'Theme Settings', 'ib-educator-theme' ),
	'screen' => array( 'post', 'page', 'ib_educator_course' ),
) );

// Option: Show featured image.
IBFW_Meta::add_field( array(
	'meta_box'  => 'educator_theme_meta',
	'label'     => __( 'Show featured image on post details page', 'ib-educator-theme' ),
	'type'      => 'checkbox',
	'name'      => '_educator_show_image',
) );

// Option: Subtitle.
IBFW_Meta::add_field( array(
	'meta_box'  => 'educator_theme_meta',
	'label'     => __( 'Page subtitle', 'ib-educator-theme' ),
	'type'      => 'text',
	'name'      => '_educator_subtitle',
) );

// Option: Number of lessons per page.
IBFW_Meta::add_field( array(
	'meta_box'    => 'educator_theme_meta',
	'label'       => __( 'Number of lessons per page', 'ib-educator-theme' ),
	'type'        => 'number',
	'name'        => '_educator_lessons_per_page',
	'description' => __( 'Set to 0 in order to remove pagination.', 'ib-educator-theme' ),
) );
