<?php


/**
 * Покритие на плътните цветове като процент от цялата анализирана площ, не от
 * площта на самите плътни пиксели: същият знаменател, който ползва и
 * transition_coverage_percent на imgcolor_CmykAccumulator. Така плътните
 * проценти и покритието на преливките се четат на една и съща скала и сумират
 * точно до 100.0%.
 *
 * Библиотечният PercentageCoverageCalculator нормализира до 100% върху
 * подадените на клъстеризатора пиксели. След маскирането на преливките този
 * знаменател е по-малък от анализираната площ и процентите се раздуват - при
 * изображение с 60% преливка плътен цвят върху 20% от площта би се отчел като
 * 50%. Без преливки двата знаменателя съвпадат и резултатът е идентичен с
 * библиотечния (регресия в cli_separation.php).
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
class imgcolor_SolidCoverage
{
    /**
     * Пълният бюджет в десети от процента (100.0% == 1000 десети)
     */
    const TENTHS_TOTAL = 1000;


    /**
     * Изчислява покритието на всеки клъстер спрямо анализираната площ.
     *
     * @param \ImageColorAnalyzer\Contracts\ClusterResult $result клъстерите на плътните пиксели
     * @param stdClass                                    $cls    резултат от imgcolor_TransitionClassifier::classify()
     *
     * @return array [{color: "#RRGGBB", coverage_percent: float}, ...], в намаляващ ред
     */
    public static function calculate($result, $cls)
    {
        $analyzed = (int) $cls->analyzedCount;
        if ($analyzed <= 0 || $result->clusters === array()) {

            return array();
        }

        // Бюджетът на плътните цветове е допълнението на закръгления дял на
        // преливките - взема се от същите броячи, за да не може плътни +
        // преливки да покажат 99.9% или 100.1% от закръгляне поотделно.
        $budget = self::TENTHS_TOTAL
                - (int) round($cls->transitionCount / $analyzed * self::TENTHS_TOTAL);

        $tenths = array();
        $remainders = array();
        $allocated = 0;
        foreach ($result->clusters as $i => $cluster) {
            $exact = $cluster->weight / $analyzed * self::TENTHS_TOTAL;
            $floor = (int) floor($exact);
            $tenths[$i] = $floor;
            $remainders[$i] = $exact - $floor;
            $allocated += $floor;
        }

        self::distributeRemainder($tenths, $remainders, $budget - $allocated, $result);

        $colors = array();
        foreach ($result->clusters as $i => $cluster) {
            $colors[] = array(
                'color' => $cluster->centroid->toHex(),
                'coverage_percent' => $tenths[$i] / 10.0,
            );
        }

        usort($colors, function ($a, $b) {
            $byPercent = $b['coverage_percent'] <=> $a['coverage_percent'];

            return $byPercent !== 0 ? $byPercent : strcmp($a['color'], $b['color']);
        });

        return $colors;
    }


    /**
     * Най-голям остатък: остатъчните десети отиват при клъстерите с най-голяма
     * дробна част, за да падне сумата точно на бюджета. Правилата за подредба
     * (остатък, тегло, hex) следват PercentageCoverageCalculator на
     * библиотеката, за да е идентичен резултатът при липса на преливки.
     *
     * @param array $tenths     модифицира се на място
     * @param array $remainders дробните части
     * @param int   $leftover   неразпределени десети
     * @param \ImageColorAnalyzer\Contracts\ClusterResult $result
     */
    private static function distributeRemainder(array &$tenths, array $remainders, $leftover, $result)
    {
        // Долните цели части сумират най-много до бюджета, така че остатъкът е
        // неотрицателен и по-малък от броя клъстери; горната граница пази от
        // непоследователен $cls (broi(TRANS) + клъстеризирани != analyzedCount).
        $leftover = min((int) $leftover, count($remainders));
        if ($leftover <= 0) {

            return;
        }

        $order = array_keys($remainders);
        usort($order, function ($a, $b) use ($remainders, $result) {
            $byRemainder = $remainders[$b] <=> $remainders[$a];
            if ($byRemainder !== 0) {

                return $byRemainder;
            }
            $byWeight = $result->clusters[$b]->weight <=> $result->clusters[$a]->weight;
            if ($byWeight !== 0) {

                return $byWeight;
            }

            return strcmp($result->clusters[$a]->centroid->toHex(), $result->clusters[$b]->centroid->toHex());
        });

        for ($i = 0; $i < $leftover; $i++) {
            $tenths[$order[$i]]++;
        }
    }
}
