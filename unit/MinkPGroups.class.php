<?php


/**
 *  Клас  'unit_MinkPGroups' - PHP тестове за групи, подгрупи, филтър (артикули и контрагенти)
 *
 * @category  bgerp
 * @package   tests
 * @author    Pavlinka Dainovska <pdainovska@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPGroups extends core_Manager {
     
    /**
     * Стартира последователно тестовете от MinkPGroups 
     */
    //http://localhost/unit_MinkPGroups/Run/
    public function act_Run()
    {
        if (!TEST_MODE) {
            return;
        }
        
        $res = '';
        $res .= "<br>".'MinkPGroups';
        $res .= "  1.".$this->act_CreateGroup1();
        $res .= "  2.".$this->act_CreateGroup2();
        $res .= "  3.".$this->act_CreateProduct1();
        $res .= "  4.".$this->act_CreateProduct2();
        $res .= "  5.".$this->act_FilterCatGroup();
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
     * 1. Създаване на група - подниво на "Продукти"
     */
    //http://localhost/unit_MinkPGroups/CreateGroup1/
    function act_CreateGroup1()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нова група
        $browser->click('Каталог');
        $browser->click('Групи');
        $browser->press('Нов запис');
        $browser->setValue('name', 'Кашони');
        $browser->setValue('parentId', 'Продукти');
        $browser->press('Запис');
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на група', 'info');
        }
        
    }
    
    /**
     * 2. Създаване на групи - подниво на поднивото
     */
    //http://localhost/unit_MinkPGroups/CreateGroup2/
    function act_CreateGroup2()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на подниво
        $browser->click('Каталог');
        $browser->click('Групи');
        $browser->press('Нов запис');
        $browser->setValue('name', 'Големи');
        $browser->setValue('parentId', 'Продукти » Кашони');
        $browser->press('Запис');
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на група', 'info');
        }
        // Създаване на подниво
        $browser->press('Нов запис');
        $browser->setValue('name', 'Малки');
        $browser->setValue('parentId', 'Продукти » Кашони');
        $browser->press('Запис');
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на група', 'info');
        }
        
    }
    
    /**
     * 3. Създаване на артикул от първата група от последното ниво
     */
    //http://localhost/unit_MinkPGroups/CreateProduct1/
    function act_CreateProduct1()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нов артикул от първата група от последното ниво
        $browser->click('Каталог');
        $browser->press('Нов запис');
        $browser->setValue('catcategorieId', 'Продукти');
        $browser->press('Напред');
        $browser->setValue('name', 'Кашон 30x50x20');
        $browser->setValue('code', 'K1');
        $browser->setValue('measureId', 'брой');
        $browser->setValue('meta_canBuy', 'canBuy');
        $browser->setValue('Продукти » Кашони » Големи', True);
        $browser->press('Запис');
        
     }
    
    /**
     * 4. Създаване на артикул от втората група от последното ниво
     */
    
    //http://localhost/unit_MinkPGroups/CreateProduct2/
    function act_CreateProduct2()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нов артикул - продукт
        $browser->click('Каталог');
        $browser->press('Нов запис');
        $browser->setValue('catcategorieId', 'Продукти');
        $browser->press('Напред');
        $browser->setValue('name', 'Кашон 20x30x16');
        $browser->setValue('code', 'K2');
        $browser->setValue('measureId', 'брой');
        $browser->setValue('meta_canBuy', 'canBuy');
        $browser->setValue('Продукти » Кашони » Малки', True);
        $browser->press('Запис');
    
    }
    
    /**
     * 5. Филтриране по група артикули
     */
    //http://localhost/unit_MinkPGroups/FilterCatGroup/
    function act_FilterCatGroup()
    {
        // Логване
        $browser = $this->SetUp();
    
        $browser->click('Каталог');
        // търсене
        $browser->setValue('groupId', 'Продукти');
        $browser->press('Филтрирай');
        if(strpos($browser->gettext(), 'Кашон 30x50x20')) {
        } else {
            return unit_MinkPbgERP::reportErr('Липсва артикул от първия запис на последното ниво', 'warning');
        }
        if(strpos($browser->gettext(), 'Кашон 20x30x16')) {
        } else {
            return unit_MinkPbgERP::reportErr('Липсва артикул от втория запис на последното ниво', 'warning');
        }
        
    }
     
}