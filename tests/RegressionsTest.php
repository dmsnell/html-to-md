<?php

use PHPUnit\Framework\Attributes\DataProvider;

class RegressionsTest extends PHPUnit\Framework\TestCase {
	/**
	 * Verifies that the parser does not break known invariants.
	 *
	 * Invariants include things like:
	 *
	 *  - Closing a format when it’s not open.
	 *  - Exiting a block that isn’t open.
	 *
	 * @ticket {TICKET_NUMBER}
	 *
	 * @param string $html Contains conditions known to have broken the invariants.
	 * @return void
	 */
	#[DataProvider( 'data_assertion_tests' )]
	public function test_invariant_breakers( string $html ): void {
		// If the assert() fails, the test will too.
		wp_html_to_markdown( $html );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public static function data_assertion_tests(): array {
		return array(
			'Link surrounding ATX header, which opens a block.' => array( '<a><h1>' ),
		);
	}
}
