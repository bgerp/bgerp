<?php


/**
 * Надценка за транспорт - твърда
 */
defIfNot('TCOST_ADD_TAX', 0);


/**
 * Надценка за транспорт - твърда
 */
defIfNot('TCOST_ADD_PER_KG', 0);


/**
 * Калкулиране на транспорт
 *
 *
 * @category  bgerp
 * @package   tcost
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tcost_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'cat=0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'tcost_FeeZones';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Калкулиране на цени за транспорт';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'tcost_FeeZones',
        'tcost_Zones',
        'tcost_Fees',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'tcost';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.5, 'Логистика', 'Навла', 'tcost_FeeZones', 'default', 'tcost, ceo'),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'TCOST_ADD_TAX' => array('double(smartRound=2)', 'caption=Надценки за транспорт->Твърда,unit=BGN'),
        'TCOST_ADD_PER_KG' => array('double(smartRound=2)', 'caption=Надценки за транспорт->За 1 кг,unit=BGN'),
    
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
    
    
    /**
     * Добавяне на валута към навлата
     */
    public function addFeeCurrencies()
    {
        if (cls::load('tcost_Fees', true)) {
            try {
                $Fees = cls::get('tcost_Fees');
                $Fees->setupMVC();
                
                $currencyId = acc_Periods::getBaseCurrencyCode();
                $query = $Fees->getQuery();
                $query->where('#currencyId IS NULL');
                while ($rec = $query->fetch()) {
                    $rec->currencyId = $currencyId;
                    $Fees->save($rec, 'currencyId');
                }
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
        }
    }
}
