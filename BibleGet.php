<?php

namespace MediaWiki\Extension\BibleGet;

use MediaWiki\Hook\ParserFirstCallInitHook;
use Parser;
use PPFrame;

class Hooks implements ParserFirstCallInitHook
{
    public static function onParserFirstCallInit( Parser $parser ) {
        $parser->setHook( 'biblequote', [ self:class, 'renderBibleQuoteTag' ] );
        $parser->setFunctionHook( 'biblequote', [ self::class, 'renderBibleQuote' ] );
    }

    private static function retrieveBibleQuoteFromApi(string $bibleVersion, string $bibleQuote, string $hash) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://query.bibleget.io");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, "query={$BGET["ref"]}&version={$BGET["version"]}&appid=SeminaVerbi&return=html");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);

        $output = str_replace(array("\n", "\r"), '', $output);
        $output = preg_replace('/&lt;br( ){0,1}(\/){0,1}&gt;/','',$output);
        $output = preg_replace('/&lt;(sm|pof|po|pol|pos|poif|poi|poil|po3|po3l|speaker|pr)&gt;/', '<span class="$1">', $output);
        $output = preg_replace('/&lt;\/(sm|pof|po|pol|pos|poif|poi|poil|po3|po3l|speaker|pr)&gt;/', '</span>', $output);
        $output = preg_replace('/&lt;(\/){0,1}i&gt;/', '<$1i>', $output);

        file_put_contents("bibleQuotes/{$hash}.html",$output);
        return $output;        
    }

    private static function renderBibleQuoteTag( $input, array $args, Parser $parser, PPFrame $frame ) {
        $bibleVersion = isset( $args['bibleVersion'] ) ? $args['bibleVersion'] : 'NABRE';
        $bibleQuote = isset( $args['bibleQuote'] ) ? $args['bibleQuote'] : $input;
        $str = $bibleVersion . "/" . $bibleQuote;
        $tmp = preg_replace("/\s+/", "", $str);
        $hash = md5($tmp);
        if(file_exists("bibleQuotes/{$hash}.html") ) {
            $html = file_get_contents("bibleQuotes/{$hash}.html");
        } else {
            $html = self::retrieveBibleQuoteFromApi($bibleVersion, $bibleQuote, $hash);
        }
        return [ $html, 'noparse' => true, 'isHTML' => true ];
    }

    private static function renderBibleQuote( Parser $parser, $bibleVersion = 'NABRE', $bibleQuote = 'John3:16' ) {
        $str = $bibleVersion . "/" . $bibleQuote;
        $tmp = preg_replace("/\s+/", "", $str);
        $hash = md5($tmp);
        if(file_exists("bibleQuotes/{$hash}.html") ) {
            $html = file_get_contents("bibleQuotes/{$hash}.html");
        } else {
            $html = self::retrieveBibleQuoteFromApi($bibleVersion, $bibleQuote, $hash);
        }
        return [ $html, 'noparse' => true, 'isHTML' => true ];
    }
}
