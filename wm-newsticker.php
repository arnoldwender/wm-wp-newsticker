<?php
/**
 * Plugin Name: WM Newsticker
 * Plugin URI: https://github.com/arnoldwender/wm-newsticker
 * Description: A Gutenberg block for animated news tickers with scroll, fade, slide and typing animations.
 * Version: 1.4.7
 * Author: Arnold Wender
 * Author URI: https://www.arnoldwender.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wm-newsticker
 * Requires at least: 5.9
 * Requires PHP: 7.4
 *
 * @package WM_Newsticker
 */

// Security: Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Security: Prevent direct file execution.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants.
if ( ! defined( 'WM_NEWSTICKER_VERSION' ) ) {
	define( 'WM_NEWSTICKER_VERSION', '1.4.7' );
}
if ( ! defined( 'WM_NEWSTICKER_PLUGIN_DIR' ) ) {
	define( 'WM_NEWSTICKER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WM_NEWSTICKER_PLUGIN_URL' ) ) {
	define( 'WM_NEWSTICKER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Register as Spoke in Wender Media Suite Hub
add_filter( 'wm_register_suite_module', function ( array $modules ): array {
	if ( ! class_exists( 'Newsticker_Spoke_Adapter' ) && file_exists( WM_NEWSTICKER_PLUGIN_DIR . 'includes/class-newsticker-spoke-adapter.php' ) ) {
		require_once WM_NEWSTICKER_PLUGIN_DIR . 'includes/class-newsticker-spoke-adapter.php';
	}
	if ( class_exists( 'Newsticker_Spoke_Adapter' ) ) {
		$modules[] = new Newsticker_Spoke_Adapter();
	}
	return $modules;
} );

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
final class WM_Newsticker {

	/**
	 * Single instance of the class.
	 *
	 * @var WM_Newsticker
	 */
	private static $instance = null;

	/**
	 * Get single instance of the class.
	 *
	 * @return WM_Newsticker
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Prevent cloning of the singleton.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing of the singleton.
	 *
	 * @throws \Exception Always.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Constructor - private to enforce singleton.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'save_post', array( $this, 'flush_transient_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_transient_cache' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
		add_action( 'current_screen', array( $this, 'add_contextual_help' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route( 'wm-newsticker/v1', '/post-types', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_post_types' ),
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}

	/**
	 * Get available post types for REST API.
	 *
	 * @return WP_REST_Response
	 */
	public function get_post_types() {
		$post_types = get_post_types(
			array(
				'public'       => true,
				'show_in_rest' => true,
			),
			'objects'
		);

		$result = array();
		foreach ( $post_types as $post_type ) {
			if ( 'attachment' === $post_type->name ) {
				continue;
			}
			$result[] = array(
				'value' => $post_type->name,
				'label' => $post_type->labels->singular_name,
			);
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Register the Gutenberg block using block.json metadata.
	 *
	 * @return void
	 */
	public function register_block() {
		load_plugin_textdomain(
			'wm-newsticker',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		register_block_type( WM_NEWSTICKER_PLUGIN_DIR, array(
			'render_callback' => array( $this, 'render_block' ),
		) );

		wp_set_script_translations(
			'wm-newsticker-editor-script',
			'wm-newsticker',
			WM_NEWSTICKER_PLUGIN_DIR . 'languages'
		);
	}

	/**
	 * Sanitize a color value (hex, rgb, rgba, or CSS variable).
	 *
	 * @param string $color The color to sanitize.
	 * @return string Sanitized color or default.
	 */
	private function sanitize_color( $color ) {
		if ( empty( $color ) ) {
			return '';
		}

		// Named safe CSS colors.
		$named_colors = array( 'transparent', 'inherit', 'currentcolor' );
		if ( in_array( strtolower( $color ), $named_colors, true ) ) {
			return strtolower( $color );
		}

		// Valid hex color (3, 4, 6, or 8 digits).
		if ( preg_match( '/^#([A-Fa-f0-9]{3,4}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $color ) ) {
			return $color;
		}

		// RGB/RGBA color.
		if ( preg_match( '/^rgba?\s*\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+))?\s*\)$/', $color ) ) {
			return $color;
		}

		// CSS variable (theme colors).
		if ( preg_match( '/^var\s*\(\s*--[a-zA-Z0-9_-]+\s*\)$/', $color ) ) {
			return $color;
		}

		return '#000000';
	}

	/**
	 * Sanitize spacing values (padding, margin, border-radius).
	 *
	 * @param array $spacing The spacing object to sanitize.
	 * @return array Sanitized spacing values.
	 */
	private function sanitize_spacing( $spacing ) {
		$default = array(
			'top'    => '0px',
			'right'  => '0px',
			'bottom' => '0px',
			'left'   => '0px',
		);

		if ( ! is_array( $spacing ) ) {
			return $default;
		}

		$sanitized = array();
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			$sanitized[ $side ] = isset( $spacing[ $side ] )
				? $this->sanitize_css_value( $spacing[ $side ] )
				: '0px';
		}

		return $sanitized;
	}

	/**
	 * Sanitize CSS value (e.g., "10px", "1rem", "5%").
	 *
	 * @param string $value The value to sanitize.
	 * @return string Sanitized value.
	 */
	private function sanitize_css_value( $value ) {
		if ( empty( $value ) ) {
			return '0px';
		}

		if ( preg_match( '/^-?\d*\.?\d+(px|em|rem|%|vh|vw)?$/', $value ) ) {
			return $value;
		}

		return '0px';
	}

	/**
	 * Sanitize box shadow value.
	 *
	 * @param string $value The box shadow value.
	 * @return string Sanitized value.
	 */
	private function sanitize_box_shadow( $value ) {
		$allowed = array(
			'none',
			'0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)',
			'0 3px 6px rgba(0,0,0,0.15), 0 2px 4px rgba(0,0,0,0.12)',
			'0 10px 20px rgba(0,0,0,0.15), 0 3px 6px rgba(0,0,0,0.10)',
			'inset 0 2px 4px rgba(0,0,0,0.1)',
		);

		return in_array( $value, $allowed, true ) ? $value : 'none';
	}

	/**
	 * Convert spacing array to CSS string.
	 *
	 * @param array $spacing The spacing values.
	 * @return string CSS value string.
	 */
	private function spacing_to_css( $spacing ) {
		return sprintf(
			'%s %s %s %s',
			$spacing['top'],
			$spacing['right'],
			$spacing['bottom'],
			$spacing['left']
		);
	}

	/**
	 * Build inline style string from array.
	 *
	 * @param array $styles Key-value pairs of CSS properties.
	 * @return string Style string.
	 */
	private function build_style_string( $styles ) {
		$style_parts = array();

		foreach ( $styles as $property => $value ) {
			if ( ! empty( $value ) && 'none' !== $value && '0px 0px 0px 0px' !== $value ) {
				$style_parts[] = $property . ': ' . $value;
			}
		}

		return implode( '; ', $style_parts );
	}

	/**
	 * Sanitize numeric value within range.
	 *
	 * @param mixed $value   The value to sanitize.
	 * @param int   $min     Minimum allowed value.
	 * @param int   $max     Maximum allowed value.
	 * @param int   $default Default value if invalid.
	 * @return int Sanitized integer.
	 */
	private function sanitize_number_range( $value, $min, $max, $default ) {
		$value = absint( $value );

		if ( $value < $min || $value > $max ) {
			return $default;
		}

		return $value;
	}

	/**
	 * Sanitize item array.
	 *
	 * @param array $item The item to sanitize.
	 * @return array Sanitized item.
	 */
	private function sanitize_item( $item ) {
		return array(
			'text'   => isset( $item['text'] ) ? sanitize_text_field( $item['text'] ) : '',
			'link'   => isset( $item['link'] ) ? esc_url_raw( $item['link'] ) : '',
			'newTab' => isset( $item['newTab'] ) ? (bool) $item['newTab'] : false,
		);
	}

	/**
	 * Get dynamic posts for ticker.
	 *
	 * @param array $attributes Block attributes.
	 * @return array Array of items.
	 */
	private function get_dynamic_items( $attributes ) {
		$items = array();

		$post_type   = isset( $attributes['postType'] ) ? sanitize_key( $attributes['postType'] ) : 'post';

		// Validate post type is registered and publicly accessible to prevent
		// unauthorized access to non-public post type content via crafted attributes.
		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object || ! $post_type_object->public ) {
			$post_type = 'post';
		}

		$posts_count = $this->sanitize_number_range(
			isset( $attributes['postsCount'] ) ? $attributes['postsCount'] : 5,
			1, 20, 5
		);

		$valid_orderby = array( 'date', 'title', 'modified', 'rand', 'comment_count' );
		$orderby       = isset( $attributes['orderBy'] ) && in_array( $attributes['orderBy'], $valid_orderby, true )
			? $attributes['orderBy']
			: 'date';

		$order = isset( $attributes['order'] ) && in_array( $attributes['order'], array( 'ASC', 'DESC' ), true )
			? $attributes['order']
			: 'DESC';

		$show_date   = isset( $attributes['showDate'] ) ? (bool) $attributes['showDate'] : false;
		$date_format = isset( $attributes['dateFormat'] ) ? sanitize_text_field( $attributes['dateFormat'] ) : 'relative';

		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => $posts_count,
			'orderby'        => $orderby,
			'order'          => $order,
			'post_status'    => 'publish',
		);

		if ( ! empty( $attributes['categoryIds'] ) && is_array( $attributes['categoryIds'] ) ) {
			$category_ids = array_map( 'absint', $attributes['categoryIds'] );
			$category_ids = array_filter( $category_ids );
			if ( ! empty( $category_ids ) ) {
				$args['category__in'] = $category_ids;
			}
		}

		if ( ! empty( $attributes['tagIds'] ) && is_array( $attributes['tagIds'] ) ) {
			$tag_ids = array_map( 'absint', $attributes['tagIds'] );
			$tag_ids = array_filter( $tag_ids );
			if ( ! empty( $tag_ids ) ) {
				$args['tag__in'] = $tag_ids;
			}
		}

		// Check transient cache to prevent cache stampedes and unneeded database roundtrips
		$cache_key = 'wm_newsticker_' . md5( (string) wp_json_encode( $args ) . '_' . ( $show_date ? '1' : '0' ) . '_' . $date_format );
		$cached_items = get_transient( $cache_key );
		if ( false !== $cached_items && is_array( $cached_items ) ) {
			return $cached_items;
		}

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$text = get_the_title();

				if ( $show_date ) {
					$date_text = $this->format_post_date( get_the_date( 'U' ), $date_format );
					$text      = $date_text . ' — ' . $text;
				}

				$items[] = array(
					'text'   => $text,
					'link'   => get_permalink(),
					'newTab' => false,
				);
			}
			wp_reset_postdata();
		}

		// Cache items for 5 minutes (invalidated on post save/delete)
		set_transient( $cache_key, $items, 5 * MINUTE_IN_SECONDS );

		return $items;
	}

	/**
	 * Invalidate transient query cache on post mutations
	 *
	 * @return void
	 */
	public function flush_transient_cache() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wm_newsticker_%' OR option_name LIKE '_transient_timeout_wm_newsticker_%'" );
	}

	/**
	 * Format post date.
	 *
	 * @param int    $timestamp Unix timestamp.
	 * @param string $format    Date format type.
	 * @return string Formatted date.
	 */
	private function format_post_date( $timestamp, $format ) {
		if ( 'relative' === $format ) {
			return human_time_diff( $timestamp, time() ) . ' ' . __( 'ago', 'wm-newsticker' );
		}

		return wp_date( get_option( 'date_format' ), $timestamp );
	}

	/**
	 * Render the block on the frontend.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_block( $attributes ) {
		$content_source = isset( $attributes['contentSource'] ) ? sanitize_key( $attributes['contentSource'] ) : 'manual';

		if ( 'posts' === $content_source ) {
			$items = $this->get_dynamic_items( $attributes );
		} else {
			$items = array();
			if ( isset( $attributes['items'] ) && is_array( $attributes['items'] ) ) {
				foreach ( $attributes['items'] as $item ) {
					if ( is_array( $item ) ) {
						$items[] = $this->sanitize_item( $item );
					}
				}
			}
		}

		/**
		 * Filter the ticker items before rendering.
		 *
		 * Documented in the handbook since 1.1.0 but never applied until 2026-09-14. Every
		 * text and link still passes through esc_html() / esc_url() below.
		 *
		 * @param array $items      Items as [ 'text' => string, 'link' => string, 'newTab' => bool ].
		 * @param array $attributes Block attributes.
		 */
		$items = apply_filters( 'wm_newsticker_rendered_items', $items, $attributes );

		if ( empty( $items ) || ! is_array( $items ) ) {
			return '';
		}

		// Sanitize numeric values.
		$speed     = $this->sanitize_number_range(
			isset( $attributes['speed'] ) ? $attributes['speed'] : 50,
			10, 100, 50
		);
		$font_size = $this->sanitize_number_range(
			isset( $attributes['fontSize'] ) ? $attributes['fontSize'] : 14,
			10, 72, 14
		);
		$height    = $this->sanitize_number_range(
			isset( $attributes['height'] ) ? $attributes['height'] : 45,
			20, 200, 45
		);

		// Sanitize booleans.
		$pause_on_hover = isset( $attributes['pauseOnHover'] ) ? (bool) $attributes['pauseOnHover'] : true;
		$is_rtl         = isset( $attributes['isRTL'] ) ? (bool) $attributes['isRTL'] : false;

		// Sanitize controls options.
		$show_controls   = isset( $attributes['showControls'] ) ? (bool) $attributes['showControls'] : false;
		$show_play_pause = isset( $attributes['showPlayPause'] ) ? (bool) $attributes['showPlayPause'] : true;
		$show_prev_next  = isset( $attributes['showPrevNext'] ) ? (bool) $attributes['showPrevNext'] : true;
		$show_progress   = isset( $attributes['showProgress'] ) ? (bool) $attributes['showProgress'] : false;

		$valid_positions   = array( 'left', 'right' );
		$controls_position = isset( $attributes['controlsPosition'] ) && in_array( $attributes['controlsPosition'], $valid_positions, true )
			? $attributes['controlsPosition']
			: 'right';

		// Sanitize animation type and direction.
		$valid_animations = array( 'scroll', 'fade', 'slide', 'typing' );
		$animation_type   = isset( $attributes['animationType'] ) && in_array( $attributes['animationType'], $valid_animations, true )
			? $attributes['animationType']
			: 'scroll';

		$valid_directions = array( 'left', 'right', 'up', 'down' );
		$direction        = isset( $attributes['direction'] ) && in_array( $attributes['direction'], $valid_directions, true )
			? $attributes['direction']
			: 'left';

		// Sanitize colors.
		$bg_color         = $this->sanitize_color( isset( $attributes['backgroundColor'] ) ? $attributes['backgroundColor'] : '#1a1a2e' );
		$text_color       = $this->sanitize_color( isset( $attributes['textColor'] ) ? $attributes['textColor'] : '#ffffff' );
		$label_bg_color   = $this->sanitize_color( isset( $attributes['labelBackgroundColor'] ) ? $attributes['labelBackgroundColor'] : '#e94560' );
		$label_text_color = $this->sanitize_color( isset( $attributes['labelTextColor'] ) ? $attributes['labelTextColor'] : '#ffffff' );

		// Sanitize text fields.
		$label_text = isset( $attributes['labelText'] ) ? sanitize_text_field( $attributes['labelText'] ) : '';
		$separator  = isset( $attributes['separator'] ) ? sanitize_text_field( $attributes['separator'] ) : '•';

		// Limit separator length.
		$separator = mb_substr( $separator, 0, 5 );

		// Sanitize spacing and border attributes.
		$border_radius = $this->sanitize_spacing( isset( $attributes['borderRadius'] ) ? $attributes['borderRadius'] : array() );
		$padding       = $this->sanitize_spacing( isset( $attributes['padding'] ) ? $attributes['padding'] : array() );
		$margin        = $this->sanitize_spacing( isset( $attributes['margin'] ) ? $attributes['margin'] : array() );
		$border_width  = $this->sanitize_css_value( isset( $attributes['borderWidth'] ) ? $attributes['borderWidth'] : '0px' );
		$border_color  = $this->sanitize_color( isset( $attributes['borderColor'] ) ? $attributes['borderColor'] : '#000000' );

		$valid_border_styles = array( 'solid', 'dashed', 'dotted', 'none' );
		$border_style        = isset( $attributes['borderStyle'] ) && in_array( $attributes['borderStyle'], $valid_border_styles, true )
			? $attributes['borderStyle']
			: 'solid';

		$box_shadow = isset( $attributes['boxShadow'] ) ? $this->sanitize_box_shadow( $attributes['boxShadow'] ) : 'none';

		// Generate unique ID.
		$unique_id = 'wm-newsticker-' . wp_unique_id();

		// Calculate animation duration.
		$item_count = count( $items );
		if ( 'scroll' === $animation_type ) {
			$animation_duration = max( 10, $item_count * ( 100 - $speed ) / 5 );
		} else {
			$animation_duration = max( 2, ( 100 - $speed ) / 10 );
		}

		$should_duplicate = ( 'scroll' === $animation_type && $item_count > 2 );

		// Build CSS classes.
		$wrapper_classes   = array( 'wm-newsticker-wrapper' );
		$wrapper_classes[] = 'wm-animation-' . $animation_type;
		$wrapper_classes[] = 'wm-direction-' . $direction;
		if ( $is_rtl ) {
			$wrapper_classes[] = 'wm-rtl';
		}
		if ( $show_controls ) {
			$wrapper_classes[] = 'wm-has-controls';
			$wrapper_classes[] = 'wm-controls-' . $controls_position;
		}

		// Build inline styles.
		$wrapper_styles = array(
			'background-color' => $bg_color,
			'height'           => $height . 'px',
			'border-radius'    => $this->spacing_to_css( $border_radius ),
			'padding'          => $this->spacing_to_css( $padding ),
			'margin'           => $this->spacing_to_css( $margin ),
			'border-width'     => $border_width,
			'border-color'     => $border_color,
			'border-style'     => $border_style,
			'box-shadow'       => $box_shadow,
		);

		$style_string = $this->build_style_string( $wrapper_styles );

		ob_start();
		?>
		<div id="<?php echo esc_attr( $unique_id ); ?>"
			class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
			style="<?php echo esc_attr( $style_string ); ?>"
			role="region"
			aria-roledescription="marquee"
			aria-label="<?php esc_attr_e( 'News ticker', 'wm-newsticker' ); ?>"
			aria-live="off"
			data-pause-on-hover="<?php echo esc_attr( $pause_on_hover ? 'true' : 'false' ); ?>"
			data-animation="<?php echo esc_attr( $animation_type ); ?>"
			data-direction="<?php echo esc_attr( $direction ); ?>"
			data-speed="<?php echo esc_attr( $animation_duration ); ?>"
			data-items="<?php echo esc_attr( $item_count ); ?>"
			data-has-controls="<?php echo esc_attr( $show_controls ? 'true' : 'false' ); ?>">

			<?php if ( ! empty( $label_text ) ) : ?>
			<div class="wm-newsticker-label" style="background-color: <?php echo esc_attr( $label_bg_color ); ?>; color: <?php echo esc_attr( $label_text_color ); ?>; font-size: <?php echo esc_attr( $font_size ); ?>px;">
				<?php echo esc_html( $label_text ); ?>
			</div>
			<?php endif; ?>

			<?php if ( $show_controls && 'left' === $controls_position ) : ?>
				<?php $this->render_controls( $show_play_pause, $show_prev_next, $text_color ); ?>
			<?php endif; ?>

			<div class="wm-newsticker-content">
				<?php if ( $show_progress ) : ?>
					<div class="wm-newsticker-progress">
						<div class="wm-newsticker-progress-bar" style="animation-duration: <?php echo esc_attr( $animation_duration ); ?>s;"></div>
					</div>
				<?php endif; ?>
				<?php if ( 'scroll' === $animation_type ) : ?>
					<div class="<?php echo esc_attr( 'wm-newsticker-track' . ( $should_duplicate ? '' : ' wm-newsticker-no-duplicate' ) ); ?>" style="animation-duration: <?php echo esc_attr( $animation_duration ); ?>s;">
						<?php
						$this->render_items( $items, $separator, $text_color, $font_size, true );

						if ( $should_duplicate ) {
							$this->render_items( $items, $separator, $text_color, $font_size, false );
						}
						?>
					</div>
				<?php else : ?>
					<div class="wm-newsticker-slides" data-current="0">
						<?php foreach ( $items as $index => $item ) : ?>
							<div class="<?php echo esc_attr( 'wm-newsticker-slide' . ( 0 === $index ? ' active' : '' ) ); ?>"
								style="color: <?php echo esc_attr( $text_color ); ?>; font-size: <?php echo esc_attr( $font_size ); ?>px;"
								data-index="<?php echo esc_attr( $index ); ?>">
								<?php if ( ! empty( $item['link'] ) ) : ?>
									<a href="<?php echo esc_url( $item['link'] ); ?>"
										style="color: <?php echo esc_attr( $text_color ); ?>;"
										<?php echo ! empty( $item['newTab'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
										<?php echo esc_html( $item['text'] ); ?>
									</a>
								<?php else : ?>
									<span><?php echo esc_html( $item['text'] ); ?></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $show_controls && 'right' === $controls_position ) : ?>
				<?php $this->render_controls( $show_play_pause, $show_prev_next, $text_color ); ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render ticker controls.
	 *
	 * @param bool   $show_play_pause Whether to show play/pause button.
	 * @param bool   $show_prev_next  Whether to show prev/next buttons.
	 * @param string $text_color      Text color for icons.
	 * @return void
	 */
	private function render_controls( $show_play_pause, $show_prev_next, $text_color ) {
		?>
		<div class="wm-newsticker-controls" style="color: <?php echo esc_attr( $text_color ); ?>;">
			<?php if ( $show_prev_next ) : ?>
				<button type="button" class="wm-control-btn wm-control-prev" aria-label="<?php esc_attr_e( 'Previous', 'wm-newsticker' ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
						<polyline points="15 18 9 12 15 6"></polyline>
					</svg>
				</button>
			<?php endif; ?>
			<?php if ( $show_play_pause ) : ?>
				<button type="button" class="wm-control-btn wm-control-play-pause" aria-label="<?php esc_attr_e( 'Play/Pause', 'wm-newsticker' ); ?>" data-playing="true">
					<svg class="wm-icon-pause" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
						<rect x="6" y="4" width="4" height="16"></rect>
						<rect x="14" y="4" width="4" height="16"></rect>
					</svg>
					<svg class="wm-icon-play" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;" aria-hidden="true" focusable="false">
						<polygon points="5 3 19 12 5 21 5 3"></polygon>
					</svg>
				</button>
			<?php endif; ?>
			<?php if ( $show_prev_next ) : ?>
				<button type="button" class="wm-control-btn wm-control-next" aria-label="<?php esc_attr_e( 'Next', 'wm-newsticker' ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
						<polyline points="9 18 15 12 9 6"></polyline>
					</svg>
				</button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render ticker items.
	 *
	 * @param array  $items     Items to render.
	 * @param string $separator Separator between items.
	 * @param string $text_color Text color.
	 * @param int    $font_size Font size.
	 * @param bool   $is_first  Whether this is the first set of items.
	 * @return void
	 */
	private function render_items( $items, $separator, $text_color, $font_size, $is_first = true ) {
		foreach ( $items as $index => $item ) {
			if ( ! $is_first || $index > 0 ) {
				?>
				<span class="wm-newsticker-separator" style="color: <?php echo esc_attr( $text_color ); ?>; font-size: <?php echo esc_attr( $font_size ); ?>px;">
					<?php echo esc_html( $separator ); ?>
				</span>
				<?php
			}

			if ( ! empty( $item['link'] ) ) {
				$target_attr = ! empty( $item['newTab'] ) ? '_blank' : '_self';
				$rel_attr    = ! empty( $item['newTab'] ) ? 'noopener noreferrer' : '';
				?>
				<a href="<?php echo esc_url( $item['link'] ); ?>"
					class="wm-newsticker-item"
					style="color: <?php echo esc_attr( $text_color ); ?>; font-size: <?php echo esc_attr( $font_size ); ?>px;"
					target="<?php echo esc_attr( $target_attr ); ?>"
					<?php if ( $rel_attr ) : ?>rel="<?php echo esc_attr( $rel_attr ); ?>"<?php endif; ?>>
					<?php echo esc_html( $item['text'] ); ?>
				</a>
				<?php
			} else {
				?>
				<span class="wm-newsticker-item" style="color: <?php echo esc_attr( $text_color ); ?>; font-size: <?php echo esc_attr( $font_size ); ?>px;">
					<?php echo esc_html( $item['text'] ); ?>
				</span>
				<?php
			}
		}
	}

	/**
	 * Enqueue the family sheets, the plugin's admin layer and the handbook script.
	 *
	 * Standalone the screens are toplevel_page_wm-newsticker, wm-newsticker_page_wm-newsticker-docs
	 * and settings_page_wm-newsticker-settings; under the hub the page is dispatched as
	 * wender-media_page_wm-newsticker. index.php carries the dashboard widget. Until 2026-09-14
	 * the plugin enqueued nothing in wp-admin: the handbook was styled by 70 inline attributes.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		$hook_suffix      = (string) $hook_suffix;
		$is_plugin_screen = str_contains( $hook_suffix, 'wm-newsticker' );
		$is_dashboard     = 'index.php' === $hook_suffix;

		if ( ! $is_plugin_screen && ! $is_dashboard ) {
			return;
		}

		// Versioned by file time: a sheet updated under an unchanged plugin version was served
		// from the browser cache (measured on another spoke, 2026-09-14).
		$version = static function ( $relative ) {
			$file = WM_NEWSTICKER_PLUGIN_DIR . $relative;
			return file_exists( $file ) ? (string) filemtime( $file ) : WM_NEWSTICKER_VERSION;
		};

		// Every WM plugin registers the same two handles, so WordPress loads one copy however
		// many plugins are active.
		wp_enqueue_style( 'wm-admin-tokens', WM_NEWSTICKER_PLUGIN_URL . 'assets/css/wm-admin-tokens.css', array(), $version( 'assets/css/wm-admin-tokens.css' ) );
		wp_enqueue_style( 'wm-admin-ui', WM_NEWSTICKER_PLUGIN_URL . 'assets/css/wm-admin-ui.css', array( 'wm-admin-tokens' ), $version( 'assets/css/wm-admin-ui.css' ) );
		wp_enqueue_style( 'wm-newsticker-admin', WM_NEWSTICKER_PLUGIN_URL . 'assets/css/newsticker-admin.css', array( 'wm-admin-ui', 'dashicons' ), $version( 'assets/css/newsticker-admin.css' ) );

		if ( $is_plugin_screen ) {
			// The live preview is the block's real output: its frontend sheet and view script are
			// the handles block.json registered on init.
			wp_enqueue_style( 'wm-newsticker-style' );
			wp_enqueue_script( 'wm-newsticker-view-script' );
			wp_enqueue_script( 'wm-newsticker-admin', WM_NEWSTICKER_PLUGIN_URL . 'assets/js/newsticker-admin.js', array(), $version( 'assets/js/newsticker-admin.js' ), true );
		}
	}

	/**
	 * Family menu icon: a monochrome hub-and-spoke mark, filled shapes only so the WordPress
	 * svg-painter can recolour it with the admin colour scheme. Placeholder until the family
	 * icon is decided (hackaton plan D12).
	 *
	 * @return string data: URI.
	 */
	public static function family_menu_icon() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="10" r="2.6" fill="black"/><circle cx="10" cy="3" r="1.8" fill="black"/><circle cx="16.1" cy="13.5" r="1.8" fill="black"/><circle cx="3.9" cy="13.5" r="1.8" fill="black"/><rect x="9.3" y="4.5" width="1.4" height="3.4" fill="black"/><rect x="9.3" y="12.1" width="1.4" height="3.4" fill="black" transform="rotate(-60 10 10)"/><rect x="9.3" y="12.1" width="1.4" height="3.4" fill="black" transform="rotate(60 10 10)"/></svg>';
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Family header shared with the other Wender Media plugins: eyebrow badge with a dashicon,
	 * title, subtitle, and the Wender Media badge carrying the hub/standalone state.
	 *
	 * @param string $icon     Dashicon class for the eyebrow.
	 * @param string $eyebrow  Eyebrow text (translated).
	 * @param string $title    Page title (translated).
	 * @param string $subtitle Subtitle (translated).
	 * @return void
	 */
	private function render_page_header( $icon, $eyebrow, $title, $subtitle ) {
		?>
		<header class="wm-admin-header wm-newsticker-admin-header">
			<div class="wm-header-title">
				<span class="wm-badge wm-badge-cyan">
					<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
					<?php echo esc_html( $eyebrow ); ?>
				</span>
				<h1><?php echo esc_html( $title ); ?></h1>
				<p class="wm-subtitle"><?php echo esc_html( $subtitle ); ?></p>
			</div>
			<div class="wm-header-badges">
				<span class="wm-badge-family" title="<?php esc_attr_e( 'Part of the Wender Media plugin family', 'wm-newsticker' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="4" r="2"/><circle cx="19" cy="16" r="2"/><circle cx="5" cy="16" r="2"/><path d="M12 7v2M13.9 13.2l3.4 2M10.1 13.2l-3.4 2"/></svg>
					<?php esc_html_e( 'Wender Media', 'wm-newsticker' ); ?>
					<span class="wm-badge-family-state"><?php echo defined( 'WM_SUITE_HUB_VERSION' ) ? esc_html__( 'Hub', 'wm-newsticker' ) : esc_html__( 'Standalone', 'wm-newsticker' ); ?></span>
				</span>
			</div>
		</header>
		<?php
	}

	/**
	 * Attributes of the handbook's live preview: three manual headlines through the real
	 * render_block(), so the page shows the block as the frontend renders it.
	 *
	 * @return array Block attributes.
	 */
	private function demo_preview_attributes() {
		return array(
			'contentSource' => 'manual',
			'labelText'     => __( 'LIVE', 'wm-newsticker' ),
			'speed'         => 40,
			'showControls'  => true,
			'showProgress'  => true,
			'items'         => array(
				array( 'text' => __( 'Die Laufbewegung ist eine CSS-Animation (transform)', 'wm-newsticker' ), 'link' => '', 'newTab' => false ),
				array( 'text' => __( 'Mit der Maus über dem Ticker hält die Bewegung an', 'wm-newsticker' ), 'link' => '', 'newTab' => false ),
				array( 'text' => __( 'prefers-reduced-motion stoppt die Animation', 'wm-newsticker' ), 'link' => '', 'newTab' => false ),
				array( 'text' => __( 'Dynamische Beiträge kommen 5 Minuten aus dem Transient-Cache', 'wm-newsticker' ), 'link' => '', 'newTab' => false ),
			),
		);
	}

	/**
	 * Counts the widget shows: pages carrying the block, demo posts, cached ticker queries.
	 *
	 * Cached 5 minutes under the plugin's transient prefix, so flush_transient_cache() on
	 * save_post / deleted_post invalidates it with the query cache.
	 *
	 * @return array{in_use:int,demo:int,cached:int}
	 */
	private function get_widget_stats() {
		$cached = get_transient( 'wm_newsticker_widget_stats' );
		if ( is_array( $cached ) && isset( $cached['in_use'], $cached['demo'], $cached['cached'] ) ) {
			return $cached;
		}

		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- three counts, cached in the transient above.
		$in_use = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE %s",
				'%' . $wpdb->esc_like( '<!-- wp:wm/newsticker' ) . '%'
			)
		);
		$demo = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_is_wm_newsticker_demo'"
		);
		$cached_queries = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_wm\_newsticker\_%' AND option_name NOT LIKE '%widget\_stats'"
		);
		// phpcs:enable

		$stats = array(
			'in_use' => $in_use,
			'demo'   => $demo,
			'cached' => $cached_queries,
		);
		set_transient( 'wm_newsticker_widget_stats', $stats, 5 * MINUTE_IN_SECONDS );

		return $stats;
	}

	/**
	 * Register Admin Dashboard Widget
	 */
	public function register_dashboard_widget(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'wm_newsticker_widget',
			__( 'WM Newsticker', 'wm-newsticker' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render Admin Dashboard Widget
	 *
	 * Counted figures only: until 2026-09-14 the three tiles printed a fixed "6 Stil-Presets",
	 * "60 FPS" and "A11Y" whatever the site held, and the banner always read "aktiv".
	 */
	public function render_dashboard_widget(): void {
		$stats      = $this->get_widget_stats();
		$registered = class_exists( 'WP_Block_Type_Registry' ) && WP_Block_Type_Registry::get_instance()->is_registered( 'wm/newsticker' );
		?>
		<div class="wm-dash-widget-box wm-newsticker-dash-widget" data-wm-theme="<?php echo esc_attr( function_exists( 'wm_get_suite_theme' ) ? wm_get_suite_theme() : 'cyber-events' ); ?>">
			<div class="wm-dash-banner <?php echo $registered ? 'is-valid' : 'is-warning'; ?>">
				<span class="wm-dash-banner-icon"><span class="dashicons dashicons-megaphone" aria-hidden="true"></span></span>
				<div>
					<div class="wm-dash-banner-title">
						<?php
						echo $registered
							? esc_html__( 'Block wm/newsticker registriert', 'wm-newsticker' )
							: esc_html__( 'Block wm/newsticker ist nicht registriert', 'wm-newsticker' );
						?>
					</div>
					<div class="wm-dash-banner-sub"><?php esc_html_e( 'Pause bei Hover, keine Bewegung bei prefers-reduced-motion', 'wm-newsticker' ); ?></div>
				</div>
			</div>

			<div class="wm-dash-kpi-grid">
				<div class="wm-dash-kpi-card">
					<div class="wm-dash-kpi-val <?php echo $stats['in_use'] > 0 ? 'is-on' : ''; ?>"><?php echo esc_html( (string) $stats['in_use'] ); ?></div>
					<div class="wm-dash-kpi-lbl"><?php esc_html_e( 'Seiten mit Ticker', 'wm-newsticker' ); ?></div>
				</div>
				<div class="wm-dash-kpi-card">
					<div class="wm-dash-kpi-val"><?php echo esc_html( (string) $stats['demo'] ); ?></div>
					<div class="wm-dash-kpi-lbl"><?php esc_html_e( 'Demo-Beiträge', 'wm-newsticker' ); ?></div>
				</div>
				<div class="wm-dash-kpi-card">
					<div class="wm-dash-kpi-val"><?php echo esc_html( (string) $stats['cached'] ); ?></div>
					<div class="wm-dash-kpi-lbl"><?php esc_html_e( 'Cache-Einträge', 'wm-newsticker' ); ?></div>
				</div>
			</div>

			<div class="wm-dash-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wm-newsticker' ) ); ?>" class="wm-dash-btn">
					<span><?php esc_html_e( 'Showcase & Handbuch öffnen', 'wm-newsticker' ); ?></span>
					<span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Register Admin Top-Level Menu, Submenus & Options Menu
	 */
	public function register_admin_menu() {
		// Top-Level Admin Menu
		add_menu_page(
			__( 'WM Newsticker', 'wm-newsticker' ),
			__( 'WM Newsticker', 'wm-newsticker' ),
			'manage_options',
			'wm-newsticker',
			array( $this, 'render_admin_docs_page' ),
			self::family_menu_icon(),
			29
		);

		// Submenu 1: Übersicht & Live-Vorschau
		add_submenu_page(
			'wm-newsticker',
			__( 'WM Newsticker — Übersicht & Showcase', 'wm-newsticker' ),
			__( 'Übersicht & Showcase', 'wm-newsticker' ),
			'manage_options',
			'wm-newsticker',
			array( $this, 'render_admin_docs_page' )
		);

		// Submenu 2: In-Plugin Handbuch
		add_submenu_page(
			'wm-newsticker',
			__( 'WM Newsticker — Handbuch & Dokumentation', 'wm-newsticker' ),
			__( 'Handbuch', 'wm-newsticker' ),
			'manage_options',
			'wm-newsticker-docs',
			array( $this, 'render_admin_docs_page' )
		);

		// Options Page under "Einstellungen" (Backwards compatibility)
		add_options_page(
			__( 'WM Newsticker Handbuch & Konfiguration', 'wm-newsticker' ),
			__( 'WM Newsticker', 'wm-newsticker' ),
			'manage_options',
			'wm-newsticker-settings',
			array( $this, 'render_admin_docs_page' )
		);
	}

	/**
	 * Add WordPress Native Contextual Help Tabs
	 */
	public function add_contextual_help() {
		$screen = get_current_screen();
		if ( ! $screen || ! str_contains( $screen->id, 'wm-newsticker' ) ) {
			return;
		}

		$screen->add_help_tab( array(
			'id'      => 'wm_newsticker_help_block',
			'title'   => __( 'Gutenberg Block Integration', 'wm-newsticker' ),
			'content' => '<p><strong>' . esc_html__( 'WM Newsticker', 'wm-newsticker' ) . '</strong> ' . esc_html__( 'ist ein nativer Gutenberg-Block. Fügen Sie ihn in jedem Beitrag oder jeder Seite über den Gutenberg-Inserter unter der Kategorie "Widgets" oder durch Eintippen von "/newsticker" ein.', 'wm-newsticker' ) . '</p>',
		) );

		$screen->add_help_tab( array(
			'id'      => 'wm_newsticker_help_a11y',
			'title'   => __( 'Barrierefreiheit', 'wm-newsticker' ),
			'content' => '<p>' . esc_html__( 'Die Bewegung hält bei Hover an (Blockeinstellung) und läuft nicht, wenn im System "Bewegung reduzieren" (prefers-reduced-motion) aktiv ist. Mit eingeblendeter Steuerung lässt sich der Ticker per Tastatur anhalten und weiterschalten. Eine Prüfung gegen BFSG oder WCAG wurde nicht durchgeführt.', 'wm-newsticker' ) . '</p>',
		) );

		$screen->add_help_tab( array(
			'id'      => 'wm_newsticker_help_caching',
			'title'   => __( 'Transient-Cache', 'wm-newsticker' ),
			'content' => '<p>' . esc_html__( 'Dynamische Beiträge werden für 5 Minuten im WordPress Transient Cache zwischengespeichert. Bei jeder Beitrags-Aktualisierung (save_post) oder Löschung wird der Cache automatisch invalidiert.', 'wm-newsticker' ) . '</p>',
		) );

		$screen->set_help_sidebar(
			'<p><strong>' . esc_html__( 'Block-Dokumentation:', 'wm-newsticker' ) . '</strong></p>' .
			'<p>' . esc_html__( 'Entwickelt von Wender Media.', 'wm-newsticker' ) . '</p>'
		);
	}

	/**
	 * Handle admin POST actions (Demodaten, Reset)
	 */
	public function handle_admin_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle Seed Demo Data
		if ( isset( $_POST['wm_newsticker_seed_demo'] ) && check_admin_referer( 'wm_newsticker_demo_action', 'wm_newsticker_demo_nonce' ) ) {
			$res = self::seed_demo_data();
			wp_safe_redirect( add_query_arg( array( 'page' => 'wm-newsticker', 'demo_seeded' => $res['posts'] ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// Handle Delete Demo Data
		if ( isset( $_POST['wm_newsticker_delete_demo'] ) && check_admin_referer( 'wm_newsticker_demo_action', 'wm_newsticker_demo_nonce' ) ) {
			$deleted = self::delete_demo_data();
			wp_safe_redirect( add_query_arg( array( 'page' => 'wm-newsticker', 'demo_deleted' => $deleted ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// Handle Factory Reset
		if ( isset( $_POST['wm_newsticker_factory_reset'] ) && check_admin_referer( 'wm_newsticker_reset_action', 'wm_newsticker_reset_nonce' ) ) {
			self::reset_to_factory_defaults();
			wp_safe_redirect( add_query_arg( array( 'page' => 'wm-newsticker', 'factory_reset' => '1' ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	/**
	 * Seed realistic Demo Posts and a dedicated Gutenberg Demo Showcase Page
	 *
	 * @return array Counts of seeded items
	 */
	public static function seed_demo_data() {
		// Invented example headlines about no real organisation, event, market or law. Until 2026-09-15 the
		// seed published news about the Cyberagentur, a conference with "1,200 experts", new BFSG audit rules
		// and the DAX, none of it true.
		$demo_posts = array(
			array(
				'title'   => 'Beispielmeldung: Der Ticker zeigt die neuesten Beiträge dieser Website [Demo]',
				'content' => 'Dieser Beitrag wurde vom Plugin WM Newsticker als Beispielinhalt angelegt. Er lässt sich im Handbuch des Plugins wieder entfernen.',
			),
			array(
				'title'   => 'Beispielmeldung: Überschriften lassen sich nach Kategorie oder Schlagwort filtern [Demo]',
				'content' => 'Beispielinhalt des Plugins WM Newsticker.',
			),
			array(
				'title'   => 'Beispielmeldung: Laufrichtung, Tempo und Farben stellen Sie im Block ein [Demo]',
				'content' => 'Beispielinhalt des Plugins WM Newsticker.',
			),
			array(
				'title'   => 'Beispielmeldung: Mit Steuerung lässt sich der Ticker anhalten und weiterschalten [Demo]',
				'content' => 'Beispielinhalt des Plugins WM Newsticker.',
			),
		);

		$seeded_posts = 0;
		foreach ( $demo_posts as $post_data ) {
			$existing = get_posts( array(
				'title'          => $post_data['title'],
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => 1,
			) );
			if ( empty( $existing ) ) {
				$post_id = wp_insert_post( array(
					'post_title'   => $post_data['title'],
					'post_content' => $post_data['content'],
					'post_status'  => 'publish',
					'post_type'    => 'post',
				) );

				if ( $post_id && ! is_wp_error( $post_id ) ) {
					update_post_meta( $post_id, '_is_wm_newsticker_demo', 1 );
					$seeded_posts++;
				}
			}
		}

		// Create Demo Showcase Page
		$demo_page_title = 'WM Newsticker — Ticker-Beispiele [Demo]';
		$existing_page   = get_posts( array(
			'title'          => $demo_page_title,
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 1,
		) );
		if ( empty( $existing_page ) ) {
			// The block reads contentSource and items; the "mode" attribute used here until 2026-09-15 does not
			// exist, so both tickers rendered nothing.
			$page_content = '<!-- wp:heading --><h2>Ticker-Beispiele</h2><!-- /wp:heading -->' .
				'<!-- wp:paragraph --><p>Zwei Konfigurationen des Blocks WM Newsticker: neueste Beiträge als Laufband und manuelle Meldungen mit Überblendung und Steuerung.</p><!-- /wp:paragraph -->' .
				'<!-- wp:wm/newsticker {"contentSource":"posts","postType":"post","postsCount":5,"speed":25,"labelText":"Neu","separator":"|"} /-->' .
				'<!-- wp:spacer {"height":"30px"} --><div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer -->' .
				'<!-- wp:wm/newsticker {"contentSource":"manual","animationType":"fade","showControls":true,"speed":35,"items":[{"text":"Erste manuelle Meldung","link":"","newTab":false},{"text":"Zweite manuelle Meldung","link":"","newTab":false},{"text":"Dritte manuelle Meldung","link":"","newTab":false}]} /-->';

			$page_id = wp_insert_post( array(
				'post_title'   => $demo_page_title,
				'post_content' => $page_content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			) );

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				update_post_meta( $page_id, '_is_wm_newsticker_demo', 1 );
			}
		}

		self::flush_all_transients();

		return array(
			'posts' => $seeded_posts,
			'pages' => $existing_page ? 0 : 1,
		);
	}

	/**
	 * Delete all generated demo posts and showcase pages
	 *
	 * @return int Number of deleted demo items
	 */
	public static function delete_demo_data() {
		global $wpdb;

		// Only what the seed marked. Until 2026-09-15 this also force-deleted every post or page whose title
		// contains "[Demo]", including content other plugins or editors created (measured on a local install: every
		// seeded item carries the marker).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$demo_post_ids = $wpdb->get_col(
			"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_is_wm_newsticker_demo'"
		);

		$deleted_count = 0;
		if ( ! empty( $demo_post_ids ) ) {
			foreach ( $demo_post_ids as $pid ) {
				$res = wp_delete_post( (int) $pid, true );
				if ( $res ) {
					$deleted_count++;
				}
			}
		}

		self::flush_all_transients();

		return $deleted_count;
	}

	/**
	 * Atomic Factory Reset (Auf Werkseinstellungen zurücksetzen)
	 */
	public static function reset_to_factory_defaults() {
		self::delete_demo_data();
		self::flush_all_transients();
		delete_option( 'wm_newsticker_settings' );
	}

	/**
	 * Flush all transient query caches for newsticker
	 */
	public static function flush_all_transients() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wm_newsticker_%' OR option_name LIKE '_transient_timeout_wm_newsticker_%'" );
	}

	/**
	 * Render In-Plugin Handbook & Interactive Guide Page
	 */
	public function render_admin_docs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unzureichende Berechtigungen.', 'wm-newsticker' ) );
		}
		?>
		<div class="wrap wm-admin wm-newsticker-admin-wrap">
			<?php
			$this->render_page_header(
				'dashicons-book',
				__( 'Handbuch', 'wm-newsticker' ),
				__( 'WM Newsticker — Handbuch', 'wm-newsticker' ),
				__( 'Gutenberg-Block für Laufband, Überblendung, Slide und Schreibmaschine, mit manuellen Meldungen oder den neuesten Beiträgen.', 'wm-newsticker' )
			);
			?>

			<?php if ( isset( $_GET['demo_seeded'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						/* translators: %d: number of demo headlines created */
						$seeded_text = __( 'Erfolg: %d Demo-Meldungen und eine interaktive Demo-Hub-Seite wurden erfolgreich erstellt.', 'wm-newsticker' );
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only counter from the redirect after a nonce-checked action.
						echo esc_html( sprintf( $seeded_text, absint( $_GET['demo_seeded'] ) ) );
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['demo_deleted'] ) ) : ?>
				<div class="notice notice-info is-dismissible">
					<p>
						<?php
						/* translators: %d: number of demo posts and pages deleted */
						$deleted_text = __( '%d Demo-Beiträge und Showcase-Seiten wurden gelöscht. Der Transient-Cache wurde geleert.', 'wm-newsticker' );
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only counter from the redirect after a nonce-checked action.
						echo esc_html( sprintf( $deleted_text, absint( $_GET['demo_deleted'] ) ) );
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['factory_reset'] ) ) : ?>
				<div class="notice notice-warning is-dismissible">
					<p><strong><?php esc_html_e( 'Werkseinstellungen wiederhergestellt:', 'wm-newsticker' ); ?></strong> <?php esc_html_e( 'Alle Demo-Daten und Caches wurden restlos bereinigt.', 'wm-newsticker' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Live Preview / Showcase: the block's real output, not a mock-up -->
			<section id="wm-sec-preview" class="wm-card wm-newsticker-section">
				<h2 class="wm-newsticker-card-title"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><?php esc_html_e( 'Live-Vorschau & Ticker-Test', 'wm-newsticker' ); ?></h2>
				<p class="wm-field-desc">
					<?php esc_html_e( 'Der Block, wie er im Frontend rendert: echte Ausgabe von render_block() mit vier manuellen Meldungen. Hover hält die Bewegung an, die Steuerung ist per Tastatur bedienbar.', 'wm-newsticker' ); ?>
				</p>
				<div class="wm-newsticker-preview">
					<?php echo $this->render_block( $this->demo_preview_attributes() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_block() escapes every token it prints. ?>
				</div>
			</section>

			<div class="wm-grid-2 wm-newsticker-grid">
				<!-- Card 1: Block Attributes -->
				<section class="wm-card">
					<h2 class="wm-newsticker-card-title"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><?php esc_html_e( '1. Block-Konfiguration (Inspector Controls)', 'wm-newsticker' ); ?></h2>
					<ul class="wm-newsticker-list">
						<li><strong>Inhaltsquelle:</strong> manuell eingetippte Meldungen oder die neuesten Beiträge eines öffentlichen Beitragstyps (1 bis 20, optional nach Kategorie oder Schlagwort).</li>
						<li><strong>Animation & Tempo:</strong> Laufband, Überblendung, Slide oder Schreibmaschine; Tempo-Regler 10 bis 100; Richtung links, rechts, oben, unten.</li>
						<li><strong>Pause & Steuerung:</strong> Pause bei Hover (abschaltbar); optional Start/Pause, Zurück/Weiter und Fortschrittsbalken.</li>
						<li><strong>Typografie & Farben:</strong> Hintergrund-, Text-, Label- und Rahmenfarbe, Schriftgröße 10 bis 72 px, Höhe 20 bis 200 px, Trennzeichen.</li>
					</ul>
				</section>

				<!-- Card 2: Accessibility -->
				<section class="wm-card">
					<h2 class="wm-newsticker-card-title"><span class="dashicons dashicons-universal-access" aria-hidden="true"></span><?php esc_html_e( '2. Barrierefreiheit', 'wm-newsticker' ); ?></h2>
					<ul class="wm-newsticker-list">
						<li><strong>prefers-reduced-motion:</strong> Laufband und Überblendung bewegen sich nicht, wenn im System "Bewegung reduzieren" aktiv ist.</li>
						<li><strong>Tastatur:</strong> Die Steuerung besteht aus echten Buttons mit Beschriftung und sichtbarem Fokusrahmen. Tastaturfokus auf einem Link hält die Bewegung nicht an; wer die Bewegung per Tastatur stoppen soll, braucht die eingeblendete Steuerung.</li>
						<li><strong>ARIA Semantik:</strong> Landmark mit <code>role="region"</code>, <code>aria-roledescription="marquee"</code> und <code>aria-label="News ticker"</code>; <code>aria-live="off"</code>, damit der Screenreader nicht jede Bewegung ansagt.</li>
					</ul>
				</section>
			</div>

			<!-- Card 3: Developer Code Snippets -->
			<section class="wm-card wm-newsticker-section">
				<h2 class="wm-newsticker-card-title"><span class="dashicons dashicons-editor-code" aria-hidden="true"></span><?php esc_html_e( '3. PHP Hook & Filter API für Entwickler', 'wm-newsticker' ); ?></h2>
				<p class="wm-field-desc">
					<?php esc_html_e( 'Passen Sie die Ticker-Einträge programmatisch in Ihrem Theme oder Plugin an. Der Filter läuft in render_block() vor dem Escaping; Text und Link werden danach weiterhin escaped.', 'wm-newsticker' ); ?>
				</p>
				<pre class="wm-code-block"><code>/**
 * Ticker-Einträge vor dem Rendering filtern
 */
add_filter( 'wm_newsticker_rendered_items', function( $items, $attributes ) {
    // Beispiel: vor jedem Titel ein Eilmeldung-Präfix einfügen
    foreach ( $items as &amp;$item ) {
        $item['text'] = 'Eilmeldung: ' . $item['text'];
    }
    return $items;
}, 10, 2 );</code></pre>
			</section>

			<!-- Card 4: Demodaten-Management & Factory Reset -->
			<section id="wm-sec-demo" class="wm-card wm-newsticker-section">
				<h2 class="wm-newsticker-card-title"><span class="dashicons dashicons-database-import" aria-hidden="true"></span><?php esc_html_e( '4. Demodaten-Management & Werkseinstellungen (Reset)', 'wm-newsticker' ); ?></h2>
				<p class="wm-field-desc">
					<?php esc_html_e( 'Generieren Sie Beispielmeldungen und eine fertige Showcase-Gutenberg-Seite zum sofortigen Testen oder bereinigen Sie alle Caches und Beispieldaten.', 'wm-newsticker' ); ?>
				</p>

				<div class="wm-newsticker-actions-row">
					<!-- Import Demo -->
					<form method="post">
						<?php wp_nonce_field( 'wm_newsticker_demo_action', 'wm_newsticker_demo_nonce' ); ?>
						<button type="submit" name="wm_newsticker_seed_demo" class="button button-secondary">
							<span class="dashicons dashicons-download" aria-hidden="true"></span>
							<?php esc_html_e( 'Demodaten & Showcase-Seite importieren', 'wm-newsticker' ); ?>
						</button>
					</form>

					<!-- Delete Demo -->
					<form method="post" data-wm-confirm="<?php echo esc_attr__( 'Möchten Sie alle generierten Demo-Meldungen und Showcase-Seiten löschen?', 'wm-newsticker' ); ?>">
						<?php wp_nonce_field( 'wm_newsticker_demo_action', 'wm_newsticker_demo_nonce' ); ?>
						<button type="submit" name="wm_newsticker_delete_demo" class="button button-secondary wm-newsticker-btn-danger-text">
							<span class="dashicons dashicons-trash" aria-hidden="true"></span>
							<?php esc_html_e( 'Demodaten bereinigen', 'wm-newsticker' ); ?>
						</button>
					</form>
				</div>

				<hr class="wm-newsticker-divider" />

				<!-- Danger Zone: Factory Reset -->
				<div class="wm-newsticker-danger-zone">
					<h3>
						<span class="dashicons dashicons-warning" aria-hidden="true"></span>
						<?php esc_html_e( 'Gefahrenbereich: Auf Werkseinstellungen zurücksetzen', 'wm-newsticker' ); ?>
					</h3>
					<p>
						<?php esc_html_e( 'Löscht die vom Plugin angelegten Demo-Beiträge und die Beispielseite und leert den Ticker-Cache. Das Plugin speichert keine eigenen Einstellungen; die Blöcke in Ihren Seiten behalten ihre Attribute.', 'wm-newsticker' ); ?>
					</p>
					<form method="post" data-wm-confirm="<?php echo esc_attr__( 'WARNUNG: Sind Sie sicher? Dies leert alle Ticker-Caches und setzt das Plugin zurück!', 'wm-newsticker' ); ?>">
						<?php wp_nonce_field( 'wm_newsticker_reset_action', 'wm_newsticker_reset_nonce' ); ?>
						<button type="submit" name="wm_newsticker_factory_reset" class="wm-btn wm-btn-danger">
							<span class="dashicons dashicons-undo" aria-hidden="true"></span>
							<?php esc_html_e( 'Werkseinstellungen wiederherstellen (Factory Reset)', 'wm-newsticker' ); ?>
						</button>
					</form>
				</div>
			</section>

			<!-- Card 5: Gotchas & Performance -->
			<section class="wm-card">
				<h2 class="wm-newsticker-card-title"><span class="dashicons dashicons-info" aria-hidden="true"></span><?php esc_html_e( '5. Häufige Fragen & Troubleshooting', 'wm-newsticker' ); ?></h2>
				<ul class="wm-newsticker-list">
					<li><strong>Warum bewegt sich der Ticker im Browser nicht?</strong> Prüfen Sie, ob in Ihrem Betriebssystem (macOS / Windows) die Option "Bewegung reduzieren" (Reduced Motion) aktiviert ist.</li>
					<li><strong>Wie leere ich den Beitrags-Cache?</strong> Speichern Sie einfach einen beliebigen Beitrag im Backend neu — der Transient-Cache leert sich automatisch.</li>
				</ul>
			</section>
		</div>
		<?php
	}
}

// Initialize the plugin.
WM_Newsticker::get_instance();

// Require WP-CLI Command Integration
require_once WM_NEWSTICKER_PLUGIN_DIR . 'includes/class-newsticker-cli.php';

// Register Plugin List Action Links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
	$handbook_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wm-newsticker' ) ) . '">' . esc_html__( 'Handbuch & Vorschau', 'wm-newsticker' ) . '</a>';
	array_unshift( $links, $handbook_link );
	return $links;
} );

// Register Plugin Row Meta Links
add_filter( 'plugin_row_meta', function( $links, $file ) {
	if ( plugin_basename( __FILE__ ) === $file ) {
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wm-newsticker' ) ) . '">' . esc_html__( 'Dokumentation', 'wm-newsticker' ) . '</a>';
		$links[] = '<a href="' . esc_url( 'https://github.com/arnoldwender/wm-newsticker' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'GitHub Repository', 'wm-newsticker' ) . '</a>';
	}
	return $links;
}, 10, 2 );
