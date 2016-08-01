<?php


/**
 *  Клас  'unit_MinkPPayment' - PHP тестове за проверка на състоянието на плащане
 *
 * @category  bgerp
 * @package   tests
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPPayment extends core_Manager {
   /** Номерацията показва препоръчвания ред на изпълнение, заради датите на фактурите. Еднаквите номера могат да се разместват.
    *return $browser->getHtml();
    */
    
    
    /**
     * Логване
     */
    public function SetUp()
    {
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        // проверка потребител/парола
        //Грешка:Грешна парола или ник!
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
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
        $browser->setValue('reff', 'А1234');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPPaymentSaleWaitP');
        $browser->setValue('paymentMethodId', "До 7 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '089-07*08');//33
        $browser->setValue('packPrice', '07+3*0.8');//9.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '09.20+0.3*08');//11.6
        $browser->setValue('discount', 2);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 107);
        $browser->setValue('packPrice', 1.0127);
        $browser->setValue('discount', 1);
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), '254,43')) {
        } else {
            return "Грешно ДДС";
        }
        if(strpos($browser->gettext(), 'Хиляда петстотин двадесет и шест BGN и 0,57')) {
        } else {
            return "Грешна обща сума";
        }
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // протокол
        $browser->press('Пр. услуги');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
    
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("now");
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
            return "Грешно чакащо плащане в деня на падеж";
        }
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
        $browser->setValue('reff', 'А1234');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPPaymentSaleOverdue');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
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
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '03*08-010');//14
        $browser->setValue('packPrice', '01.20+0.3*08');//3.6
        $browser->setValue('discount', 2);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 17);
        $browser->setValue('packPrice', '1.017');
        $browser->setValue('discount', 1);
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
             
        if(strpos($browser->gettext(), '95,09')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Петстотин и седемдесет BGN и 0,55')) {
        } else {
            return "Грешна обща сума";
        }
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
             
        // протокол
        $browser->press('Пр. услуги');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
    
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("-1 Day");
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
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return "Грешно чакащо плащане";
        }
    }
    
    /**
     * 2.
     * Проверка състояние плащане - просрочено, метод - на момента, краен срок - Null
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
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '01+03*08');//25
        $browser->setValue('packPrice', '010+3*0.8');//12.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '010.20-0.3*08');//7.8
        $browser->setValue('discount', 2);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 123);
        $browser->setValue('packPrice', 1.121);
        $browser->setValue('discount', 1);
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), '203,63')) {
        } else {
            return "Грешно ДДС";
        }
        
        if(strpos($browser->gettext(), 'Хиляда двеста двадесет и един BGN и 0,77')) {
        } else {
            return "Грешна обща сума";
        }
        
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // протокол
        $browser->press('Пр. услуги');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}    
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        //$browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->setValue('dueDate', null);
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
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
            return "Грешно чакащо плащане";
        }     
            
    }
     
    /**
     * 2.
     * Проверка състояние плащане - просрочено, част. доставено, част.платено и фактурирано
     * Нова продажба на съществуваща фирма с папка 3448.13
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
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*08');//34
        $browser->setValue('packPrice', '066-3*0.8');//63,6
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
         // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '068-03*8');//44
        $browser->setValue('packPrice', '07.20+0.3*08');//9.6
        $browser->setValue('discount', 2);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 117);
        $browser->setValue('packPrice', 1.6207);
        $browser->setValue('discount', 1);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '140 / 05-03*08');//4
        $browser->setValue('packPrice', '100/05+3*08');//44
        $browser->setValue('discount', 1);
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), '574,69')) {
        } else {
            return "Грешно ДДС";
        }
        if(strpos($browser->gettext(), 'Три хиляди четиристотин четиридесет и осем BGN и 0,13')) {
        } else {
            return "Грешна обща сума";
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
        $browser->press('Пр. услуги');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return "Грешно чакащо плащане";
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
        $browser->setValue('packPrice', '09,20+0,3*04');//10.4
        $browser->setValue('discount', 2);
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), '27,74')) {
        } else {
            return "Грешно ДДС";
        }
        if(strpos($browser->gettext(), 'Сто шестдесет и шест BGN и 0,42')) {
        } else {
            return "Грешна обща сума";
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
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return "Грешно чакащо плащане";
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
        //return $browser->getHtml();
        //$browser->press('Активиране/Контиране');
             
        if(strpos($browser->gettext(), '27,88')) {
        } else {
            return "Грешно ДДС";
        }
        if(strpos($browser->gettext(), 'Сто шестдесет и седем BGN и 0,26')) {
        } else {
            return "Грешна обща сума";
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
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        } else {
            return "Грешно чакащо плащане (вярно - Няма)";
        }
    
    }
    
    /**
     * 3.
     * Проверка състояние плащане - чакащо, метод - на момента, падежът е днес
     * Нова продажба на съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPPayment/CreateSaleMomentWaitP/
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
        $browser->setValue('note', 'MinkPPaymentSaleMomentWaitP');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
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
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '010.20+0.3*08');//12.6
        $browser->setValue('discount', 2);
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
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '274,74')) {
        } else {
            return "Грешно ДДС";
        }
        
        if(strpos($browser->gettext(), 'Хиляда шестстотин четиридесет и осем BGN и 0,46')) {
        } else {
            return "Грешна обща сума";
        }
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // протокол
        $browser->press('Пр. услуги');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
        
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        //$browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->setValue('dueDate', null);
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
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
        
        if(strpos($browser->gettext(), 'Чакащо плащане: Има')) {
        } else {
            return "Грешно чакащо плащане";
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
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-05*8');//60
        $browser->setValue('packPrice', '010.21+0.3*08');//12.61
        $browser->setValue('discount', 2);
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
        //$browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), '574,22')) {
        } else {
            return "Грешно ДДС";
        }
        
        if(strpos($browser->gettext(), 'Три хиляди четиристотин четиридесет и пет BGN и 0,34')) {
        } else {
            return "Грешна обща сума";
        }
        
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // протокол
        $browser->press('Пр. услуги');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
        
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
            return "Грешно чакащо плащане";
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
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
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
        $browser->press('Запис и Нов');
    
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '089,00-03*8');//65
        $browser->setValue('packPrice', '010,020+0.3*07');//12.12
        $browser->setValue('discount', 2);
    
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        //return $browser->getHtml();
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
        $browser->setValue('action_pay', 'pay');
        $browser->press('Активиране/Контиране');
    
        if(strpos($browser->gettext(), '181,16')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Хиляда осемдесет и шест BGN и 0,92')) {
        } else {
            return "Грешна обща сума";
        }
         
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        $browser->press('Контиране');
         
        if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        } else {
            return "Грешно чакащо плащане";
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
        //$browser->setValue('deliveryTime[d]', date('d-m-Y'));
        //$browser->setValue('deliveryTime[t]', date('h:i:sa', $endhour));
        $browser->setValue('deliveryTime[t]', '10:30');
    
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPPaymentSale');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*08');//34
        $browser->setValue('packPrice', '01+3*0,8');//3.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
         
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Плик 7 л');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '03*048-0123');//21
        $browser->setValue('packPrice', '010.20+0.3*08');//12.6
        $browser->setValue('discount', 2);
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '023 + 017*02');//57
        $browser->setValue('packPrice', '091 - 013*02');//65
        $browser->setValue('discount', 3);
        
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 114);
        $browser->setValue('packPrice', 1.1124);
        $browser->setValue('discount', 1);
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
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '887,87')) {
        } else {
            return "Грешно ДДС";
        }
        
        if(strpos($browser->gettext(), 'Пет хиляди триста двадесет и седем BGN и 0,18')) {
        } else {
            return "Грешна обща сума";
        }
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('storeId', 'Склад 1');
        $browser->setValue('template', 'Експедиционно нареждане с цени');
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //}
        if(strpos($browser->gettext(), 'Четири хиляди седемстотин петдесет и осем BGN и 0,35')) {
        // връща грешка, ако не е избрано ЕН с цени
        } else {
            return "Грешна сума в ЕН";
        }
                     
        // протокол
        $browser->press('Пр. услуги');
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
        
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
        //$browser->selectNode("#Sal89 > td:nth-child(2) > div:nth-child(1) > div:nth-child(1) > input:nth-child(1)");
        //$browser->click();
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        //return  $browser->getHtml();
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        } else {
            return "Грешно чакащо плащане";
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
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Други резервни части');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-09*8');//28
        $browser->setValue('packPrice', '08,20+0.3*08');//10.6
        $browser->setValue('discount', 5);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 107);
        $browser->setValue('packPrice', '0,027');
        //$browser->setValue('discount', 2);
        // Записваме артикула
        $browser->press('Запис');
        // активираме покупката
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '138,76')) {
        } else {
            return "Грешно ДДС";
        }
        
        if(strpos($browser->gettext(), 'Осемстотин тридесет и два BGN и 0,56')) {
        } else {
            return "Грешна обща сума";
        }
        
        // Складова разписка
        $browser->press('Засклаждане');
        $browser->setValue('storeId', 'Склад 1');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // протокол
        $browser->press('Приемане');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}    
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
            return "Грешно чакащо плащане";
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
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Други резервни части');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '080-07*8');//24
        $browser->setValue('packPrice', '01,20+0,3*08');//3,6
        $browser->setValue('discount', 2);
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
        //return $browser->getHtml();
        //$browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), '75,61')) {
        } else {
            return "Грешно ДДС";
        }
        if(strpos($browser->gettext(), 'Четиристотин петдесет и три BGN и 0,68')) {
        } else {
            return "Грешна обща сума";
        }
         
        // Складова разписка
        $browser->press('Засклаждане');
        $browser->setValue('storeId', 'Склад 1');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // протокол
        $browser->press('Приемане');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}        
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
            return "Грешно чакащо плащане";
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
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Други резервни части');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '010,020+0,3*08');//12.6
        $browser->setValue('discount', 2);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 01);
        $browser->setValue('packPrice', '1,0202');
        $browser->setValue('discount', 1);
        // Записваме артикула
        $browser->press('Запис');
        // активираме покупката
        $browser->press('Активиране');
        if(strpos($browser->gettext(), '209,52')) {
        } else {
            return "Грешно ДДС";
        }
        if(strpos($browser->gettext(), 'Хиляда двеста петдесет и седем BGN и 0,13')) {
        } else {
            return "Грешна обща сума";
        }
    
        // Складова разписка
        $browser->press('Засклаждане');
        $browser->setValue('storeId', 'Склад 1');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // протокол
        $browser->press('Приемане');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
    
        // Фактурата се оттегля при повторен тест!
        $browser->press('Вх. фактура');
        $browser->setValue('number', '17923');
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
        $browser->setValue('amountDeal', '01251,36');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Проверка Чакащо плащане
        if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        } else {
            return "Грешно чакащо плащане";
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
        $browser->setValue('note', 'MinkPPaymentPurchase');
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
        $browser->setValue('packPrice', '010+3*0.8');//12.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        //return  $browser->getHtml();
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Други резервни части');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-07*8');//44
        $browser->setValue('packPrice', '010.20+0.3*08');//12.6
        $browser->setValue('discount', 2);
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '023 + 012*03');//59
        $browser->setValue('packPrice', '091 - 023*02');//45
        $browser->setValue('discount', 4);
        // Записваме артикула и добавяме нов - услуга
        // Категория Услуги да се маркира като 'купуваем'
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други външни услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 113);
        $browser->setValue('packPrice', '1,127');
        $browser->setValue('discount', 5);
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '1000 / 08-09*08');//48
        $browser->setValue('packPrice', '100/02-3*08');//26
        $browser->setValue('discount', 10);
        // Записваме артикула
        $browser->press('Запис');
        // активираме покупката
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '967,64')) {
        } else {
            return "Грешно ДДС";
        }
        
        if(strpos($browser->gettext(), 'Пет хиляди осемстотин и пет BGN и 0,83')) {
        } else {
            return "Грешна обща сума";
        }
        // Складова разписка
        $browser->press('Засклаждане');
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // протокол
        $browser->press('Приемане');
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //  $browser->press('Контиране');
        //}
        
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
            return "Грешно чакащо плащане";
        }
            
    }
   
}