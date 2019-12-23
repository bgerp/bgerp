<?php


/**
 * Да се правили опит за автоматично създаване на оферта от артикул
 */
defIfNot('AUTO_TRY_TO_CREATE_QUOTATION_FROM_INQUIRY', 'no');


/**
 * class auto_Setup
 *
 * Пакет за автоматизации
 *
 *
 * @category  bgerp
 * @package   auto
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class auto_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Пакет за автоматизации';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'auto_Calls',
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'auto_handler_CreateQuotationFromInquiry';
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Do automations',
            'description' => 'Извършване на автоматизации',
            'controller' => 'auto_Calls',
            'action' => 'Automations',
            'period' => 1,
            'offset' => 0,
            'timeLimit' => 100
        ));
    
    
    /**
     * Зареждане на начални данни
     */
    public function loadSetupData($itr = '')
    {
        $html = parent::loadSetupData($itr);
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Автоматично създаване на оферта от запитване', 'auto_plg_QuotationFromInquiry', 'marketing_Inquiries2', 'private');
        
        return $html;
    }
}
