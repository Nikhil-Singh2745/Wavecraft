<?php

namespace App\Synth\Language;

readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string    $value,
        public int       $line,
    ) {}
}
