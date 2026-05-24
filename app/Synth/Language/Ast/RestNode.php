<?php

namespace App\Synth\Language\Ast;

class RestNode extends Node
{
    public function __construct(public readonly string $duration) {}
}
