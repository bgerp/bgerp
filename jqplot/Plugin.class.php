<?php
/**
 * Плъгин за рендиране на графики, използващ jqplot - http://www.jqplot.com/
 *
 * @category  vendors
 * @package   jqplot
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
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
     * @param core_Mvc $mvc домакин
     * @param array $res конфигурация на графика (на изхода)
     * @param string $name име на графика; ако е NULL, метода връща всички налични конфигурации
     *                 на графики, описани в домакина.
     */
    static function on_AfterGetChartConfig($mvc, &$res, $name = NULL)
    {
        $res = array();

        if (isset($mvc::$charts)) {
            $res = $mvc::$charts;

            if (isset($name)) {
                $res = isset($mvc::$charts[$name]) ? $mvc::$charts[$name] : array();
            }
        }
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
     * @param string $res име на графика, или празно, ако не е заявена
     */
    static function on_AfterGetRequestedChartName($mvc, &$res)
    {
        $res = core_Request::get('chart');
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
     * @param core_Mvc $mvc домакин
     * @param array $url URL на графиката $chartName (на изхода)
     * @param string $chartName име на графика
     */
    static function on_AfterGetChartUrl($mvc, &$url, $chartName)
    {
        $url = getCurrentUrl();
        $url['chart'] = $chartName;
    }


    /**
     * Добавя списък хипервръзки към дефинираните от домакина графики.
     *
     * Списъка се добавя след заглавието на списъчния изглед на домакина
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterRenderListTitle($mvc, &$title, $data)
    {

        $menu = static::buildChartsMenu($mvc);

        if (empty($menu)) {
            return;
        }

        $title = new ET('[#1#]', $title);
        $title->append('<div style="margin-top:5px;margin-bottom:5px;font-size:0.80em;font-family:arial;" id="chartMenu">', 'ListSummary');

        $first = TRUE;
        foreach ($menu as $item) {
            if (!$first) {
                $title->append("&nbsp;|&nbsp;", 'ListSummary');
            }
            $title->append($item, 'ListSummary');
            $first = FALSE;
        }

        $title->append('</div>', 'ListSummary');
    }


    /**
     * Подменя табличния изглед на домакина с указана в HTTP заявката графика
     *
     * @param core_Mvc $mvc домакин
     * @param core_ET $tpl
     * @param stdClass $data
     */
    static function on_AfterRenderListTable($mvc, $tpl, $data)
    {

        static $defaultChartConfig = array(
            'menu'     => NULL,         // заглавие на графиката в менюто
            'per'      => NULL,         // име на поле от $mvc: по една графика за всяка различна стойност на това поле
            'titleTpl' => NULL,         // core_ET: Шаблон за заглавие на всяка графика
            'labelTpl' => NULL,         // core_ET: Шаблон за етикет на стойност
            'ax'       => NULL,         // string: име на поле от $mvc
            'ay'       => NULL,         // масив от имена на полета
            'series'   => NULL,         // string: име на поле от $mvc

            'type'     => 'lines',      // lines | bars
            'dir'      => 'vertical',   // horizontal | vertical
            'log'      => FALSE,        // използване на логаритмична скала за стойностите
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
     * @param core_Mvc $mvc
     * @return array
     */
    protected static function buildChartsMenu($mvc)
    {
        $menu = array();

        $chartConfigs = $mvc::getChartConfig();

        if (count($chartConfigs)) {
            $reqestedChartName = $mvc::getRequestedChartName();

            if ($reqestedChartName) {
                $menu[] = ht::createLink(tr('Tаблица'), $mvc::getChartUrl(NULL));
            } else {
                $menu[] = tr('Tаблица');
            }

            foreach ($chartConfigs as $chartName=>$chartConfig) {
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
     * @param array $config
     * @param stdClass $data данните на модела на домакина
     * @return array масив от jqplot_Chart
     */
    protected static function createCharts($config, $data)
    {
        $charts = array();

        foreach ($data->recs as $i=>$rec) {
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

            $chart->addPoint($seriesKey,
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
     * @param array $config конфигурация на графи
     * @param stdClass $row вербални стойности на запис на модела-домакин
     * @return string
     */
    protected static function calcChartTitle($config, $row)
    {
        $title = '';

        if ($config['titleTpl']) {
            $title = new core_ET($config['titleTpl']);
            $title->placeObject($row);
        } elseif ($config['titleTpl'] !== FALSE) {
            if ($config['per']) {
                $title = $row->{$config['per']};
            }
        }

       return (string)$title;
    }


    /**
     * Помощен метод за изчисляване на етикет на точка от графика
     *
     * @param array $config конфигурация на графи
     * @param stdClass $row вербални стойности на запис на модела-домакин
     * @return string
     */
    protected static function calcPointLabel($config, $row)
    {
        $label = '';

        if ($config['labelTpl']) {
            $label = new core_ET($config['labelTpl']);
            $label->placeObject($row);
        } elseif ($config['labelTpl'] !== FALSE) {
            $label = $row->{$config['ay']};
        }

        return (string)$label;
    }
}