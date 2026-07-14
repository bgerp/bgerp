# imgcolor: CMYK separation of transitions (преливки) — design

Date: 2026-07-13
Status: approved direction (Approach C, hybrid three-way classification), refined
against prototype measurements.

## 1. Business requirement and settled semantics

Original: "Да отделим CMYK, т.е. да останат плътните цветове. Само преливките да
останат на CMYK. Когато е CMYK, изчисляваме колко е процента на четирите вида
цвята и ги събираме в акумулатор, който показва процентното съдържание на
подцветовете на CMYK."

Decisions settled with the requester:

1. **True separation.** Transition (gradient) pixels are excluded from the
   solid-color clustering. The existing color list reports only genuine solids;
   the new CMYK result covers only the transition area. Images without genuine
   transitions must keep today's solid output unchanged.
2. **CMYK output = normalized ink composition.** `C% + M% + Y% + K% = 100` over
   the accumulated ink of the transition area. Raw channel totals are kept in
   the result for auditability. Transition *area* coverage is reported
   separately so a small gradient is not presented as the whole image.
3. **Anti-aliased edge pixels are not transitions.** They remain in the solid
   path and are absorbed by the existing cluster merge machinery
   (`KMeansClusterer::mergeByDeltaE()` / `foldLowCoverage()`).
4. **Conversion quality over zero-dependency.** ICC-based conversion through
   Imagick when available and configured; documented mathematical conversion as
   fallback. No third-party ICC profile is bundled silently — profile paths are
   configuration; without them the math engine is used and recorded.
5. **Storage/UI.** New `cmykJson` text field on `imgcolor_Analyses`, new result
   section in `imgcolor_Demo`. Existing `colorsJson` schema and the LLM tool
   contract (`analyze_image_print_colors`) are unchanged.

## 2. Repository integration evidence

- `imgcolor_Analyzer` (imgcolor/Analyzer.class.php) is the only entry to the
  vendored library and already hand-wires the six pipeline components for the
  Imagick loader case (`makeAnalyzer()`), so wrapper-side orchestration of
  loader → cropper → classifier → clusterer → coverage is an established
  pattern.
- The vendored library under `imgcolor/lib/image-color-analyzer/` stays
  untouched ("Never edit files under lib/", docs/integration.md). New code
  consumes its public contracts: `Raster`, `ColorRGBA`, `ColorConverter`,
  `ColorHistogram`, `KMeansClusterer`, `PercentageCoverageCalculator`,
  `GdPngEncoder`, `WhiteBackgroundCropper`.
- Solid colors today are k-means clusters over a weighted RGB histogram
  (`ColorHistogram::build()` skips pixels with `alpha < alphaThreshold`,
  weights are pixel counts). The "existing color table" is the JSON list
  `[{color, coverage_percent}]` persisted in `imgcolor_Analyses.colorsJson`.
- There is no CMYK logic anywhere in the repository; CMYK JPEG input is
  explicitly rejected (`GdImageLoader::rejectUnsupportedJpeg()`).
- `ColorConverter` provides deterministic sRGB↔Lab (D65) and CIE76/CIE94
  delta-E. CIE76 is what the clusterer uses; the classifier uses the same
  metric for consistency (see §5 "Color difference").

## 3. Pixel classes — final semantics

Every pixel of the cropped raster gets exactly one class:

- **BACKGROUND** — `alpha < clusterAlphaThreshold` (default 8). Contributes to
  nothing, exactly like today's histogram behavior.
- **SOLID** — analyzed pixel whose local neighborhood shows no coherent color
  drift (see §4). Goes to the existing clustering path.
- **AA/EDGE** — analyzed pixel that changes locally but does not belong to a
  reconstructed transition region: anti-alias ramps (1–2 px), hard edge
  fringes, blurred edges up to ~2r px wide, JPEG ringing/block noise. Goes to
  the existing clustering path, where merge/fold absorbs it into its solid —
  the same place those pixels end up today.
- **TRANSITION** — analyzed pixel inside a coherent, spatially extended color
  ramp (linear/radial/multi-stop/irregular gradient, continuous-tone or
  photographic content, alpha fades). Excluded from clustering; accumulated
  into the CMYK result.

How specific inputs classify (validated by prototype fixtures):

| Input                          | Class outcome                                   |
|--------------------------------|-------------------------------------------------|
| Smooth gradient (any shape)    | TRANSITION (93–99% of area)                     |
| Hard edge between solids       | AA/EDGE (band ≈ sampling span)                  |
| 1–2 px anti-alias ramp         | AA/EDGE (0% false transition)                   |
| JPEG ringing / block noise     | SOLID or AA/EDGE (coherence test rejects it)    |
| Resampling blur ≤ ~6 px        | AA/EDGE (erosion radius kills narrow bands)     |
| Shadow / soft glow             | TRANSITION if wide and coherent — correct: it   |
|                                | cannot be printed as a spot color               |
| Alpha fade (color→transparent) | TRANSITION (alpha is a 4th channel, §6)         |
| Photograph / texture           | TRANSITION — correct for print separation       |
| Gradient < min coverage        | folded back to AA/EDGE by the guard             |

## 4. Classifier algorithm (selected variant)

Variants measured on 22 synthetic fixtures with ground truth (prototype,
scratchpad): V1 dual-threshold + adjacency cleanup; V2 erosion+reconstruction;
V3 = V2 + hard-edge flood barrier; V4 = V3 with a *directional coherence* test
replacing the plain magnitude test. V1 missed steep gradients and let 25% of a
q60 JPEG through; V2/V3 leaked 9–56% on blurred edges and JPEG; V4 scored 0%
false transitions on every solid/AA/JPEG fixture while keeping 93–99% recall on
all gradient families. V4 is selected.

Stage 1 — coherent drift ("CHANGING") mask, one pass, rolling 2s+1 row window:

For each analyzed pixel `p` and each axis (horizontal, vertical), take the
samples at distance `s` on both sides (skip the axis if either sample is
outside the image or BACKGROUND). With `F(q) = [L*, a*, b*, alpha/255*100]`:

    v1 = F(p) − F(p−s),  v2 = F(p+s) − F(p)
    magnitude  = (|v1| + |v2|) / 2
    coherence  = (v1·v2) / (|v1|·|v2|)
    axis is drifting  ⇔  magnitude ≥ noiseFloor  AND  coherence ≥ cosMin

`p` is CHANGING iff at least one axis drifts. Rationale: gradient steps
accumulate linearly with `s` and keep direction; JPEG noise, ringing and block
steps have random or opposing local directions and fail `cosMin`, and pixels
flanking a hard edge fail it too (one side is flat), which keeps the CHANGING
band tight around real ramps.

Stage 2 — erosion: a CHANGING pixel is a *seed* iff its (2r+1)² Chebyshev
window contains no SOLID analyzed pixel and at least `minSeed` CHANGING pixels.
Kills bands narrower than 2r+1 px (AA ramps, blurred edges, leftover noise
specks). Implemented with sliding column counts, O(N).

Stage 3 — reconstruction: 8-connected flood from seeds across CHANGING pixels,
blocked at pixels whose max 4-neighbor (span-1) difference exceeds `edgeCap`
(prevents leaking along connected AA outlines and across hard boundaries).
Non-reached CHANGING pixels become AA/EDGE.

Stage 4 — coverage guard: if `transitionCount / analyzedCount < minCoverage`,
all TRANSITION pixels are reclassified AA/EDGE and the CMYK result is empty.

Defaults (calibrated on the prototype fixture set, all configurable, §9):

| Parameter  | Default | Units                | Effect / rationale                                              |
|------------|---------|----------------------|-----------------------------------------------------------------|
| span s     | 4       | px                   | Signal gain ×4 vs noise; detectability bound ≈ floor/s ΔE/px    |
| noiseFloor | 1.0     | ΔE (CIE76+alpha)     | Below: SOLID. 1.0 catches ≥0.3 ΔE/px slopes, ignores dithering  |
| cosMin     | 0.4     | cosine               | 0.2 left 2.4% JPEG false transitions; 0.4 → 0% with no recall loss |
| r          | 3       | px                   | Min gradient core width 2r+1=7 px; r=4 killed a real 16px ramp  |
| minSeed    | 20      | px count             | Of 49-px window; rejects sparse speckle seeds                   |
| edgeCap    | 10.0    | ΔE                   | Flood barrier; ~2× a JND above any in-gradient per-px step      |
| minCoverage| 0.005   | fraction             | 10×10 gradient in 200×200 solid folds away; real gradients pass |

Known, accepted limitations (documented, tested as such): slopes below
~0.3 ΔE/px read as SOLID (graceful: they cluster as близки solids); transition
bands narrower than 7 px fold to AA/EDGE; the flat extremum ring of radial
gradients and the last ~s px at gradient ends read as AA/EDGE (recall 76–99%,
solids win ties by design).

## 5. Color difference choice

CIE76 over Lab, same as the clustering pipeline (`ColorConverter::deltaE()`),
extended with alpha as a fourth component scaled to percent
(`Δalpha/255*100`). CIE94 exists in the converter but weights chroma
differences *down*, which would only lower gradient recall in chromatic ramps;
the classifier compares *neighboring* (perceptually close) colors where CIE76
is adequate. Prototype scores confirmed no need for a costlier metric.

Lab values are memoized per packed 24-bit RGB with a hard cap (2^16 entries,
mirroring `WhiteBackgroundCropper::MEMO_CAP`); colors beyond the cap are
computed uncached — correct, just slower on adversarial many-color inputs.

## 6. Transparency rules

- `alpha < clusterAlphaThreshold` (default 8): BACKGROUND everywhere — not
  classified, not clustered, not accumulated; identical to today's histogram
  skip rule.
- `alpha ≥ threshold`: the pixel is analyzed. Classification treats alpha as a
  4th color channel, so color-constant alpha ramps classify as TRANSITION and
  abrupt alpha edges (AA on transparent background) classify as AA/EDGE.
- Solid path: unchanged — analyzed pixels enter the histogram at full weight
  regardless of partial alpha (existing behavior, preserved for parity).
- CMYK accumulation: pixel weight = `alpha / 255` (a 50%-transparent pixel
  deposits half the ink). Classification weight is discrete (1 for TRANSITION,
  0 otherwise) — prototype showed no need for confidence weighting.
- GD provides straight (non-premultiplied) alpha; 7-bit GD alpha is already
  expanded to 0–255 by `GdRaster::colorAt()`. Indexed transparency is
  normalized to RGBA by `GdImageLoader::normalizeTruecolorWithAlpha()`.
- Fully transparent image: everything BACKGROUND → empty solid list (as today)
  and empty CMYK result.

## 7. RGB→CMYK conversion

Engine selection (`IMGCOLOR_CMYK_ENGINE`):

- `auto` (default): use ICC when the Imagick extension is loaded **and** both
  profile paths are configured and readable; otherwise math.
- `imagick`: require the ICC path, error loudly if unavailable.
- `math`: always use the formula.

ICC path: unique transition colors (histogram-binned, so hundreds not
millions) are written into a small Imagick canvas, tagged with the configured
RGB source profile, converted with `profileImage()` to the configured CMYK
profile (LCMS relative-colorimetric by ImageMagick default), and read back as
0–1 CMYK fractions. Embedded source profiles are *not* honored in v1: the GD
decode path has already stripped them, and the whole pipeline (crop, cluster)
assumes sRGB; recorded as `source_profile: "assumed-sRGB"` plus the configured
profile filename. No profile files ship with the package; the config
documents where to obtain e.g. ECI ISOcoated_v2_300 and the result metadata
records profile file basename + MD5 for reproducibility.

Math fallback (documented as approximate, not press-accurate):

    R' = R/255, G' = G/255, B' = B/255
    K  = 1 − max(R', G', B')
    if K = 1: C = M = Y = 0
    else:     C = (1−R'−K)/(1−K), M = (1−G'−K)/(1−K), Y = (1−B'−K)/(1−K)

Result metadata always includes: `engine` (`math` | `imagick-icc`),
`source_profile`, `destination_profile` (basename + md5, or null),
`fallback` (true when auto degraded to math), `version` (algorithm version,
starts at 1).

## 8. Accumulator and output

Per TRANSITION pixel with color (R,G,B) and alpha a:

    w        = a / 255                  (classification weight = 1, discrete)
    C_raw   += C(R,G,B) × w             (per unique color via histogram bins:
    M_raw   += M(R,G,B) × w              accumulate Σw per bin, convert each
    Y_raw   += Y(R,G,B) × w              bin color once, multiply)
    K_raw   += K(R,G,B) × w

    inkTotal = C_raw + M_raw + Y_raw + K_raw

If `inkTotal > 0`: `X_percent = 100 × X_raw / inkTotal`, rounded to one
decimal with the largest-remainder method (same technique as
`PercentageCoverageCalculator`) so the four values sum to exactly 100.0.
If `inkTotal = 0` (e.g. a white→transparent fade): all four percentages are
0.0 and `ink_total` is 0 — no division, no misleading composition.

`cmykJson` schema (empty transitions → `null` field / no section rendered):

    {
      "transition_coverage_percent": 31.2,   // TRANSITION px / analyzed px ×100
      "composition_percent": {"c": 41.3, "m": 30.1, "y": 20.4, "k": 8.2},
      "ink_total": 12345.678,                // Σ raw channels, alpha-weighted px
      "raw_channels": {"c": ..., "m": ..., "y": ..., "k": ...},
      "conversion": {
        "engine": "math",
        "source_profile": "assumed-sRGB",
        "destination_profile": null,
        "fallback": true,
        "version": 1
      }
    }

The solid list (`colorsJson`) keeps its schema; its percentages are relative to
the solid+AA analyzed area (they still sum to 100).

## 9. Configuration

New `IMGCOLOR_*` constants on `imgcolor_Setup` (global config only in v1 — not
added to `imgcolor_Profiles`, which stays a mirror of the *library* options):

| Constant                        | Type   | Default | Range      |
|---------------------------------|--------|---------|------------|
| `IMGCOLOR_TRANS_SPAN`           | int    | 4       | 1..8       |
| `IMGCOLOR_TRANS_NOISE_DELTAE`   | double | 1.0     | >0..20     |
| `IMGCOLOR_TRANS_COHERENCE_MIN`  | double | 0.4     | -1..1      |
| `IMGCOLOR_TRANS_AA_RADIUS`      | int    | 3       | 1..8       |
| `IMGCOLOR_TRANS_MIN_SEED`       | int    | 20      | 1..289     |
| `IMGCOLOR_TRANS_EDGE_DELTAE`    | double | 10.0    | >0..200    |
| `IMGCOLOR_TRANS_MIN_COVERAGE`   | double | 0.005   | 0..<1      |
| `IMGCOLOR_CMYK_ENGINE`          | enum   | auto    | auto/math/imagick |
| `IMGCOLOR_CMYK_ICC_RGB_PROFILE` | varchar| ''      | file path  |
| `IMGCOLOR_CMYK_ICC_CMYK_PROFILE`| varchar| ''      | file path  |

Invalid values throw `InvalidArgumentException` with the field name at options
build time, mirroring the library's `CropOptions`/`ClusterOptions` style, and
are surfaced by `imgcolor_Setup::checkConfig()`.

## 10. Components and integration

New wrapper classes (bgERP conventions, no edits under `lib/`):

- `imgcolor_TransitionClassifier` — stages 1–4; input a library `Raster` +
  options array; output an object: class mask (byte string, row-major),
  `analyzedCount`, `transitionCount`, plus a debug `renderMaskPng()` used only
  by tests/CLI (solid=gray, AA=red, transition=blue, background=transparent).
- `imgcolor_MaskedRaster` — implements `ImageColorAnalyzer\Contracts\Raster`;
  wraps raster + mask; `pixels()` yields only non-TRANSITION pixels, so
  `ColorHistogram` sees the solid+AA subset and its `total` (the percentage
  denominator) is the solid+AA count. Used *only* when transitionCount > 0 —
  otherwise the original raster object is passed through untouched, which
  makes the no-transition path structurally identical to today (verified by
  byte-comparison tests, not just asserted).
- `imgcolor_CmykConverter` — engine selection, ICC/math conversion of a list
  of RGB triplets, metadata block.
- `imgcolor_CmykAccumulator` — streams TRANSITION pixels into per-color bins,
  applies the converter, normalizes with largest-remainder, builds `cmykJson`.
- `imgcolor_Analyzer::processSeparated($source, $options = null)` — orchestrates
  load → crop → classify → (masked) cluster → coverage → CMYK; returns an
  object `{json, cmykJson, croppedImage, boundingBox, wasCropped}` mirroring
  `ProcessedImageResult` plus `cmykJson`. Existing `analyze*/process*` methods
  and the LLM tool are unchanged.

Modified: `imgcolor_Setup` (constants, version 0.4), `imgcolor_Analyses`
(`cmykJson` field — bgERP auto-migrates added fields on setup, no manual
migration; `createFromResult()` gains a `$cmykJson` param, default null for
backward compatibility), `imgcolor_Demo` (uses `processSeparated()`, renders
the CMYK section, persists `cmykJson`), `docs/integration.md`.

## 11. Performance

- Passes over the cropped raster: classification stage 1 (rolling 2s+1 rows of
  packed ints), erosion (sliding counts over mask strings), flood (only over
  CHANGING pixels, random access via `Raster::pixelAt()` for the edge cap),
  CMYK accumulation (one streaming pass over TRANSITION pixels), clustering
  (existing pass, unchanged). Each pass is O(N); prototype cost ≈ 0.9 s per
  megapixel — same order as the existing crop+histogram passes.
- Peak additional memory: 2 mask strings (1 byte/px each), one (2s+1)×W int
  row window, the bounded Lab memo, and the flood queue (worst case O(N) ints,
  only on gradient-dominated images). At the loader's 64 MP cap: ~128 MB masks
  — same order as GD's own bitmap; acceptable, and typical print-logo inputs
  are 1–16 MP.
- ICC conversion cost is per *unique binned color* (≤ 32³ bins), not per pixel.

## 12. Test strategy

Framework-free CLI regression (`imgcolor/tests/cli_separation.php`, PHP 8.2+ +
GD, no bgERP DB) generating deterministic fixtures in code, mirroring
`cli_parity.php` style:

1. Classifier fixtures (from the prototype set): solid single/multi, AA logo on
   white/transparent, linear/shallow/steep/radial/multi-stop/diagonal/gray
   gradients, gradient at hard edge, 1px/2px AA ramps, blurred edge, JPEG q60
   flat + q55 ringing, sub-coverage gradient, white→transparent and
   color→transparent fades, white-containing gradient, mixed scene, plasma
   texture — asserting class-mask statistics (transition coverage, false
   transition rate) within tolerances.
2. Separation invariants: solid-only images → separated `colorsJson` byte-equal
   to the legacy path and `cmykJson` empty; mixed image → solid list free of
   gradient centroids, CMYK composition sums to exactly 100.0; white-only fade
   → zero-ink case; fully transparent → both empty; 1×1 and 3×3 images (below
   span) → all solid, no errors.
3. Math converter unit vectors: pure R/G/B/black/white/gray → exact expected
   CMYK; largest-remainder normalization sums.
4. ICC engine: metadata/fallback logic tested without Imagick (auto→math with
   `fallback: true`); conversion itself exercised only where Imagick exists
   (skipped with a notice otherwise — recorded as a validation limitation).
5. Web unit tests (`imgcolor_tests_Separation`) for the fileman/DB-backed flow:
   `processSeparated()` result persisted via `imgcolor_Analyses`, `cmykJson`
   round-trip, `renderRec()` output contains the CMYK section.
6. Performance probe inside the CLI test (1200×800 mixed fixture, wall-clock
   report, no hard assert).

## 13. Risks and follow-ups

- Thresholds are calibrated on synthetic fixtures; real-world scans may need
  per-install tuning — all knobs are in pack config, and the debug mask PNG
  (test/CLI only) makes disputes inspectable.
- Percentages in the solid list change meaning for gradient-containing images
  (denominator = solid area). Communicated in integration.md; history records
  (`imgcolor_Analyses`) are not migrated — old records keep old semantics.
- The LLM tool still reports the legacy un-separated view; exposing the
  separated result to the tool is a follow-up once the JSON contract consumers
  are known.
- Профили (imgcolor_Profiles) do not carry the new thresholds in v1; adding
  them (with form fields and calibration snapshot keys) is a follow-up.
- Honoring embedded ICC source profiles requires an Imagick-first decode path —
  out of scope; recorded in conversion metadata.
