<?php
// Copyright 2021 Fandom, Inc.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
// http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

use AhoCorasick\MultiStringMatcher;
use MediaWiki\MediaWikiServices;

class MessageCachePerformance {

	/**
	 * Prefixes for messages that are known not to exist in core/extensions and are not user-customizable.
	 */
	private const NONEXISTENT_MSG_PREFIXES = [
		// Special:SpecialPages and GlobalShortcuts
		'specialpages-specialpagegroup-',

		// Used by SkinTemplate for Vector <-> Monobook B/C
		'oasis-view-',
		'oasis-action-',
		'apioutput-view-',
		'fallback-view-',
		'mobileve-view-',
		'fandommobile-view-',
		'hydra-view-',
		'hydra-action-',
		'hydradark-view-',
		'hydradark-action-',
		'minerva-view-',
		'minerva-action-',
		'exvius-view-',
		'exvius-action-',

		// LanguageConverter
		'conversion-ns',

		// Linker
		'tooltip-',
		'accesskey-',

		// SkinTemplate namespace tab navigation
		'nstab-',
	];

	/**
	 * Map of known message keys defined by core/extensions (key => true)
	 * @var bool[] $knownMsgKeys
	 */
	private static $knownMsgKeys;

	/**
	 * Matcher for NONEXISTENT_MSG_PREFIXES
	 * @var MultiStringMatcher $notExistentMsgMatcher
	 */
	private static $notExistentMsgMatcher;

	/**
	 * Hook: MessageCache::get
	 * Due to https://phabricator.wikimedia.org/T193271, lookups for messages that are not defined in core/extensions
	 * and are not customized on the wiki trigger memcached/APCu lookups. This can be quite expensive when many wikis
	 * and messages are involved.
	 *
	 * This hook catches the most common messages being looked up that are known not to exist, and short-circuits the
	 * MessageCache lookup by explicitly designating them as nonexistent.
	 *
	 * See: https://phabricator.wikimedia.org/T193271
	 * See also: https://phabricator.wikimedia.org/T275033
	 *
	 * @param string &$lcKey message key being looked up
	 * @see MessageCache::get()
	 *
	 */
	public static function onMessageCacheGet( &$lcKey ): bool {
		$knownMsgKeys = self::getKnownMsgKeys();

		// Message is known to exist in code - nothing to do.
		if ( isset( $knownMsgKeys[$lcKey] ) ) {
			return true;
		}

		// The message is known to not exist in code and cannot be customized
		if ( self::getNotExistentMsgMatcher()->searchIn( $lcKey ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Preload all known message keys defined by core/extensions
	 * @return bool[] map of message key => true
	 */
	private static function getKnownMsgKeys(): array {
		if ( !self::$knownMsgKeys ) {
			global $wgLanguageCode;
			$localisationCache = MediaWikiServices::getInstance()->getLocalisationCache();
			self::$knownMsgKeys =
				array_flip( $localisationCache->getSubitemList( $wgLanguageCode, 'messages' ) );
		}

		return self::$knownMsgKeys;
	}

	private static function getNotExistentMsgMatcher(): MultiStringMatcher {
		if ( !self::$notExistentMsgMatcher ) {
			self::$notExistentMsgMatcher = new MultiStringMatcher( self::NONEXISTENT_MSG_PREFIXES );
		}

		return self::$notExistentMsgMatcher;
	}
}
