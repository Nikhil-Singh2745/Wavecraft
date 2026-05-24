<?php

namespace App\Synth\Language\Ast;

class ProgramNode extends Node
{
    /** @param Node[] $statements */
    public function __construct(public readonly array $statements) {}
}
