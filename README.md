# WaveCraft

A web-based audio synthesizer that takes music written in a custom domain-specific language and renders it as a downloadable, playable WAV file. No external audio libraries are involved - the synthesis pipeline is implemented entirely in PHP.

---

## What it does

You write music in a small notation language directly in the browser. The server lexes and parses that code into an AST, walks the AST to generate raw PCM float samples using oscillator math, applies ADSR envelopes, encodes the result as a binary WAV file, and returns it alongside waveform data for visualization. The frontend plays the audio through the HTML5 Audio API and renders a waveform on canvas.

---

## Why this exists

I wanted to build something technically non-trivial in PHP that isn't a CRUD application. Audio synthesis in PHP is unusual enough to be interesting - almost nobody uses PHP for DSP work. The project touches three distinct areas: language design (lexer + recursive descent parser), digital signal processing (oscillators, envelope shaping, mixing), and binary file construction (WAV format via `pack()`).

---

## Tech stack

- PHP 8.2
- Laravel 12 (routing, controllers, Blade templating)
- Vanilla JS frontend - no build step, no npm, no framework
- HTML5 Canvas for waveform visualization
- HTML5 Audio API for playback

---

## Running locally

**Requirements:** PHP 8.2+, Composer

```bash
git clone https://github.com/your-username/wavecraft.git
cd wavecraft
composer install
cp .env.example .env
php artisan key:generate
php artisan serve --port=8000
```

Open `http://localhost:8000`. Pick a preset or write your own DSL code and click Synthesize.

---

## Deploying

The app is containerized. A `Dockerfile` is included in the repository.

**Render (free tier):**
1. Push the repo to GitHub
2. Create a new Web Service on Render, select the repository, and set Runtime to **Docker**
3. Add environment variables: `APP_ENV=production`, `APP_KEY` (generate with `php artisan key:generate --show`), `APP_DEBUG=false`, `SESSION_DRIVER=file`, `LOG_CHANNEL=stderr`
4. Deploy - first build takes 3–5 minutes

The free tier spins down after 15 minutes of inactivity. Cold start on the next request takes ~30 seconds. Storage is ephemeral, which does not matter here since there is nothing to persist.

**Locally via Docker:**
```bash
docker build -t wavecraft .
docker run -e APP_KEY=$(php artisan key:generate --show) -p 8000:8000 wavecraft
```

---

## DSL syntax

```
tempo 120
instrument sine

play C4 quarter
play E4 eighth
rest eighth
chord [C4, E4, G4] half
sequence [C4, D4, E4, F4, G4] sixteenth
```

**Statements:**
- `tempo <bpm>` - set tempo, 20–400 BPM
- `instrument <sine|square|saw|triangle>` - set waveform type
- `play <note> <duration>` - single note (e.g. `C4`, `A#3`, `Db5`)
- `rest <duration>` - silence
- `chord [n, n, ...] <duration>` - notes played simultaneously
- `sequence [n, n, ...] <duration>` - notes played in rapid succession, total time divided evenly

**Durations:** `whole`, `half`, `quarter`, `eighth`, `sixteenth`

**Comments:** `# anything after a hash is ignored`

---

## Project structure

```
app/Synth/
  Language/
    Lexer.php          - tokenizes the DSL source string
    Parser.php         - recursive descent parser → AST
    Token.php          - Token value object
    TokenType.php      - enum of token types
    Ast/               - one file per AST node class
  Audio/
    Oscillator.php     - sine, square, sawtooth, triangle waveform generators
    Envelope.php       - ADSR amplitude envelope
    NoteFrequency.php  - note name → Hz (equal temperament, A4 = 440 Hz)
    Renderer.php       - walks AST, emits PCM float samples
  Wav/
    WavEncoder.php     - float samples → 16-bit PCM → RIFF WAV binary

app/Http/Controllers/SynthController.php
resources/views/synth.blade.php
routes/web.php
```

---

## FAQ

**Does it support multiple tracks or stereo output?**
No. Everything is mixed into a single mono channel. There is no concept of tracks, panning, or layering independent parts.

**Can I use it to compose real music?**
Within limits. The DSL can express melodies, chord progressions, and arpeggios. It cannot express dynamics (velocity), articulation, pitch bend, or anything time-variant within a note. It is a toy synthesizer, not a DAW.

**Why does the audio sometimes have a metallic or aliased quality?**
Square and sawtooth waveforms contain harmonics that extend above the Nyquist frequency (22050 Hz at 44100 Hz sample rate). There is no anti-aliasing filter on the oscillators. This produces some aliasing distortion, which is audible particularly at higher octaves.

**Can I add more instruments?**
Yes. Add a new case to `Oscillator::generate()` in `app/Synth/Audio/Oscillator.php`, add it to the lexer's keyword list in `Lexer.php`, and it will be available as `instrument <name>`.

**Why PHP for this?**
Because using the wrong tool for a job in an interesting way is more educational than using the right tool in a boring way.

---

## Known limitations

**Performance:** PHP is not fast at tight numeric loops. Generating a 30-second piece at 44100 Hz means roughly 1.3 million iterations of the oscillator loop. On a low-end server this takes a few seconds. There is no streaming or incremental rendering - the entire WAV is computed synchronously before any response is sent.

**No polyphony within a single `play` statement:** Only `chord` allows simultaneous notes. A `play` statement always produces a single wave. You cannot layer two `play` statements in time.

**No volume control:** There is no `volume` or `gain` command in the DSL. The master gain is hardcoded at 0.6. Loud chords can clip if you stack many notes; the mixing normalization (`/ sqrt(noteCount)`) helps but is not a substitute for a proper limiter.

**No WAV download button:** The audio is playable in the browser via the HTML5 audio element, but there is no explicit download link. You can right-click the audio player and use "Save audio as" in most browsers.

**The waveform display is approximate:** The oscilloscope visualization shows ~1000 peak-picked points, not the full sample data. It is representative, not exact.

**No persistence:** Nothing is saved. Closing the browser tab discards your code.

---
