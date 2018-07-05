<?php

/**
 * API key за google
 */
defIfNot('GOOGLE_API_KEY', '');


/**
 * class google_Setup
 *
 * Инсталиране/Деинсталиране на
 * плъгини свързани с 'google'
 *
 * @category  vendors
 * @package   google
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class google_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = null;
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = null;
    
    
    /**
     * Описание на модула
     */
    public $info = 'Услуги на Google';
    
    
    /**
     * Необходими пакети
     */
    public $depends = '';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
             
            'GOOGLE_API_KEY' => array('varchar', 'caption=Ключ за приложенията на google->API KEY')
    );
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        //
        // Инсталиране на плъгин за автоматичен превод
        //
        $html .= core_Plugins::installPlugin('core_Lg Translate', 'google_plg_LgTranslate', 'core_Lg', 'private');
        
        return $html;
    }
    

    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        $html = parent::deinstall();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
    
        // Инсталираме клавиатурата към password полета
        if ($delCnt = $Plugins->deinstallPlugin('google_plg_LgTranslate')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'google_plg_LgTranslate'";
        } else {
            $html .= '<li>Не са премахнати закачания на плъгина';
        }
    
        return $html;
    }
}
