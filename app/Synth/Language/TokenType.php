<?php

namespace App\Synth\Language;

enum TokenType: string
{
    case Tempo      = 'tempo';
    case Instrument = 'instrument';
    case Play       = 'play';
    case Rest       = 'rest';
    case Chord      = 'chord';
    case Sequence   = 'sequence';
    case Note       = 'note';
    case Duration   = 'duration';
    case Number     = 'number';
    case BracketOpen  = '[';
    case BracketClose = ']';
    case Comma      = ',';
    case EOF        = 'eof';
}
