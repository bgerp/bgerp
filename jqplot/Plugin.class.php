<?php
/**
 * Плъгин за рендиране на графики, използващ jqplot - http://www.jqplot.com/
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class jqplot_Plugin extends core_Plugin
{
    /**
     * Манипулации със заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterRenderListTitle($mvc, &$title, $data)
    {

        $chartConfigs = $mvc::getChartConfig();

        if (count($chartConfigs)) {
            $title = new ET('[#1#]', $title);

            $title->append('<div style="margin-top:5px;margin-bottom:5px;font-size:0.80em;font-family:arial;" id="chartMenu">', 'ListSummary');

            $reqestedChartName = $mvc::getRequestedChartName();

            if ($reqestedChartName) {
                $title->append(ht::createLink(tr('Tаблица'), $mvc::getChartUrl(NULL)) , 'ListSummary');
            } else {
                $title->append(tr('Tаблица') , 'ListSummary');
            }

            foreach ($chartConfigs as $chartName=>$chartConfig) {
                $title->append("&nbsp;|&nbsp;", 'ListSummary');

                $caption = isset($chartConfig['menu']) ? $chartConfig['menu'] : $chartName;

                $url = getCurrentUrl();

                if($reqestedChartName != $chartName) {
                    $title->append(ht::createLink(tr($caption), $mvc::getChartUrl($chartName)) , 'ListSummary');
                } else {
                    $title->append(tr($caption), 'ListSummary');
                }
            }

            $title->append('</div>', 'ListSummary');
        }
    }


    /**
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     */
    static function on_AfterRenderListTable($mvc, $tpl, $data)
    {

        static $defaultChartConfig = array(
            'menu'     => NULL,         // заглавие на графиката в менюто
            'type'     => 'lines',      // lines | bars
            'dir'      => 'vertical',   // horizontal | vertical
            'per'      => NULL,         // име на поле от $mvc: по една графика за всяка различна стойност на това поле
            'titleTpl' => NULL,         // core_ET: Шаблон за заглавие на всяка графика
            'labelTpl' => NULL,         // core_ET: Шаблон за етикет на стойност
            'ax'       => NULL,         // string: име на поле от $mvc
            'ay'       => NULL,         // масив от имена на полета
            'series'   => NULL,         // string: име на поле от $mvc
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

        $groups = $data->recs;

        if (!empty($chartConfig['per'])) {
            // Ще правим толкова графики, колкото различни стойности на полето
            // $chartConfig['per'] има
            expect($mvc->getField($chartConfig['per']));

            $groups = arr::group($groups, $chartConfig['per']);
        } else {
            // Ще правим една графика
            $groups = array($groups);
        }

        foreach ($groups as $recs) {
            $chart = static::createChart($chartConfig, $recs, $data);
            $chart->appendTo($tpl);
            $tpl->append('<hr/>');
        }
    }


    static function createChart($config, $recs, $data)
    {
        $chart = new jqplot_Jqplot($config);

        $chart->setTitle(static::calcChartTitle($config, $data->rows[key($recs)]));
        $chart->setHtmlAttr($config['htmlAttr']);

        foreach ($recs as $i=>$rec) {
            $row = $data->rows[$i];

            $seriesKey = $config['series'] ? $rec->{$config['series']} : 0;

            $chart->addPoint($seriesKey,
                $row->{$config['ax']},
                floatval($rec->{$config['ay']}),
                static::calcPointLabel($config, $row)
            );
        }

        return $chart;
    }


    protected static function calcChartTitle($config, $row)
    {
        if ($config['titleTpl']) {
            $title = new core_ET($config['titleTpl']);
            $title->placeObject($row);
        } elseif ($config['per']) {
            $title = $row->{$config['per']};
        }

       return (string)$title;
    }


    protected static function calcPointLabel($config, $row)
    {
        if ($config['labelTpl']) {
            $label = new core_ET($config['labelTpl']);
            $label->placeObject($row);
        } else {
            $label = $row->{$config['ay']};
        }

        return (string)$label;
    }


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

    static function on_AfterGetRequestedChartName($mvc, &$res)
    {
        $res = core_Request::get('chart');
    }


    static function on_AfterGetChartUrl($mvc, &$url, $chartName)
    {
        $url = getCurrentUrl();
        $url['chart'] = $chartName;
    }
}