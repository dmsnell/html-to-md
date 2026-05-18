<?php

namespace WordPress\Experiments\HtmlToMarkdown;

class WP_Experimental_HTML_Renderer_Block_Table_Row extends WP_Experimental_HTML_Renderer_Block {
	/**
	 * @var Array<WP_Experimental_HTML_Renderer_Block_Table_Cell>
	 */
	public array $cells = array();

	public function append( WP_Experimental_HTML_Renderer_Block $block ): void {
		if ( $block instanceof WP_Experimental_HTML_Renderer_Block_Table_Cell ) {
			$this->cells[] = $block;
		}
	}

	public function flush( WP_Experimental_HTML_Renderer_Options $options ): string {
		$parts = array();
		foreach ( $this->cells as $cell ) {
			$parts[] = $cell->flush( $options );
		}
		return '| ' . \implode( ' | ', $parts ) . ' |';
	}

	public function is_empty(): bool {
		return empty( $this->cells );
	}

	public function is_header(): bool {
		foreach ( $this->cells as $cell ) {
			if ( ! $cell->is_header ) {
				return false;
			}
		}
		return ! empty( $this->cells );
	}

	public function column_count(): int {
		return \count( $this->cells );
	}
}
