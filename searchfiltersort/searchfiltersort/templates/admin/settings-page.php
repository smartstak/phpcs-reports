<?php
/**
 * Admin settings page.
 *
 * @package searchfiltersort
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sflts_helper = \SearchFilterSort\SFLTS_Helpers::class;

// Get saved settings with defaults to prevent missing keys.
$sflts_options  = $sflts_helper::sflts_settings();
$sflts_defaults = array(
	'post_type'              => 'post',
	'taxonomy'               => 'category',
	'products_per_page'      => 10,
	'enable_category_filter' => 0,
	'enable_price_filter'    => 0,
	'enable_sort'            => 0,
	'columns'                => 3,
	'pagination_type'        => 'numeric',
	'filter_position'        => 'top',
	'button_type'            => 'view',
	'button_label'           => '',
	'button_alignment'       => 'left',
);
$sflts_options  = wp_parse_args( $sflts_options, $sflts_defaults );

$sflts_feature_message = __( 'Upgrade to SearchFilterSort Pro to unlock this option.', 'searchfiltersort' );
?>

<div class="wrap sflts-settings-wrap">
	<h1 class="sflts-settings-title"><?php esc_html_e( 'SearchFilterSort Settings', 'searchfiltersort' ); ?></h1>

	<form method="post" action="options.php" class="sflts-settings-form">
		<?php
		settings_fields( 'sflts_settings_group' );
		wp_nonce_field( 'sflts_save_settings', 'sflts_settings_nonce' );
		?>

		<div class="sflts-card">
			<h2><?php esc_html_e( 'General Settings', 'searchfiltersort' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Post Type', 'searchfiltersort' ); ?></th>
					<td>
						<select name="sflts_settings[post_type]" class="sflts-field sflts-post-type">
							<?php foreach ( $sflts_helper::sflts_post_types() as $sflts_value => $sflts_label ) : ?>
								<option value="<?php echo esc_attr( $sflts_value ); ?>" <?php selected( $sflts_options['post_type'], $sflts_value ); ?>><?php echo esc_html( $sflts_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Taxonomy', 'searchfiltersort' ); ?></th>
					<td>
						<select name="sflts_settings[taxonomy]" class="sflts-field sflts-taxonomy">
							<?php foreach ( $sflts_helper::sflts_global_taxonomies() as $sflts_value => $sflts_label ) : ?>
								<option value="<?php echo esc_attr( $sflts_value ); ?>" <?php selected( $sflts_options['taxonomy'], $sflts_value ); ?>><?php echo esc_html( $sflts_label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Ensure this taxonomy belongs to the selected post type.', 'searchfiltersort' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Items Per Page', 'searchfiltersort' ); ?></th>
					<td>
						<input type="number" min="1" max="50" name="sflts_settings[products_per_page]" class="sflts-field sflts-items-per-page" value="<?php echo esc_attr( absint( $sflts_options['products_per_page'] ) ); ?>" />
					</td>
				</tr>
			</table>
		</div>

		<div class="sflts-card">
			<h2><?php esc_html_e( 'Display Options', 'searchfiltersort' ); ?></h2>

			<?php
			$sflts_checkboxes = array(
				'enable_category_filter' => __( 'Category Filter', 'searchfiltersort' ),
				'enable_price_filter'    => __( 'Price Filter', 'searchfiltersort' ),
				'enable_sort'            => __( 'Sort Dropdown', 'searchfiltersort' ),
			);

			foreach ( $sflts_checkboxes as $sflts_key => $sflts_label ) :
				?>
				<div class="sflts-checkbox-switch-wrapper">
					<label class="sflts-checkbox-switch">
						<div class="sflts-checkbox-switch-input">
							<input type="checkbox" name="sflts_settings[<?php echo esc_attr( $sflts_key ); ?>]" value="1" <?php checked( ! empty( $sflts_options[ $sflts_key ] ) ); ?> />
							<span class="sflts-checkbox-slider"></span>
						</div>
						<div class="sflts-checkbox-switch-label">
							<?php echo esc_html( $sflts_label ); ?>
						</div>
					</label>
				</div>
			<?php endforeach; ?>

		</div>

		<div class="sflts-card">
			<h2><?php esc_html_e( 'Layout Settings', 'searchfiltersort' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Columns', 'searchfiltersort' ); ?></th>
					<td>
						<input type="number" min="2" max="4" name="sflts_settings[columns]" class="sflts-field sflts-columns" value="<?php echo esc_attr( absint( $sflts_options['columns'] ) ); ?>" />
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Pagination Type', 'searchfiltersort' ); ?></th>
					<td>
						<select name="sflts_settings[pagination_type]" class="sflts-field">
							<?php foreach ( $sflts_helper::sflts_pagination_types() as $sflts_value => $sflts_label ) : ?>
								<option value="<?php echo esc_attr( $sflts_value ); ?>" <?php selected( $sflts_options['pagination_type'], $sflts_value ); ?>><?php echo esc_html( $sflts_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Filter Position', 'searchfiltersort' ); ?></th>
					<td>
						<select name="sflts_settings[filter_position]" class="sflts-field sflts-filter-position">
							<?php foreach ( $sflts_helper::sflts_filter_positions() as $sflts_value => $sflts_label ) : ?>
								<option value="<?php echo esc_attr( $sflts_value ); ?>" <?php selected( $sflts_options['filter_position'], $sflts_value ); ?>><?php echo esc_html( $sflts_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
		</div>

		<div class="sflts-card">
			<h2><?php esc_html_e( 'Action Button Settings', 'searchfiltersort' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Button Type', 'searchfiltersort' ); ?></th>
					<td>
						<select name="sflts_settings[button_type]" class="sflts-field">
							<option value="view" <?php selected( $sflts_options['button_type'], 'view' ); ?>><?php esc_html_e( 'View Button', 'searchfiltersort' ); ?></option>
							<option value="add_to_cart" <?php selected( $sflts_options['button_type'], 'add_to_cart' ); ?>><?php esc_html_e( 'Add to Cart Button', 'searchfiltersort' ); ?></option>
							<option value="hide" <?php selected( $sflts_options['button_type'], 'hide' ); ?>><?php esc_html_e( 'Hide Button', 'searchfiltersort' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Button Label', 'searchfiltersort' ); ?></th>
					<td>
						<input type="text" name="sflts_settings[button_label]" value="<?php echo esc_attr( $sflts_options['button_label'] ); ?>" class="sflts-field" />
						<p class="description"><?php esc_html_e( 'Text to display on the button.', 'searchfiltersort' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Alignment', 'searchfiltersort' ); ?></th>
					<td>
						<select name="sflts_settings[button_alignment]" class="sflts-field">
							<option value="left" <?php selected( $sflts_options['button_alignment'], 'left' ); ?>><?php esc_html_e( 'Left', 'searchfiltersort' ); ?></option>
							<option value="center" <?php selected( $sflts_options['button_alignment'], 'center' ); ?>><?php esc_html_e( 'Center', 'searchfiltersort' ); ?></option>
							<option value="right" <?php selected( $sflts_options['button_alignment'], 'right' ); ?>><?php esc_html_e( 'Right', 'searchfiltersort' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button(); ?>
	</form>

	<div class="sflts-card sflts-shortcode-preview">
		<h2><?php esc_html_e( 'Shortcode Preview', 'searchfiltersort' ); ?></h2>
		<div class="sflts-shortcode-wrapper">
			<code id="sflts-shortcode-box" class="sflts-shortcode-display">[SearchFilterSort]</code>
			<button type="button" class="button button-secondary sflts-copy-shortcode" data-clipboard-target="#sflts-shortcode-box">
				<?php esc_html_e( 'Copy Shortcode', 'searchfiltersort' ); ?>
			</button>
		</div>
		<p class="description">
			<?php esc_html_e( 'This shortcode updates automatically as you change settings above. Copy and paste it into any page or post.', 'searchfiltersort' ); ?>
		</p>

		<h3><?php esc_html_e( 'Shortcode Parameters', 'searchfiltersort' ); ?></h3>
		<p class="description"><?php esc_html_e( 'You can override any default setting by adding parameters to the shortcode:', 'searchfiltersort' ); ?></p>
		<table class="sflts-params-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Parameter', 'searchfiltersort' ); ?></th>
					<th><?php esc_html_e( 'Description', 'searchfiltersort' ); ?></th>
					<th><?php esc_html_e( 'Example', 'searchfiltersort' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>post_type</code></td>
					<td><?php esc_html_e( 'Target post type to filter', 'searchfiltersort' ); ?></td>
					<td><code>post_type="product"</code></td>
				</tr>
				<tr>
					<td><code>taxonomy</code></td>
					<td><?php esc_html_e( 'Taxonomy used for category filter', 'searchfiltersort' ); ?></td>
					<td><code>taxonomy="product_cat"</code></td>
				</tr>
				<tr>
					<td><code>columns</code></td>
					<td><?php esc_html_e( 'Number of columns in grid (2-4)', 'searchfiltersort' ); ?></td>
					<td><code>columns="3"</code></td>
				</tr>
				<tr>
					<td><code>filter_position</code></td>
					<td><?php esc_html_e( 'Position of filter panel', 'searchfiltersort' ); ?></td>
					<td><code>filter_position="top"</code></td>
				</tr>
			</tbody>
		</table>
		<br>
		<p class="description">
			<strong><?php esc_html_e( 'Example:', 'searchfiltersort' ); ?></strong><br>
			<code>[SearchFilterSort post_type="product" taxonomy="product_cat" columns="4" filter_position="left"]</code>
		</p>
	</div>
</div>