<?php


/**
 * Колко пъти поне да се покаже дадена помощна информация на даден потребител
 * в отворено състояние
 */
defIfNot('HELP_MAX_OPEN_DISPLAY_CNT', 3);


/**
 * Колко време след първото показване, да се показва дадена помощна информация
 * в отворено състояние
 */
defIfNot('HELP_MAX_OPEN_DISPLAY_TIME', 1 * 24 * 60 * 60);


/**
 * Колко пъти поне да се покаже дадена помощна информация на даден потребител
 * в затворено състояние
 */
defIfNot('HELP_MAX_CLOSE_DISPLAY_CNT', 30);


/**
 * Колко време след първото показване, да се показва дадена помощна информация
 * в затворено състояние
 */
defIfNot('HELP_MAX_CLOSE_DISPLAY_TIME', 30 * 24 * 60 * 60);


/**
 * След колко на бездействие трябва да се покаже прозореца за помощ
 */
defIfNot('HELP_BGERP_INACTIVE_SECS', 15);


/**
 * URL за подаване на сигнал за поддръжка на bgERP
 */
defIfNot('BGERP_SUPPORT_URL', 'https://experta.bg/cal' . '_Tasks/new/?systemId=1');


/**
 * Дали автоматично да се попълват потребителското име и имейла при подаване на сигнал
 */
defIfNot('HELP_AUTO_FILL_USER_NAME_AND_EMAIL', 'yes');


/**
 * class help_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с модул Help
 *
 *
 * @category  bgerp
 * @package   help
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class help_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'help_Info';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Подсистема за помощ';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'help_Info',
        'help_Log',
        'migrate::version2',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'help';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'HELP_MAX_OPEN_DISPLAY_TIME' => array('time', 'caption=Отворен изглед за помощната информация->Максимално време'),
        
        'HELP_MAX_OPEN_DISPLAY_CNT' => array('int', 'caption=Отворен изглед за помощната информация->Максимален брой пъти'),
        
        'HELP_MAX_CLOSE_DISPLAY_TIME' => array('time', 'caption=Затворен изглед за помощната информация->Максимално време'),
        
        'HELP_MAX_CLOSE_DISPLAY_CNT' => array('int', 'caption=Затворен изглед за помощната информация->Максимален брой пъти'),
        
        'HELP_BGERP_INACTIVE_SECS' => array('time(suggestions=10 сек.|15 сек.|30 сек.|1 мин)', 'caption=След колко време на бездействие трябва да се покаже прозореца за помощ->Време'),
    
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
        $html .= $Plugins->installPlugin('helpHint', 'help_Plugin', 'plg_ProtoWrapper', 'family');
        
        $html .= $Plugins->installPlugin('Въпроси за bgERP', 'help_BgerpPlg', 'core_Manager', 'family');
        
        return $html;
    }
    
    
    /**
     * Миграция към втория формат
     */
    public function version2()
    {
        $query = help_Info::getQuery();
        $info = $query->mvc;
        $info->setupMvc();
        
        if ($info->db->isFieldExists($info->dbTableName, 'action') || 1) {
            //$query->FLD('action', 'varchar');
            //$query->delete("#action != 'list'");
            //$info->db->query("ALTER TABLE `{$info->dbTableName}` DROP COLUMN `action`");
            $query = $info->getQuery();
            while ($rec = $query->fetch()) {
                $info->save($rec);
            }
        }
    }
}
