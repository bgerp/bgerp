<?php


/**
 * Дефолт група за артикули без ддс
 */
defIfNot('TREMOL_BASE_GROUP_WITH_ZERO_VAT', 'A');


/**
 * Дефолт група за артикули с ддс
 */
defIfNot('TREMOL_BASE_GROUP_WITH_VAT', 'B');


/**
 * Дефолт група за артикули с ддс
 */
defIfNot('TREMOL_GROUP_A', 0);


/**
 * Дефолт група за артикули с ддс
 */
defIfNot('TREMOL_GROUP_B', 1);


/**
 * Дефолт група за артикули с ддс
 */
defIfNot('TREMOL_GROUP_V', 2);


/**
 * Дефолт група за артикули с ддс
 */
defIfNot('TREMOL_GROUP_G', 3);


/**
 * class tremol_Setup
 *
 * Инсталиране/Деинсталиране на
 * Драйвър за работа на POS модула с фискален принтер на Тремол
 *
 *
 * @category  vendors
 * @package   tremol
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tremol_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Фискален принтер на Тремол';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
            'TREMOL_BASE_GROUP_WITH_ZERO_VAT' => array('customKey(mvc=acc_VatGroups,key=sysId,select=title,allowEmpty)', 'caption=Дефолт за артикули с нулева ставка на ДДС->Група'),
            'TREMOL_BASE_GROUP_WITH_VAT' => array('customKey(mvc=acc_VatGroups,key=sysId,select=title,allowEmpty)', 'caption=Дефолт за артикули с  ДДС->Група'),
            'TREMOL_GROUP_A' => array('int', 'caption=Кодове за синхронизация с фискалния принтер->Група "А"'),
            'TREMOL_GROUP_B' => array('int', 'caption=Кодове за синхронизация с фискалния принтер->Група "Б"'),
            'TREMOL_GROUP_V' => array('int', 'caption=Кодове за синхронизация с фискалния принтер->Група "В"'),
            'TREMOL_GROUP_G' => array('int', 'caption=Кодове за синхронизация с фискалния принтер->Група "Г"'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Добавяме драйвъра в core_Classes
        $html .= core_Classes::add('tremol_FiscPrinterDriver');
        
        return $html;
    }
}
