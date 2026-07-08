<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\WhiteBackgroundCropper;

use ImageColorAnalyzer\Color\ColorConverter;
use ImageColorAnalyzer\Contracts\BoundingBox;
use ImageColorAnalyzer\Contracts\ColorRGBA;
use ImageColorAnalyzer\Contracts\CropperInterface;
use ImageColorAnalyzer\Contracts\CropResult;
use ImageColorAnalyzer\Contracts\Raster;
use ImageColorAnalyzer\Options\CropOptions;

/**
 * OWNER: Developer B.
 *
 * Removes the near-white margin surrounding image content using a
 * **border-inward scan**: it only ever trims from the four outer edges toward
 * the centre, so white *inside* the artwork is structurally impossible to
 * remove. The result is the smallest axis-aligned rectangle that still contains
 * every non-background pixel.
 *
 * A pixel is treated as background when it is transparent
 * (`alpha < alphaThreshold`) or near-white in CIELAB — perceptually meaningful
 * "whiteness" rather than a raw RGB test, so slight scanner hue casts and JPEG
 * halos are handled robustly:
 *
 *     background := alpha < alphaThreshold
 *                OR ( L* >= lightnessMin AND sqrt(a*^2 + b*^2) <= chromaMax )
 *
 * Tuning (see {@see CropOptions}): raise `chromaMax` / lower `lightnessMin` for
 * off-white scanned paper; lower `chromaMax` for clean exports where only true
 * white should be trimmed. `lineContentFraction` is a per-line noise guard: a
 * row/column counts as content only once its fraction of content pixels crosses
 * that floor, so dust and stray specks in the margin do not defeat cropping —
 * while a raw-extent fallback guarantees genuinely small content (a single
 * pixel, a thin line) is never erased.
 *
 * The scan is a single O(W·H) pass; the near-white decision is memoized by
 * packed RGB so the highly repetitive margin costs one Lab evaluation per unique
 * colour. Deterministic: no randomness, no global state.
 */
final class WhiteBackgroundCropper implements CropperInterface
{
    /**
     * Upper bound on distinct colours cached per {@see crop()} call. The key is a
     * 24-bit packed RGB, so the map is inherently bounded; this cap keeps memory
     * flat on adversarial many-colour inputs (colours beyond it are simply
     * recomputed — still correct, just not cached).
     */
    private const MEMO_CAP = 1 << 16;

    /** @var array<int, bool> packed-RGB => isBackground, scoped to one crop() call */
    private array $backgroundMemo = [];

    public function __construct(private readonly ColorConverter $converter)
    {
    }

    public function crop(Raster $image, CropOptions $options): CropResult
    {
        $this->backgroundMemo = [];

        $width = $image->width();
        $height = $image->height();

        // Content-pixel tallies per scan line, plus the raw min/max content
        // extent used as a fallback when the noise guard would trim everything.
        $rowContent = array_fill(0, $height, 0);
        $colContent = array_fill(0, $width, 0);
        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;
        $hasContent = false;

        // Single row-major pass; track (x, y) manually to avoid W*H pixelAt() calls.
        $x = 0;
        $y = 0;
        foreach ($image->pixels() as $pixel) {
            if (!$this->isBackground($pixel, $options)) {
                ++$rowContent[$y];
                ++$colContent[$x];
                $minX = min($minX, $x);
                $maxX = max($maxX, $x);
                $minY = min($minY, $y);
                $maxY = max($maxY, $y);
                $hasContent = true;
            }

            if (++$x === $width) {
                $x = 0;
                ++$y;
            }
        }

        // Fully white / fully transparent: nothing to crop, hand back the original.
        if (!$hasContent) {
            return new CropResult($image, new BoundingBox(0, 0, $width, $height), false);
        }

        // Derive each edge from the first/last scan line that clears the noise floor.
        [$top, $bottom] = $this->contentRange($rowContent, $options->lineContentFraction * $width);
        [$left, $right] = $this->contentRange($colContent, $options->lineContentFraction * $height);

        // Guard removed every qualifying line on an axis, yet real content exists:
        // fall back to the raw extent so small-but-genuine artwork survives.
        if ($top === null || $bottom === null) {
            $top = $minY;
            $bottom = $maxY;
        }
        if ($left === null || $right === null) {
            $left = $minX;
            $right = $maxX;
        }

        $box = new BoundingBox($left, $top, $right - $left + 1, $bottom - $top + 1);

        $isWholeImage = $left === 0 && $top === 0 && $right === $width - 1 && $bottom === $height - 1;

        if ($isWholeImage) {
            return new CropResult($image, $box, false);
        }

        return new CropResult($image->crop($box), $box, true);
    }

    /**
     * First and last scan-line index whose content count clears the noise floor,
     * or [null, null] if no line qualifies. A line with zero content pixels never
     * qualifies, regardless of the floor.
     *
     * @param array<int, int> $lineCounts       content-pixel count per scan line
     * @param float           $minContentPixels noise floor (fraction * perpendicular dimension)
     * @return array{0: int|null, 1: int|null}
     */
    private function contentRange(array $lineCounts, float $minContentPixels): array
    {
        $first = null;
        $last = null;
        foreach ($lineCounts as $index => $count) {
            if ($count > 0 && $count >= $minContentPixels) {
                $first ??= $index;
                $last = $index;
            }
        }

        return [$first, $last];
    }

    /**
     * Whether a pixel belongs to the near-white (or transparent) background.
     * Transparent pixels short-circuit; opaque ones are judged in CIELAB and
     * memoized by packed RGB so repeated margin colours are evaluated once.
     */
    private function isBackground(ColorRGBA $pixel, CropOptions $options): bool
    {
        if ($pixel->a < $options->alphaThreshold) {
            return true;
        }

        $key = ($pixel->r << 16) | ($pixel->g << 8) | $pixel->b;
        if (isset($this->backgroundMemo[$key])) {
            return $this->backgroundMemo[$key];
        }

        [$l, $a, $b] = $this->converter->rgbToLab($pixel);
        $chroma = sqrt($a * $a + $b * $b);
        $isBackground = $l >= $options->lightnessMin && $chroma <= $options->chromaMax;

        if (count($this->backgroundMemo) < self::MEMO_CAP) {
            $this->backgroundMemo[$key] = $isBackground;
        }

        return $isBackground;
    }
}
