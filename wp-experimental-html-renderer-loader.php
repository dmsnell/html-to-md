<?php

if ( ! defined( 'ABSPATH' ) ) {
	require __DIR__ . '/deps/Polyfill/wordpress.php';
}

require __DIR__ . '/lib/class-wp-experimental-html-renderer-options.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-block.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-block-atx.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-block-blockquote.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-block-code.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-block-list.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-block-list-item.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-block-paragraph.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-block-table-cell.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-block-table-row.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-block-table.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-format.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-format-generic.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-format-image.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-format-link.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-line-buffer.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer-line-wrapper.php';
require __DIR__ . '/lib/class-wp-experimental-html-renderer.php';