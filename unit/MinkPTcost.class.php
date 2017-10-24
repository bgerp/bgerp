<?php


/**
 *  Клас  'unit_MinkPTcost' - PHP тестове за транспортни разходи и зони
 *
 * @category  bgerp
 * @package   tests
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPTcost extends core_Manager {
     
    /**
     * Стартира последователно тестовете от MinkPTcost 
     */
    //http://localhost/unit_MinkPTcost/Run/
    public function act_Run()
    {
        if (!TEST_MODE) {
            return;
        }
        
        $res = '';
        $res .= "<br>".'MinkPTcost';
        $res .= "  1.".$this->act_InstallTcost();
        $res .= "  2.".$this->act_CreateDeliveryTerm();
        $res .= "  3.".$this->act_CreateFeeZones();
        return $res;
    }
    
    /**
     * Логване
     */
    public function SetUp()
    {
        $browser = cls::get('unit_Browser');
        //$browser->start('http://localhost/');
        $host = unit_Setup::get('DEFAULT_HOST');
        $browser->start($host);
        //Потребител DEFAULT_USER (bgerp)
        $browser->click('Вход');
        $browser->setValue('nick', unit_Setup::get('DEFAULT_USER'));
        $browser->setValue('pass', unit_Setup::get('DEFAULT_USER_PASS'));
        $browser->press('Вход');
        return $browser;
    }
  
    /**
     * Избор на фирма
     */
    public function SetFirm()
    {
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
        return $browser;
    }
    /**
     * 1. Инсталиране на пакета Tcost
     */
    //http://localhost/unit_MinkPTcost/InstallTcost/
    function act_InstallTcost()
    {
        // Логване
        $browser = $this->SetUp();
    
        $browser->click('Админ');
        $browser->setValue('search', 'Tcost');
        $browser->press('Филтрирай');
        //$browser->click('Активирай');
        if(strpos($browser->gettext(), 'Активирай')) {
        $browser->open('http://localhost/core_Packs/install/?pack=Tcost');
        //echo $browser->getHtml();
        }
    }
    
    /**
     * 2. Създаване на условие на доставка с изчисляване на транспортна себестойност
     */
    //http://localhost/unit_MinkPTcost/CreateDeliveryTerm/
    function act_CreateDeliveryTerm()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на условие на доставка
        $browser->click('Дефиниции');
        $browser->press('Нов запис');
        $browser->setValue('codeName', 'TRR');
        $browser->setValue('term', 'За изчисляване на транспортна себестойност');
        $browser->setValue('costCalc', 'Навла');
        $browser->setValue('calcCost', 'Включено');
        $browser->press('Запис');
        //return $browser->getHtml();
        
    }
    /**
     * 3. Създаване на транспортни зони
     */
    //http://localhost/unit_MinkPTcost/CreateFeeZones/
    function act_CreateFeeZones()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на транспортна зона 1
        $browser->click('Навла');
        $browser->press('Нов запис');
        $browser->setValue('name', 'Зона BG 1');
        $browser->setValue('deliveryTermId', 'TRR');
        $browser->press('Запис');
        $browser->click('Зона BG 1');
        
        // Добавяне на правило за изчисление към трансп. зона
        $browser->press('Нов запис');
        $browser->setValue('weight', '100');
        $browser->setValue('price', '20');
        $browser->press('Запис');
        $browser->press('Нов запис');
        $browser->setValue('weight', '200');
        $browser->setValue('price', '30');
        $browser->press('Запис');
        
        // Добавяне на държава и пощ. код към трансп. зона
        
        //clearfix21 tcost_Zones -'Нов запис' - втори бутон - не работи
        //$browser->press('Нов запис(2)');
        //$browser->setValue('countryId', 'BG');
        //$browser->setValue('pCode', '1000');
        //$browser->press('Запис');
        
        
        // Създаване на транспортна зона 2
        $browser->click('Навла');
        $browser->press('Нов запис');
        $browser->setValue('name', 'Зона BG 2');
        $browser->setValue('deliveryTermId', 'TRR');
        $browser->press('Запис');
        $browser->click('Зона BG 2');
    
    }
     
}