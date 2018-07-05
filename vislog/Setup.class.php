<?php


/**
 *  Време в което не се логва заявка от същото ip/ресурс
 */
defIfNot('VISLOG_ALLOW_SAME_IP', 5 * 60);


/**
 * Клас 'vislog_Setup' -
 *
 *
 * @category  vendors
 * @package   vislog
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class vislog_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'vislog_History';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Хронология за действията на посетителите на сайта';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'vislog_HistoryResources',
            'vislog_History',
            'vislog_Referer',
            'vislog_IpNames',
            'vislog_Adwords',
        );
    

    public $configDescription = array(
            'VISLOG_ALLOW_SAME_IP' => array('time', 'caption=Време за недопускане на запис за едни и същи ip/ресурс->Време'),
        );

         
    /**
     * Роли за достъп до модула
     */
    //var $roles = 'vislog';


    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(3.53, 'Сайт', 'Лог', 'vislog_History', 'default', 'admin, ceo, cms'),
        );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'vislog_reports_IpImpl,vislog_reports_Resources';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Прикачаме плъгина
        $html .= $Plugins->forcePlugin('Декориране на IP', 'vislog_DecoratePlugin', 'type_Ip', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        return '';
    }
}
