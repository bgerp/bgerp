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
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class writetab_Setup extends core_ProtoSetup
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
    public $info = 'Индикиране на табовете в които са отворени форми за въвеждане';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'WRITETAB_SYMBOL' => array('varchar(1)', 'mandatory, caption=Символ'),
        'WRITETAB_COLOR' => array('color_Type', 'mandatory, caption=Цвят'),
        'WRITETAB_BGROUND' => array('color_Type', 'mandatory, caption=Фон'),
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
        $html .= $Plugins->installPlugin('Write Tabs', 'writetab_Plugin', 'core_Form', 'private');
        
        return $html;
    }
}
