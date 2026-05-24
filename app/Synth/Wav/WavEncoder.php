<?php

namespace App\Synth\Wav;

class WavEncoder
{
    private const BITS_PER_SAMPLE = 16;
    private const NUM_CHANNELS    = 1; // mono
    private const MAX_INT16       = 32767;

    /**
     * Encodes an array of float samples [-1, 1] to a binary WAV string.
     *
     * WAV structure:
     *   RIFF chunk descriptor (12 bytes)
     *   fmt  sub-chunk       (24 bytes)
     *   data sub-chunk       (8 bytes header + PCM data)
     */
    public static function encode(array $samples, int $sampleRate): string
    {
        $numSamples   = count($samples);
        $byteRate     = $sampleRate * self::NUM_CHANNELS * (self::BITS_PER_SAMPLE / 8);
        $blockAlign   = self::NUM_CHANNELS * (self::BITS_PER_SAMPLE / 8);
        $dataSize     = $numSamples * $blockAlign;
        $fileSize     = 36 + $dataSize; // 36 = rest of header after "RIFF" + size

        // RIFF chunk descriptor
        $header  = 'RIFF';
        $header .= pack('V', $fileSize);        // file size - 8
        $header .= 'WAVE';

        // fmt sub-chunk
        $header .= 'fmt ';
        $header .= pack('V', 16);               // sub-chunk size (16 for PCM)
        $header .= pack('v', 1);                // audio format: PCM = 1
        $header .= pack('v', self::NUM_CHANNELS);
        $header .= pack('V', $sampleRate);
        $header .= pack('V', $byteRate);
        $header .= pack('v', $blockAlign);
        $header .= pack('v', self::BITS_PER_SAMPLE);

        // data sub-chunk
        $header .= 'data';
        $header .= pack('V', $dataSize);

        // PCM data: convert floats to signed 16-bit integers
        $pcm = '';
        foreach ($samples as $sample) {
            // Clamp to [-1, 1] before converting
            $clamped = max(-1.0, min(1.0, $sample));
            $int16   = (int) round($clamped * self::MAX_INT16);
            $pcm    .= pack('v', $int16 & 0xFFFF); // unsigned 16-bit, little-endian
        }

        return $header . $pcm;
    }
}
