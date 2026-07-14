# imgcolor - integration notes

## What this package is
A BGERP wrapper around the vendored, unmodified `image-color-analyzer`
library. It crops the near-white background of a PNG/JPEG and reports the
principal print colors with coverage percentages. Since v0.4 it can also
*separate* solid (spot-printable) colors from transitions (преливки):
gradient/continuous-tone pixels are excluded from the solid list and
accumulated into a CMYK ink-composition result instead. Design and calibrated
thresholds: `docs/superpowers/specs/2026-07-13-imgcolor-cmyk-separation-design.md`.

## Entry points
- Service (any PHP caller / other module):
  - `imgcolor_Analyzer::analyzePath($path)` / `::analyze($bytesOrSource)`
  - `imgcolor_Analyzer::analyzeAsJson(...)` / `::analyzePathAsJson(...)`
  - `imgcolor_Analyzer::process($bytesOrSource)` -> ProcessedImageResult (json + cropped PNG bytes)
  - `imgcolor_Analyzer::processSeparated($bytesOrSource)` -> stdClass
    `{json, cmykJson, croppedImage, boundingBox, wasCropped}`. `json` lists
    only solid colors (percentages over the solid+AA area, still summing to
    100); `cmykJson` is null when the image has no transitions, otherwise:

    ```json
    {
      "transition_coverage_percent": 31.2,
      "composition_percent": {"c": 41.3, "m": 30.1, "y": 20.4, "k": 8.2},
      "ink_total": 12345.678,
      "raw_channels": {"c": 1.0, "m": 2.0, "y": 3.0, "k": 4.0},
      "conversion": {
        "engine": "math",
        "source_profile": "assumed-sRGB",
        "destination_profile": null,
        "fallback": true,
        "version": 1
      }
    }
    ```

    `composition_percent` sums to exactly 100.0 (largest-remainder rounding);
    when `ink_total` is 0 (e.g. a white-to-transparent fade) all four values
    are 0.0. For images without genuine transitions `json` is byte-identical
    to the legacy `process()` output (regression-tested).
- LLM tool:
  - `imgcolor_Analyzer::analyzeFileHandle($fh)` -> JSON
  - `imgcolor_Analyzer::getToolDefinition()` -> `{name, description, parameters}`
- UI: `imgcolor_Demo` (menu `Инструменти -> Цветове за печат`) + a fileman
  file-action button on PNG/JPEG files. The analysis form exposes the complete
  calibration set: selecting a profile loads its values, and the displayed
  values can be adjusted before the run.
- Calibration profiles: `imgcolor_Profiles` (menu `Инструменти -> Профили за
  калибриране`) - named, reusable threshold sets. The analysis form can save
  the displayed values as a new profile or update the selected profile. The
  manager remains the place to list, rename, edit notes, and delete profiles.
  Optional: the global `IMGCOLOR_*` constants remain the zero-config default;
  a profile is an explicit override, selected per-run in `imgcolor_Demo` or
  passed to `imgcolor_Analyzer::buildOptions($profileRec)` by another caller.
- Analysis history: `imgcolor_Analyses` (menu `Инструменти -> История на
  анализите`) - every completed run (via `imgcolor_Demo`) is persisted:
  source image, profile selected (if any), exact calibration snapshot, color
  JSON, and cropped image. The profile records where values came from; the
  snapshot records what the analyzer actually used, even after an inline
  adjustment or a later profile edit. Records are created only in code
  (`imgcolor_Analyses::createFromResult()`), never through a manual add form.

## Upload policy

The package bucket `imgcolorImages` accepts only `jpg`, `jpeg`, and `png`
extensions. Fileman derives the browser `accept` attribute and applies the same
extension policy to its server-side upload paths. Extension matching is
case-insensitive.

This is an upload filter, not a content-trust boundary. The analyzer separately
sniffs and decodes the bytes through the vendored library, so a renamed or
malformed file with an allowed extension is still rejected. The restriction is
local to `imgcolorImages`; no global Fileman MIME or extension behavior changes.

## Interactive calibration workflow

1. Open `Инструменти -> Цветове за печат`.
2. Use the global configuration shown initially, or select a saved profile to
   load its calibration values.
3. Adjust any displayed value and analyze an image. The submitted values are
   used directly and saved in the analysis snapshot.
4. To retain the displayed values, enter a short identifier and name and choose
   `Запази като профил`. An image is not required for this action.
5. With an editable profile selected, choose `Обнови профила` to replace only
   its calibration values. Its name and notes are preserved.
6. Use `Профили` for listing, renaming, editing notes, and deleting profiles.

Profiles are shared records governed by the existing `imgcolor`, `ceo`, and
`admin` rights. There is no per-user ownership or implicit default profile.

## Wrapper to library map
| BGERP wrapper | Library object/method | Notes |
|---|---|---|
| `imgcolor_Analyzer::registerAutoload()` | PSR-4 prefix `ImageColorAnalyzer\` -> `lib/image-color-analyzer/src/` | Prepended autoloader; no Composer runtime in BGERP. |
| `imgcolor_Analyzer::buildOptions($profileRec = null)` | `Options\AnalyzerOptions`, `CropOptions`, `ClusterOptions` | No argument: reads `IMGCOLOR_*` through `imgcolor_Setup::get(...)`. With an `imgcolor_Profiles` record: builds from its fields instead. Both paths funnel through `imgcolor_Calibration::buildOptions()`. |
| `imgcolor_Analyzer::makeAnalyzer()` | `PublicAPI\AnalyzerFactory::createDefault()` or explicit Imagick wiring | GD is default; Imagick still depends on GD for PNG decode/encode paths. |
| `imgcolor_Analyzer::analyze*()` | `PublicAPI\ImageColorAnalyzer::analyze*()` | Returns the library array/JSON unchanged. |
| `imgcolor_Analyzer::process*()` | `PublicAPI\ImageColorAnalyzer::process*()` | Returns the library `ProcessedImageResult` DTO unchanged. |
| `imgcolor_Analyzer::analyzeFileHandle($fh)` | `fileman::extractStr($fh)` -> `analyzeAsJson($bytes)` | Machine/LLM entry point; input is a fileman handle and output is JSON. |
| `imgcolor_Demo` | `imgcolor_Analyzer::process($bytes, $options)` | Interactive analysis/calibration UI and fileman file-action button; persists every run via `imgcolor_Analyses::createFromResult()`. |
| `imgcolor_Calibration` | `Options\CropOptions`, `Options\ClusterOptions`, `Options\AnalyzerOptions` | Authoritative field/default/record mapping through `getDefaultValues()`, `getValues()`, `applyValues()`, and `buildOptions()`. Standalone-testable: `php imgcolor/tests/cli_calibration.php`. |
| `imgcolor_Profiles` | n/a (bgERP `core_Manager`) | CRUD for named calibration profiles; `on_BeforeSave` validates through `imgcolor_Calibration::buildOptions()`. |
| `imgcolor_Analyses` | n/a (bgERP `core_Manager`) | Append-mostly history with optional profile attribution and exact calibration JSON; written by `imgcolor_Demo`, not by a manual form. |

## Tool definition (function-calling)
```json
{
  "name": "analyze_image_print_colors",
  "description": "Crop the near-white background of a PNG/JPEG stored in fileman and return the principal print colors with coverage percentages.",
  "parameters": {
    "type": "object",
    "properties": {
      "fileHandle": {
        "type": "string",
        "description": "fileman handle (fh) of the image"
      }
    },
    "required": ["fileHandle"]
  }
}
```

Output: `[{ "color": "#RRGGBB", "coverage_percent": 42.5 }, ...]`
(library JSON, verbatim).

If the LLM harness expects a different envelope (for example Anthropic
`input_schema`), adjust `imgcolor_Analyzer::getToolDefinition()` only. Nothing
else changes.

## Config
All tuning is `IMGCOLOR_*` on `imgcolor_Setup` (pack config UI). Defaults equal
the library defaults, so an unconfigured install matches the library exactly.
`IMGCOLOR_LOADER` picks GD (default) or Imagick. GD is required either way.

Profiles (`imgcolor_Profiles`) mirror this same field list (minus the loader
choice, which stays a global/infra setting) for per-run overrides. The two
are intentionally parallel, not derived from one another - one is a single
global scalar config, the other a multi-row table - `imgcolor_Calibration` is
the one place both funnel through before reaching the library, so they can
never drift in *how* a value becomes an `AnalyzerOptions`, only in *where the
value comes from*.

| Constant | Default | Maps to |
|---|---:|---|
| `IMGCOLOR_LOADER` | `gd` | Loader selection (`gd` or `imagick`) |
| `IMGCOLOR_CROP_LIGHTNESS_MIN` | `95.0` | `CropOptions::$lightnessMin` |
| `IMGCOLOR_CROP_CHROMA_MAX` | `5.0` | `CropOptions::$chromaMax` |
| `IMGCOLOR_CROP_LINE_CONTENT_FRACTION` | `0.002` | `CropOptions::$lineContentFraction` |
| `IMGCOLOR_CROP_ALPHA_THRESHOLD` | `8` | `CropOptions::$alphaThreshold` |
| `IMGCOLOR_CLUSTER_FIXED_K` | empty | `ClusterOptions::$fixedK`; empty or `0` means automatic k |
| `IMGCOLOR_CLUSTER_KMAX` | `8` | `ClusterOptions::$kMax` |
| `IMGCOLOR_CLUSTER_HISTOGRAM_BITS` | `5` | `ClusterOptions::$histogramBitsPerChannel` |
| `IMGCOLOR_CLUSTER_MERGE_DELTAE` | `3.0` | `ClusterOptions::$mergeDeltaE` |
| `IMGCOLOR_CLUSTER_MIN_COVERAGE` | `0.01` | `ClusterOptions::$minClusterCoverage` |
| `IMGCOLOR_CLUSTER_SEED` | `1` | `ClusterOptions::$seed` |
| `IMGCOLOR_CLUSTER_ALPHA_THRESHOLD` | `8` | `ClusterOptions::$alphaThreshold` |

### Transition classification (CMYK separation)

All thresholds feed `imgcolor_TransitionClassifier` via
`imgcolor_Analyzer::getTransParams()`; ranges are validated on options build
and by `imgcolor_Setup::checkConfig()`. Calibration evidence for the defaults
is in the design spec (§4).

| Constant | Default | Units / effect |
|---|---:|---|
| `IMGCOLOR_TRANS_SPAN` | `4` | px; sampling distance of the coherent-drift test. Detectability bound: gradients shallower than ~noiseDeltaE/span ΔE per px read as solid. |
| `IMGCOLOR_TRANS_NOISE_DELTAE` | `1.0` | CIE76 ΔE (+alpha%); minimum average half-difference to count as changing |
| `IMGCOLOR_TRANS_COHERENCE_MIN` | `0.4` | cosine; both half-differences must point the same way (rejects JPEG noise/ringing) |
| `IMGCOLOR_TRANS_AA_RADIUS` | `3` | px; erosion radius - bands narrower than 2r+1 px (AA ramps, blurred edges) never become transitions |
| `IMGCOLOR_TRANS_MIN_SEED` | `20` | px; minimum changing pixels in the erosion window to seed a region |
| `IMGCOLOR_TRANS_EDGE_DELTAE` | `10.0` | ΔE; reconstruction/dilation stops at harder edges |
| `IMGCOLOR_TRANS_MIN_COVERAGE` | `0.005` | fraction; smaller transition areas are folded back to the solid path |

### CMYK conversion

| Constant | Default | Meaning |
|---|---:|---|
| `IMGCOLOR_CMYK_ENGINE` | `auto` | `auto` uses Imagick+ICC when available and configured, else the documented math formula (recorded as `fallback: true`); `imagick` requires ICC and fails loudly; `math` forces the formula |
| `IMGCOLOR_CMYK_ICC_RGB_PROFILE` | empty | path to the source RGB ICC profile (e.g. sRGB IEC61966-2.1) |
| `IMGCOLOR_CMYK_ICC_CMYK_PROFILE` | empty | path to the destination CMYK ICC profile (e.g. ECI ISOcoated_v2_300) |

No ICC profiles ship with the package - obtain them separately (the ECI
profiles are freely redistributable from eci.org) and point the constants at
the files. The result metadata records the profile basenames + MD5 for
reproducibility. The math formula (`K = 1 - max(R',G',B')`, `C = (1-R'-K)/(1-K)`,
...) is an approximation and is **not** press-accurate; treat its output as an
indicator, not a proof. Embedded source profiles are not honored (the GD
decode path strips them); sources are assumed sRGB.

### Semantics note (v0.4)

For images that contain transitions, the solid-color list changes meaning:
transition pixels no longer surface as pseudo-solid swatches, and solid
percentages are relative to the solid+AA area only. Anti-aliased edges and
narrow blurred edges stay in the solid path (absorbed by cluster merging,
as before). Analysis history records created before v0.4 keep their old
semantics; they are not migrated.

## Tests

The framework-free regressions require PHP 8.2+ with GD and can run without a
configured bgERP database:

```sh
php imgcolor/tests/cli_setup_bucket.php
php imgcolor/tests/cli_calibration.php
php imgcolor/tests/cli_analysis_snapshot.php
php imgcolor/tests/cli_init_signature.php
php imgcolor/tests/cli_parity.php
php imgcolor/tests/cli_separation.php
```

`cli_separation.php` carries the full classifier fixture battery (solid, AA,
blurred edges, JPEG noise/ringing, all gradient families, alpha fades, mixed
scenes), the CMYK converter/accumulator units, the solid-path byte-parity
regression, and a performance probe. `imgcolor_TransitionClassifier::renderMaskPng()`
produces a diagnostic class-mask PNG (solid=gray, AA=red, transition=blue)
for inspecting disputed classifications.

The web unit runner (`?Ctr=unit_Tests`) provides the Fileman/database-backed
coverage in `imgcolor_tests_Analyzer`, `imgcolor_tests_Profiles`,
`imgcolor_tests_Analyses`, and `imgcolor_tests_Separation`.

## Updating the library
Replace `lib/image-color-analyzer/src/` with the new upstream `src/`, update
`lib/image-color-analyzer/VENDORED.md`, then re-run `tests/cli_parity.php`
(CLI) and `imgcolor_tests_Analyzer` (web). Never edit files under `lib/`.

## Source notes
The design spec names the Composer package as
`teodor-todorov1/image-color-analyzer`, but the current vendored
`php_demo/composer.json` declares `acme/image-color-analyzer`. The wrapper keeps
the copied composer metadata unchanged and records both names in `VENDORED.md`.
