<?php

class IBFW_Meta_Field {
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
	 * @access public
	 * @var string
	 */
	public $meta_box;

	/**
	 * @access public
	 * @var string
	 */
	public $description;

	/**
	 * @access public
	 * @var array
	 */
	public $choices;

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
	 * @param mixed $value
	 */
	public function output( $value, $name = null ) {
		if ( is_null( $name ) ) {
			$name = $this->name;
		}

		switch ( $this->type ) {
			case 'checkbox':
				?>
				<div class="ibfw-field ibfw-checkbox">
					<div class="ibfw-control">
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1"<?php checked( $value, 1 ); ?>>
							<?php echo $this->label; ?>
						</label>

						<?php
							if ( $this->description ) {
								echo '<div class="description">' . $this->description . '</div>';
							}
						?>
					</div>
				</div>
				<?php
				break;

			case 'textarea':
				?>
				<div class="ibfw-field">
					<div class="ibfw-label"><label><?php echo $this->label; ?></label></div>
					<div class="ibfw-control">
						<textarea name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
						<?php
							if ( $this->description ) {
								echo '<div class="description">' . $this->description . '</div>';
							}
						?>
					</div>
				</div>
				<?php
				break;

			case 'select':
				if ( empty( $this->choices ) ) return;

				?>
				<div class="ibfw-field">
					<div class="ibfw-label"><label><?php echo $this->label; ?></label></div>
					<div class="ibfw-control">
						<select name="<?php echo esc_attr( $name ); ?>">
							<?php
								foreach ( $this->choices as $choice_value => $label ) {
									echo '<option value="' . esc_attr( $choice_value ) . '"' . selected( $value, $choice_value, false ) . '>' . esc_html( $label ) . '</option>';
								}
							?>
						</select>
						<?php
							if ( $this->description ) {
								echo '<div class="description">' . $this->description . '</div>';
							}
						?>
					</div>
				</div>
				<?php
				break;

			case 'number':
				?>
				<div class="ibfw-field">
					<div class="ibfw-label"><label><?php echo $this->label; ?></label></div>
					<div class="ibfw-control">
						<input type="number" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
						<?php
							if ( $this->description ) {
								echo '<div class="description">' . $this->description . '</div>';
							}
						?>
					</div>
				</div>
				<?php
				break;

			default:
				?>
				<div class="ibfw-field">
					<div class="ibfw-label"><label><?php echo $this->label; ?></label></div>
					<div class="ibfw-control">
						<input type="text" name="<?php echo esc_attr( $name ); ?>" class="regular-text" value="<?php echo esc_attr( $value ); ?>">
						<?php
							if ( $this->description ) {
								echo '<div class="description">' . $this->description . '</div>';
							}
						?>
					</div>
				</div>
				<?php
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
			case 'checkbox':
				if ( 1 != $value ) $value = 0;
				break;

			case 'select':
				if ( empty( $this->choices ) || ! array_key_exists( $value, $this->choices ) ) {
					$value = '';
				}
				break;

			case 'number':
				$value = (float) $value;
				break;

			default:
				if ( ! current_user_can( 'unfiltered_html' ) ) {
					$value = wp_kses_data( $value );
				}
		}

		return $value;
	}
}

class IBFW_Meta {
	/**
	 * @access private
	 * @var string
	 */
	private static $version = '1.0.0';

	/**
	 * @access private
	 * @var array
	 */
	private static $fields;

	/**
	 * @access private
	 * @var array
	 */
	private static $meta_boxes;

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta_boxes' ) );
	}

	/**
	 * Add meta boxes action hook.
	 */
	public static function add_meta_boxes() {
		foreach ( self::$meta_boxes as $meta_box ) {
			foreach ( $meta_box['screen'] as $screen ) {
				add_meta_box(
					$meta_box['id'],
					$meta_box['title'],
					array( __CLASS__, 'output_meta_box' ),
					$screen,
					! isset( $meta_box['context'] ) ? 'advanced' : $meta_box['context'],
					! isset( $meta_box['priority'] ) ? 'default' : $meta_box['priority'],
					null
				);
			}
		}
	}

	/**
	 * Add meta box.
	 *
	 * @param array $meta_box
	 */
	public static function add_meta_box( $meta_box ) {
		self::$meta_boxes[] = $meta_box;
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
			self::$fields[] = new IBFW_Meta_Field( $field );
	}

	/**
	 * Output meta box.
	 *
	 * @param WP_Post $post
	 * @param array $meta_box
	 */
	public static function output_meta_box( $post, $meta_box ) {
		wp_nonce_field( $meta_box['id'] . '_meta_box', $meta_box['id'] . '_nonce' );
		
		foreach ( self::$fields as $field ) {
			if ( $field->meta_box != $meta_box['id'] ) continue;

			$value = get_post_meta( $post->ID, $field->name, true );

			if ( 0 !== $value && empty( $value ) && isset( $field->default ) ) {
				$value = $field->default;
			}

			echo $field->output( $value );
		}
	}

	/**
	 * Save custom fields.
	 *
	 * @param int $post_id
	 */
	public static function save_meta_boxes( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		foreach ( self::$meta_boxes as $meta_box ) {
			if ( ! isset( $_POST[ $meta_box['id'] . '_nonce' ] ) ) {
				continue;
			}

			if ( ! wp_verify_nonce( $_POST[ $meta_box['id'] . '_nonce' ], $meta_box['id'] . '_meta_box' ) ) {
				continue;
			}

			foreach ( self::$fields as $field ) {
				if ( $field->meta_box != $meta_box['id'] ) {
					continue;
				}

				$value = isset( $_POST[ $field->name ] ) ? $_POST[ $field->name ] : '';
				update_post_meta( $post_id, $field->name, $field->sanitize( $value ) );
			}
		}
	}
}
