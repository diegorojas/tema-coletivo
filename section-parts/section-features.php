<?php
/**
 * Section Features
 *
 * @package coletivo
 */

$coletivo_featureds_id       = get_theme_mod( 'coletivo_features_id', esc_html__( 'features', 'coletivo' ) );
$coletivo_featureds_disable  = get_theme_mod( 'coletivo_features_disable' ) === 1 ? true : false;
$coletivo_featureds_title    = get_theme_mod( 'coletivo_features_title', esc_html__( 'Features', 'coletivo' ) );
$coletivo_featureds_subtitle = get_theme_mod( 'coletivo_features_subtitle', esc_html__( 'Why choose Us', 'coletivo' ) );
if ( coletivo_is_selective_refresh() ) {
	$disable = false;
}
$data = coletivo_get_section_features_data();
if ( ! $disable && ! empty( $data ) ) {
	$coletivo_featureds_desc = get_theme_mod( 'coletivo_features_desc' );

	?>
	<?php if ( ! coletivo_is_selective_refresh() ) { ?>
		<section id="<?php echo esc_attr( $coletivo_featureds_id ); ?>" <?php do_action( 'coletivo_section_atts', 'features' ); ?>
		class="<?php echo esc_attr( apply_filters( 'coletivo_section_class', 'section-features section-padding section-meta onepage-section', 'features' ) ); ?>">
	<?php } ?>
	<?php do_action( 'coletivo_section_before_inner', 'features' ); ?>
	<div class="container">
		<?php if ( $coletivo_featureds_title || $coletivo_featureds_subtitle || $coletivo_featureds_desc ) { ?>
			<div class="section-title-area">
				<?php
				if ( '' !== $coletivo_featureds_subtitle ) {
					echo '<h5 class="section-subtitle">' . esc_html( $coletivo_featureds_subtitle ) . '</h5>';
				}

				if ( '' !== $coletivo_featureds_title ) {
					echo '<h2 class="section-title">' . esc_html( $coletivo_featureds_title ) . '</h2>';
				}

				if ( $coletivo_featureds_desc ) {
					echo '<div class="section-desc">' . apply_filters( 'the_content', wp_kses_post( $coletivo_featureds_desc ) ) . '</div>'; // phpcs:ignore
				}
				?>
			</div>
		<?php } ?>
		<div class="section-content">
			<div class="row">
				<?php
				$layout = intval( get_theme_mod( 'coletivo_features_layout', 3 ) );
				foreach ( $data as $k => $f ) {
					$media = '';
					$f     = wp_parse_args(
						$f,
						array(
							'icon_type' => 'icon',
							'icon'      => 'gg',
							'image'     => '',
							'link'      => '',
							'title'     => '',
							'desc'      => '',
						)
					);
					if ( 'image' === $f['icon_type'] && $f['image'] ) {
						$url = coletivo_get_media_url( $f['image'] );
						if ( $url ) {
							$media = '<span class="icon-image"><img src="' . esc_url( $url ) . '" alt=""></span>';
						}
					} elseif ( $f['icon'] ) {
						$f['icon'] = trim( $f['icon'] );
						$media     = '<span class="fa-stack fa-5x"><i class="fa fa-circle fa-stack-2x icon-background-default"></i> <i class="feature-icon fa ' . esc_attr( $f['icon'] ) . ' fa-stack-1x"></i></span>';
					}
					?>
					<div class="feature-item col-lg-<?php echo esc_attr( $layout ); ?> col-sm-6 wow slideInUp">
						<div class="feature-media">
							<?php if ( $f['link'] ) { ?>
								<a href="<?php echo esc_url( $f['link'] ); ?>">
							<?php } ?>
							<?php echo wp_kses_post( $media ); ?>
							<?php if ( $f['link'] ) { ?>
								</a>
							<?php } ?>
						</div>
						<h4>
							<?php if ( $f['link'] ) { ?>
								<a href="<?php echo esc_url( $f['link'] ); ?>">
							<?php } ?>
							<?php echo esc_html( $f['title'] ); ?>
							<?php if ( $f['link'] ) { ?>
								</a>
							<?php } ?>
						</h4>
						<div><?php echo wp_kses_post( $f['desc'] ); ?></div>
					</div>
					<?php
				}// end loop featues
				?>
			</div>
		</div>
	</div>
	<?php do_action( 'coletivo_section_after_inner', 'features' ); ?>

	<?php if ( ! coletivo_is_selective_refresh() ) { ?>
		</section>
		<?php
	}
}
