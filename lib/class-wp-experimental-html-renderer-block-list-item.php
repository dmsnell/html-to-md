<?php

namespace WordPress\Experiments\HtmlToMarkdown;

class WP_Experimental_HTML_Renderer_Block_ListItem extends WP_Experimental_HTML_Renderer_Block {
	/**
	 * @var Array<WP_Experimental_HTML_Renderer_Block>
	 */
	public array $items = array();

	public function append( WP_Experimental_HTML_Renderer_Block $block ): void {
		$this->items[] = $block;
	}

	public function flush( WP_Experimental_HTML_Renderer_Options $options ): string {
		$rendered = array();
		$is_list  = array();
		foreach ( $this->items as $item ) {
			if ( $item->is_empty() ) {
				continue;
			}
			$rendered[] = $item->flush( $options );
			$is_list[]  = $item instanceof WP_Experimental_HTML_Renderer_Block_List;
		}

		if ( empty( $rendered ) ) {
			return '';
		}

		$out = $rendered[0];
		for ( $i = 1, $n = \count( $rendered ); $i < $n; $i++ ) {
			// A sub-list directly follows its preceding sibling without a blank line; other
			// sibling combinations need a blank line so markdown parsers see them as
			// separate blocks.
			$out .= ( $is_list[ $i ] ? "\n" : "\n\n" ) . $rendered[ $i ];
		}

		return $out;
	}

	public function is_empty(): bool {
		foreach ( $this->items as $item ) {
			if ( ! $item->is_empty() ) {
				return false;
			}
		}

		return true;
	}
}
