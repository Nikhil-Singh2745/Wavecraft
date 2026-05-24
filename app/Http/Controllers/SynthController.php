<?php

namespace App\Http\Controllers;

use App\Synth\Language\Lexer;
use App\Synth\Language\Parser;
use App\Synth\Language\Ast\ProgramNode;
use App\Synth\Audio\Renderer;
use App\Synth\Wav\WavEncoder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SynthController extends Controller
{
    public function index()
    {
        return view('synth', ['presets' => $this->presets()]);
    }

    public function synthesize(Request $request): JsonResponse
    {
        $code = $request->input('code', '');

        if (strlen($code) > 8000) {
            return response()->json(['error' => 'Code too long (max 8000 chars)'], 422);
        }

        try {
            $lexer    = new Lexer($code);
            $tokens   = $lexer->tokenize();
            $parser   = new Parser($tokens);
            $program  = $parser->parse();
            $renderer = new Renderer();
            $samples  = $renderer->render($program);

            if (empty($samples)) {
                return response()->json(['error' => 'No audio was generated — add some notes!'], 422);
            }

            $wav = WavEncoder::encode($samples, Renderer::getSampleRate());

            // Downsample waveform for visualization: ~1000 representative points
            $waveform = $this->downsampleWaveform($samples, 1000);

            // Return WAV as base64 so we can embed it in JSON alongside waveform data
            return response()->json([
                'wav'      => base64_encode($wav),
                'waveform' => $waveform,
                'duration' => round(count($samples) / Renderer::getSampleRate(), 2),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /** Downsample the sample array to $points values for waveform display */
    private function downsampleWaveform(array $samples, int $points): array
    {
        $count  = count($samples);
        $result = [];
        $step   = $count / $points;

        for ($i = 0; $i < $points; $i++) {
            $start = (int) ($i * $step);
            $end   = (int) (($i + 1) * $step);
            $end   = min($end, $count);

            // Take the peak absolute value in this window (looks better than average)
            $peak = 0.0;
            for ($j = $start; $j < $end; $j++) {
                if (abs($samples[$j]) > abs($peak)) {
                    $peak = $samples[$j];
                }
            }
            $result[] = round($peak, 4);
        }

        return $result;
    }

    private function presets(): array
    {
        return [
            'C Major Scale' => <<<DSL
                tempo 120
                instrument sine

                # C major scale ascending
                play C4 quarter
                play D4 quarter
                play E4 quarter
                play F4 quarter
                play G4 quarter
                play A4 quarter
                play B4 quarter
                play C5 half

                # And back down
                play B4 quarter
                play A4 quarter
                play G4 quarter
                play F4 quarter
                play E4 quarter
                play D4 quarter
                play C4 half
                DSL,

            'Arpeggio' => <<<DSL
                tempo 140
                instrument triangle

                # Broken C major chord going up
                sequence [C4, E4, G4, C5] eighth
                sequence [C4, E4, G4, C5] eighth
                sequence [G3, B3, D4, G4] eighth
                sequence [G3, B3, D4, G4] eighth
                sequence [F3, A3, C4, F4] eighth
                sequence [F3, A3, C4, F4] eighth
                sequence [C4, E4, G4, C5] quarter
                DSL,

            'Ode to Joy' => <<<DSL
                tempo 108
                instrument square

                play E4 quarter
                play E4 quarter
                play F4 quarter
                play G4 quarter
                play G4 quarter
                play F4 quarter
                play E4 quarter
                play D4 quarter
                play C4 quarter
                play C4 quarter
                play D4 quarter
                play E4 quarter
                play E4 half
                rest quarter
                play D4 half
                rest quarter
                DSL,

            'Chord Progression' => <<<DSL
                tempo 90
                instrument saw

                # I - IV - V - I in C major
                chord [C4, E4, G4] whole
                chord [F4, A4, C5] whole
                chord [G4, B4, D5] whole
                chord [C4, E4, G4] whole
                DSL,

            'Glitch Sequence' => <<<DSL
                tempo 180
                instrument saw

                sequence [C4, C#4, D4, D#4, E4, F4, F#4, G4] sixteenth
                instrument square
                sequence [G4, G#4, A4, A#4, B4, C5] sixteenth

                instrument triangle
                chord [C4, G4] eighth
                rest sixteenth
                chord [D4, A4] eighth
                rest sixteenth

                instrument sine
                sequence [C5, B4, A4, G4, F4, E4, D4, C4] sixteenth
                DSL,
        ];
    }
}
