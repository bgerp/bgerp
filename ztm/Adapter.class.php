<?php


/**
 * Клас 'ztm_Adapter'
 *
 * Табло с настройки за състояния
 *
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class ztm_Adapter extends core_Mvc
{
    public function act_Default()
    {
        Mode::set('wrapper', 'page_PureHtml');
        
        
        $tpl = new ET(getTplFromFile('ztm/tpl/dashboard.shtml'));
        
        
        $tpl->push('ztm/css/bootstrap.min.css', 'CSS');
        $tpl->push('ztm/css/bootstrap-grid.css', 'CSS');
        $tpl->push('ztm/css/font-awesome.min.css', 'CSS');
        $tpl->push('ztm/css/rangeslider.css', 'CSS');
        $tpl->push('ztm/css/layout.css', 'CSS');
        
        
        $tpl->push('ztm/js/jquery-3.1.1.min.js', 'JS');
        $tpl->push('ztm/js/popper.min.js', 'JS');
        $tpl->push('ztm/js/bootstrap.min.js', 'JS');
        $tpl->push('ztm/js/skycons.js', 'JS');
        $tpl->push('ztm/js/rangeslider.js', 'JS');
        $tpl->push('ztm/js/custom.js', 'JS');
        
        
        $now = dt::now(false);
        if (core_Packs::isInstalled('darksky') && darksky_Setup::get('API_KEY') && darksky_Setup::get('LOCATION')) {
            $data = darksky_Forecasts::getForecast($now);
        } else {
            $data = '';
        }
        $data = json_encode($data);
        jquery_Jquery::run($tpl, "prepareDashboard({$data})");
        
        return $tpl;
    }
}
