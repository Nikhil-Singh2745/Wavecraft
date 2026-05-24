<?php

namespace App\Synth\Audio;

use App\Synth\Language\Ast\{
    ProgramNode, TempoNode, InstrumentNode,
    NoteNode, RestNode, ChordNode, SequenceNode
};

class Renderer
{
    private const SAMPLE_RATE = 44100;
    private const MASTER_GAIN = 0.6;

    private const DURATION_BEATS = [
        'whole'     => 4.0,
        'half'      => 2.0,
        'quarter'   => 1.0,
        'eighth'    => 0.5,
        'sixteenth' => 0.25,
    ];

    private int    $tempo    = 120;
    private string $waveform = 'sine';

    /** @var float[] */
    private array $samples = [];

    public function render(ProgramNode $program): array
    {
        $this->samples  = [];
        $this->tempo    = 120;
        $this->waveform = 'sine';

        foreach ($program->statements as $node) {
            match (true) {
                $node instanceof TempoNode      => $this->tempo = $node->bpm,
                $node instanceof InstrumentNode => $this->waveform = $node->waveform,
                $node instanceof NoteNode       => $this->renderNote($node->note, $node->duration),
                $node instanceof RestNode       => $this->renderRest($node->duration),
                $node instanceof ChordNode      => $this->renderChord($node->notes, $node->duration),
                $node instanceof SequenceNode   => $this->renderSequence($node->notes, $node->duration),
                default                         => null,
            };
        }

        return $this->samples;
    }

    private function durationToSamples(string $duration): int
    {
        $beats   = self::DURATION_BEATS[$duration] ?? 1.0;
        $seconds = $beats * (60.0 / $this->tempo);
        return (int) round($seconds * self::SAMPLE_RATE);
    }

    private function renderNote(string $note, string $duration): void
    {
        $freq         = NoteFrequency::toHz($note);
        $totalSamples = $this->durationToSamples($duration);

        for ($i = 0; $i < $totalSamples; $i++) {
            $sample          = Oscillator::generate($this->waveform, $freq, self::SAMPLE_RATE, $i);
            $envelope        = Envelope::amplitude($i, $totalSamples);
            $this->samples[] = $sample * $envelope * self::MASTER_GAIN;
        }
    }

    private function renderRest(string $duration): void
    {
        $count = $this->durationToSamples($duration);
        for ($i = 0; $i < $count; $i++) {
            $this->samples[] = 0.0;
        }
    }

    private function renderChord(array $notes, string $duration): void
    {
        $totalSamples = $this->durationToSamples($duration);
        $freqs        = array_map(fn($n) => NoteFrequency::toHz($n), $notes);
        $noteCount    = count($freqs);

        for ($i = 0; $i < $totalSamples; $i++) {
            $mixed = 0.0;
            foreach ($freqs as $freq) {
                $mixed += Oscillator::generate($this->waveform, $freq, self::SAMPLE_RATE, $i);
            }
            $envelope        = Envelope::amplitude($i, $totalSamples);
            $this->samples[] = ($mixed / sqrt($noteCount)) * $envelope * self::MASTER_GAIN;
        }
    }

    private function renderSequence(array $notes, string $duration): void
    {
        $noteCount    = count($notes);
        $totalSamples = $this->durationToSamples($duration);
        $perNote      = (int) round($totalSamples / $noteCount);

        foreach ($notes as $note) {
            $freq = NoteFrequency::toHz($note);
            for ($i = 0; $i < $perNote; $i++) {
                $sample          = Oscillator::generate($this->waveform, $freq, self::SAMPLE_RATE, $i);
                $envelope        = Envelope::amplitude($i, $perNote);
                $this->samples[] = $sample * $envelope * self::MASTER_GAIN;
            }
        }
    }

    public static function getSampleRate(): int
    {
        return self::SAMPLE_RATE;
    }
}
