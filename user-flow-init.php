<?php

/**
 * Add custom user flow settings to the customizer.
 *
 * @param WP_Customize_Manager $wp_customize
 */
function educator_add_ufw_settings( $wp_customize ) {
	// Enable custom user flow.
	$wp_customize->add_setting(
		'ib_ufw_enabled',
		array(
			'default'              => 0,
			'type'                 => 'option',
			'capability'           => 'edit_theme_options',
			'sanitize_callback'    => 'absint',
			'sanitize_js_callback' => 'absint',
		)
	);

	$wp_customize->add_control(
		'educator_ufw_enabled',
		array(
			'label'    => __( 'Enable custom user flow', 'ib-educator-theme' ) . ' (beta)',
			'section'  => 'educator_login_settings',
			'settings' => 'ib_ufw_enabled',
			'type'     => 'checkbox',
			'priority' => 3,
		)
	);

	// Enable captcha on registration form.
	$wp_customize->add_setting(
		'ib_ufw_captcha',
		array(
			'default'              => 0,
			'type'                 => 'option',
			'capability'           => 'edit_theme_options',
			'sanitize_callback'    => 'absint',
			'sanitize_js_callback' => 'absint',
		)
	);

	$wp_customize->add_control(
		'educator_ufw_captcha',
		array(
			'label'    => __( 'Enable captcha on registration form', 'ib-educator-theme' ),
			'section'  => 'educator_login_settings',
			'settings' => 'ib_ufw_captcha',
			'type'     => 'checkbox',
		)
	);

	// Recaptcha site key.
	$wp_customize->add_setting(
		'ib_ufw_site_key',
		array(
			'default'           => '',
			'type'              => 'option',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'educator_ufw_site_key',
		array(
			'label'    => __( 'ReCaptcha Site Key', 'ib-educator-theme' ),
			'section'  => 'educator_login_settings',
			'settings' => 'ib_ufw_site_key',
			'type'     => 'text',
		)
	);

	// Recaptcha secret key.
	$wp_customize->add_setting(
		'ib_ufw_secret_key',
		array(
			'default'           => '',
			'type'              => 'option',
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'educator_ufw_secret_key',
		array(
			'label'    => __( 'ReCaptcha Secret Key', 'ib-educator-theme' ),
			'section'  => 'educator_login_settings',
			'settings' => 'ib_ufw_secret_key',
			'type'     => 'text',
		)
	);
}
add_action( 'customize_register', 'educator_add_ufw_settings', 20 );

if ( get_option( 'ib_ufw_enabled' ) ) {
	require 'user-flow.php';

	$ibfw_user_flow = IBFW_User_Flow::get_instance();

	if ( get_option( 'ib_ufw_captcha' ) ) {
		$ibfw_user_flow->turnOnCaptcha();
	}

	$ibfw_user_flow->processRequest();
}
