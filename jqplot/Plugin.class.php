<?php
/**
 * Плъгин за рендиране на графики, използващ jqplot - http://www.jqplot.com/
 *
 * @category  bgerp
 * @package   jqplot
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 */
class jqplot_Plugin extends core_Plugin
{

    /**
     * Конфигурация на графика със зададено име.
     *
     * Този метод е реализация по подразбиране на метода
     *
     *     getChartConfig($name = NULL)
     *
     * Домакина може да реализира свой собствен getChartConfig() като по този начин дефинира
     * друг механизъм за указване на наличните графики
     *
     * @param core_Mvc $mvc  домакин
     * @param array    $res  конфигурация на графика (на изхода)
     * @param string   $name име на графика; ако е NULL, метода връща всички налични конфигурации
     *                       на графики, описани в домакина.
     */
    public static function on_AfterGetChartConfig($mvc, &$res, $name = null)
    {
        $res = array();

        if (isset($mvc::$charts)) {
            $res = $mvc::$charts;
        }

        if (isset($name, $res[$name])) {
            $res = $res[$name];

            return;
        }

        $res += static::modelChartConfigs($mvc);

        if (isset($name, $res[$name])) {
            $res = $res[$name];

            return;
        }

        return $res;
    }



    protected static function modelChartConfigs($mvc)
    {
        // Намираме полетата, дефинирани като оста Х
        $xFieldArr = $mvc->selectFields("#chart == 'ax'");

        // Намираме полетата, дефинирани като оста У
        $yFieldArr = $mvc->selectFields("#chart == 'ay'");

        // Намираме полетата дефиниращи серии
        $sFieldArr = $mvc->selectFields("#chart == 'series'");

        // Намираме полетата, дефинирани като разграничаващи различните графики
        $diffFieldArr = $mvc->selectFields("#chart == 'diff'");

        // Очакваме ...
        expect(count($xFieldArr) == 1);    // да има само едно поле по оста X
        expect(count($yFieldArr));         // най-малко едно поле по оста Y
        expect(count($diffFieldArr) <= 1); // най-много едно diff поле
        expect(count($sFieldArr) <= 1);    // най-много едно series поле

        $chart = array();

        $xField = current($xFieldArr); // X полето
        $yField = current($yFieldArr); // Y полето

        $chart['ax'] = $xField->name;
        $chart['ay'] = $yField->name;

        if (count($diffFieldArr) > 0) {
            $dField = current($diffFieldArr); // diff/per полето
            $chart['per'] = $dField->name;
        }
        if (count($sFieldArr) > 0) {
            $sField = current($sFieldArr); // series полето
            $chart['series'] = $sField->name;
        }

        return array(
            'model' => $chart + array(
                'menu' => 'Линии'
            )
        );
    }


    /**
     * Кога графика е заявена в HTTP заявката?
     *
     * Този метод е реализация по подразбиране на метода
     *
     *     getRequestedChartName()
     *
     * Домакина може да реализира свой собствен getRequestedChartName() като по този начин
     * дефинира друг механизъм за определяне на заявената за показване графика.
     *
     * @param core_Mvc $mvc домакин
     * @param string   $res име на графика, или празно, ако не е заявена
     */
    public static function on_AfterGetRequestedChartName($mvc, &$res)
    {
        $res = core_Request::get('Chart');
    }


    /**
     * URL на графика с определено име
     *
     * Този метод е реализация по подразбиране на метода
     *
     *     getChartUrl($name)
     *
     * Домакина може да реализира свой собствен getChartUrl() като по този начин
     * дефинира друг механизъм за генериране на URL към графика.
     *
     * @param core_Mvc $mvc       домакин
     * @param array    $url       URL на графиката $chartName (на изхода)
     * @param string   $chartName име на графика
     */
    public static function on_AfterGetChartUrl($mvc, &$url, $chartName)
    {
        $url = getCurrentUrl();
        $url['Chart'] = $chartName;
    }


    /**
     * Добавя списък хипервръзки към дефинираните от домакина графики.
     *
     * Списъка се добавя след заглавието на списъчния изглед на домакина
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterRenderListTitle($mvc, &$title, $data)
    {
        $menu = static::buildChartsMenu($mvc);

        if (empty($menu)) {
            return;
        }

        $title = new ET('[#1#]', $title);
        $title->append('<div style="margin-top:5px;margin-bottom:15px;font-size:0.80em;font-family:arial;" id="chartMenu">', 'ListSummary');

        $first = true;
        foreach ($menu as $item) {
            if (!$first) {
                $title->append('&nbsp;|&nbsp;', 'ListSummary');
            }
            $title->append($item, 'ListSummary');
            $first = false;
        }

        $title->append('</div>', 'ListSummary');
    }


    /**
     * Подменя табличния изглед на домакина с указана в HTTP заявката графика
     *
     * @param core_Mvc $mvc  домакин
     * @param core_ET  $tpl
     * @param stdClass $data
     */
    public static function on_AfterRenderListTable($mvc, $tpl, $data)
    {
        static $defaultChartConfig = array(
            'menu' => null,         // заглавие на графиката в менюто
            'per' => null,         // име на поле от $mvc: по една графика за всяка различна стойност на това поле
            'titleTpl' => null,         // core_ET: Шаблон за заглавие на всяка графика
            'labelTpl' => null,         // core_ET: Шаблон за етикет на стойност
            'ax' => null,         // string: име на поле от $mvc
            'ay' => null,         // масив от имена на полета
            'series' => null,         // string: име на поле от $mvc

            'type' => 'lines',      // lines | bars
            'dir' => 'vertical',   // horizontal | vertical
            'log' => false,        // използване на логаритмична скала за стойностите
            'htmlAttr' => array(),      // допълнителни HTML атрибути
        );

        if (!$chartName = $mvc::getRequestedChartName()) {
            // Не е указана графика
            return;
        }

        if (!$chartConfig = $mvc::getChartConfig($chartName)) {
            // Няма конфигурация - няма да показваме графика
            return;
        }

        $chartConfig += $defaultChartConfig;

        $tpl = new core_ET();

        // Генерираме графиката / графиките
        $charts = static::createCharts($chartConfig, $data);

        // Заместваме в резултата
        foreach ($charts as $chart) {
            $tpl->append($chart->getElement());
            $tpl->append('<hr/>');
        }
    }


    /**
     * Генерира списък хипервръзки към дефинираните от домакина графики.
     *
     * Списъка се добавя след заглавието на списъчния изглед на домакина
     *
     * @param  core_Mvc $mvc
     * @return array
     */
    protected static function buildChartsMenu($mvc)
    {
        $menu = array();

        $chartConfigs = $mvc::getChartConfig();

        if (count($chartConfigs)) {
            $reqestedChartName = $mvc::getRequestedChartName();

            if ($reqestedChartName) {
                $menu[] = ht::createLink(tr('Таблица'), $mvc::getChartUrl(null));
            } else {
                $menu[] = tr('Таблица');
            }

            foreach ($chartConfigs as $chartName => $chartConfig) {
                $caption = isset($chartConfig['menu']) ? $chartConfig['menu'] : $chartName;

                if ($reqestedChartName != $chartName) {
                    $menu[] = ht::createLink(tr($caption), $mvc::getChartUrl($chartName));
                } else {
                    $menu[] = tr($caption);
                }
            }
        }

        return $menu;
    }


    /**
     * Създава една или повече графики според конфигурацията и данните на модела на домакина
     *
     * @param  array    $config
     * @param  stdClass $data   данните на модела на домакина
     * @return array    масив от jqplot_Chart
     */
    protected static function createCharts($config, $data)
    {
        $charts = array();

        foreach ($data->recs as $i => $rec) {
            $row = $data->rows[$i];

            if ($config['per']) {
                $chartIdx = $rec->{$config['per']};
            } else {
                $chartIdx = 0;
            }

            if (!isset($charts[$chartIdx])) {
                // създаваме нова графика
                $chart = $charts[$chartIdx] = new jqplot_Chart($config);

                // Инициализираме заглавието, използвайки първия запис от поредицата
                $chart->setTitle(static::calcChartTitle($config, $row));
            } else {
                $chart = $charts[$chartIdx];
            }

            // Добавяме данните
            $seriesKey = $config['series'] ? $rec->{$config['series']} : 0;

            $chart->addPoint(
                $seriesKey,
                $row->{$config['ax']},
                floatval($rec->{$config['ay']}),
                static::calcPointLabel($config, $row)
            );
        }

        return $charts;
    }


    /**
     * Помощен метод за изчисляване на заглавие на графика
     *
     * @param  array    $config конфигурация на графи
     * @param  stdClass $row    вербални стойности на запис на модела-домакин
     * @return string
     */
    protected static function calcChartTitle($config, $row)
    {
        $title = '';

        if ($config['titleTpl']) {
            $title = new core_ET($config['titleTpl']);
            $title->placeObject($row);
        } elseif ($config['titleTpl'] !== false) {
            if ($config['per']) {
                $title = $row->{$config['per']};
            }
        }

        return (string) $title;
    }


    /**
     * Помощен метод за изчисляване на етикет на точка от графика
     *
     * @param  array    $config конфигурация на графи
     * @param  stdClass $row    вербални стойности на запис на модела-домакин
     * @return string
     */
    protected static function calcPointLabel($config, $row)
    {
        $label = '';

        if ($config['labelTpl']) {
            $label = new core_ET($config['labelTpl']);
            $label->placeObject($row);
        } elseif ($config['labelTpl'] !== false) {
            $label = $row->{$config['ay']};
        }

        return (string) $label;
    }
}
