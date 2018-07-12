<?php


/**
 * Версията на JQueryUI, която се използва
 */
defIfNot(JQUERYUI_VERSION, '1.11.3');


/**
 * Клас 'jqueryui_Ui' - Работа с JQuery UI библиотеката
 *
 *
 * @category  bgerp
 * @package   jqueryui
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class jqueryui_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'JQueryUI';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'JQUERYUI_VERSION' => array('enum(1.8.2, 1.11.3)', 'caption=Версия на JQueryUI->Версия'),
    );
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        $conf = core_Packs::getConfig('jqueryui');
        
        return 'jqueryui/' . $conf->JQUERYUI_VERSION . '/jquery-ui.min.js';
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        $conf = core_Packs::getConfig('jqueryui');
        
        return 'jqueryui/' . $conf->JQUERYUI_VERSION . '/jquery-ui.min.css';
    }
}
