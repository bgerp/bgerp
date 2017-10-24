<?php



/**
 * Символ за индикиране на табовете в които се пише
 */
defIfNot('WRITETAB_SYMBOL', '✍');


/**
 * Цвят на символа
 */
defIfNot('WRITETAB_COLOR', '#ffffff');


/**
 * Фон на символа
 */
defIfNot('WRITETAB_BGROUND', '#ff3333');


/**
 * @category  bgerp
 * @package   writetab
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class writetab_Setup extends core_ProtoSetup 
{
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
    var $info = "Индикиране на табовете в които са отворени форми за въвеждане";
    

    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
           'WRITETAB_SYMBOL' => array ('varchar(1)', 'mandatory, caption=Символ'),
           'WRITETAB_COLOR' => array ('color_Type', 'mandatory, caption=Цвят'),
           'WRITETAB_BGROUND' => array ('color_Type', 'mandatory, caption=Фон'),
         );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('Write Tabs', 'writetab_Plugin', 'core_Form', 'private');
        
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
        
        // Инсталираме клавиатурата към password полета
        if($delCnt = $Plugins->deinstallPlugin('writetab_Plugin')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'writetab_Plugin'";
        } else {
            $html .= "<li>Не са премахнати закачания на плъгина";
        }
        
        return $html;
    }
}