<?php


/**
 * Версия на JS компонента
 */
defIfNot('ESCPOS_HASH_LEN', '6');


/**
 * "Подправка" за кодиране
 */
defIfNot('ESCPOS_SALT', md5(EF_SALT . '_ESCPOS'));


/**
 *
 *
 * @category  bgerp
 * @package   escpos
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class escpos_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Escpos принтиране';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'ESCPOS_HASH_LEN' => array('int', 'caption=Дължина на хеша за линковете->Стойност'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'escpos_printer_TD2120N';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Закачаме плъгина
        $html .= core_Plugins::installPlugin('Мобилно принтиране на продажби', 'escpos_PrintPlg', 'sales_Sales', 'private');
        $html .= core_Plugins::installPlugin('Мобилно принтиране на ЕН', 'escpos_PrintPlg', 'store_ShipmentOrders', 'private');
        $html .= core_Plugins::installPlugin('Мобилно принтиране на фактури', 'escpos_PrintPlg', 'sales_Invoices', 'private');
        
        // Добавяне на етикет за производствена операция
        core_Classes::add('escpos_printer_TD2120N');
        core_Users::forceSystemUser();
        if (label_Templates::addFromFile('Етикет за прогрес на производствена операция', 'planning/tpl/DefaultTaskProgressLabel.shtml', 'defaultEscposTaskRec', array('100', '72'), 'bg', planning_ProductionTaskDetails::getClassId(), escpos_printer_TD2120N::getClassId())) {
            $html = "<li class='green'>Обновен шаблон за етикети на прогреса на производствената операция";
        } else {
            $html = '<li>Пропуснато обновяване на шаблон за прогреса на производствената операция</li>';
        }
        core_Users::cancelSystemUser();
        
        return $html;
    }
}
