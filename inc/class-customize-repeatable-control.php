<?php
/**
 * Coletivo Repeatable Control Class
 *
 * @package Coletivo/Classes
 */

declare(strict_types = 1);

namespace Coletivo;

use WP_Customize_Control;

// Prevents dipostt access.
defined( 'ABSPATH' ) || exit;

/**
 * Repeatable Control Class.
 *
 * @since  1.0.0
 * @access public
 */
class Customize_Repeatable_Control extends WP_Customize_Control {

	/**
	 * The type of customize control being rendered.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $type = 'repeatable';

	/**
	 * The fields of customize control being rendered.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    array
	 */
	public $fields = array();

	/**
	 * The live title ID.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    null
	 */
	public $live_title_id = null;

	/**
	 * The title format.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    null
	 */
	public $title_format = null;

	/**
	 * Defined values.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    null
	 */
	public $defined_values = null;

	/**
	 * ID key.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    null
	 */
	public $id_key = null;

	/**
	 * Limited messages.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    null
	 */
	public $limited_msg = null;

	/**
	 * The args.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    array
	 */
	public $args = array();

	/**
	 * Construct
	 *
	 * @access public
	 * @param  string $manager Manager.
	 * @param  int    $id      The ID.
	 * @param  array  $args    The args.
	 */
	public function __construct( $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );
		if ( empty( $args['fields'] ) || ! is_array( $args['fields'] ) ) {
			$args['fields'] = array();
		}
		foreach ( $args['fields'] as $key => $op ) {
			$args['fields'][ $key ]['id'] = $key;
			if ( ! isset( $op['value'] ) ) {
				if ( isset( $op['default'] ) ) {
					$args['fields'][ $key ]['value'] = $op['default'];
				} else {
					$args['fields'][ $key ]['value'] = '';
				}
			}
		}

		$this->fields         = $args['fields'];
		$this->live_title_id  = isset( $args['live_title_id'] ) ? $args['live_title_id'] : false;
		$this->defined_values = isset( $args['defined_values'] ) ? $args['defined_values'] : false;
		$this->id_key         = isset( $args['id_key'] ) ? $args['id_key'] : false;
		if ( isset( $args['title_format'] ) && '' !== $args['title_format'] ) {
			$this->title_format = $args['title_format'];
		} else {
			$this->title_format = '';
		}

		if ( isset( $args['limited_msg'] ) && '' !== $args['limited_msg'] ) {
			$this->limited_msg = $args['limited_msg'];
		} else {
			$this->limited_msg = '';
		}

		if ( ! isset( $args['max_item'] ) ) {
			$args['max_item'] = 0;
		}

		if ( ! isset( $args['allow_unlimited'] ) || false !== $args['allow_unlimited'] ) {
			$this->max_item = apply_filters( 'coletivo_reepeatable_max_item', absint( $args['max_item'] ) );
		} else {
			$this->max_item = absint( $args['max_item'] );
		}

		$this->changeable          = isset( $args['changeable'] ) && 'no' === $args['changeable'] ? 'no' : 'yes';
		$this->default_empty_title = isset( $args['default_empty_title'] ) && '' !== $args['default_empty_title'] ? $args['default_empty_title'] : esc_html__( 'Item', 'coletivo' );
	}

	/**
	 * Merge data
	 *
	 * @access public
	 * @param  array $array_value   Array value.
	 * @param  array $array_default Array value.
	 *
	 * @return array
	 */
	public function merge_data( $array_value, $array_default ) {

		if ( ! $this->id_key ) {
			return $array_value;
		}

		if ( ! is_array( $array_value ) ) {
			$array_value = array();
		}

		if ( ! is_array( $array_default ) ) {
			$array_default = array();
		}

		$new_array = array();
		foreach ( $array_value as $k => $a ) {

			if ( is_array( $a ) ) {
				if ( isset( $a[ $this->id_key ] ) && '' !== $a[ $this->id_key ] ) {
					$new_array[ $a[ $this->id_key ] ] = $a;
				} else {
					$new_array[ $k ] = $a;
				}
			}
		}

		foreach ( $array_default as $k => $a ) {
			if ( is_array( $a ) && isset( $a[ $this->id_key ] ) ) {
				if ( ! isset( $new_array[ $a[ $this->id_key ] ] ) ) {
					$new_array[ $a[ $this->id_key ] ] = $a;
				}
			}
		}

		return array_values( $new_array );
	}

	/**
	 * To JSON
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function to_json() {
		parent::to_json();
		$value = $this->value();

		if ( is_string( $value ) ) {
			$value = json_decode( $value, true );
		}
		if ( empty( $value ) ) {
			$value = $this->defined_values;
		} elseif ( is_array( $this->defined_values ) && ! empty( $this->defined_values ) ) {
			$value = $this->merge_data( $value, $this->defined_values );
		}

		$this->json['live_title_id']       = $this->live_title_id;
		$this->json['title_format']        = $this->title_format;
		$this->json['max_item']            = $this->max_item;
		$this->json['limited_msg']         = $this->limited_msg;
		$this->json['changeable']          = $this->changeable;
		$this->json['default_empty_title'] = $this->default_empty_title;
		$this->json['value']               = $value;
		$this->json['id_key']              = $this->id_key;
		$this->json['fields']              = $this->fields;
	}

	/**
	 * Enqueue scripts/styles.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function enqueue() {
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'item_tpl' ), 66 );
	}

	/**
	 * Item TPL
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function item_tpl() {
		?>
		<script type="text/html" id="repeatable-js-item-tpl">
			<?php self::js_item(); ?>
		</script>
		<?php
	}

	/**
	 * Render title and description
	 *
	 * @return void
	 */
	public function render_content() {
		?>
		<label>
			<?php if ( ! empty( $this->label ) ) { ?>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php } ?>
			<?php if ( ! empty( $this->description ) ) { ?>
				<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
			<?php } ?>
		</label>
		<input data-hidden-value type="hidden" <?php $this->input_attrs(); ?> value="" <?php $this->link(); ?> />
		<div class="form-data">
			<ul class="list-repeatable"></ul>
		</div>
		<div class="repeatable-actions">
			<span class="button-secondary add-new-repeat-item"><?php esc_html_e( 'Add an item', 'coletivo' ); ?></span>
		</div>
		<?php
	}

	/**
	 * JS Item
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function js_item() {
		?>
		<li class="repeatable-customize-control">
			<div class="widget">
				<div class="widget-top">
					<div class="widget-title-action">
						<a class="widget-action" href="#"></a>
					</div>
					<div class="widget-title">
						<h4 class="live-title"><?php esc_html_e( 'Item', 'coletivo' ); ?></h4>
					</div>
				</div>

				<div class="widget-inside">
					<div class="form">
						<div class="widget-content">
							<# var cond_v; #>
							<# for ( i in data ) { #>
								<# if ( ! data.hasOwnProperty( i ) ) continue; #>
								<# field = data[i]; #>
								<# if ( ! field.type ) continue; #>
								<# if ( field.type ){ #>

									<#
									if ( field.required  && field.required.length >= 3 ) {
										#>
										<div class="conditionize item item-{{ field.type }} item-{{ field.id }}" data-cond-option="{{ field.required[0] }}" data-cond-operator="{{ field.required[1] }}" data-cond-value="{{ field.required[2] }}" >
										<#
									} else {
										#>
										<div class="item item-{{ field.type }} item-{{ field.id }}" >
										<#
									}
									#>
										<# if ( 'checkbox' !== field.type ) { #>
											<# if ( field.title ) { #>
											<label class="field-label">{{ field.title }}</label>
											<# } #>

											<# if ( field.desc ) { #>
											<p class="field-desc description">{{{ field.desc }}}</p>
											<# } #>
										<# } #>

										<# if ( 'hidden' === field.type ) { #>
											<input data-live-id="{{ field.id }}" type="hidden" value="{{ field.value }}" data-repeat-name="_items[__i__][{{ field.id }}]" class="">
										<# } else if ( 'add_by' === field.type ) { #>
											<input data-live-id="{{ field.id }}" type="hidden" value="{{ field.value }}" data-repeat-name="_items[__i__][{{ field.id }}]" class="add_by">
										<# } else if ( 'text' === field.type ) { #>
											<input data-live-id="{{ field.id }}" type="text" value="{{ field.value }}" data-repeat-name="_items[__i__][{{ field.id }}]" class="">
										<# } else if ( 'checkbox' === field.type ) { #>

											<# if ( field.title ) { #>
												<label class="checkbox-label">
													<input data-live-id="{{ field.id }}" type="checkbox" <# if ( field.value ) { #> checked="checked" <# } #> value="1" data-repeat-name="_items[__i__][{{ field.id }}]" class="">
													{{ field.title }}</label>
											<# } #>

											<# if ( field.desc ) { #>
											<p class="field-desc description">{{ field.desc }}</p>
											<# } #>


										<# } else if ( 'select' === field.type ) { #>

											<# if ( field.multiple ) { #>
												<select data-live-id="{{ field.id }}"  class="select-multiple" multiple="multiple" data-repeat-name="_items[__i__][{{ field.id }}][]">
											<# } else  { #>
												<select data-live-id="{{ field.id }}"  class="select-one" data-repeat-name="_items[__i__][{{ field.id }}]">
											<# } #>

												<# for ( k in field.options ) { #>
													<# if ( _.isArray( field.value ) ) { #>
														<option <# if ( _.contains( field.value , k ) ) { #> selected="selected" <# } #>  value="{{ k }}">{{ field.options[k] }}</option>
													<# } else { #>
														<option <# if ( k === field.value ) { #> selected="selected" <# } #>  value="{{ k }}">{{ field.options[k] }}</option>
													<# } #>
												<# } #>
											</select>

										<# } else if ( 'radio' === field.type ) { #>

											<# for ( k in field.options ) { #>

												<# if ( field.options.hasOwnProperty( k ) ) { #>

													<label>
														<input data-live-id="{{ field.id }}"  type="radio" <# if ( k === field.value ) { #> checked="checked" <# } #> value="{{ k }}" data-repeat-name="_items[__i__][{{ field.id }}]" class="widefat">
														{{ field.options[k] }}
													</label>

												<# } #>
											<# } #>

										<# } else if ( 'color' === field.type || 'coloralpha' === field.type ) { #>

											<# if ( '' !== field.value ) { field.value = '#'+field.value ; }  #>

											<input data-live-id="{{ field.id }}" data-show-opacity="true" type="text" value="{{ field.value }}" data-repeat-name="_items[__i__][{{ field.id }}]" class="color-field c-{{ field.type }} alpha-color-control">

										<# } else if ( 'media' === field.type ) { #>

											<# if ( ! field.media  || '' === field.media || 'image' === field.media ) {  #>
												<input type="hidden" value="{{ field.value.url }}" data-repeat-name="_items[__i__][{{ field.id }}][url]" class="image_url widefat">
											<# } else { #>
												<input type="text" value="{{ field.value.url }}" data-repeat-name="_items[__i__][{{ field.id }}][url]" class="image_url widefat">
											<# } #>
											<input type="hidden" data-live-id="{{ field.id }}"  value="{{ field.value.id }}" data-repeat-name="_items[__i__][{{ field.id }}][id]" class="image_id widefat">

											<# if ( ! field.media  || '' === field.media || 'image' === field.media ) {  #>
											<div class="current <# if ( '' !== field.value.url ){ #> show <# } #>">
												<div class="container">
													<div class="attachment-media-view attachment-media-view-image landscape">
														<div class="thumbnail thumbnail-image">
															<# if ( '' !== field.value.url ){ #>
																<img src="{{ field.value.url }}" alt="">
															<# } #>
														</div>
													</div>
												</div>
											</div>
											<# } #>

											<div class="actions">
												<button class="button remove-button " <# if ( ! field.value.url ){ #> style="display:none"; <# } #> type="button"><?php esc_html_e( 'Remove', 'coletivo' ); ?></button>
												<button class="button upload-button" data-media="{{field.media}}" data-add-txt="<?php esc_attr_e( 'Add', 'coletivo' ); ?>" data-change-txt="<?php esc_attr_e( 'Change', 'coletivo' ); ?>" type="button"><# if ( ! field.value.url  ){ #> <?php esc_html_e( 'Add', 'coletivo' ); ?> <# } else { #> <?php esc_html_e( 'Change', 'coletivo' ); ?> <# } #> </button>
												<div style="clear:both"></div>
											</div>

										<# } else if ( 'textarea' === field.type || 'editor' === field.type ) { #>
											<textarea data-live-id="{{{ field.id }}}" data-repeat-name="_items[__i__][{{ field.id }}]">{{ field.value }}</textarea>
										<# }  else if ( 'icon' === field.type  ) { #>
											<#
												var icon_class = field.value;
												if ( 0 !== icon_class.indexOf( 'fa-' ) ) {
													icon_class = 'fa-' + field.value;
												} else {
													icon_class = icon_class.replace( 'fa ', '' );
												}
												icon_class = icon_class.replace( 'fa-fa', '' );

												#>
											<div class="icon-wrapper">
												<i class="fa {{ icon_class }}"></i>
												<input data-live-id="{{ field.id }}" type="hidden" value="{{ field.value }}" data-repeat-name="_items[__i__][{{ field.id }}]" class="">
											</div>
											<a href="#" class="remove-icon"><?php esc_html_e( 'Remove', 'coletivo' ); ?></a>
										<# }  #>

									</div>

								<# } #>
							<# } #>
							<div class="widget-control-actions">
								<div class="alignleft">
									<span class="edit-section" style="display:none;">
										<a href="#">
											<?php esc_html_e( 'Edit Section', 'coletivo' ); ?>
											|
										</a>
									</span>
									<span class="remove-btn-wrapper">
										<a href="#" class="repeat-control-remove" title=""><?php esc_html_e( 'Remove', 'coletivo' ); ?></a> |
									</span>
									<a href="#" class="repeat-control-close"><?php esc_html_e( 'Close', 'coletivo' ); ?></a>
								</div>
								<br class="clear">
							</div>
						</div>
					</div><!-- .form -->

				</div>

			</div>
		</li>
		<?php

	}

}