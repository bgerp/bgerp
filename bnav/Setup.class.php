<?php



/**
 * class bnav_Setup
 *
 * Инсталиране/Деинсталиране на
 * Драйвър за импортиране от csv файл на Бизнес навигатор
 *
 *
 * @category  bgerp
 * @package   bnav
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bnav_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Драйвър за импорт от "Бизнес навигатор"';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('bnavPlugin', 'bnav_Plugin', 'cat_Products', 'private');
        
        // Добавяме Импортиращия драйвър в core_Classes
        $html .= core_Classes::add('bnav_bnavImporter');
        $html .= cls::get('cat_Products')->setupMvc();
        
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
        if ($delCnt = $Plugins->deinstallPlugin('bnav_Plugin')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'bnav_Plugin'";
        } else {
            $html .= '<li>Не са премахнати закачания на плъгина';
        }
        
        return $html;
    }
}
