<?php

namespace WordPress\Experiments\HtmlToMarkdown;

/**
 * Renders a PHP array as a YAML mapping or sequence body.
 *
 * Accepts string, int, float, bool, null, and nested arrays. Empty strings and
 * arrays whose contents all filter out are dropped. Unsupported leaf types
 * (objects, resources, etc.) trigger _doing_it_wrong() and are skipped.
 *
 * Output is the inner body only — callers wrap it in `---` document markers
 * if they need a frontmatter block.
 */
function render_yaml( array $value, int $indent = 0 ): string {
	$pad     = str_repeat( '  ', $indent );
	$is_list = array_is_list( $value );
	$out     = '';

	foreach ( $value as $key => $v ) {
		if ( is_array( $v ) ) {
			$rendered = render_yaml( $v, $indent + 1 );
			if ( '' === $rendered ) {
				continue;
			}
		} elseif ( is_yaml_scalar( $v ) ) {
			if ( is_string( $v ) && '' === $v ) {
				continue;
			}
			$rendered = render_yaml_scalar( $v );
		} else {
			_doing_it_wrong(
				__NAMESPACE__ . '\\render_yaml',
				sprintf( 'Unsupported value type in YAML output: %s', esc_html( gettype( $v ) ) ),
				'2026.02.18'
			);
			continue;
		}

		if ( $is_list ) {
			$out .= is_array( $v )
				? "{$pad}-\n{$rendered}"
				: "{$pad}- {$rendered}\n";
		} else {
			$key_str = render_yaml_key( (string) $key );
			$out    .= is_array( $v )
				? "{$pad}{$key_str}:\n{$rendered}"
				: "{$pad}{$key_str}: {$rendered}\n";
		}
	}

	return $out;
}

function is_yaml_scalar( $v ): bool {
	return is_string( $v ) || is_int( $v ) || is_float( $v ) || is_bool( $v ) || null === $v;
}

function render_yaml_scalar( $v ): string {
	if ( is_string( $v ) ) {
		$escaped = str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $v );
		$escaped = str_replace( array( "\r\n", "\r", "\n" ), ' ', $escaped );
		return "\"{$escaped}\"";
	}
	if ( is_bool( $v ) ) {
		return $v ? 'true' : 'false';
	}
	if ( null === $v ) {
		return 'null';
	}
	if ( is_float( $v ) ) {
		if ( is_nan( $v ) ) {
			return '.nan';
		}
		if ( is_infinite( $v ) ) {
			return $v > 0 ? '.inf' : '-.inf';
		}
	}
	return (string) $v;
}

function render_yaml_key( string $key ): string {
	// Quote when the key would otherwise be misparsed: empty, leading indicator
	// character, embedded `: ` or `# `, trailing whitespace, numeric-looking, or
	// matching a YAML 1.1 boolean/null literal.
	$needs_quoting = (
		'' === $key
		|| 1 === preg_match( '/^[\s\-?:,\[\]\{\}#&*!|>\'"%@`]/', $key )
		|| 1 === preg_match( '/[:#]\s/', $key )
		|| 1 === preg_match( '/\s$/', $key )
		|| is_numeric( $key )
		|| in_array( strtolower( $key ), array( 'true', 'false', 'null', 'yes', 'no', 'on', 'off', '~' ), true )
	);

	if ( ! $needs_quoting ) {
		return $key;
	}

	$escaped = str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $key );
	$escaped = str_replace( array( "\r\n", "\r", "\n" ), ' ', $escaped );
	return "\"{$escaped}\"";
}
