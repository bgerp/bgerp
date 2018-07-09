<?php


/**
 * JS файловете
 */
defIfNot('COMPACTOR_JS_FILES', 'jquery/[#jquery::JQUERY_VERSION#]/jquery.min.js, js/efCommon.js, js/overthrow-detect.js, toast/[#toast::TOAST_MESSAGE_VERSION#]/javascript/jquery.toastmessage.js');


/**
 * CSS файловете
 */
defIfNot('COMPACTOR_CSS_FILES', 'css/common.css, css/Application.css, toast/[#toast::TOAST_MESSAGE_VERSION#]/resources/css/jquery.toastmessage.css, css/default-theme.css');


/**
 *
 *
 * @category  compactor
 * @package   toast
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class compactor_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Компактиране на CSS и JS. Ускорява зареждането в браузъра';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html .= parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за показване на статусите като toast съобщения
        $html .= $Plugins->installPlugin('Компактиране на файлове', 'compactor_Plugin', 'page_Html', 'private');
        
        return $html;
    }
    
    
    /**
     * Захранване с данни
     */
    public function loadSetupData($itr = '')
    {
        $res .= parent::loadSetupData($itr);
        
        // JS и CSS файловете от конфигурацията от константите
        // Не се вземат с ::get, защото се връщат записаните в модела
        $jsFilesArrB = arr::make(COMPACTOR_JS_FILES);
        $cssFilesArrB = arr::make(COMPACTOR_CSS_FILES);
        
        $jsFilesArr = array();
        $cssFilesArr = array();
        
        foreach ($jsFilesArrB as $jsFile) {
            $jsFile = $this->preparePacksPath('compactor', $jsFile);
            $jsFilesArr[$jsFile] = $jsFile;
        }
        
        foreach ($cssFilesArrB as $cssFile) {
            $cssFile = $this->preparePacksPath('compactor', $cssFile);
            $cssFilesArr[$cssFile] = $cssFile;
        }
        
        $usedPacksArr = core_Packs::getUsedPacksNamesArr();
        
        // Всички записани пакети
        foreach ($usedPacksArr as $name) {
            
            // Ако няма име
            if (!$name) {
                continue;
            }
            
            // Сетъп пакета
            $pack = $name  . '_Setup';
            
            // Ако файлът съществува
            if (cls::load($pack, true)) {
                
                // Инстанция на пакета
                $inst = cls::get($pack);
                
                // Вземаме CSS файловете и заместваме плейсхолдерите от конфига
                if (method_exists($inst, 'getCommonCss')) {
                    $commonCss = $inst->getCommonCss();
                }
                
                // Вземаме JS файловете и заместваме плейсхолдерите от конфига
                if (method_exists($inst, 'getCommonJs')) {
                    $commonJs = $inst->getCommonJs();
                }
                
                // Ако няма файлове за добавяне
                if (!$commonCss && !$commonJs) {
                    continue;
                }
                
                // Добавяме зададените CSS файлове към главния
                if ($commonCss) {
                    $commonCssArr = arr::make($commonCss, true);
                    $cssFilesArr = array_merge((array) $cssFilesArr, (array) $commonCssArr);
                }
                
                // Добавяме зададените JS файлове към главния
                if ($commonJs) {
                    $commonJsArr = arr::make($commonJs, true);
                    $jsFilesArr = array_merge((array) $jsFilesArr, (array) $commonJsArr);
                }
            }
        }
        
        $data = array();
        
        // Ако има добавен CSS файл, добавяме ги към конфигурацията
        if (!empty($cssFilesArr)) {
            $cssFilesStr = implode(', ', $cssFilesArr);
            $data['COMPACTOR_CSS_FILES'] = $cssFilesStr;
            $res .= '<li>CSS файловете за компактиране: ' . $cssFilesStr;
        }
        
        // Ако има добавен JS файл, добавяме ги към конфигурацията
        if (!empty($jsFilesArr)) {
            $jsFilesStr = implode(', ', $jsFilesArr);
            $data['COMPACTOR_JS_FILES'] = $jsFilesStr;
            $res .= '<li>JS файловете за компактиране: ' . $jsFilesStr;
        }
        
        // Ако има данни за добавяме, обновяваме данние от компактора
        if (!empty($data)) {
            core_Packs::setConfig('compactor', $data);
        }
        
        return $res;
    }
}
