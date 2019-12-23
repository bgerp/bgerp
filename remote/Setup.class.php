<?php


/**
 * Получаване на нотификации от отдалечени системи
 */
defIfNot('REMOTE_RECEIVE_NOTIFICATIONS', 'yes');


/**
 * class newsbar_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с пакета за новини
 *
 *
 * @category  bgerp
 * @package   neswbar
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class remote_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'remote_Authorizations';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Оторизация от и към външни услуги';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'REMOTE_RECEIVE_NOTIFICATIONS' => array('enum(yes=Да,no=Не)', 'caption=Получаване на нотификации от отдалечени системи->Избор, customizeBy=powerUser'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'remote_Authorizations',
        'remote_Tokens',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'remote';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $html .= core_Classes::add('remote_BgerpDriver');
        
        $rec = new stdClass();
        $rec->systemId = 'UpdateRemoteNotification';
        $rec->description = 'Обновяване на отдалечени нотификации';
        $rec->controller = 'remote_BgerpDriver';
        $rec->action = 'UpdateRemoteNotification';
        $rec->period = 3;
        $rec->timeLimit = 50;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'DeleteExpiredTokens';
        $rec->description = 'Изтриване на изтеклите tokens';
        $rec->controller = 'remote_Tokens';
        $rec->action = 'DeleteExpiredTokens';
        $rec->period = 50;
        $rec->timeLimit = 50;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'AlertForNotifications';
        $rec->description = 'Изтриване на изтеклите tokens';
        $rec->controller = 'remote_Authorizations';
        $rec->action = 'AlertForNotifications';
        $rec->period = 1;
        $rec->timeLimit = 50;
        $html .= core_Cron::addOnce($rec);
        
        return $html;
    }
}
