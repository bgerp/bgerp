<?php

/**
 *  Клас  'unit_MinkPListProduct' - PHP тестове за листване на продукти към контрагент и продажба по списък
 *
 * @category  bgerp
 * @package   tests
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPListProduct extends core_Manager {
    //Изпълнява се след unit_MinkPbgERP!
    //http://localhost/unit_MinkPListProduct/Run/
    public function act_Run()
    {
        if (!TEST_MODE) {
            return;
        }
        
        $res = '';
        $res .= '<br>'.'MinkPListProduct';
        $res .=  " 1.".$this->act_CreateListProducts();
        $res .=  " 2.".$this->act_CreateSaleList();
        $res .= "  3.".$this->act_ImportListProducts();
        //$res .= "  4.".$this->act_CreateSaleList1();
        
        return $res;
    }
       
    /**
     * Логване
     */
    public function SetUp()
    {
        $browser = cls::get('unit_Browser');
        $host = unit_Setup::get('DEFAULT_HOST');
        //$browser->start('http://localhost/');
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
        //$browser->press('Папка');
        return $browser;
    }
    
    /**
     * Избор на чуждестранна фирма
     */
    public function SetFirmEUR()
    {
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('N');
        $Company = 'NEW INTERNATIONAL GMBH';
        $browser->click($Company);
        //$browser->press('Папка');
        return $browser;
    }
    
    /**
     * Ръчно добавяне на артикули за листване към фирма
     */
     
    //http://localhost/unit_MinkPListProduct/CreateListProducts/
    function act_CreateListProducts()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне корицата на фирмата
        $browser = $this->SetFirm();
        $browser->click('Търговия');
       
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Плик 7 л');
        $browser->refresh('Запис');
        $browser->setValue('reff', 'p7');
        $browser->setValue('moq', '10');
       
        // Записване артикула и добавяне нов
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('reff', 's50');
        $browser->setValue('moq', '50');
    
        // Записване на артикула
        $browser->press('Запис');
    
    }
    
    /**
     * Продажба - артикули по списък
     */
     
    //http://localhost/unit_MinkPListProduct/CreateSaleList/
    function act_CreateSaleList()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
         $browser = $this->SetFirm();
      
         $browser->press('Папка');
         
         
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        $browser->setValue('reff', 'MinkPListProducts');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPlistVatInclude');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикули
        $browser->press('Списък');
        /// Количества на двата артикула
      
        $browser->setValue('quantity1', '100');
        //return $browser->getHtml();
        $browser->setValue('quantity2', '50');
        
        // Записване артикулите
        $browser->press('Импорт');
        //return $browser->getHtml();
        // активиране на продажбата
        $browser->press('Активиране');
       
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Шестдесет и един BGN и 0,73')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        //Проверка на статистиката
        if(strpos($browser->gettext(), '61,73 61,73 0,00 0,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        
    }
     
    /**
     * Добавяне на артикули за листване към фирма от група/предишни продажби - не работи
     */
    //http://localhost/unit_MinkPListProduct/ImportListProducts/
    function act_ImportListProducts()
    {
    
        // Логване
        $browser = $this->SetUp();
        //Отваряне корицата на фирмата
        $browser = $this->SetFirm();
       
        $browser->click('Търговия');
        // Добавяне на артикул
        $browser->press('Импорт');
     
        $browser->setValue('from', 'group');
        //return $browser->getHtml();
        //$browser->setValue('Ценова група » Промоция', '15');
        //$browser->setValue('group', 'Ценова група » Промоция');
        $browser->setValue('group', '15');
        // Записване на списъка
        $browser->press('Импорт');
    
    }
    
    /**
     * Продажба - артикули по списък от предишни продажби
     */
}