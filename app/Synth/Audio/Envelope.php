<?php

namespace App\Synth\Audio;

class Envelope
{
    // ADSR as fractions of totalSamples
    private const ATTACK_FRAC  = 0.05;
    private const DECAY_FRAC   = 0.10;
    private const SUSTAIN_LEVEL = 0.70;
    private const RELEASE_FRAC = 0.15;

    /**
     * Returns the amplitude multiplier [0.0, 1.0] at sample position $i
     * within a note of $totalSamples length.
     */
    public static function amplitude(int $i, int $totalSamples): float
    {
        $attack  = (int) ($totalSamples * self::ATTACK_FRAC);
        $decay   = (int) ($totalSamples * self::DECAY_FRAC);
        $release = (int) ($totalSamples * self::RELEASE_FRAC);
        $sustain = $totalSamples - $attack - $decay - $release;

        if ($i < $attack) {
            // Linear ramp up 0 → 1
            return $i / max($attack, 1);
        }

        if ($i < $attack + $decay) {
            // Linear ramp down 1 → SUSTAIN_LEVEL
            $t = ($i - $attack) / max($decay, 1);
            return 1.0 - $t * (1.0 - self::SUSTAIN_LEVEL);
        }

        if ($i < $attack + $decay + $sustain) {
            return self::SUSTAIN_LEVEL;
        }

        // Release: linear ramp down SUSTAIN_LEVEL → 0
        $releaseStart = $attack + $decay + $sustain;
        $t = ($i - $releaseStart) / max($release, 1);
        return self::SUSTAIN_LEVEL * (1.0 - $t);
    }
}
