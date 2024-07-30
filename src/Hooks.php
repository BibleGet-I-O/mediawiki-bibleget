<?php

namespace MediaWiki\Extension\BibleGet;

use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

class Hooks implements ParserFirstCallInitHook {

	/**
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'biblequote', [ self::class, 'renderBibleQuoteTag' ] );
		$parser->setFunctionHook( 'biblequote', [ self::class, 'renderBibleQuote' ] );
	}

	/**
	 * @param string $bibleVersion
	 * @param string $bibleQuote
	 * @param string $hash
	 * @return string
	 */
	// phpcs:ignore Generic.Files.LineLength.TooLong
	private static function retrieveBibleQuoteFromApi( string $bibleVersion, string $bibleQuote, string $hash ): string {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, "https://query.bibleget.io");
		curl_setopt( $ch, CURLOPT_POST, true );
		// phpcs:ignore Generic.Files.LineLength.TooLong
		curl_setopt( $ch, CURLOPT_POSTFIELDS, "query={$bibleQuote}&version={$bibleVersion}&appid=SeminaVerbi&return=html" );
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
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return array
	 */
	// phpcs:ignore Generic.Files.LineLength.TooLong
	private static function renderBibleQuoteTag( string $input, array $args ): array {
		$bibleVersion = isset( $args['bibleVersion'] ) ? $args['bibleVersion'] : 'NABRE';
		$bibleQuote = isset( $args['bibleQuote'] ) ? $args['bibleQuote'] : $input;
		$str = $bibleVersion . "/" . $bibleQuote;
		$tmp = preg_replace( "/\s+/", "", $str );
		$hash = md5($tmp);
		if ( file_exists( "bibleQuotes/{$hash}.html" ) ) {
			$html = file_get_contents( "bibleQuotes/{$hash}.html" );
		} else {
			$html = self::retrieveBibleQuoteFromApi( $bibleVersion, $bibleQuote, $hash );
		}
		return [ $html, 'noparse' => true, 'isHTML' => true ];
	}

	/**
	 * @param Parser $parser
	 * @param string $bibleVersion
	 * @param string $bibleQuote
	 * @return array
	 */
	// phpcs:ignore Generic.Files.LineLength.TooLong
	private static function renderBibleQuote( Parser $parser, string $bibleVersion = 'NABRE', string $bibleQuote = 'John3:16' ): array {
		$str = $bibleVersion . "/" . $bibleQuote;
		$tmp = preg_replace( "/\s+/", "", $str );
		$hash = md5( $tmp );
		if ( file_exists( "bibleQuotes/{$hash}.html" ) ) {
			$html = file_get_contents( "bibleQuotes/{$hash}.html" );
		} else {
			$html = self::retrieveBibleQuoteFromApi( $bibleVersion, $bibleQuote, $hash );
		}
		return [ $html, 'noparse' => true, 'isHTML' => true ];
	}
}
