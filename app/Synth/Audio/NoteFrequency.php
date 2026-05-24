<?php

namespace App\Synth\Audio;

class NoteFrequency
{
    // Maps note letter + accidental to semitone offset within octave (C=0)
    private const SEMITONES = [
        'C'  => 0,  'C#' => 1,  'Db' => 1,
        'D'  => 2,  'D#' => 3,  'Eb' => 3,
        'E'  => 4,
        'F'  => 5,  'F#' => 6,  'Gb' => 6,
        'G'  => 7,  'G#' => 8,  'Ab' => 8,
        'A'  => 9,  'A#' => 10, 'Bb' => 10,
        'B'  => 11,
    ];

    // MIDI note number → frequency using equal temperament, A4 = 440 Hz
    public static function toHz(string $note): float
    {
        if (!preg_match('/^([A-G][b#]?)(\d)$/', $note, $m)) {
            throw new \InvalidArgumentException("Invalid note: $note");
        }

        $noteName = $m[1];
        $octave   = (int) $m[2];

        if (!isset(self::SEMITONES[$noteName])) {
            throw new \InvalidArgumentException("Unknown note name: $noteName");
        }

        $midi = (($octave + 1) * 12) + self::SEMITONES[$noteName];

        return 440.0 * (2 ** (($midi - 69) / 12));
    }
}
