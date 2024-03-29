<?php

class PSOURCE_Field_Advanced_Select extends PSOURCE_Field {

	/**
	 * Läuft auf dem übergeordneten Konstrukt
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param array $args {
	 *        Eine Reihe von Argumenten. Optional.
	 *
	 * @type bool $multiple Gibt an, ob Mehrfachauswahl oder nur eine Option zulässig ist
	 * @type string $placeholder Der Text, der angezeigt wird, wenn das Feld leer ist.
	 * @type array $options Ein Array von $key=>$value Paaren der verfügbaren Optionen..
	 * @type string $format_dropdown_header Der Text, der im Dropdown-Header angezeigt werden soll (z.B. alle auswählen, keine auswählen)
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive( array(
			'multiple'               => true,
			'placeholder'            => __( 'Wähle einige Möglichkeiten', 'mp' ),
			'options'                => array(),
			'is_tag'                 => false,
			'format_dropdown_header' => '',
		), $args );

		$this->args['class'] .= ' psource-advanced-select';
		$this->args['custom']['data-placeholder']            = $this->args['placeholder'];
		$this->args['custom']['data-multiple']               = (int) $this->args['multiple'];
		$this->args['custom']['data-format-dropdown-header'] = $this->args['format_dropdown_header'];
		if ( isset( $this->args['custom']['is_tag'] ) ) {
			$this->args['is_tag'] = $this->args['custom']['is_tag'];
		}
	}

	/**
	 * Formatiert den Feldwert für die Anzeige.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param mixed $value
	 * @param mixed $post_id
	 */
	public function format_value( $value, $post_id ) {
		$values = ( is_array( $value ) ) ? $value : explode( ',', $value );

		return parent::format_value( $values, $post_id );
	}

	/**
	 * Druckt Skripte
	 *
	 * @since 3.0
	 * @access public
	 */
	public function print_scripts() {
		?>
		<script type="text/javascript">
			( function ($) {
				var parseOptions = function (opts) {
					var options = opts.split('||'),
						theArray = [];

					$(options).each(function () {
						var val = this.split('='),
							obj = {"id": val[0], "text": val[1]};

						if (obj.id.indexOf('|disabled') >= 0) {
							obj.disabled = true;
						}

						theArray.push(obj);
					});

					return theArray;
				}

				var getOptionText = function (opts, val) {
					var returnVal = '';

					$(opts).each(function () {
						if (this.id == val) {
							returnVal = this.text;
						}
					});

					return returnVal;
				}

				var initSelect2 = function () {
					$('input.psource-advanced-select').each(function () {
						var $this = $(this),
							options = [];

						if (!$this.is('select')) {

							if ($this.attr('data-options').length > 0) {
								options = parseOptions($this.attr('data-options'));
							}

							var args = {
								"allowSelectAllNone": true,
								"multiple": $this.attr('data-multiple'),
								"placeholder": $this.attr('data-placeholder'),
								"initSelection": function (element, callback) {
									var data = [];

									$(element.val().split(',')).each(function () {
										data.push({"id": this, "text": getOptionText(options, this)});
									});

									callback(data);
								},
								"data": options,
								"width": "100%"
							};
							if ($this.attr('data-format-dropdown-header') !== undefined) {
								args.formatDropdownHeader = function () {
									return $this.attr('data-format-dropdown-header');
								};
							}

							$this.mp_select2(args);
						} else {
							var args = {
								"dropdownAutoWidth": true,
								"placeholder": $this.attr('data-placeholder'),
								"allowClear": true
							};

							if ($this.attr('data-format-dropdown-header') !== undefined) {
								args.formatDropdownHeader = function () {
									return $this.attr('data-format-dropdown-header');
								};
							}

							$this.mp_select2(args);
						}
					});
				};

				$(document).on('psource_repeater_field/before_add_field_group', function () {
					$('.psource-advanced-select').mp_select2('destroy');
					$('[id^="s2id_"]').remove(); // Remove select2 autogenerated elements. For some reason there is a bug in the destroy method.
				});

				$(document).on('psource_repeater_field/after_add_field_group', function (e, $group) {
					initSelect2();
				});

				$(document).ready(function () {
					initSelect2();
				});
			}(jQuery) );
		</script>
		<?php
		parent::print_scripts();
	}

	/**
	 * Sanitizes the field value before saving to database.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param $value
	 * @param $post_id
	 */
	public function sanitize_for_db( $value, $post_id ) {
		$value = trim( $value, ',' );

		return parent::sanitize_for_db( $value, $post_id );
	}

	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$value   = $this->get_value( $post_id );
		$vals    = is_array( $value ) ? $value : explode( ',', $value );
		$values  = array();
		$options = array();

		foreach ( $this->args['options'] as $val => $label ) {
			$options[] = $val . '=' . $label;
		}

		$this->before_field();

		if ( $this->args['multiple'] ) :
			$this->args['custom']['data-options'] = implode( '||', $options );
			echo '<input type="hidden" ' . $this->parse_atts() . ' value="' . implode( ',', $vals ) . '" />';
		else :
			?>
			<select <?php echo $this->parse_atts(); ?>>
				<?php
				foreach ( $this->args['options'] as $val => $label ) :
					$value = empty( $val ) ? '' : ' value="' . $val . '"';
					?>
					<option<?php echo $value;
					echo in_array( $val, $vals ) ? ' selected' : ''; ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		endif;

		$this->after_field();
	}

	/**
	 * Enqueues the field's scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'psource-field-select2', PSOURCE_Metabox::class_url( 'ui/select2/select2.js' ), array( 'jquery' ), PSOURCE_METABOX_VERSION );
	}

	/**
	 * Enqueues the field's styles
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'psource-field-select2', PSOURCE_Metabox::class_url( 'ui/select2/select2.css' ), array(), PSOURCE_METABOX_VERSION );
	}

}