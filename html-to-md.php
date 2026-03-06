<?php

/*
 * Plugin Name: HTML to Markdown
 * Plugin URI: https://github.com/dmsnell/html-to-md
 * Author: WordPress Core Team
 * Description: Convert HTML documents to Markdown using WordPress’ HTML API.
 * Version: 2026.02.18
 * Requires at least: 6.9
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

use WordPress\Experiments\HtmlToMarkdown\WP_Experimental_HTML_Renderer_Options;

// Don’t load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

require_once __DIR__ . '/wp-experimental-html-renderer-loader.php';
require_once __DIR__ . '/wp-html-to-markdown.php';

add_filter( 'html_to_markdown_starting_node_finder', fn ( $prev ) =>
	$prev ?? function ( $p ) {
	while ( $p->next_token() ) {
		if (
			'MAIN' === $p->get_tag() ||
			'main' === $p->get_attribute( 'role' ) ||
			'main-content' === $p->get_attribute( 'id' ) || // cloudflare.
			'hnmain' === $p->get_attribute( 'id' )          // Hackernews.
		) {
			return true;
		}
	}

	return false;
} );

add_action( 'init', function () {
	$has_accept_header = isset( $_SERVER['HTTP_ACCEPT'] );
	$has_markdown_type = $has_accept_header && 1 === preg_match( '~^text/(?:x-)?markdown(?:;|$)~', $_SERVER['HTTP_ACCEPT'] );
	$has_markdown_query_arg = in_array( $_GET['output_format'] ?? '', array( 'md', 'markdown' ), true );

	if ( ! ( $has_markdown_type || $has_markdown_query_arg ) ) {
		return;
	}

	// Force the template enhancement output buffer to always be enabled for markdown responses.
	add_filter( 'wp_should_output_buffer_template_for_enhancement', '__return_true', PHP_INT_MAX );

	remove_all_filters( 'wp_template_enhancement_output_buffer' );

	add_filter(
		'wp_template_enhancement_output_buffer',
		function ( $output ) use ( $has_markdown_type ) {
			header( 'Content-type: text/markdown; charset=utf-8' );
			if ( $has_markdown_type ) {
				header( 'Vary: Accept' );
			}

			$title        = '';
			$author       = '';
			$published_on = '';
			$modified_on  = '';
			if ( is_singular() ) {
				$title        = get_the_title();
				$author       = get_the_author_meta( 'display_name' );
				$published_on = get_the_date();
				$modified_on  = get_the_modified_date();
			} else {
				$title_finder = new WP_HTML_Tag_Processor( $output );
				if ( $title_finder->next_tag( 'title' ) ) {
					$title = $title_finder->get_modifiable_text();
				}
			}

			$frontmatter = '';
			if ( ! empty( $title ) ) {
				$frontmatter .= "Title: {$title}\n";
			}
			if ( ! empty( $author ) ) {
				$frontmatter .= "Author: {$author}\n";
			}
			if ( ! empty( $published_on ) ) {
				$frontmatter .= "Published: {$published_on}\n";
			}
			if ( ! empty( $modified_on ) && $modified_on !== $published_on ) {
				$frontmatter .= "Last modified: {$modified_on}\n";
			}
			if ( ! empty( $frontmatter ) ) {
				$frontmatter .= "\n---\n\n";
			}

			$options           = new WP_Experimental_HTML_Renderer_Options();
			$options->base_url = rtrim( home_url( '/' ), '/' ) . $_SERVER['REQUEST_URI'];
			$markdown          = wp_html_to_markdown( $output, $options );

			return "{$frontmatter}{$markdown}";
		},
		1000
	);

	// Add a sanity check for whether the output buffer did indeed start.
	add_action(
		'wp_before_include_template',
		function () {
			if ( ! did_action( 'wp_template_enhancement_output_buffer_started' ) ) {
				wp_die( 'Markdown is not available.', 406 );
			}
		},
		PHP_INT_MAX
	);
} );

add_action(
	'wp_head',
	function () {
		printf(
			'<link rel="alternate" type="text/markdown" title="%s" href="%s">' . "\n",
			'Markdown format',
			esc_url( add_query_arg( 'output_format', 'md', home_url( '/' ) . ltrim( $_SERVER['REQUEST_URI'], '/' ) ) )
		);
	},
	2 // To be output with feed_links().
);
