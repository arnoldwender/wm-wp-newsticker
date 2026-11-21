<?php
/**
 * Gutenberg Block Bindings, GenerateBlocks 2.x Dynamic Tags, Block Patterns & WP 6.9 Abilities
 *
 * Bridges WM Newsticker with the WordPress Block Editor (Block Bindings API & Block Patterns),
 * GeneratePress / GenerateBlocks 2.x (GenerateBlocks_Register_Dynamic_Tag & gbp-section patterns),
 * and WordPress 6.9+ Abilities API for third-party plugins and automation workflows.
 *
 * @package WM_Newsticker
 * @since   1.5.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ecosystem & Editor Integrations for WM Newsticker.
 */
final class WM_Newsticker_Integrations {

	/**
	 * Register editor, dynamic tag, pattern, and ability hooks.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_block_bindings' ), 20 );
		add_action( 'init', array( __CLASS__, 'register_generateblocks_tags' ), 20 );
		add_action( 'init', array( __CLASS__, 'register_patterns' ), 25 );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Register WordPress 6.5+ Block Bindings source (`wm-newsticker/feed`).
	 *
	 * Allows core/paragraph, core/heading, and core/button blocks to bind directly
	 * to live ticker headlines (`headline`, `url`).
	 */
	public static function register_block_bindings(): void {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			'wm-newsticker/feed',
			array(
				'label'              => __( 'WM Newsticker Feed', 'wm-newsticker' ),
				'get_value_callback' => array( __CLASS__, 'resolve_binding_value' ),
				'uses_context'       => array( 'postId', 'postType' ),
			)
		);
	}

	/**
	 * Resolve a Block Bindings value from the cached ticker feed.
	 *
	 * @param array  $source_args Binding arguments (`key`, `post_type`, `offset`).
	 * @param object $block       WP_Block instance.
	 * @return string|null Escaped text/URL or null when unavailable.
	 */
	public static function resolve_binding_value( array $source_args, $block = null ): ?string {
		$field     = isset( $source_args['key'] ) ? sanitize_key( (string) $source_args['key'] ) : 'headline';
		$post_type = isset( $source_args['post_type'] ) ? sanitize_key( (string) $source_args['post_type'] ) : 'post';
		$offset    = isset( $source_args['offset'] ) ? max( 0, min( 19, absint( $source_args['offset'] ) ) ) : 0;

		$items = WM_Newsticker::get_instance()->get_latest_items(
			array(
				'postType'   => $post_type,
				'postsCount' => $offset + 1,
			)
		);

		if ( ! isset( $items[ $offset ] ) || ! is_array( $items[ $offset ] ) ) {
			return '';
		}

		$item = $items[ $offset ];
		if ( 'url' === $field ) {
			return isset( $item['link'] ) ? esc_url( (string) $item['link'] ) : '';
		}

		return isset( $item['text'] ) ? esc_html( (string) $item['text'] ) : '';
	}

	/**
	 * Register GenerateBlocks 2.x Dynamic Tags (`{{wm_newsticker_headline}}`) when GenerateBlocks 2.0+ is active.
	 */
	public static function register_generateblocks_tags(): void {
		if ( ! class_exists( 'GenerateBlocks_Register_Dynamic_Tag' ) ) {
			return;
		}

		new GenerateBlocks_Register_Dynamic_Tag(
			array(
				'title'       => __( 'WM Newsticker Headline', 'wm-newsticker' ),
				'tag'         => 'wm_newsticker_headline',
				'type'        => 'wm-suite',
				'supports'    => array( 'source' ),
				'description' => __( 'Outputs a cached headline from WM Newsticker inside any GenerateBlocks Text or Element block.', 'wm-newsticker' ),
				'options'     => array(
					'post_type' => array(
						'type'    => 'text',
						'label'   => __( 'Post type slug', 'wm-newsticker' ),
						'default' => 'post',
					),
					'offset'    => array(
						'type'    => 'number',
						'label'   => __( 'Headline index (0 = newest)', 'wm-newsticker' ),
						'default' => 0,
					),
					'field'     => array(
						'type'    => 'select',
						'label'   => __( 'Field', 'wm-newsticker' ),
						'default' => 'headline',
						'options' => array(
							array(
								'value' => 'headline',
								'label' => __( 'Headline text', 'wm-newsticker' ),
							),
							array(
								'value' => 'url',
								'label' => __( 'Permalink URL', 'wm-newsticker' ),
							),
						),
					),
				),
				'return'      => array( __CLASS__, 'render_generateblocks_tag' ),
			)
		);
	}

	/**
	 * Callback for GenerateBlocks 2.x Dynamic Tag rendering.
	 *
	 * @param array $options  Tag options passed by GenerateBlocks.
	 * @param array $block    Parsed block array.
	 * @param mixed $instance WP_Block instance.
	 * @return string Rendered dynamic value.
	 */
	public static function render_generateblocks_tag( array $options, array $block = array(), $instance = null ): string {
		$resolved = self::resolve_binding_value(
			array(
				'key'       => isset( $options['field'] ) ? (string) $options['field'] : 'headline',
				'post_type' => isset( $options['post_type'] ) ? (string) $options['post_type'] : 'post',
				'offset'    => isset( $options['offset'] ) ? (int) $options['offset'] : 0,
			),
			is_object( $instance ) ? $instance : null
		);

		return null !== $resolved ? $resolved : '';
	}

	/**
	 * Register Block Patterns for Core Gutenberg and GenerateBlocks 2.x (`gbp-section` + `gbp-section__inner`).
	 */
	public static function register_patterns(): void {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category(
				'wm-newsticker',
				array(
					'label' => __( 'WM Newsticker', 'wm-newsticker' ),
				)
			);
		}

		register_block_pattern(
			'wm-newsticker/breaking-news-bar',
			array(
				'title'       => __( 'Breaking News Ticker Bar', 'wm-newsticker' ),
				'description' => __( 'Full-width news ticker bar with accessible controls and recent posts.', 'wm-newsticker' ),
				'categories'  => array( 'wm-newsticker' ),
				'content'     => '<!-- wp:wm/newsticker {"align":"full","contentSource":"posts","postsCount":5,"labelText":"' . esc_attr__( 'NEWS', 'wm-newsticker' ) . '","labelBackgroundColor":"#b81d38","backgroundColor":"#1a1a2e","textColor":"#ffffff","showControls":true,"height":48} /-->',
			)
		);

		if ( defined( 'GENERATEBLOCKS_VERSION' ) ) {
			register_block_pattern(
				'wm-newsticker/gb-breaking-section',
				array(
					'title'       => __( 'GenerateBlocks Section with News Ticker', 'wm-newsticker' ),
					'description' => __( 'Two-layer GenerateBlocks section (gbp-section + gbp-section__inner) embedding WM Newsticker with GeneratePress global colour tokens.', 'wm-newsticker' ),
					'categories'  => array( 'wm-newsticker' ),
					'content'     => '<!-- wp:generateblocks/element {"tagName":"section","className":"gbp-section","styles":{"paddingTop":"var(--space-md, 1.5rem)","paddingBottom":"var(--space-md, 1.5rem)","backgroundColor":"var(--base-3, #ffffff)"}} -->'
						. '<section class="gbp-section">'
						. '<!-- wp:generateblocks/element {"tagName":"div","className":"gbp-section__inner","styles":{"maxWidth":"var(--gb-container-width, 80rem)","marginLeft":"auto","marginRight":"auto","paddingLeft":"var(--space-md, 1.5rem)","paddingRight":"var(--space-md, 1.5rem)"}} -->'
						. '<div class="gbp-section__inner">'
						. '<!-- wp:wm/newsticker {"contentSource":"posts","postsCount":5,"labelText":"' . esc_attr__( 'AKTUELL', 'wm-newsticker' ) . '","labelBackgroundColor":"var(--accent)","backgroundColor":"var(--contrast)","textColor":"var(--base-3)","showControls":true,"height":48} /-->'
						. '</div>'
						. '<!-- /wp:generateblocks/element -->'
						. '</section>'
						. '<!-- /wp:generateblocks/element -->',
				)
			);
		}
	}

	/**
	 * Register declarative WordPress 6.9+ Ability (`wm-newsticker/get-headlines`) for third-party plugins & AI/MCP tools.
	 */
	public static function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'wm-newsticker/get-headlines',
			array(
				'label'               => __( 'Get Newsticker Headlines', 'wm-newsticker' ),
				'description'         => __( 'Returns sanitized, cached public headlines from WM Newsticker for a public post type.', 'wm-newsticker' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_type' => array(
							'type'    => 'string',
							'default' => 'post',
						),
						'count'     => array(
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => 20,
							'default' => 5,
						),
					),
				),
				'output_schema'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'text'   => array( 'type' => 'string' ),
							'link'   => array( 'type' => 'string' ),
							'newTab' => array( 'type' => 'boolean' ),
						),
					),
				),
				'execute_callback'    => static function ( $input = array() ): array {
					$args = is_array( $input ) ? $input : array();
					return WM_Newsticker::get_instance()->get_latest_items(
						array(
							'postType'   => isset( $args['post_type'] ) ? (string) $args['post_type'] : 'post',
							'postsCount' => isset( $args['count'] ) ? (int) $args['count'] : 5,
						)
					);
				},
				'permission_callback' => '__return_true',
			)
		);
	}
}
