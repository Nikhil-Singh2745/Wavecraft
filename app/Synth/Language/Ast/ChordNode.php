<?php

namespace App\Synth\Language\Ast;

class ChordNode extends Node
{
    /** @param string[] $notes */
    public function __construct(
        public readonly array  $notes,
        public readonly string $duration,
    ) {}
}
