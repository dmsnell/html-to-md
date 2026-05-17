<?php

namespace WordPress\Experiments\HtmlToMarkdown;

class WP_Experimental_HTML_Renderer_Block_Table extends WP_Experimental_HTML_Renderer_Block {
	/**
	 * @var Array<WP_Experimental_HTML_Renderer_Block_Table_Row>
	 */
	public array $rows = array();

	/**
	 * Index of the last row that belongs to <thead>, or -1 if no <thead>
	 * was seen. Used to place the GFM header separator row.
	 */
	public int $last_thead_row_index = -1;

	public bool $in_thead = false;

	public function begin_thead(): void {
		$this->in_thead = true;
	}

	public function end_thead(): void {
		$this->in_thead = false;
	}

	public function append( WP_Experimental_HTML_Renderer_Block $block ): void {
		if ( $block instanceof WP_Experimental_HTML_Renderer_Block_Table_Row ) {
			$this->rows[] = $block;
			if ( $this->in_thead ) {
				$this->last_thead_row_index = \count( $this->rows ) - 1;
			}
		}
	}

	public function flush( WP_Experimental_HTML_Renderer_Options $options ): string {
		$rows = array_values( array_filter( $this->rows, static fn ( $r ) => ! $r->is_empty() ) );
		if ( empty( $rows ) ) {
			return '';
		}

		$column_count = 0;
		foreach ( $rows as $row ) {
			$column_count = \max( $column_count, $row->column_count() );
		}

		// Pick where the header ends so we know where to insert the GFM separator.
		// Prefer explicit <thead>; otherwise treat a leading all-th row as the header.
		$header_end = $this->last_thead_row_index;
		if ( -1 === $header_end && $rows[0]->is_header() ) {
			$header_end = 0;
		}

		$separator = '| ' . \implode( ' | ', \array_fill( 0, $column_count, '---' ) ) . ' |';

		$lines = array();
		foreach ( $rows as $i => $row ) {
			$lines[] = $row->flush( $options );
			if ( $i === $header_end ) {
				$lines[] = $separator;
			}
		}

		// If there was no header at all, prepend an empty header so GFM still parses
		// it as a table. Otherwise the rows render as plain pipe-decorated paragraphs.
		if ( -1 === $header_end ) {
			\array_unshift(
				$lines,
				'| ' . \implode( ' | ', \array_fill( 0, $column_count, '' ) ) . ' |',
				$separator
			);
		}

		return \implode( "\n", $lines );
	}

	public function is_empty(): bool {
		foreach ( $this->rows as $row ) {
			if ( ! $row->is_empty() ) {
				return false;
			}
		}
		return true;
	}
}
