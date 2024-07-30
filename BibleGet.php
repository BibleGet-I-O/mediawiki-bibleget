<?php

namespace MediaWiki\Extension\BibleGet;

use MediaWiki\Hook\ParserFirstCallInitHook;
use Parser;
use PPFrame;

class Hooks implements ParserFirstCallInitHook
{
    public static function onParserFirstCallInit( Parser $parser ) {
        $parser->setHook( 'bibleget', [ self:class, 'renderBibleQuoteMagic' ] );
        $parser->setFunctionHook( 'bibleget', [ self::class, 'renderBibleQuote' ] );
    }

    private static function renderBibleQuoteMagic( $input, array $args, Parser $parser, PPFrame $frame ) {
        // The Lua script will handle the actual API call and rendering
        return [ '', 'noparse' => true, 'isHTML' => true ];        
    }
    private static function renderBibleQuote( Parser $parser, $input ) {
        // The Lua script will handle the actual API call and rendering
        return [ '', 'noparse' => true, 'isHTML' => true ];
    }
}
