<?php

namespace MediaWiki\Extension\BibleGet;

use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Parser\Parser;

class Hooks implements ParserFirstCallInitHook {

	private const VERSIONS_AVAILABLE = [
		"NABRE",
		"NVBSE",
		"LUZZI",
		"CEI2008",
		"DRB",
		"VGCL"
	];

	private static function isValidVersion( string $version ): bool {
		return in_array( $version, self::VERSIONS_AVAILABLE );
	}

	/**
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ): void {
		$parser->setHook( 'biblequote', [ self::class, 'renderBibleQuoteTag' ] );
		$parser->setFunctionHook( 'biblequote', [ self::class, 'renderBibleQuote' ] );
	}

	/**
	 * @param string $bibleVersion
	 * @param string $bibleRef
	 * @param string $hash
	 * @return string
	 */
	// phpcs:ignore Generic.Files.LineLength.TooLong
	private static function retrieveBibleQuoteFromApi( string $bibleVersion, string $bibleRef, string $hash ): string {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, "https://query.bibleget.io" );
		curl_setopt( $ch, CURLOPT_POST, true );
		// phpcs:ignore Generic.Files.LineLength.TooLong
		curl_setopt( $ch, CURLOPT_POSTFIELDS, "query={$bibleRef}&version={$bibleVersion}&appid=SeminaVerbi&return=html" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$output = curl_exec( $ch );
		curl_close( $ch );

		$output = str_replace(
			[ "\n", "\r" ],
			'',
			$output );
		$output = preg_replace(
			'/&lt;br( ){0,1}(\/){0,1}&gt;/',
			'',
			$output
		);
		$output = preg_replace(
			'/&lt;(sm|pof|po|pol|pos|poif|poi|poil|po3|po3l|speaker|pr)&gt;/',
			'<span class="$1">',
			$output
		);
		$output = preg_replace(
			'/&lt;\/(sm|pof|po|pol|pos|poif|poi|poil|po3|po3l|speaker|pr)&gt;/',
			'</span>',
			$output
		);
		$output = preg_replace(
			'/&lt;(\/){0,1}i&gt;/',
			'<$1i>',
			$output
		);
		$output = preg_replace(
			'/(bookChapter">[1-4])(\p{L})/',
			'$1&nbsp;$2',
			$output
		);

		file_put_contents( "bibleQuotes/{$hash}.html", $output );
		return $output;
	}

	/**
	 * @param string $input
	 * @param array $args
	 * @return array
	 */
	// phpcs:ignore Generic.Files.LineLength.TooLong
	public static function renderBibleQuoteTag( ?string $input, array $args ): array {
		global $wgBibleGetDefaultBibleVersion;
		$bibleRef 	  = isset( $args['ref'] )
						? $args['ref']
						: ( $input ?? 'John3:16' );
		$inline = isset( $args['inline'] )
						? filter_var( $args['inline'], FILTER_VALIDATE_BOOLEAN )
						: false;
		$bibleVersion = $wgBibleGetDefaultBibleVersion;
		if ( isset( $args['version'] ) ) {
			if ( self::isValidVersion( $args['version'] ) ) {
				$bibleVersion = $args['version'];
			} else {
				$inlineStr = $inline ? 'true' : 'false';
				$html = "<span class=\"bibleQuoteRefBroken\""
					. " data-ref=\"{$bibleRef}\" data-version=\"{$args['version']}\" data-inline=\"{$inlineStr}\""
					. " title=\"The Bible version '{$args['version']}' is not supported by the BibleGet endpoint.\">"
					. $bibleRef
					. "</span>"
					. "<sup class=\"bibleQuoteRefBrokenReason\""
					. " title=\"The Bible version '{$args['version']}' is not supported by the BibleGet endpoint.\""
					. ">[!]</sup>";
				return [ $html, 'noparse' => true, 'isHTML' => true ];
			}
		}
		$str = $bibleVersion . "/" . $bibleRef;
		$tmp = preg_replace( "/\s+/", "", $str );
		$hash = md5( $tmp );
		if ( file_exists( "bibleQuotes/{$hash}.html" ) ) {
			$html = file_get_contents( "bibleQuotes/{$hash}.html" );
		} else {
			$html = self::retrieveBibleQuoteFromApi( $bibleVersion, $bibleRef, $hash );
		}
		return [ $html, 'noparse' => true, 'isHTML' => true ];
	}

	/**
	 * @param Parser $parser
	 * @param string $bibleVersion
	 * @param string $bibleRef
	 * @return array
	 */
	// phpcs:ignore Generic.Files.LineLength.TooLong
	public static function renderBibleQuote( Parser $parser, string $bibleRef = 'John3:16', bool $inline = false, ?string $bibleVersion = null ): array {
		global $wgBibleGetDefaultBibleVersion;
		$bibleEdition = $wgBibleGetDefaultBibleVersion;
		if ( $bibleVersion !== null ) {
			if ( self::isValidVersion( $bibleVersion ) ) {
				$bibleEdition = $bibleVersion;
			} else {
				$inlineStr = $inline ? 'true' : 'false';
				$html = "<span class=\"bibleQuoteRefBroken\""
					. " data-ref=\"{$bibleRef}\" data-version=\"{$bibleVersion}\" data-inline=\"{$inlineStr}\""
					. " title=\"The Bible version '{$bibleVersion}' is not supported by the BibleGet endpoint.\">"
					. $bibleRef
					. "</span>"
					. "<sup class=\"bibleQuoteRefBrokenReason\""
					. " title=\"The Bible version '{$bibleVersion}' is not supported by the BibleGet endpoint.\""
					. ">[!]</sup>";
				return [ $html, 'noparse' => true, 'isHTML' => true ];
			}
		}
		$str = $bibleEdition . "/" . $bibleRef;
		$tmp = preg_replace( "/\s+/", "", $str );
		$hash = md5( $tmp );
		if ( file_exists( "bibleQuotes/{$hash}.html" ) ) {
			$html = file_get_contents( "bibleQuotes/{$hash}.html" );
		} else {
			$html = self::retrieveBibleQuoteFromApi( $bibleEdition, $bibleRef, $hash );
		}
		return [ $html, 'noparse' => true, 'isHTML' => true ];
	}
}
