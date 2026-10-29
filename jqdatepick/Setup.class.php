<?php


/**
 * @todo Чака за документация...
 */
defIfNot('JQDATEPICKER_VERSION', 'v5.0.0');


/**
 * Клас 'jqdatepick_Setup' -
 *
 *
 * @category  vendors
 * @package   jqdatepick
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class jqdatepick_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Адаптер за JQDatepicker: Показва календар в полетата за избор на дата';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'JQDATEPICKER_VERSION' => array('enum(v4.0.6, v5.0.0, v5.1.1)', 'mandatory, caption=Версията на програмата->Версия')
    
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('Избор на дата', 'jqdatepick_Plugin', 'type_Date', 'private');
        
        return $html;
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        $confCore = core_Packs::getConfig('core');

        return implode(', ', self::getJsFiles($confCore->EF_DEFAULT_LANGUAGE ?? 'bg', false));
    }


    /**
     * Подрежда ресурсите за адаптера и compactor; 4.0.6 няма kbw-plugin зависимост.
     */
    public static function getJsFiles($lg, $includeExtensions = true)
    {
        $conf = core_Packs::getConfig('jqdatepick');
        $version = $conf->JQDATEPICKER_VERSION ?? JQDATEPICKER_VERSION;
        $path = 'jqdatepick/' . $version . '/';
        $files = array();
        if ($version !== 'v4.0.6') {
            $files[] = $path . 'jquery.plugin.min.js';
        }
        $files[] = $path . 'jquery.datepick.js';
        if ($includeExtensions) {
            $files[] = $path . 'jquery.datepick.ext.js';
        }
        $files[] = $path . 'jquery.datepick-' . $lg . '.js';

        return $files;
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        $conf = core_Packs::getConfig('jqdatepick');
        
        return 'jqdatepick/' . $conf->JQDATEPICKER_VERSION . '/jquery.datepick.css';
    }
}
