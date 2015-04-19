<?php



/**
 * Клас 'minify_Setup'
 *
 *
 * @category  vendors
 * @package   minify
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class minify_Setup extends core_ProtoSetup {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Минифициране на CSS и JS файловете за ускорено зареждане";
    
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->forcePlugin('Minify', 'minify_Plugin', 'core_Sbf', 'private');
        
        $sbf = cls::get('core_Sbf');
        $sbf->loadSingle('minify_Plugin');

        $delCnt = core_Os::deleteOldFiles(EF_SBF_PATH, 1, "#^_[a-z0-9\-\/_]+#i", "#[a-z0-9\-\/_]+(.js|.css)$#i");
        if($delCnt) {
            $html .= "<li class='status-new'>Изтрити са $delCnt .js и .css файла в " . EF_SBF_PATH . "/</li>";
        }

        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	$html = parent::deinstall();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от core_Sbf
        $Plugins->deinstallPlugin('minify_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'minify_Plugin'";
        $sbf = cls::get('core_Sbf');
        $sbf->unloadPlugin('minify_Plugin');

        $delCnt = core_Os::deleteOldFiles(EF_SBF_PATH, 1, "#^_[a-z0-9\-\/_]+#i", "#[a-z0-9\-\/_]+(.js|.css)$#i");
        if($delCnt) {
            $html .= "<li class='status-new'>Изтрити са $delCnt .js и .css файла в " . EF_SBF_PATH . "/</li>";
        }
               
        return $html;
    }
}