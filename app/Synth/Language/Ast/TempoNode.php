<?php

namespace App\Synth\Language\Ast;

class TempoNode extends Node
{
    public function __construct(public readonly int $bpm) {}
}
