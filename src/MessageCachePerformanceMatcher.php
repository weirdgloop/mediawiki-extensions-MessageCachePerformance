<?php

namespace MessageCachePerformance;

/**
 * Allows to match set of message prefixes with the subject
 */
class MessageCachePerformanceMatcher {
	private string $regex;

	public function __construct( array $matchers ) {
		$messagePrefixes = implode(
			'|',
			array_map(
				fn( string $msg ) => preg_quote( $msg ),
				$matchers
			)
		);
		$this->regex = "/^($messagePrefixes)/";
	}

	public function searchIn( string $text ): bool {
		return (bool)preg_match( $this->regex, $text );
	}
}
