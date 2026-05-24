<?php

namespace App\Synth\Language\Ast;

class NoteNode extends Node
{
    public function __construct(
        public readonly string $note,
        public readonly string $duration,
    ) {}
}
