<?php
/**
 * Плъгин за рендиране на графики, използващ jqplot - http://www.jqplot.com/
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class jqplot_Plugin extends core_Plugin
{

    static $defaultJqplotOptions = array(
        'seriesDefaults' => array(
            'pointLabels' => array(
                'show' => true,
                'escapeHTML' => false,
            )
        ),
        'axes' => array(
            'xaxis' => array(
                'renderer' => '@$.jqplot.CategoryAxisRenderer@',
            )
        )
    );

    static $defaultJqplotPlugins = array(
        'categoryAxisRenderer',
        'pointLabels',
    );


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
        if (!$chartName = $mvc::getRequestedChartName()) {
            // Не е указана графика
            return;
        }

        if (!$chartConfig = $mvc::getChartConfig($chartName)) {
            // Няма конфигурация - няма да показваме графика
            return;
        }

        $tpl = new core_ET();

        $jqplotPlugins     = static::$defaultJqplotPlugins;
        $jqplotOptionsBase = static::$defaultJqplotOptions;

        if (isset($chartConfig['type']) && $chartConfig['type'] == 'bars') {
            $jqplotOptionsBase['seriesDefaults']['renderer'] = '@$.jqplot.BarRenderer@';
            $jqplotPlugins[] = 'barRenderer';
        }

        $groups = $data->recs;


        if (!empty($chartConfig['diff'])) {
            // Ще правим толкова графики, колкото различни стойности на полето
            // $chartConfig['diff'] има
            expect($mvc->getField($chartConfig['diff']));

            $groups = arr::group($groups, $chartConfig['diff']);
        } else {
            // Ще правим една графика
            $groups = array($groups);
        }

        if (!empty($chartConfig['series'])) {
            expect($mvc->getField($chartConfig['series']));

            foreach($groups as $i => $recs) {
                $groups[$i] = arr::group($recs, $chartConfig['series']);
            }
        } else {
            $groups = array($groups);
        }

        foreach ($groups as $recSeries) {

            $jqplotOptions = $jqplotOptionsBase;

            $ticks  = array();
            $series = array();
            $labels = array();
            $title  = NULL;

            foreach ($recSeries as $serTitle => $recs) {
                foreach ($recs as $i=>$rec) {
                    $row = $data->rows[$i];

                    if (!isset($title)) {
                        if (isset($chartConfig['diff'])) {
                            $options['axes']['yaxis']['label'] = $row->{$chartConfig['diff']};
                            $title = $row->{$chartConfig['diff']};
                        }

                        if (isset($chartConfig['titleTpl'])) {
                            $title = new core_ET($chartConfig['titleTpl']);
                            $title->placeObject($row);
                            $title = (string)$title;
                        }
                    }

                    $ticks[$row->{$chartConfig['ax']}] = $row->{$chartConfig['ax']};
                    $series[$serTitle][] = floatval($rec->{$chartConfig['ay']});

                    if (isset($chartConfig['labelTpl'])) {
                        $label = new core_ET($chartConfig['labelTpl']);
                        $label->placeObject($row);
                        $label = (string)$label;
                    } else {
                        $label = $row->{$chartConfig['ay']};
                    }

                    $jqplotOptions['series'][count($series)-1]['pointLabels']['labels'][] = $label;
                }
            }

            $series = array_values($series);
            $ticks  = array_values($ticks);

            $jqplotOptions['axes']['xaxis']['ticks'] = $ticks;
            $jqplotOptions['title'] = $title;

            $tpl->append(jqplot_Jqplot::chart($series, $jqplotOptions));
            $tpl->append('<hr/>');
        }

        jqplot_Jqplot::setup($tpl, $jqplotPlugins);
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