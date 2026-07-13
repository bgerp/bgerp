<?php


/**
 * CMYK акумулатор върху преливките: групира TRANSITION пикселите по цвят,
 * конвертира уникалните цветове (imgcolor_CmykConverter), натрупва мастилата
 * с тегло alpha/255 и нормализира състава до точно 100.0% по метода на
 * най-големия остатък (както PercentageCoverageCalculator на библиотеката).
 *
 * Без bgERP зависимост - тества се директно с
 * `php imgcolor/tests/cli_separation.php`.
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.4
 */
class imgcolor_CmykAccumulator
{
    /**
     * Натрупва CMYK резултата за TRANSITION пикселите на растера.
     *
     * @param \ImageColorAnalyzer\Contracts\Raster $raster    изрязаният растер
     * @param stdClass                             $cls       резултат от imgcolor_TransitionClassifier::classify()
     * @param imgcolor_CmykConverter               $converter
     *
     * @return array|null null когато няма преливки; иначе payload за cmykJson
     */
    public static function accumulate($raster, $cls, imgcolor_CmykConverter $converter)
    {
        if (empty($cls->transitionCount)) {

            return null;
        }

        // Групиране по 24-битов RGB ключ: тегло = сума alpha/255
        $bins = array();
        $i = 0;
        foreach ($raster->pixels() as $px) {
            if ($cls->mask[$i++] !== imgcolor_TransitionClassifier::CLS_TRANS) {
                continue;
            }
            $key = ($px->r << 16) | ($px->g << 8) | $px->b;
            $bins[$key] = (isset($bins[$key]) ? $bins[$key] : 0.0) + $px->a / 255;
        }

        // Каноничен ред за детерминизъм, независим от реда на срещане
        ksort($bins);

        $colors = array();
        foreach ($bins as $key => $weight) {
            $colors[] = array(($key >> 16) & 0xFF, ($key >> 8) & 0xFF, $key & 0xFF);
        }

        $cmyk = $converter->convert($colors);

        $raw = array('c' => 0.0, 'm' => 0.0, 'y' => 0.0, 'k' => 0.0);
        $j = 0;
        foreach ($bins as $weight) {
            $raw['c'] += $cmyk[$j][0] * $weight;
            $raw['m'] += $cmyk[$j][1] * $weight;
            $raw['y'] += $cmyk[$j][2] * $weight;
            $raw['k'] += $cmyk[$j][3] * $weight;
            $j++;
        }

        $inkTotal = $raw['c'] + $raw['m'] + $raw['y'] + $raw['k'];

        // Изрично дефиниран нулев случай (напр. само-бяла преливка): нули,
        // без деление - процентният състав няма смисъл без мастило.
        $percent = array('c' => 0.0, 'm' => 0.0, 'y' => 0.0, 'k' => 0.0);
        if ($inkTotal > 0) {
            $percent = self::normalizePercent($raw, $inkTotal);
        }

        $rounded = array();
        foreach ($raw as $ch => $v) {
            $rounded[$ch] = round($v, 3);
        }

        return array(
            'transition_coverage_percent' => round($cls->transitionCount / $cls->analyzedCount * 100, 1),
            'composition_percent' => $percent,
            'ink_total' => round($inkTotal, 3),
            'raw_channels' => $rounded,
            'conversion' => $converter->getMetadata(),
        );
    }


    /**
     * Най-голям остатък в десети от процента: четирите стойности сумират
     * точно до 100.0 (1000 десети), без "99.9%" артефакти.
     *
     * @param array $raw      натрупани канали
     * @param float $inkTotal сума на каналите (> 0)
     *
     * @return array
     */
    private static function normalizePercent(array $raw, $inkTotal)
    {
        $tenths = array();
        $remainders = array();
        $allocated = 0;
        foreach ($raw as $ch => $v) {
            $exact = $v / $inkTotal * 1000;
            $floor = (int) floor($exact);
            $tenths[$ch] = $floor;
            $remainders[$ch] = $exact - $floor;
            $allocated += $floor;
        }

        $order = array_keys($remainders);
        usort($order, function ($a, $b) use ($remainders) {
            $byRemainder = $remainders[$b] <=> $remainders[$a];

            return $byRemainder !== 0 ? $byRemainder : strcmp($a, $b);
        });

        for ($i = 0; $i < 1000 - $allocated; $i++) {
            $tenths[$order[$i]]++;
        }

        $percent = array();
        foreach ($tenths as $ch => $t) {
            $percent[$ch] = $t / 10.0;
        }

        return $percent;
    }
}
