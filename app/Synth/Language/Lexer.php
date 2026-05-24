<?php

namespace App\Synth\Language;

class Lexer
{
    private int $pos  = 0;
    private int $line = 1;

    private const KEYWORDS = [
        'tempo'      => TokenType::Tempo,
        'instrument' => TokenType::Instrument,
        'play'       => TokenType::Play,
        'rest'       => TokenType::Rest,
        'chord'      => TokenType::Chord,
        'sequence'   => TokenType::Sequence,
    ];

    private const DURATIONS = [
        'whole', 'half', 'quarter', 'eighth', 'sixteenth',
    ];

    public function __construct(private readonly string $source) {}

    /** @return Token[] */
    public function tokenize(): array
    {
        $tokens = [];

        while ($this->pos < strlen($this->source)) {
            $this->skipWhitespaceAndComments();

            if ($this->pos >= strlen($this->source)) {
                break;
            }

            $ch = $this->source[$this->pos];

            if ($ch === '[') {
                $tokens[] = new Token(TokenType::BracketOpen, '[', $this->line);
                $this->pos++;
                continue;
            }

            if ($ch === ']') {
                $tokens[] = new Token(TokenType::BracketClose, ']', $this->line);
                $this->pos++;
                continue;
            }

            if ($ch === ',') {
                $tokens[] = new Token(TokenType::Comma, ',', $this->line);
                $this->pos++;
                continue;
            }

            if (ctype_digit($ch)) {
                $tokens[] = $this->readNumber();
                continue;
            }

            if (ctype_alpha($ch)) {
                $tokens[] = $this->readWord();
                continue;
            }

            throw new \RuntimeException("Unexpected character '$ch' on line {$this->line}");
        }

        $tokens[] = new Token(TokenType::EOF, '', $this->line);
        return $tokens;
    }

    private function skipWhitespaceAndComments(): void
    {
        while ($this->pos < strlen($this->source)) {
            $ch = $this->source[$this->pos];

            if ($ch === "\n") {
                $this->line++;
                $this->pos++;
            } elseif (ctype_space($ch)) {
                $this->pos++;
            } elseif ($ch === '#') {
                // Line comment — skip to end of line
                while ($this->pos < strlen($this->source) && $this->source[$this->pos] !== "\n") {
                    $this->pos++;
                }
            } else {
                break;
            }
        }
    }

    private function readNumber(): Token
    {
        $start = $this->pos;
        while ($this->pos < strlen($this->source) && ctype_digit($this->source[$this->pos])) {
            $this->pos++;
        }
        return new Token(TokenType::Number, substr($this->source, $start, $this->pos - $start), $this->line);
    }

    private function readWord(): Token
    {
        $start = $this->pos;
        // Allow letters, digits, # (for C#), and b (for Db) in words
        while ($this->pos < strlen($this->source)) {
            $ch = $this->source[$this->pos];
            if (ctype_alnum($ch) || $ch === '#') {
                $this->pos++;
            } else {
                break;
            }
        }
        $word = substr($this->source, $start, $this->pos - $start);

        // Check for keywords first
        if (isset(self::KEYWORDS[$word])) {
            return new Token(self::KEYWORDS[$word], $word, $this->line);
        }

        // Check for instrument names
        if (in_array($word, ['sine', 'square', 'saw', 'triangle'])) {
            return new Token(TokenType::Instrument, $word, $this->line);
        }

        // Check for durations
        if (in_array($word, self::DURATIONS)) {
            return new Token(TokenType::Duration, $word, $this->line);
        }

        // Note: letter(s) followed by optional accidental then digit, e.g. C4, A#3, Db5
        if (preg_match('/^[A-G][b#]?\d$/', $word)) {
            return new Token(TokenType::Note, $word, $this->line);
        }

        throw new \RuntimeException("Unknown word '$word' on line {$this->line}");
    }
}
