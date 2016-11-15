<?php


/**
 *  Клас  'unit_MinkPPrices' - PHP тестове за ценообразуване и ценоразписи
 *
 * @category  bgerp
 * @package   tests
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPPrices extends core_Manager {
     
    /**
     * Стартира последователно тестовете от MinkPPrices 
     */
    //http://localhost/unit_MinkPPrices/Run/
    public function act_Run()
    {
        if (!TEST_MODE) {
            return;
        }
        
        $res = '';
        $res .= "<br>".'MinkPPrices';
        $res .= "  1.".$this->act_EditPriceList();
        $res .= "  2.".$this->act_AddPriceList();
        $res .= "  3.".$this->act_AddCustomerPriceList();
        $res .= "  4.".$this->act_SetCustomerPriceList();
        return $res;
    }
    
    /**
     * Логване
     */
    public function SetUp()
    {
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
        
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
     * 1. Редакция на ценова политика
     */
    //http://localhost/unit_MinkPPrices/EditPriceList/
    function act_EditPriceList()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Отваряне на Ценова политика "Каталог"
        $browser->click('Всички');
        $browser->click('Проекти');
        $browser->click('Ценови политики');
        $browser->press('Папка');
        $browser->click('Ценова политика "Каталог"');
        //Задаване на цена
        $browser->press('Стойност');
        $browser->setValue('productId', 'Плик 7 л');
        $browser->setValue('price', '0.6');
        $enddate=strtotime("+10 Days");
        $browser->setValue('validUntil[d]', date('d-m-Y', $enddate));
        $browser->press('Запис');
        if(strpos($browser->gettext(), '0,60000 BGN с ДДС')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно заредена цена', 'warning');
        }
        //Задаване на цена - марж
        $browser->press('Продуктов марж');
        $browser->setValue('productId', 'Труд');
        $browser->setValue('targetPrice', '20');
        $enddate=strtotime("+30 Days");
        $browser->setValue('validUntil[d]', date('d-m-Y', $enddate));
        $browser->press('Запис');
        if(strpos($browser->gettext(), '[Себестойност] + 66,67')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно заредена цена по марж', 'warning');
        }
        //Задаване на групов марж
        $browser->press('Групов марж');
        $browser->setValue('groupId', 'Ценова група » Промоция');
        $browser->setValue('discount', '11');
        $enddate=strtotime("+3 Days");
        $browser->setValue('validFrom[d]', date('d-m-Y', $enddate));
        $browser->setValue('validFrom[t]', '10:00');
        $enddate=strtotime("+33 Days");
        $browser->setValue('validUntil[d]', date('d-m-Y', $enddate));
        $browser->setValue('validUntil[t]', '18:30');
        $browser->press('Запис');
        if(strpos($browser->gettext(), '[Себестойност] + 11,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно зареден групов марж', 'warning');
        }
        
    }
    
    /**
     * 2. Добавяне на ценова политика (от папка на проект)
     */
    //http://localhost/unit_MinkPPrices/AddPriceList/
    function act_AddPriceList()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Отваряне на папка Ценова политика
        $browser->click('Всички');
        $browser->click('Проекти');
        $browser->click('Ценови политики');
        $browser->press('Папка');
        $browser->press('Ценова политика');
        //Добавяне на ценова политика
        $browser->setValue('title', 'Ценова политика 2017');
        $browser->setValue('parent', 'Каталог');
        $browser->setValue('discountCompared', 'Каталог');
        $browser->setValue('significantDigits', '4');
        $browser->setValue('defaultSurcharge', '9');
        $browser->setValue('minSurcharge', '15');
        $browser->setValue('maxSurcharge', '19');
        $browser->press('Запис');
        
        if(strpos($browser->gettext(), 'Надценка по подразбиране 9,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна надценка', 'warning');
        }
       
    }
    
}