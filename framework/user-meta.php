<?php

class IBFW_User_Meta_Field {
	/**
	 * @access public
	 * @var string
	 */
	public $type;

	/**
	 * @access public
	 * @var string
	 */
	public $name;

	/**
	 * @access public
	 * @var string
	 */
	public $label;

	/**
	 * Constructor.
	 *
	 * @param array $options
	 */
	public function __construct( $options ) {
		$keys = array_keys( get_object_vars( $this ) );
		foreach ( $keys as $key ) {
			if ( isset( $options[ $key ] ) ) {
				$this->$key = $options[ $key ];
			}
		}
	}

	/**
	 * Output the field.
	 *
	 * @param int $user_id
	 */
	public function output( $user_id ) {
		$value = ( $user_id > 0 ) ? get_user_meta( $user_id, $this->name, true ) : '';

		switch ( $this->type ) {
			case 'textarea':
				?>
				<tr>
					<th><label><?php echo $this->label; ?></label></th>
					<td>
						<textarea name="<?php echo esc_attr( $this->name ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
					</td>
				</tr>
				<?php
				break;
		}
	}

	/**
	 * Sanitize the field value.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function sanitize( $value ) {
		switch ( $this->type ) {
			default:
				if ( ! current_user_can( 'unfiltered_html' ) ) {
					$value = wp_kses_data( $value );
				}
		}

		return $value;
	}

	/**
	 * Update user meta.
	 *
	 * @param int $user_id
	 */
	public function update( $user_id ) {
		$value = ! isset( $_POST[ $this->name ] ) ? '' : $_POST[ $this->name ];
		update_user_meta( $user_id, $this->name, $this->sanitize( $value ) );
	}
}

class IBFW_User_Meta_Image_Upload extends IBFW_User_Meta_Field {
	/**
	 * @access public
	 * @var boolean
	 */
	public static $ready = false;

	/**
	 * Add enctype attribute to the user profile form.
	 */
	public static function add_enctype() {
		echo ' enctype="multipart/form-data"';
	}

	/**
	 * Constructor.
	 *
	 * @param array $options
	 */
	public function __construct( $options ) {
		if ( ! self::$ready ) {
			add_action( 'user_edit_form_tag', array( __CLASS__, 'add_enctype' ) );
			self::$ready = true;
		}

		$this->type = 'image_upload';
		parent::__construct( $options );
	}

	/**
	 * Output the field.
	 *
	 * @param int $user_id
	 */
	public function output( $user_id ) {
		?>
		<tr>
			<th><label><?php echo $this->label; ?></label></th>
			<td>
				<?php
					if ( $user_id > 0 ) {
						$value = get_user_meta( $user_id, $this->name, true );
						$photo = wp_get_attachment_image_src( $value );

						if ( $photo ) {
							$photo_class = ( $photo[1] > $photo[2] ) ? 'landscape' : 'portrait';
							echo '<div class="ibfw-photo-preview ' . $photo_class . '"><img src="' . esc_url( $photo[0] ) . '" alt=""></div>';
						}
					}
				?>
				<input type="file" name="<?php echo esc_attr( $this->name ); ?>">
			</td>
		</tr>
		<?php
	}

	/**
	 * Update user meta.
	 *
	 * @param int $user_id
	 */
	public function update( $user_id ) {
		if ( ! empty( $_FILES ) && isset( $_FILES[ $this->name ] ) && ! empty( $_FILES[ $this->name ]['tmp_name'] ) ) {
			$current_attachment_id = get_user_meta( $user_id, $this->name, true );

			if ( $current_attachment_id ) {
				wp_delete_attachment( $current_attachment_id );
			}

			$filetype = wp_check_filetype_and_ext( $_FILES[ $this->name ]['tmp_name'], $_FILES[ $this->name ]['name'] );

			if ( ! wp_match_mime_types( 'image', $filetype['type'] ) ) {
				die();
			}

			$attachment_id = media_handle_upload( $this->name, 0 );

			if ( ! is_wp_error( $attachment_id ) ) {
				update_user_meta( $user_id, $this->name, $attachment_id );
			}
		}
	}
}

class IBFW_User_Meta {
	/**
	 * @access private
	 * @var string
	 */
	private static $fields = array();

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'show_user_profile', array( __CLASS__, 'output_fields' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'output_fields' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'update' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'update' ) );
	}

	/**
	 * Add field.
	 *
	 * @param mixed $field
	 */
	public static function add_field( $field ) {
		if ( is_object( $field ) )
			self::$fields[] = $field;
		else
			self::$fields[] = new IBFW_User_Meta_Field( $field );
	}

	/**
	 * Output fields.
	 *
	 * @param WP_User $user
	 */
	public static function output_fields( $user ) {
		$user_id = ( $user instanceof WP_User ) ? $user->ID : 0;
		?>
		<table class="form-table">
			<tbody>
				<?php
					foreach ( self::$fields as $field ) {
						$field->output( $user_id );
					}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Update user meta.
	 *
	 * @param int $user_id
	 */
	public static function update( $user_id ) {
		// Check capability.
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		foreach ( self::$fields as $field ) {
			$field->update( $user_id );
		}
	}
}
