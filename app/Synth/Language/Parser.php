<?php

namespace App\Synth\Language;

use App\Synth\Language\Ast\{
    ProgramNode, TempoNode, InstrumentNode,
    NoteNode, RestNode, ChordNode, SequenceNode
};

class Parser
{
    private int $pos = 0;

    /** @param Token[] $tokens */
    public function __construct(private readonly array $tokens) {}

    public function parse(): ProgramNode
    {
        $statements = [];

        while (!$this->isEof()) {
            $statements[] = $this->parseStatement();
        }

        return new ProgramNode($statements);
    }

    private function parseStatement(): \App\Synth\Language\Ast\Node
    {
        $token = $this->peek();

        return match ($token->type) {
            TokenType::Tempo      => $this->parseTempo(),
            TokenType::Instrument => $this->parseInstrument(),
            TokenType::Play       => $this->parsePlay(),
            TokenType::Rest       => $this->parseRest(),
            TokenType::Chord      => $this->parseChord(),
            TokenType::Sequence   => $this->parseSequence(),
            default               => throw new \RuntimeException(
                "Unexpected token '{$token->value}' on line {$token->line}"
            ),
        };
    }

    private function parseTempo(): TempoNode
    {
        $this->consume(TokenType::Tempo);
        $bpm = (int) $this->consume(TokenType::Number)->value;
        if ($bpm < 20 || $bpm > 400) {
            throw new \RuntimeException("Tempo must be between 20 and 400 BPM, got $bpm");
        }
        return new TempoNode($bpm);
    }

    private function parseInstrument(): InstrumentNode
    {
        $this->consume(TokenType::Instrument);
        $token = $this->peek();
        if ($token->type !== TokenType::Instrument) {
            throw new \RuntimeException(
                "Expected instrument name (sine|square|saw|triangle) on line {$token->line}"
            );
        }
        $waveform = $this->advance()->value;
        return new InstrumentNode($waveform);
    }

    private function parsePlay(): NoteNode
    {
        $this->consume(TokenType::Play);
        $note     = $this->consume(TokenType::Note)->value;
        $duration = $this->consume(TokenType::Duration)->value;
        return new NoteNode($note, $duration);
    }

    private function parseRest(): RestNode
    {
        $this->consume(TokenType::Rest);
        $duration = $this->consume(TokenType::Duration)->value;
        return new RestNode($duration);
    }

    private function parseChord(): ChordNode
    {
        $this->consume(TokenType::Chord);
        $notes    = $this->parseNoteList();
        $duration = $this->consume(TokenType::Duration)->value;
        return new ChordNode($notes, $duration);
    }

    private function parseSequence(): SequenceNode
    {
        $this->consume(TokenType::Sequence);
        $notes    = $this->parseNoteList();
        $duration = $this->consume(TokenType::Duration)->value;
        return new SequenceNode($notes, $duration);
    }

    /** @return string[] */
    private function parseNoteList(): array
    {
        $this->consume(TokenType::BracketOpen);
        $notes = [];

        $notes[] = $this->consume(TokenType::Note)->value;

        while ($this->peek()->type === TokenType::Comma) {
            $this->advance();
            $notes[] = $this->consume(TokenType::Note)->value;
        }

        $this->consume(TokenType::BracketClose);
        return $notes;
    }

    private function consume(TokenType $type): Token
    {
        $token = $this->peek();
        if ($token->type !== $type) {
            throw new \RuntimeException(
                "Expected {$type->value} but got '{$token->value}' ({$token->type->value}) on line {$token->line}"
            );
        }
        return $this->advance();
    }

    private function advance(): Token
    {
        $token = $this->tokens[$this->pos];
        $this->pos++;
        return $token;
    }

    private function peek(): Token
    {
        return $this->tokens[$this->pos];
    }

    private function isEof(): bool
    {
        return $this->tokens[$this->pos]->type === TokenType::EOF;
    }
}
