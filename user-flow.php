<?php

/**
 * Custom User Flow.
 */
class IBFW_User_Flow {
	/**
	 * @var IBFW_User_Flow
	 */
	protected static $instance = null;

	/**
	 * @var null|WP_Error
	 */
	protected $errors = null;

	/**
	 * Get instance.
	 *
	 * @return IBFW_User_Flow
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

	/**
	 * Turn on captcha.
	 */
	public function turnOnCaptcha() {
		add_action( 'ibfw_ufw_register_footer', array( $this, 'add_captcha_html' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_captcha_script' ) );
		add_action( 'ibfw_ufw_register_errors', array( $this, 'verify_captcha' ) );
	}

	/**
	 * Process request.
	 */
	public function processRequest() {
		if ( isset( $_POST['ibfw_ufw_action'] ) ) {
			$action = $_POST['ibfw_ufw_action'];

			switch ( $action ) {
				case 'register':
				case 'update_profile':
					add_action( 'template_redirect', array( $this, $action ) );
					break;
			}
		}
	}

	/**
	 * Get errors.
	 *
	 * @return null|WP_Error
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Get error message.
	 *
	 * @param string $error_code
	 * @return string
	 */
	public function get_error_message( $error_code ) {
		return apply_filters( 'ibfw_ufw_error_message', '', $error_code );
	}

	/**
	 * Action: register.
	 */
	public function register() {
		// Check if registration is enabled.
		if ( ! get_option( 'users_can_register' ) || ! get_option( 'ib_ufw_enabled' ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['ibfw_ufw_nonce'] ) || ! wp_verify_nonce( $_POST['ibfw_ufw_nonce'], 'ibfw_ufw_register' ) ) {
			$this->errors = new WP_Error( 'nonce_invalid', $this->get_error_message( 'nonce_invalid' ) );

			return;
		}

		$input = array();
		$errors = new WP_Error();

		// Username.
		$input['user_login'] = isset( $_POST['user_login'] ) ? sanitize_user( $_POST['user_login'] ) : '';

		if ( '' == $input['user_login'] ) {
			$errors->add( 'login_empty', $this->get_error_message( 'login_empty' ) );
		} elseif ( ! validate_username( $input['user_login'] ) ) {
			$errors->add( 'login_invalid', $this->get_error_message( 'login_invalid' ) );
		} elseif ( username_exists( $input['user_login'] ) ) {
			$errors->add( 'login_exists', $this->get_error_message( 'login_exists' ) );
		}

		// Email.
		$input['user_email'] = isset( $_POST['user_email'] ) ? apply_filters( 'user_registration_email', $_POST['user_email'] ) : '';

		if ( '' == $input['user_email'] ) {
			$errors->add( 'email_empty', $this->get_error_message( 'email_empty' ) );
		} elseif ( ! is_email( $input['user_email'] ) ) {
			$errors->add( 'email_invalid', $this->get_error_message( 'email_invalid' ) );
		} elseif ( email_exists( $input['user_email'] ) ) {
			$errors->add( 'email_exists', $this->get_error_message( 'email_exists' ) );
		}

		// First Name.
		if ( isset( $_POST['first_name'] ) ) {
			$input['first_name'] = sanitize_text_field( $_POST['first_name'] );
		}

		// Last Name.
		if ( isset( $_POST['last_name'] ) ) {
			$input['last_name'] = sanitize_text_field( $_POST['last_name'] );
		}

		// Password.
		$input['user_pass'] = isset( $_POST['user_pass'] ) ? $_POST['user_pass'] : '';

		if ( 6 > strlen( $input['user_pass'] ) ) {
			$errors->add( 'password_length', $this->get_error_message( 'password_length' ) );
		} elseif ( ! isset( $_POST['repeat_user_pass'] ) || $_POST['repeat_user_pass'] != $input['user_pass'] ) {
			$errors->add( 'password_mismatch', $this->get_error_message( 'password_mismatch' ) );
		}

		/**
		 * Filter the registration form errors.
		 *
		 * @param WP_Error $errors
		 * @param array $input
		 */
		$errors = apply_filters( 'ibfw_ufw_register_errors', $errors, $input );

		if ( ! $errors->get_error_code() ) {
			/**
			 * Filter the arguments for the wp_insert_user function.
			 *
			 * @param array $input
			 */
			$input = apply_filters( 'ibfw_ufw_insert_user', $input );

			$new_user_id = wp_insert_user( $input );

			if ( ! is_wp_error( $new_user_id ) ) {
				/**
				 * Fires when a user is registered.
				 *
				 * @param int $new_user_id
				 */
				do_action( 'ibfw_ufw_registered', $new_user_id );

				wp_new_user_notification( $new_user_id );

				if ( isset( $_POST['redirect_to'] ) ) {
					wp_safe_redirect( $_POST['redirect_to'] );

					exit();
				}
			}
		} else {
			$this->errors = $errors;
		}
	}

	/**
	 * Update profile.
	 */
	public function update_profile() {
		if ( empty( $_POST ) ) {
			return;
		}

		// Get current user.
		$curauth = wp_get_current_user();

		if ( ! $curauth->ID ) {
			return;
		}

		$user_data = array();
		$errors = new WP_Error();

		// First Name.
		if ( isset( $_POST['first_name'] ) ) {
			$user_data['first_name'] = sanitize_text_field( $_POST['first_name'] );
		}

		// Last Name.
		if ( isset( $_POST['last_name'] ) ) {
			$user_data['last_name'] = sanitize_text_field( $_POST['last_name'] );
		}

		// Email.
		if ( isset( $_POST['user_email'] ) ) {
			if ( ! is_email( $_POST['user_email'] ) ) {
				$errors->add( 'email_invalid', $this->get_error_message( 'email_invalid' ) );
			} elseif ( $_POST['user_email'] != $curauth->user_email && email_exists( $_POST['user_email'] ) ) {
				$errors->add( 'email_exists', $this->get_error_message( 'email_exists' ) );
			} else {
				$user_data['user_email'] = $_POST['user_email'];
			}
		}

		// Description.
		if ( isset( $_POST['description'] ) ) {
			$user_data['description'] = sanitize_text_field( $_POST['description'] );
		}

		// Password.
		if ( ! empty( $_POST['user_pass'] ) ) {
			if ( strlen( $_POST['user_pass'] ) < 6 ) {
				$errors->add( 'password_length', $this->get_error_message( 'password_length' ) );
			} elseif ( ! isset( $_POST['user_pass_2'] ) || $_POST['user_pass_2'] != $_POST['user_pass'] ) {
				$errors->add( 'password_mismatch', $this->get_error_message( 'password_mismatch' ) );
			} else {
				$user_data['user_pass'] = $_POST['user_pass'];
			}
		}

		/**
		 * Filter the profile form errors.
		 *
		 * @param WP_Error $errors
		 * @param array $user_data
		 */
		$errors = apply_filters( 'ibfw_ufw_update_errors', $errors, $user_data );

		if ( ! $errors->get_error_code() ) {
			$user_data['ID'] = $curauth->ID;

			/**
			 * This filter can be used to pass additional data
			 * to the 'wp_update_user' function.
			 *
			 * @param array $user_data
			 */
			$user_data = apply_filters( 'ibfw_ufw_update_user', $user_data );

			$user_id = wp_update_user( $user_data );

			if ( ! is_wp_error( $user_id ) ) {
				/**
				 * Fires when a user is registered.
				 *
				 * @param int $user_id
				 */
				do_action( 'ibfw_ufw_updated', $user_id );

				wp_redirect( add_query_arg( array( 'updated' => 'true' ), get_permalink() ) );

				exit();
			}
		} else {
			$this->errors = $errors;
		}
	}

	/**
	 * Add captcha HTML.
	 */
	public function add_captcha_html() {
		$site_key = get_option( 'ib_ufw_site_key', '' );
		?>
			<div class="captcha-container">
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			</div>
		<?php
	}

	/**
	 * Add captcha script.
	 */
	public function add_captcha_script() {
		$register_page_id = get_theme_mod( 'register_page' );

		if ( $register_page_id && is_page( $register_page_id ) ) {
			wp_enqueue_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js' );
		}
	}

	/**
	 * Verify captcha.
	 *
	 * @param WP_Error $errors
	 * @return WP_Error
	 */
	public function verify_captcha( $errors ) {
		$success = false;

		if ( isset( $_POST['g-recaptcha-response'] ) ) {
			$captcha = $_POST['g-recaptcha-response'];
			
			$secret_key = get_option( 'ib_ufw_secret_key', '' );
			$response = wp_remote_post(
				'https://www.google.com/recaptcha/api/siteverify',
				array(
					'body' => array(
						'secret'   => $secret_key,
						'response' => $captcha,
					),
				)
			);

			if ( $response && is_array( $response ) ) {
				$decoded = json_decode( $response['body'] );
				$success = $decoded->success;
			}
		}

		if ( ! $success ) {
			$errors->add( 'captcha_invalid', $this->get_error_message( 'captcha_invalid' ) );
		}

		return $errors;
	}
}
