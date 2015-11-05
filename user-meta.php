<?php

if ( ! defined( 'ABSPATH' ) ) exit();

// Initialize user meta.
IBFW_User_Meta::init();

// Option: Short Biographical Info.
IBFW_User_Meta::add_field( array(
	'type'  => 'textarea',
	'name'  => '_educator_short_bio',
	'label' => __( 'Short Biographical Info', 'ib-educator-theme' ),
) );

// Option: Upload Photo.
IBFW_User_Meta::add_field( new IBFW_User_Meta_Image_Upload( array(
	'name'  => '_educator_photo',
	'label' => __( 'Upload Photo', 'ib-educator-theme' ),
) ) );
