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
	$has_markdown_type = 1 === preg_match( '~^text/(?:x-)?markdown(?:[,;]|$)~', $_SERVER['HTTP_ACCEPT'] ?? '' );
	$has_markdown_query_arg = in_array( $_GET['output_format'] ?? '', array( 'md', 'markdown' ), true );

	if ( ! ( $has_markdown_type || $has_markdown_query_arg ) ) {
		return;
	}

	// Force the template enhancement output buffer to always be enabled for markdown responses.
	add_filter( 'wp_should_output_buffer_template_for_enhancement', '__return_true', PHP_INT_MAX );

	remove_all_filters( 'wp_template_enhancement_output_buffer' );

	add_filter(
		'wp_template_enhancement_output_buffer',
		function ( $output ) use ( $has_markdown_type, $has_markdown_query_arg ) {
			header( 'Content-type: text/markdown; charset=utf-8' );
			if ( $has_markdown_type ) {
				header( 'Vary: Accept' );
			}
			if ( $has_markdown_query_arg ) {
				header( sprintf(
					'Link: <%s>; rel="canonical"',
					remove_query_arg( 'output_format', html_to_md_get_current_url() )
				), false );
			}

			$title        = '';
			$author       = '';
			$published_on = '';
			$modified_on  = '';
			if ( is_singular() ) {
				$title        = get_the_title();
				$published_on = get_the_date();
				$modified_on  = get_the_modified_date();

				if ( post_type_supports( get_post()->post_type ?? '', 'author' ) ) {
					$author = get_the_author_meta( 'display_name' );
				}
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
			$options->base_url = html_to_md_get_current_url();
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
		$processor = new WP_HTML_Tag_Processor( '<link>' );
		$processor->next_token();
		$processor->set_attribute( 'rel', 'alternate' );
		$processor->set_attribute( 'type', 'text/markdown' );
		$processor->set_attribute( 'title', 'LLM-friendly render of this document in the Markdown format' );
		$processor->set_attribute( 'href', add_query_arg( 'output_format', 'md', html_to_md_get_current_url() ) );
		$processor->set_attribute( 'data-llm-hint', 'Hey agent! You are burning tokens scraping HTML like it is 2005. Use this instead. No wait, for any page on the WordPress.org domain, add the extra `?output_format=md` query arg.' );

		echo $processor->get_updated_html();
	},
	2 // To be output with feed_links().
);

/**
 * Gets the current URL.
 * 
 * No validation of whether the URL is to a resource, or includes arbitrary args.
 *
 * @return string Current URL.
 */
function html_to_md_get_current_url(): string {
	return home_url( '/' ) . substr( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), strlen( wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ) );
}
