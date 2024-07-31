<?php

namespace MediaWiki\Extension\BibleGet;

use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Parser\Parser;

class Hooks implements ParserFirstCallInitHook {

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
		$bibleVersion = isset( $args['version'] ) ? $args['version'] : 'NABRE';
		$bibleRef = isset( $args['ref'] )
						? $args['ref']
						: ( $input ?? 'John3:16' );
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
	public static function renderBibleQuote( Parser $parser, string $bibleVersion = 'NABRE', string $bibleRef = 'John3:16' ): array {
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
}
