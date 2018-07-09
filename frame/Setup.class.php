<?php


/**
 * Какъв да е десетичният разделител на числата при експорт в csv
 */
defIfNot('FRAME_TYPE_DECIMALS_SEP', 'comma');


/**
 * Как да е форматирана датата
 */
defIfNot('FRAME_FORMAT_DATE', 'dot');


/**
 * class frame_Setup
 *
 * Инсталиране/Деинсталиране на пакета frame
 *
 *
 * @category  bgerp
 * @package   frame
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class frame_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    public $depends = '';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    public $startCtr = 'frame_Reports';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Отчети и табла';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'FRAME_TYPE_DECIMALS_SEP' => array('enum(dot=точка,comma=запетая)', 'caption=Десетичен разделител на числата при експорт в csv->Символ'),
        'FRAME_FORMAT_DATE' => array('enum(dot=точка (дд.мм.гггг),slash=наклонена черта (мм/дд/гг))', 'caption=Формат на датата->Формат с'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'frame_Reports',
    
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'report,dashboard';
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Activate Pending Reports',
            'description' => 'Активиране на чакащи отчети',
            'controller' => 'frame_Reports',
            'action' => 'ActivateEarlyOn',
            'period' => 1440,
            'offset' => 60,
            'timeLimit' => 50
        ),
    );
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
