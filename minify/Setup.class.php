<?php

/** Досегашният CSS minifier остава избран по подразбиране. */
defIfNot('MINIFY_CSS_VERSION', 'legacy');

/** Досегашният JS minifier остава избран по подразбиране. */
defIfNot('MINIFY_JS_VERSION', 'legacy');

/**
 * Клас 'minify_Setup'
 *
 *
 * @category  vendors
 * @package   minify
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class minify_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Минифициране на CSS и JS файловете за ускорено зареждане';


    /**
     * Избираеми библиотеки, без промяна на досегашното поведение по подразбиране.
     */
    public $configDescription = array(
        'MINIFY_CSS_VERSION' => array('enum(legacy=Досегашна 2.4.8-2,4.1.1)', 'mandatory, caption=CSSMin->Версия'),
        'MINIFY_JS_VERSION' => array('enum(legacy=Досегашна,jshrink-1.8.1=JShrink 1.8.1)', 'mandatory, caption=JavaScript->Минификатор'),
    );


    /**
     * Зарежда вградената нова библиотека с отделни имена на класовете.
     *
     * @param string $library css или jshrink
     * @return string Име на основния клас
     */
    public static function loadLibrary($library)
    {
        expect(in_array($library, array('css', 'jshrink'), true), 'Неподдържана minify библиотека');
        if ($library === 'jshrink') {
            require_once getFullPath('minify/jshrink/1.8.1/Minifier.php');
            return 'JShrinkV181\\Minifier';
        }
        foreach (array('Colors', 'Utils', 'Minifier') as $file) {
            require_once getFullPath('minify/cssmin/4.1.1/' . $file . '.php');
        }
        return 'tubalmartin\\CssMinV411\\Minifier';
    }
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->forcePlugin('Minify', 'minify_Plugin', 'core_Sbf', 'private');
        
        $sbf = cls::get('core_Sbf');
        $sbf->loadSingle('minify_Plugin');
        
        $delCnt = core_Os::deleteOldFiles(EF_SBF_PATH, 1, "#^_[a-z0-9\-\/_]+#i", "#[a-z0-9\-\/_]+(.js|.css)$#i");
        if ($delCnt) {
            $html .= "<li class='status-new'>Изтрити са {$delCnt} .js и .css файла в " . EF_SBF_PATH . '/</li>';
        }
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        $html = parent::deinstall();
        
        $delCnt = core_Os::deleteOldFiles(EF_SBF_PATH, 1, "#^_[a-z0-9\-\/_]+#i", "#[a-z0-9\-\/_]+(.js|.css)$#i");
        if ($delCnt) {
            $html .= "<li class='status-new'>Изтрити са {$delCnt} .js и .css файла в " . EF_SBF_PATH . '/</li>';
        }
        
        return $html;
    }
}
