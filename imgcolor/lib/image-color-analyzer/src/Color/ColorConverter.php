<?php

declare(strict_types=1);

namespace ImageColorAnalyzer\Color;

use ImageColorAnalyzer\Contracts\ColorRGBA;
use InvalidArgumentException;

/**
 * OWNER: Developer A (foundation).
 *
 * Pure, deterministic sRGB <-> CIELAB <-> HSV conversions (D65 reference white)
 * plus CIE76 delta-E. Analysis happens in Lab; RGB stays the transport format.
 */
final class ColorConverter
{
    /**
     * @return array{0:float,1:float,2:float} [L*, a*, b*]
     */
    public function rgbToLab(ColorRGBA $c): array
    {
        [$x, $y, $z] = $this->rgbToXyz($c);

        $fx = $this->pivotXyz($x / 95.047);
        $fy = $this->pivotXyz($y / 100.0);
        $fz = $this->pivotXyz($z / 108.883);

        return [
            116.0 * $fy - 16.0,
            500.0 * ($fx - $fy),
            200.0 * ($fy - $fz),
        ];
    }

    /**
     * @param array{0:float,1:float,2:float} $lab [L*, a*, b*]
     */
    public function labToRgb(array $lab, int $alpha = 255): ColorRGBA
    {
        $fy = ($lab[0] + 16.0) / 116.0;
        $fx = ($lab[1] / 500.0) + $fy;
        $fz = $fy - ($lab[2] / 200.0);

        return $this->xyzToRgb([
            95.047 * $this->inversePivotXyz($fx),
            100.0 * $this->inversePivotXyz($fy),
            108.883 * $this->inversePivotXyz($fz),
        ], $alpha);
    }

    /**
     * @return array{0:float,1:float,2:float} [H in 0-360, S in 0-1, V in 0-1]
     */
    public function rgbToHsv(ColorRGBA $c): array
    {
        $r = $c->r / 255.0;
        $g = $c->g / 255.0;
        $b = $c->b / 255.0;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        $h = 0.0;
        if ($delta > 0.0) {
            if ($max === $r) {
                $h = 60.0 * fmod(($g - $b) / $delta, 6.0);
            } elseif ($max === $g) {
                $h = 60.0 * ((($b - $r) / $delta) + 2.0);
            } else {
                $h = 60.0 * ((($r - $g) / $delta) + 4.0);
            }
        }
        if ($h < 0.0) {
            $h += 360.0;
        }

        $s = $max > 0.0 ? $delta / $max : 0.0;

        return [$h, $s, $max];
    }

    public function hsvToRgb(float $h, float $s, float $v, int $alpha = 255): ColorRGBA
    {
        if ($h < 0.0 || $h > 360.0 || $s < 0.0 || $s > 1.0 || $v < 0.0 || $v > 1.0) {
            throw new InvalidArgumentException('HSV values must be H 0-360, S 0-1, V 0-1.');
        }

        $h = $h === 360.0 ? 0.0 : $h;
        $c = $v * $s;
        $x = $c * (1.0 - abs(fmod($h / 60.0, 2.0) - 1.0));
        $m = $v - $c;

        [$r, $g, $b] = match (true) {
            $h < 60.0 => [$c, $x, 0.0],
            $h < 120.0 => [$x, $c, 0.0],
            $h < 180.0 => [0.0, $c, $x],
            $h < 240.0 => [0.0, $x, $c],
            $h < 300.0 => [$x, 0.0, $c],
            default => [$c, 0.0, $x],
        };

        return new ColorRGBA(
            $this->toByte($r + $m),
            $this->toByte($g + $m),
            $this->toByte($b + $m),
            $alpha,
        );
    }

    /**
     * CIE76 delta-E: Euclidean distance in CIELAB.
     *
     * @param array{0:float,1:float,2:float} $lab1
     * @param array{0:float,1:float,2:float} $lab2
     */
    public function deltaE(array $lab1, array $lab2): float
    {
        return sqrt(
            ($lab1[0] - $lab2[0]) ** 2
            + ($lab1[1] - $lab2[1]) ** 2
            + ($lab1[2] - $lab2[2]) ** 2
        );
    }

    /**
     * CIE94 delta-E using the graphic-arts weighting factors.
     *
     * @param array{0:float,1:float,2:float} $lab1
     * @param array{0:float,1:float,2:float} $lab2
     */
    public function deltaE94(array $lab1, array $lab2): float
    {
        $deltaL = $lab1[0] - $lab2[0];
        $c1 = sqrt($lab1[1] ** 2 + $lab1[2] ** 2);
        $c2 = sqrt($lab2[1] ** 2 + $lab2[2] ** 2);
        $deltaC = $c1 - $c2;
        $deltaA = $lab1[1] - $lab2[1];
        $deltaB = $lab1[2] - $lab2[2];
        $deltaH2 = max(0.0, $deltaA ** 2 + $deltaB ** 2 - $deltaC ** 2);

        $scaleL = 1.0;
        $scaleC = 1.0 + 0.045 * $c1;
        $scaleH = 1.0 + 0.015 * $c1;

        return sqrt(
            ($deltaL / $scaleL) ** 2
            + ($deltaC / $scaleC) ** 2
            + ($deltaH2 / ($scaleH ** 2))
        );
    }

    /**
     * @return array{0:float,1:float,2:float} XYZ scaled to 0-100 (D65)
     */
    public function rgbToXyz(ColorRGBA $c): array
    {
        $r = $this->linearize($c->r / 255.0) * 100.0;
        $g = $this->linearize($c->g / 255.0) * 100.0;
        $b = $this->linearize($c->b / 255.0) * 100.0;

        return [
            $r * 0.4124 + $g * 0.3576 + $b * 0.1805,
            $r * 0.2126 + $g * 0.7152 + $b * 0.0722,
            $r * 0.0193 + $g * 0.1192 + $b * 0.9505,
        ];
    }

    /**
     * @param array{0:float,1:float,2:float} $xyz XYZ scaled to 0-100 (D65)
     */
    public function xyzToRgb(array $xyz, int $alpha = 255): ColorRGBA
    {
        $x = $xyz[0] / 100.0;
        $y = $xyz[1] / 100.0;
        $z = $xyz[2] / 100.0;

        $r = $x * 3.2406 + $y * -1.5372 + $z * -0.4986;
        $g = $x * -0.9689 + $y * 1.8758 + $z * 0.0415;
        $b = $x * 0.0557 + $y * -0.2040 + $z * 1.0570;

        return new ColorRGBA(
            $this->toByte($this->delinearize($r)),
            $this->toByte($this->delinearize($g)),
            $this->toByte($this->delinearize($b)),
            $alpha,
        );
    }

    private function linearize(float $channel): float
    {
        return $channel > 0.04045
            ? (($channel + 0.055) / 1.055) ** 2.4
            : $channel / 12.92;
    }

    private function delinearize(float $channel): float
    {
        $channel = max(0.0, min(1.0, $channel));

        return $channel > 0.0031308
            ? 1.055 * ($channel ** (1.0 / 2.4)) - 0.055
            : 12.92 * $channel;
    }

    private function pivotXyz(float $t): float
    {
        return $t > 0.008856
            ? $t ** (1.0 / 3.0)
            : (7.787 * $t) + (16.0 / 116.0);
    }

    private function inversePivotXyz(float $t): float
    {
        $cubed = $t ** 3;

        return $cubed > 0.008856
            ? $cubed
            : ($t - (16.0 / 116.0)) / 7.787;
    }

    private function toByte(float $channel): int
    {
        return (int) round(max(0.0, min(1.0, $channel)) * 255.0);
    }
}
