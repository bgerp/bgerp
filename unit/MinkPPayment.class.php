<?php


/**
 *  Клас  'unit_MinkPPayment' - PHP тестове за проверка на състоянието на плащане; Проверка количество и цени - изрази
 *
 * @category  bgerp
 * @package   tests
 * @author    Pavlinka Dainovska <pdainovska@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPPayment extends core_Manager {
   /** Изпълнява се след unit_MinkPbgERP!
    *  Номерацията показва препоръчвания ред на изпълнение, заради датите на фактурите. Еднаквите номера могат да се разместват.
    */
    //http://localhost/unit_MinkPPayment/Run/
    public function act_Run()
    {
        if (!TEST_MODE) {
            return;
        }
        $res = '';
        $res .= "<br>".'MinkPPayment';
        $res .= "  1.".$this->act_CreateSaleWaitP();
        $res .= "  2.".$this->act_CreateSaleOverdue3days();
        $res .= "  3.".$this->act_CreateSaleMomentOverdueNull();
        $res .= "  4.".$this->act_CreateSaleExped();
        $res .= "  5.".$this->act_CreateSaleExpedn();
        $res .= "  6.".$this->act_CreateSaleOverpaid();
        $res .= "  7.".$this->act_CreateSaleMomentWait3();
        $res .= "  8.".$this->act_CreateSaleWait3();
        $res .= "  9.".$this->act_CreateSaleMomentNow();
        $res .= "  10.".$this->act_CreateSale();
        $res .= "  11.".$this->act_CreatePurchaseOverdue();
        $res .= "  12.".$this->act_CreatePurchaseWait();
        $res .= "  13.".$this->act_CreatePurchaseOverpaid();
        $res .= "  14.".$this->act_CreatePurchase3();
        $res .= "  15.".$this->act_CreatePurchaseMoment();
           
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
     * 1.
     * Проверка състояние плащане - чакащо, метод - до x дни след фактуриране (3,7,10,15,21,30) в деня на падеж
     * Нова продажба на съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPPayment/CreateSaleWaitP/
    function act_CreateSaleWaitP()
    {
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        $valior=strtotime("-7 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPPaymentSaleWaitP');
        $browser->setValue('paymentMethodId', "До 7 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('template', "Договор за продажба");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '089-07*08');//33
        $browser->setValue('packPrice', '07+3*0.8');//9.4
        $browser->setValue('discount', 3.0);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 107);
        $browser->setValue('packPrice', 1.0127);
        $browser->setValue('discount', '01,00');
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 81,64')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        if(strpos($browser->gettext(), 'Четиристотин осемдесет и девет BGN и 0,81')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("now");
        $browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->setValue('dueTime', Null);
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Има')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане в деня на падеж', 'warning');
        }
        //Проверка на статистиката
        if(strpos($browser->gettext(), '489,81 489,81 200,00 489,81')) {
        } else {
            return $this->reportErr('Грешни суми в мастера', 'warning');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 2.
     * Проверка състояние плащане - просрочено, Метод - До x дни след фактуриране
     * Нова продажба на съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPPayment/CreateSaleOverdue3days/
    function act_CreateSaleOverdue3days()
    {
        // Логваме се
        $browser = $this->SetUp();
         
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
    
        $valior=strtotime("-4 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPPaymentSaleOverdue');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('template', "Договор за продажба");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*08');//34
        $browser->setValue('packPrice', '09.20+0.3*08');//11.6
        $browser->setValue('discount', '3.4');
       // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '17');
        $browser->setValue('packPrice', '1.017');
        $browser->setValue('discount', '1');
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
             
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 79,62')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Четиристотин седемдесет и седем BGN и 0,73')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        // експедиционно нареждане
        //$browser->press('Експедиране');
        //$browser->setValue('valior', date('d-m-Y', $valior));
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
             
        // протокол
        //$browser->press('Пр. услуги');
        //$browser->setValue('valior', date('d-m-Y', $valior));
        //$browser->press('Чернова');
        //$browser->press('Контиране');
       
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("-1 Day");
        $browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
       
        $browser->press('Чернова');
        //return $browser->getHtml();
        $browser->press('Контиране');
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
    }
    
    /** 2.
     *  Проверка състояние плащане - просрочено, метод - на момента, краен срок - Null
     * Нова продажба на съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPPayment/CreateSaleMomentOverdueNull/
    function act_CreateSaleMomentOverdueNull()
    {
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        // -4 Days - за да се обхване случая, когато няма краен срок на плащане на фактурата
        $valior=strtotime("-4 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'MomentOverdue');
        $browser->setValue('note', 'MinkPPaymentSaleMomentOverdue');
        $browser->setValue('paymentMethodId', "В брой при получаване");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('template', "Договор за продажба");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010,0+03*08');//34
        $browser->setValue('packPrice', '01,00+3*0.8');//3.4
        $browser->setValue('discount', '3.5*1.2');//4.2
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 123);
        $browser->setValue('packPrice', '1,121');
        $browser->setValue('discount', 1);
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        // Изключваме плащането
        //$browser->setValue('action_pay', False);
        $browser->setValue('action_ship', 'ship');
        $browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 49,45')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        
        if(strpos($browser->gettext(), 'Двеста деветдесет и шест BGN и 0,69')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
           
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        //$browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->setValue('dueDate', null);
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        $browser->setValue('Ignore', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
         
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }     
    }
     
    /**     
     * 2.
     * Проверка състояние плащане - просрочено, част. доставено, част.платено и фактурирано
     * Нова продажба на съществуваща фирма с папка 
     */
    //http://localhost/unit_MinkPPayment/CreateSaleExped/
    function act_CreateSaleExped()
    {
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        $valior=strtotime("-4 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'exp');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPPaymentCreateSaleE');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('template', "Договор за продажба");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*08');//34
        $browser->setValue('packPrice', '016-3*0.8');//13,6
        $browser->setValue('discount', '0.23');
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '140 / 05-03*08');//4
        $browser->setValue('packPrice', '10/05+3*08');//26
        $browser->setValue('discount', 1);
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        // Изключваме експедирането
        $browser->setValue('action_ship', False);
        $browser->press('Активиране/Контиране');
       
        if(strpos($browser->gettext(), 'ДДС: BGN 112,86')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        if(strpos($browser->gettext(), 'Шестстотин седемдесет и седем BGN и 0,16')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 'Склад 1');
        $browser->setValue('template', 'Експедиционно нареждане');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("-1 day");
        $browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // протокол
        //$browser->press('Пр. услуги');
        //$browser->setValue('valior', date('d-m-Y', $valior));
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        if(strpos($browser->gettext(), 'Данъчна основа: BGN 461,34')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа', 'warning');
        }
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 92,27')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        if(strpos($browser->gettext(), 'Петстотин петдесет и три BGN и 0,61')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
    }
       
    /**
     * 2.
     * Проверка състояние плащане - просрочено, доставено и нефактурирано
     * Нова продажба на съществуваща фирма с папка -  лв
     */
    //http://localhost/unit_MinkPPayment/CreateSaleExpedn/
    function act_CreateSaleExpedn()
    {
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        $valior=strtotime("-4 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'exp1');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPbgErpCreateSaleE1');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('template', "Договор за продажба");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*04');//22
        $browser->setValue('packPrice', '01+3*0.4');//2.2
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '021-03*4');//9
        $browser->setValue('packPrice', '09,20+0,3*04');//10.4
        $browser->setValue('discount', 2);
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 27,74')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        if(strpos($browser->gettext(), 'Сто шестдесет и шест BGN и 0,42')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '10');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // експедиционно нареждане
        //$browser->press('Експедиране');
        //$browser->setValue('valior', date('d-m-Y', $valior));
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
    }
    
    /**
     * 2.
     * Проверка състояние плащане - надплатено, доставено и нефактурирано
     * Нова продажба на съществуваща фирма с папка -  лв
     */
    //http://localhost/unit_MinkPPayment/CreateSaleOverpaid/
    function act_CreateSaleOverpaid()
    {
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $valior=strtotime("-4 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'Overpaid');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPbgErpSaleOverpaid');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('template', "Договор за продажба");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*04');//22
        $browser->setValue('packPrice', '01+3*0.4');//2.2
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '021-03*4');//9
        $browser->setValue('packPrice', '09,28+0,3*04');//10.48
        $browser->setValue('discount', 2);
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
             
        if(strpos($browser->gettext(), '27,88')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        if(strpos($browser->gettext(), 'Сто шестдесет и седем BGN и 0,26')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '70');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // експедиционно нареждане
        //$browser->press('Експедиране');
        //$browser->setValue('valior', date('d-m-Y', $valior));
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        //} else {
        //    return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        //}
    }
    
    /**
     * 3.
     * Проверка състояние плащане - чакащо, метод - на момента, падежът е днес
     * Нова продажба на съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPPayment/CreateSaleMomentWait3/
    function act_CreateSaleMomentWait3()
    {
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        // -3 Days - за да се обхване случая, когато няма краен срок на плащане на фактурата
        $valior=strtotime("-3 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'MomentWaitP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPPaymentSaleMomentWait3');
        $browser->setValue('paymentMethodId', "В брой при получаване");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('template', "Договор за продажба");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*08');//34
        $browser->setValue('packPrice', '010+3*0.8');//12.4
        $browser->setValue('discount', 3);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 113);
        $browser->setValue('packPrice', 0.2353);
        $browser->setValue('discount', 1);
        // Записваме артикула
        $browser->press('Запис');
        
        // активираме продажбата
        $browser->press('Активиране');
        // Изключваме плащането
        //$browser->setValue('action_pay', False);
        //Изключваме експедирането
        //$browser->setValue('action_ship', False);
        
       //return  $browser->getHtml();
        $browser->press('Активиране/Контиране');
        
         
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 87,05')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        
        if(strpos($browser->gettext(), 'Петстотин двадесет и два BGN и 0,32')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        //$browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->setValue('dueDate', null);
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        $browser->setValue('Ignore', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        if(strpos($browser->gettext(), 'Чакащо плащане: Има')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }    
    }
    
    /**
     * 4.
     * Нова продажба на съществуваща фирма с папка
     * Проверка състояние плащане - чакащо, метод - до x дни след фактуриране (3,7,10,15,21,30) в ден преди падеж
     */
    //http://localhost/unit_MinkPPayment/CreateSaleWait3/
    function act_CreateSaleWait3()
    {
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        $valior=strtotime("-2 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'Wait');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPPaymentSaleWait3');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('template', "Договор за продажба");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+02*09');//28
        $browser->setValue('packPrice', '080-3*0.8');//77,6
        $browser->setValue('discount', 3);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 112);
        $browser->setValue('packPrice', 0.1987);
        $browser->setValue('discount', 1);
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 425,93')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        
        if(strpos($browser->gettext(), 'Две хиляди петстотин петдесет и пет BGN и 0,58')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("+1 day");
        $browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
         
        if(strpos($browser->gettext(), 'Чакащо плащане: Има')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }     
    }
    
    /**
     * 5.
     * Проверка състояние чакащо плащане - не, метод - на момента
     * Бърза продажба на съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPPayment/CreateSaleMomentNow/
    function act_CreateSaleMomentNow()
    {
        // Логваме се
        $browser = $this->SetUp();
         
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
    
        $valior=strtotime("Now");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'Moment');
        $browser->setValue('note', 'MinkPPaymentSaleMoment');
        $browser->setValue('caseId', 1);
        $browser->setValue('shipmentStoreId', 1);
        $browser->setValue('paymentMethodId', "В брой при получаване");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('template', "Договор за продажба");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010,0+03*08');//34
        $browser->setValue('packPrice', '01,00+3*0.8');//3.4
        $browser->setValue('discount', 3);
        
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 18);
        $browser->setValue('packPrice', 1.2117);
        $browser->setValue('discount', 1);
    
        // Записваме артикула
        $browser->press('Запис');
         
        // активираме продажбата
        $browser->press('Активиране');
        //Контиране на извършени на момента действия (опционално):
        //'Експедиране на продукти от склад "Склад 1"'
        //'Прието плащане в брой в каса "КАСА 2"'
        $browser->setValue('action_ship', 'ship');
        //$browser->setValue('action_pay', 'pay');
        $browser->press('Активиране/Контиране');
    
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 26,75')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Сто и шестдесет BGN и 0,47')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
         
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        $browser->setValue('Ignore', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
         
        if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
        if(strpos($browser->gettext(), 'Плащане в брой')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешен начин на плащане', 'warning');
        }
    }
    
    /**
     * 5. Нова продажба на съществуваща фирма с папка
     * Проверка количество и цени - изрази
     * Проверка състояние чакащо плащане - не (платено)
     */
     
    //http://localhost/unit_MinkPPayment/CreateSale/
    function act_CreateSale()
    {
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $endhour=strtotime("+5 hours");
        $enddate=strtotime("+1 Day");
         
        $browser->setValue('deliveryTime[d]', date('d-m-Y', $enddate));
        $browser->setValue('deliveryTime[t]', '10:30');
    
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPPaymentSale');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('template', "Договор за продажба");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*08');//34
        $browser->setValue('packPrice', '01+3*0,8');//3.4
        $browser->setValue('discount', 3);
         
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '160 / 05-03*08');//8
        $browser->setValue('packPrice', '100/05+3*08');//44
        $browser->setValue('discount', 1);
        
        // Записваме артикула
        $browser->press('Запис');
        // Игнорираме предупреждението за липсваща стока
        //$browser->setValue('Ignore', 1);
        //$browser->press('Запис');
        
        // активираме продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
        
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 92,13')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        
        if(strpos($browser->gettext(), 'Петстотин петдесет и два BGN и 0,74')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        // експедиционно нареждане
        //$browser->press('Експедиране');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->setValue('template', 'Експедиционно нареждане с цени');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Четири хиляди седемстотин петдесет и осем BGN и 0,35')) {
        //} else {
        //    return unit_MinkPbgERP::reportErr('Грешна сума в ЕН', 'warning');
        //}
                     
        // протокол
        //$browser->press('Пр. услуги');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        //return 'paymentType';
        //$browser->setValue('paymentType', 'По банков път');
        $browser->press('Контиране');
        
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        //$browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
    }            
            
    /**
     * 6. Нова покупка от съществуваща фирма с папка
     * Проверка състояние чакащо плащане - просрочено
     */
     
    //http://localhost/unit_MinkPPayment/CreatePurchaseOverdue/
    function act_CreatePurchaseOverdue()
    {
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->setValue('bankAccountId', '');
        $valior=strtotime("-4 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('note', 'MinkPPaymentPurchaseOverdue');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('template', "Договор за покупка");
        
        $browser->press('Чернова');
        // Записваме черновата на покупката
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*08');//34
        $browser->setValue('packPrice', '010+3*0.8');//12.4
        $browser->setValue('discount', 3);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 107);
        $browser->setValue('packPrice', '0,027');
        // Записваме артикула
        $browser->press('Запис');
        // активираме покупката
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 82,37')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        
        if(strpos($browser->gettext(), 'Четиристотин деветдесет и четири BGN и 0,21')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        
        // Складова разписка
        //$browser->press('Засклаждане');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->setValue('valior', date('d-m-Y', $valior));
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        
        // протокол
        //$browser->press('Приемане');
        //$browser->setValue('valior', date('d-m-Y', $valior));
        //$browser->press('Чернова');
        //$browser->press('Контиране');
            
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '1722');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("-1 Day");
        $browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // РКО
        $browser->press('РКО');
        $browser->setValue('beneficiary', 'Иван Петров');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '0126,36');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        // Проверка Чакащо плащане
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
    }    
                    
    /**
     * 6. Нова покупка от съществуваща фирма с папка
     * Проверка състояние чакащо плащане - Има
     */
    //http://localhost/unit_MinkPPayment/CreatePurchaseWait/
    function act_CreatePurchaseWait()
    {        
        // Логваме се
        $browser = $this->SetUp();
        
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
        
        // нова покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->setValue('bankAccountId', '');
        $valior=strtotime("-2 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('note', 'MinkPPaymentPurchaseWait');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->press('Чернова');
        // Записваме черновата на покупката
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'. Ако е с дес. запетая - също апостроф.
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '004+03*08');//28
        $browser->setValue('packPrice', '010,2');//10.2
        $browser->setValue('discount', '0,02');
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '017');
        $browser->setValue('packPrice', '1.07');
        $browser->setValue('discount', '10.02');
        // Записваме артикула
        $browser->press('Запис');
        // активираме покупката
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 60,38')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        if(strpos($browser->gettext(), 'Триста шестдесет и два BGN и 0,29')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
         
        // Складова разписка
        //$browser->press('Засклаждане');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->setValue('valior', date('d-m-Y', $valior));
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        
        // протокол
        //$browser->press('Приемане');
        //$browser->setValue('valior', date('d-m-Y', $valior));
        //$browser->press('Чернова');
        //$browser->press('Контиране');
               
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '78319');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("+1 Day");
        $browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // РКО
        $browser->press('РКО');
        $browser->setValue('beneficiary', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '0126,36');
        $browser->press('Чернова');
        $browser->press('Контиране');
        // Проверка Чакащо плащане
        if(strpos($browser->gettext(), 'Чакащо плащане: Има')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
    }        
                
    /**
     * 6. Нова покупка от съществуваща фирма с папка
     * Проверка състояние чакащо плащане - надплатено
     */
     
    //http://localhost/unit_MinkPPayment/CreatePurchaseOverpaid/
    // Фактура - №17923 се оттегля при повторен тест!
    function act_CreatePurchaseOverpaid()
    {
        // Логваме се
        $browser = $this->SetUp();
            
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->setValue('bankAccountId', '');
        $valior=strtotime("-2 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('note', 'MinkPPaymentPurchaseOverpaid');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        //$browser->setValue('shipmentStoreId', "Склад 2");
        $browser->press('Чернова');
        // Записваме черновата на покупката
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'. Ако е с дес. запетая - също апостроф.
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-06*08');//52
        $browser->setValue('packPrice', '0,0100+3*0,8');//2,41
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов - услуга
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 01);
        $browser->setValue('packPrice', '1,0202');
        $browser->setValue('discount', 1);
        // Записваме артикула
        $browser->press('Запис');
        // активираме покупката
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 24,51')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        if(strpos($browser->gettext(), 'Сто четиридесет и седем BGN и 0,08')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // Складова разписка
        //$browser->press('Засклаждане');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->setValue('valior', date('d-m-Y', $valior));
        //$browser->press('Чернова');
        //$browser->press('Контиране');
    
        // протокол
        //$browser->press('Приемане');
        //$browser->setValue('valior', date('d-m-Y', $valior));
        //$browser->press('Чернова');
        //$browser->press('Контиране');
      
        // Фактурата се оттегля при повторен тест!
        $browser->press('Вх. фактура');
        $browser->setValue('number', '17923');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("+1 Day");
        $browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '0148,07');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Проверка Чакащо плащане
        if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
    }
    
    /**
     * 6. Нова покупка от съществуваща фирма с папка
     * Проверка количество и цени - изрази
     * Проверка състояние чакащо плащане - няма (платено)
     */
     
    //http://localhost/unit_MinkPPayment/CreatePurchase3/
    function act_CreatePurchase3()
    {
        // Логваме се
        $browser = $this->SetUp();
    
       //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPPaymentPurchase3');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->press('Чернова');
        // Записваме черновата на покупката
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '008+03*08');//32
        $browser->setValue('packPrice', '010+3*0,8');//12.4
        $browser->setValue('discount', 3);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '1000 / 08-09*08');//53
        $browser->setValue('packPrice', '100/04-3*0,8');//22,6
        $browser->setValue('discount', '10,16');
        // Записваме артикула
        $browser->press('Запис');
        // активираме покупката
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 292,20')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС', 'warning');
        }
        
        if(strpos($browser->gettext(), 'Хиляда седемстотин петдесет и три BGN и 0,20')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        // Складова разписка
        //$browser->press('Засклаждане');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        
        // протокол
        //$browser->press('Приемане');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '1276');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // РКО
        $browser->press('РКО');
        $browser->setValue('beneficiary', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
    }
   
    /**
     * 6. Бърза покупка от съществуваща фирма с папка
     * Проверка количество и цени - изрази
     * Проверка състояние чакащо плащане - няма (платено)
     */
     
    //http://localhost/unit_MinkPPayment/CreatePurchaseMoment/
    function act_CreatePurchaseMoment()
    {
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        ///////$browser = $this->SetFirm();
    
        $browser->click('Визитник');
        $browser->click('F');
        //$browser->hasText('Фирма bgErp');
        $browser->click('Фирма bgErp');
        $browser->press('Папка');
        // нова покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPPaymentPurchaseMoment');
        $browser->setValue('paymentMethodId', "В брой при получаване");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        $browser->press('Чернова');
        // Записваме черновата на покупката
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '008+03*08');//32
        $browser->setValue('packPrice', '006-7*0,8');//0.4
        $browser->setValue('discount', 3);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '1000 / 08-09*08');//53
        $browser->setValue('packPrice', '100/04-3*0,8');//22,6
        $browser->setValue('discount', '10,16');
        // Записваме артикула
        $browser->press('Запис');
        // активираме покупката
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Отстъпка: BGN 122,07')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Хиляда осемдесет и осем BGN и 0,53')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '276');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
    }
    
}