<?php
/**
 * Frontend results template.
 *
 * Renders each post/product item inside the SearchFilterSort results grid,
 * including thumbnail, title, price, and optional action button.
 *
 * @package searchfiltersort
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure $query exists and is a WP_Query instance.
if ( empty( $query ) || ! $query instanceof WP_Query ) {
	return;
}

$sflts_currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$';

$sflts_helper  = \SearchFilterSort\SFLTS_Helpers::class;
$sflts_options = $sflts_helper::sflts_settings();

$sflts_button_type      = isset( $sflts_options['button_type'] ) ? $sflts_options['button_type'] : 'view';
$sflts_button_label     = isset( $sflts_options['button_label'] ) ? $sflts_options['button_label'] : __( 'View', 'searchfiltersort' );
$sflts_button_alignment = isset( $sflts_options['button_alignment'] ) ? $sflts_options['button_alignment'] : 'left';

// Alignment class.
$sflts_alignment_class = 'sflts-btn-align-' . esc_attr( $sflts_button_alignment );
?>

<?php if ( $query->have_posts() ) : ?>
	<?php
	while ( $query->have_posts() ) :
		$query->the_post();
		?>
		<article class="sflts-item">

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="sflts-thumb">
					<a href="<?php echo esc_url( get_permalink() ); ?>">
						<?php the_post_thumbnail( 'medium', array( 'alt' => esc_attr( get_the_title() ) ) ); ?>
					</a>
				</div>
			<?php endif; ?>

			<h3>
				<a href="<?php echo esc_url( get_permalink() ); ?>">
					<?php echo esc_html( get_the_title() ); ?>
				</a>
			</h3>

			<?php
			$sflts_price = get_post_meta( get_the_ID(), '_price', true );
			if ( $sflts_price ) :
				?>
				<div class="sflts-price">
					<?php echo esc_html( $sflts_currency_symbol . $sflts_helper::sflts_format_price( $sflts_price ) ); ?>
				</div>
			<?php endif; ?>

			<div class="sflts-btn-wrap <?php echo esc_attr( $sflts_alignment_class ); ?>">
				<?php if ( 'hide' !== $sflts_button_type ) : ?>
					<?php if ( 'add_to_cart' === $sflts_button_type ) : ?>
						<?php
						if ( class_exists( 'WooCommerce' ) && get_post_type() === 'product' ) {
							$sflts_product = wc_get_product( get_the_ID() );
							if ( $sflts_product ) :
								// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core hook
								$sflts_add_to_cart_html = apply_filters(
									'woocommerce_loop_add_to_cart_link',
									sprintf(
										'<a href="%s" 
											data-product_id="%s"
											class="sflts-btn add_to_cart_button ajax_add_to_cart" 
											rel="nofollow">%s</a>',
										esc_url( $sflts_product->add_to_cart_url() ),
										esc_attr( $sflts_product->get_id() ),
										esc_html( $sflts_button_label )
									),
									$sflts_product
								);
								echo wp_kses_post( $sflts_add_to_cart_html );
							endif;
						} else {
							// Fallback for non-product post type.
							echo '<a href="' . esc_url( get_permalink() ) . '" class="sflts-btn">' . esc_html( $sflts_button_label ) . '</a>';
						}
						?>
					<?php else : ?>
						<!-- Default View Button -->
						<a href="<?php echo esc_url( get_permalink() ); ?>" class="sflts-btn">
							<?php echo esc_html( $sflts_button_label ); ?>
						</a>
					<?php endif; ?>
				<?php endif; ?>
			</div>

		</article>
	<?php endwhile; ?>

	<?php wp_reset_postdata(); ?>

<?php else : ?>
	<p class="sflts-no-results"><?php esc_html_e( 'No results found.', 'searchfiltersort' ); ?></p>
<?php endif; ?>
