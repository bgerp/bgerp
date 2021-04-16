<?php


/**
 * Версия на JS компонента
 */
defIfNot('AUTOSIZE_VERSION', 'v1.18.4');


/**
 * Максималните редове в широк режим
 */
defIfNot('AUTOSIZE_MAX_ROWS_WIDE', '600');


/**
 * Максималните редове в тесен режим
 */
defIfNot('AUTOSIZE_MAX_ROWS_NARROW', '400');


/**
 * Клас 'jqdatepick_Setup' -
 *
 *
 * @category  vendors
 * @package   autosize
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class autosize_Setup extends core_ProtoSetup
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
    public $info = 'Автоматично увеличаване на височината на полетата за въвеждане на текст';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'AUTOSIZE_VERSION' => array('enum(v1.18.4=v1.18.4,
                                                 v1.18.9=v1.18.9)', 'mandatory, caption=Версията на програмата->Версия'),
    );
    
    
    /**
     * Плъгини, които трябва да се инсталират
     */
    public $plugins = array(
        array('Редове на текст', 'autosize_Plugin', 'type_Richtext', 'private'),
    );
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        $conf = core_Packs::getConfig('autosize');
        
        $res = 'autosize/' .$conf->AUTOSIZE_VERSION. '/jquery.autosize.min.js';
        
        return $res;
    }
}
