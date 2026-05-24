<?php

namespace App\Synth\Language\Ast;

class InstrumentNode extends Node
{
    public function __construct(public readonly string $waveform) {}
}
