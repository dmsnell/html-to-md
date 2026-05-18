<?php

namespace WordPress\Experiments\HtmlToMarkdown;

class WP_Experimental_HTML_Renderer_Block_Table_Cell extends WP_Experimental_HTML_Renderer_Block {
	public bool $is_header;

	/**
	 * Children of the cell. May be Line_Buffers (for inline content) or Blocks
	 * (for paragraphs, code, sub-lists). Markdown tables can't represent
	 * multi-line cells, so all of these are joined with spaces on flush.
	 *
	 * @var Array<WP_Experimental_HTML_Renderer_Line_Buffer|WP_Experimental_HTML_Renderer_Block>
	 */
	public array $children = array();

	public function __construct( bool $is_header = false ) {
		$this->is_header = $is_header;
	}

	public function append( WP_Experimental_HTML_Renderer_Block $block ): void {
		if ( $block instanceof WP_Experimental_HTML_Renderer_Block_Paragraph ) {
			foreach ( $block->lines as $line ) {
				$this->children[] = $line;
			}
			return;
		}

		$this->children[] = $block;
	}

	public function append_line( WP_Experimental_HTML_Renderer_Line_Buffer $line ): void {
		$this->children[] = $line;
	}

	public function flush( WP_Experimental_HTML_Renderer_Options $options ): string {
		$parts = array();
		foreach ( $this->children as $child ) {
			$rendered = \trim( $child->flush( $options ) );
			if ( '' !== $rendered ) {
				$parts[] = $rendered;
			}
		}

		$text = \implode( ' ', $parts );
		// Collapse embedded newlines so the cell stays on one row.
		$text = \preg_replace( '~[\r\n]+~', ' ', $text );
		// Escape pipes so they don't terminate the cell.
		$text = \str_replace( '|', '\\|', $text );
		// Strip the invisible nobr markers links carry through Line_Buffer::flush().
		$text = strip_nobr_markers( $text );

		return $text;
	}

	public function is_empty(): bool {
		foreach ( $this->children as $child ) {
			if ( $child instanceof WP_Experimental_HTML_Renderer_Block ) {
				if ( ! $child->is_empty() ) {
					return false;
				}
			} elseif ( $child->has_non_whitespace_content() ) {
				return false;
			}
		}

		return true;
	}
}
