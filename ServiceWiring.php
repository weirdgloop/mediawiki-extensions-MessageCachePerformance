<?php

use MediaWiki\MediaWikiServices;
use MessageCachePerformance\MessageCachePerformanceMatcher;

return [
	'MessageCachePerformanceConfig' => fn( MediaWikiServices $services ): Config => new MultiConfig(
		[ new GlobalVarConfig( 'mcp' ), $services->getMainConfig() ]
	),
	'MessageCachePerformanceMatcher' => static function ( MediaWikiServices $services ) {
		return new MessageCachePerformanceMatcher(
			$services->get( 'MessageCachePerformanceConfig' )->get(
				'MessageCachePerformanceMsgPrefixes'
			)
		);
	}
];
