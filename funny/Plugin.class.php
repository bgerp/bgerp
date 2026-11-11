<?php


/**
 * Добавя ефекти само в интерактивни страници на powerUser.
 *
 * @category  bgerp
 * @package   funny
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class funny_Plugin extends core_Plugin
{
    /**
     * Подаване на личните настройки и локалните ресурси в интерактивна страница.
     *
     * @param core_page_Active $page
     * @param mixed            $res
     * @param stdClass         $files Списъци с CSS и JavaScript файлове
     */
    public function on_BeforeAppendFiles($page, &$res, &$files)
    {
        if (core_Users::getCurrent() <= 0 || !haveRole('powerUser') || Mode::is('printing') || Mode::is('pdf') ||
            Mode::is('text') || Mode::is('readOnly') || Mode::is('noWrapper') || Request::get('ajax_mode')) {
            return;
        }

        $mode = funny_Setup::get('MODE');
        $frequency = funny_Setup::get('FREQUENCY');
        $weather = funny_Setup::get('WEATHER');
        $forecast = in_array($weather, array('on', 'sound'), true) || ($mode == 'auto' && $frequency != 'off') ? $this->getForecast() : array();
        $weatherEnabled = $this->appendWeather($page, $files, $forecast);
        if (($mode == 'off' || $frequency == 'off') && !$weatherEnabled) {
            return;
        }

        $year = (int) date('Y');
        $easterDates = array();
        for ($y = $year; $y <= $year + 1; $y++) {
            $easterDates[$y] = date('Y-m-d', dt::getOrthodoxEasterTms($y));
        }

        $options = array(
            'mode' => $mode,
            'characters' => explode(',', (string) funny_Setup::get('CHARACTERS')),
            'trigger' => funny_Setup::get('TRIGGER'),
            'frequency' => $frequency,
            'sound' => funny_Setup::get('SOUND'),
            'strength' => funny_Setup::get('STRENGTH'),
            'scoreKey' => 'funny.best.' . EF_APP_NAME . '.' . (int) core_Users::getCurrent(),
            'easterDates' => $easterDates,
            'forecast' => $forecast,
            'labels' => array('caught' => tr('Уцелени'), 'escaped' => tr('Избягали'), 'best' => tr('Рекорд')),
            'spriteUrl' => sbf('funny/img/characters.svg', ''),
        );

        $files->css = array_merge((array) ($files->css ?? array()), array('funny/css/funny.css'));
        $files->js = array_merge((array) ($files->js ?? array()), array('funny/js/funny.js'));
        jquery_Jquery::run($page, 'window.funnyEffects.init(' . json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');');
    }


    /**
     * Една налична прогноза за ефектите и сезонните герои, включително при изключени ефекти.
     *
     * @return array Снимка от локалната прогноза без заявка към доставчика
     */
    protected function getForecast()
    {
        $today = dt::now(false);
        $icon = '';
        $forecast = null;
        foreach (array('visualcrossing', 'darksky') as $provider) {
            if (!core_Packs::isInstalled($provider)) continue;
            $class = $provider . '_Forecasts';
            if ($provider == 'visualcrossing') {
                $hourly = $class::getForecast($today, date('G'));
                $icon = $hourly->icon ?? '';
                if ($icon && preg_match('/^[a-z-]+$/D', $icon)) { $forecast = $hourly; break; }
            }
            $daily = $class::getForecast($today);
            $icon = $daily->icon ?? '';
            if ($icon && preg_match('/^[a-z-]+$/D', $icon)) { $forecast = $daily; break; }
            $icon = '';
        }
        if (!$icon) return array();

        return array(
            'icon' => $icon,
            'temperature' => is_numeric($forecast->low ?? null) && is_numeric($forecast->high ?? null) ? ((float) ($forecast->low ?? 0) + (float) ($forecast->high ?? 0)) / 2 : null,
            'wind' => is_numeric($forecast->wind ?? null) ? (float) ($forecast->wind ?? 0) : null,
            'windDirection' => is_numeric($forecast->winddir ?? null) ? (float) ($forecast->winddir ?? 0) : null,
            'expires' => strtotime($today . ' +1 day') * 1000,
        );
    }


    /**
     * Подаване на общата прогноза към визуалните ефекти.
     *
     * @param core_page_Active $page
     * @param stdClass $files
     * @param array $forecast
     * @return bool Има ли прогноза за обработване и на ръчните появи без герои
     */
    protected function appendWeather($page, &$files, $forecast)
    {
        $weather = funny_Setup::get('WEATHER');
        if (!in_array($weather, array('on', 'sound'), true) || !$forecast) return false;
        $frequency = funny_Setup::get('FREQUENCY');
        $options = array_merge($forecast, array(
            'mode' => $weather,
            'strength' => funny_Setup::get('STRENGTH'),
            'standalone' => funny_Setup::get('MODE') == 'off' || $frequency == 'off',
            'frequency' => $frequency,
        ));
        $files->css = array_merge((array) ($files->css ?? array()), array('funny/css/weather.css'));
        $files->js = array_merge((array) ($files->js ?? array()), array('funny/js/weather.js'));
        jquery_Jquery::run($page, 'window.funnyWeather.init(' . json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');');
        return true;
    }
}
