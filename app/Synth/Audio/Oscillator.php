<?php

namespace App\Synth\Audio;

class Oscillator
{
    // Each method returns a float in [-1.0, 1.0]

    public static function sine(float $freq, int $sampleRate, int $i): float
    {
        return sin(2.0 * M_PI * $freq * $i / $sampleRate);
    }

    public static function square(float $freq, int $sampleRate, int $i): float
    {
        return self::sine($freq, $sampleRate, $i) >= 0 ? 1.0 : -1.0;
    }

    public static function saw(float $freq, int $sampleRate, int $i): float
    {
        $t = $freq * $i / $sampleRate;
        return 2.0 * ($t - floor($t)) - 1.0;
    }

    public static function triangle(float $freq, int $sampleRate, int $i): float
    {
        $t = $freq * $i / $sampleRate;
        return 4.0 * abs($t - floor($t + 0.5)) - 1.0;
    }

    public static function generate(string $waveform, float $freq, int $sampleRate, int $i): float
    {
        return match ($waveform) {
            'sine'     => self::sine($freq, $sampleRate, $i),
            'square'   => self::square($freq, $sampleRate, $i),
            'saw'      => self::saw($freq, $sampleRate, $i),
            'triangle' => self::triangle($freq, $sampleRate, $i),
            default    => throw new \InvalidArgumentException("Unknown waveform: $waveform"),
        };
    }
}
