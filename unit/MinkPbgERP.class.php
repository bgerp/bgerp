
<?php


/**
 * Клас  'tests_Test' - Разни тестове на PHP-to
 *
 * @category  bgerp
 * @package   tests
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPbgERP extends core_Manager {
   
   /** Номерацията показва препоръчвания ред на изпълнение. Еднаквите номера могат да се разместват.
    * bgERP 25-05-2016
    *return  $browser->getHtml();
    */
    
    
    
    /**
     * 15.
     * Проверка състояние плащане - чакащо, метод - на момента
     * Нова продажба на съществуваща фирма с папка
     */
    ///http://localhost/unit_MinkPbgERP/CreateSaleMoment/
    function act_CreateSaleMoment()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
       
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
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
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSaleMoment');
        $browser->setValue('pricesAtDate', date('d-m-Y'));
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100+03*08');//124
        $browser->setValue('packPrice', '0100+3*0.8');//102.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
             
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '0100.20+0.3*08');//102.6
        $browser->setValue('discount', 2);
    
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1887);
        $browser->setValue('packPrice', 1.6987);
        $browser->setValue('discount', 1);
    
        // Записваме артикула
        $browser->press('Запис');
       
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
             
        if(strpos($browser->gettext(), '568,93')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Двадесет и седем хиляди седемстотин петдесет и осем BGN и 0,06')) {
        } else {
            return "Грешна обща сума";
        }
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // протокол
        $browser->press('Пр. услуги');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
            
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        //return  $browser->getHtml();
        //$browser->setValue('paymentType', "bank");
    
        $browser->press('Контиране');
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
             
        if(strpos($browser->gettext(), 'Чакащо плащане: Да')) {
        } else {
            return "Грешно чакащо плащане";
        }
    }
    /**
     * 14. 
     * Проверка състояние плащане - просрочено, метод - на момента
     * Нова продажба на съществуваща фирма с папка
     */
    ///http://localhost/unit_MinkPbgERP/CreateSaleMomentOverdue/
    function act_CreateSaleMomentOverdue()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
                
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
            
        $browser->click($Company);
        
        $browser->press('Папка');
    
        // нова продажба - проверка има ли бутон
    
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        $valior=strtotime("-2 Days");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('reff', 'MomentOverdue');
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSaleMomentOverdue');
        $browser->setValue('pricesAtDate', date('d-m-Y'));
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100+03*08');//124
        $browser->setValue('packPrice', '0100+3*0.8');//102.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
             
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '0100.20+0.3*08');//102.6
        $browser->setValue('discount', 2);
    
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1887);
        $browser->setValue('packPrice', 1.6987);
        $browser->setValue('discount', 1);
    
        // Записваме артикула
        $browser->press('Запис');
            
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
             
        if(strpos($browser->gettext(), '568,93')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Двадесет и седем хиляди седемстотин петдесет и осем BGN и 0,06')) {
        } else {
            return "Грешна обща сума";
        }
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
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
        $browser->setValue('numlimit', '2000000 - 3000000');
        //$browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        //return  $browser->getHtml();
        //$browser->setValue('paymentType', "bank");
    
        $browser->press('Контиране');
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
             
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return "Грешно чакащо плащане";
        }
    }
    /**
     * 13.
     * Нова продажба на съществуваща фирма с папка
     * Проверка състояние плащане - чакащо, метод - до x дни след фактуриране (3,7,10,15,21,30) в ден преди падеж
     */
    ///http://localhost/unit_MinkPbgERP/CreateSaleWait/
    function act_CreateSaleWait()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
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
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSaleWait');
        $browser->setValue('pricesAtDate', date('d-m-Y', $valior));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100+03*08');//124
        $browser->setValue('packPrice', '0100+3*0.8');//102.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
             
        // Записваме артикула и добавяме нов
         $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '0100.20+0.3*08');//102.6
        $browser->setValue('discount', 2);
    
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1887);
        $browser->setValue('packPrice', 1.6987);
        $browser->setValue('discount', 1);
    
        // Записваме артикула
        $browser->press('Запис');
        
        // активираме продажбата
        $browser->press('Активиране');
        //$browser->press('Активиране/Контиране');
             
        if(strpos($browser->gettext(), '568,93')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Двадесет и седем хиляди седемстотин петдесет и осем BGN и 0,06')) {
        } else {
            return "Грешна обща сума";
        }
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
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
        //return  $browser->getHtml();
        //$browser->setValue('paymentType', "bank");
    
        $browser->press('Контиране');
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
         
        if(strpos($browser->gettext(), 'Чакащо плащане: Да')) {
        } else {
            return "Грешно чакащо плащане";
        }
    }
    /**
     * 12.
     * Проверка състояние плащане - надплатено, доставено и нефактурирано
     * Нова продажба на съществуваща фирма с папка -  лв
     */
    //http://localhost/unit_MinkPbgERP/CreateSaleOverpaid/
    function act_CreateSaleOverpaid()
    {
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
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
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPbgErpCreateOverpaid');
        $browser->setValue('pricesAtDate', date('d-m-Y', $valior));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
     
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*04');//22
        $browser->setValue('packPrice', '01+3*0.4');//2.2
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
    
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', '5');
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
         
        if(strpos($browser->gettext(), '3,32')) {
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
        $browser->setValue('amountDeal', '70');
        $browser->setValue('peroCase', '1');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: He')) {
        } else {
            return "Грешно чакащо плащане";
        }
    
    }
    /**
     * 12.
     * Проверка състояние плащане - просрочено, доставено и нефактурирано
     * Нова продажба на съществуваща фирма с папка -  лв
     */
    //http://localhost/unit_MinkPbgERP/CreateSaleExped1/
    function act_CreateSaleExped1()
    {
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
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
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPbgErpCreateSaleE1');
        $browser->setValue('pricesAtDate', date('d-m-Y', $valior));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010+03*04');//22
        $browser->setValue('packPrice', '01+3*0.4');//2.2
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', '5');
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
             
        if(strpos($browser->gettext(), '3,32')) {
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
        $browser->setValue('peroCase', '1');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return "Грешно чакащо плащане";
        }
    }
    /**
     * 12.
     * Проверка състояние плащане - просрочено, част. доставено, част.платено и фактурирано
     * Нова продажба на съществуваща фирма с папка - 35 473,79 лв
     */ 
    //http://localhost/unit_MinkPbgERP/CreateSaleExped/
    function act_CreateSaleExped()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
          
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
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
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSaleE');
        $browser->setValue('pricesAtDate', date('d-m-Y', $valior));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100+03*08');//124
        $browser->setValue('packPrice', '0100+3*0.8');//102.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
       
        // Записваме артикула и добавяме нов
        
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '0100.20+0.3*08');//102.6
        $browser->setValue('discount', 2);
     
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1207);
        $browser->setValue('packPrice', 1.6207);
        $browser->setValue('discount', 1);
          
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '3');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '1000 / 05-03*08');//176
        $browser->setValue('packPrice', '100/05+3*08');//44
        $browser->setValue('discount', 1);
    
        // Записваме артикула
        $browser->press('Запис');
            
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '633,88')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Тридесет и пет хиляди четиристотин седемдесет и три BGN и 0,79')) {
        } else {
            return "Грешна обща сума";
        }
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
      
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('date', date('d-m-Y', $valior));
        $valior=strtotime("-1 day");
        $browser->setValue('dueDate', date('d-m-Y', $valior));
        $browser->setValue('numlimit', '2000000 - 3000000');
        $browser->press('Чернова');
        //return  $browser->getHtml();
          
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
     * 12.
     * Проверка състояние плащане - просрочено, Метод - До x дни след фактуриране
     * Нова продажба на съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPbgERP/CreateSaleOverdue3days/
    function act_CreateSaleOverdue3days()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
         
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
    
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
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSaleOverdue');
        $browser->setValue('pricesAtDate', date('d-m-Y', $valior));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
    
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100+03*08');//124
        $browser->setValue('packPrice', '0100+3*0.8');//102.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
       
        // Записваме артикула и добавяме нов
    
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '0100.20+0.3*08');//102.6
        $browser->setValue('discount', 2);
             
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1887);
        $browser->setValue('packPrice', 1.6987);
        $browser->setValue('discount', 1);
             
        // Записваме артикула
        $browser->press('Запис');
        
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
             
        if(strpos($browser->gettext(), '568,93')) {
        } else {
            return "Грешно ДДС";
        }
    
        if(strpos($browser->gettext(), 'Двадесет и седем хиляди седемстотин петдесет и осем BGN и 0,06')) {
        } else {
            return "Грешна обща сума";
        }
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
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
        //return  $browser->getHtml();
        //$browser->setValue('paymentType', "bank");
    
        $browser->press('Контиране');
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Просрочено')) {
        } else {
            return "Грешно чакащо плащане";
        }    
    }
    
    /**
    * 11.
    * Проверка състояние плащане - чакащо, метод - до x дни след фактуриране (3,7,10,15,21,30) в деня на падеж
    * Нова продажба на съществуваща фирма с папка
    */
    //http://localhost/unit_MinkPbgERP/CreateSaleWaitP/
    function act_CreateSaleWaitP()
        {
                
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
        
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
                              
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
        
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
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSaleWaitP');
        $browser->setValue('pricesAtDate', date('d-m-Y', $valior));
        $browser->setValue('paymentMethodId', "До 7 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
             
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100+03*08');//124
        $browser->setValue('packPrice', '0100+3*0.8');//102.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        
        // Записваме артикула и добавяме нов
        
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '0100.20+0.3*08');//102.6
        $browser->setValue('discount', 2);
                 
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1887);
        $browser->setValue('packPrice', 1.6987);
        $browser->setValue('discount', 1);
            
        // Записваме артикула
        $browser->press('Запис');
                
        // активираме продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        //$browser->press('Активиране/Контиране');
                 
        if(strpos($browser->gettext(), '568,93')) {
        } else {
            return "Грешно ДДС";
        }
        
        if(strpos($browser->gettext(), 'Двадесет и седем хиляди седемстотин петдесет и осем BGN и 0,06')) {
                     
        } else {
            return "Грешна обща сума";
        }
        
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('storeId', 1);
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
        //return  $browser->getHtml();
        //$browser->setValue('paymentType', "bank");
        $browser->press('Контиране');
        
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('amountDeal', '100');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Да')) {
        } else {
            return "Грешно чакащо плащане в деня на падеж";
        }
    }
        
    
    /**
    * 10. Нова продажба на съществуваща фирма с папка
    * Проверка количество и цени - изрази
    * Проверка състояние чакащо плащане - не (платено)
    */
       
    //http://localhost/unit_MinkPbgERP/CreateSale/
        
    function act_CreateSale()
    {
        
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
        
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
            
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
        
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
        $browser->setValue('deliveryTime[t]', date('h:i:sa', $endhour));
        //$browser->setValue('deliveryTime[t]', '10:30');
        
        $browser->setValue('reff', 'А1234');
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkPTestCreateSale');
        $browser->setValue('pricesAtDate', date('d-m-Y'));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
             
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        
        // Добавяме нов артикул
        // За да смята добре с водещи нули - апостроф '023+045*03', '013+091*02'
        
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100+03*08');//124
        $browser->setValue('packPrice', '0100+3*0.8');//102.4
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        //return  $browser->getHtml();
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', '5');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0100-03*8');//76
        $browser->setValue('packPrice', '0100.20+0.3*08');//102.6
        $browser->setValue('discount', 2);
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        
        $browser->setValue('productId', '6');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '023 + 045*03');//158
        $browser->setValue('packPrice', '091 - 013*02');//65
        $browser->setValue('discount', 3);
        
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1887);
        $browser->setValue('packPrice', 1.6987);
        $browser->setValue('discount', 1);
        
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '3');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '1000 / 05-03*08');//176
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
                 
        if(strpos($browser->gettext(), '954,47')) {
        } else {
             return "Грешно ДДС";
        }
        
        if(strpos($browser->gettext(), 'Четиридесет и осем хиляди деветстотин и дванадесет BGN и 0,21')) {
        } else {
             return "Грешна обща сума";
        }
                
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
        // тази проверка не работи
        //if(strpos($browser->gettext(), 'Контиране')) {
        //}
        //if(strpos($browser->gettext(), 'Двадесет и седем хиляди осемстотин и осемнадесет')) {
        // връща грешка, ако не е избрано ЕН с цени
        //} else {
        //    return Err3;
        //}
                 
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
        //$browser->setValue('paymentType', "bank");
        
        $browser->press('Контиране');
        
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '100');
        $browser->setValue('peroCase', '1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', 'BG21 CREX 9260 3114 5487 01');
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
        if(strpos($browser->gettext(), 'Чакащо плащане: He')) {
        } else {
            return "Грешно чакащо плащане";
        }
    }
    /**
     *9.Рецепта - не приема мярката, като я няма - също не записва
     *
     */
    //http://localhost/unit_MinkPbgERP/CreateBom/
    function act_CreateBom()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
           
        $browser->click('Каталог');
        $browser->click('Продукти');
        $browser->click('Други продукти');
        $browser->click('Рецепти');
        $browser->click('Добавяне на нова търговска технологична рецепта');
        //Return $browser->getHtml();
        //$browser->hasText('Добавяне на търговска рецепта към');
        $browser->setValue('expenses', '13');
        $browser->setValue('quantityForPrice', '100');
        $browser->press('Чернова');
        $browser->press('Влагане');
        $browser->setValue('resourceId', '1');
        $browser->setValue('propQuantity', '19');
        //не приема мярката, като я няма - също не записва
        $browser->setValue('measureId', '3');
        return $browser->getHtml();
        $browser->press('Запис и нов');
        $browser->setValue('resourceId', '1');
        $browser->setValue('propQuantity', '1 + $Начално= 10');
        $browser->press('Запис');
        $browser->press('Активиране');
        $browser->press('OK');
       
    }
         
    /**
    * 8.Създава задание за производство Работи ли?
    */
    //http://localhost/unit_MinkPbgERP/CreatePlanningJob/
    function act_CreatePlanningJob()
    {
        
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
        
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pdainovska');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
             
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('N');
        $Company = "NEW INTERNATIONAL GMBH";
        
        $browser->click($Company);
        $browser->press('Папка');
        
        // нова продажба - проверка има ли бутон
        
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
                
        $endhour=strtotime("+5 hours");
        $enddate=strtotime("+2 weeks");
             
        $browser->setValue('deliveryTime[d]', date('d-m-Y', $enddate));
        //$browser->setValue('deliveryTime[d]', date('d-m-Y'));
        
        $browser->setValue('deliveryTime[t]', date('h:i:sa', $endhour));
        //$browser->setValue('deliveryTime[t]', '10:30');
        //$browser->setValue('reff', 'А1234455');
        $browser->setValue('caseId', 1);
        //$browser->setValue('note', 'С куриер');
        $browser->setValue('paymentMethodId', "50% авансово и 50% преди експедиция");
        $browser->setValue('pricesAtDate', date('d-m-Y'));
        $browser->setValue('template', 'Договор за изработка');
        $browser->setValue('template', '');
        
        // Записваме дотук черновата на продажбата
        $browser->press('Чернова');
        
        }
    
    /**
    * 7.Нова оферта на съществуваща фирма с папка
    */
    ///http://localhost/unit_MinkPbgERP/CreateQuotation/
    function act_CreateQuotation()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
       
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
                
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('N');
        $Company = "NEW INTERNATIONAL GMBH";
        $browser->click($Company);
    
        $browser->press('Папка');
    
        // нова оферта
        $browser->press('Нов...');
        $browser->press('Оферта');
        //$browser->hasText('Създаване на оферта в');
        $browser->press('Чернова');
        
        // Добавяме артикул - нестандартен
        $browser->press('Добавяне');
        $browser->setValue('productId', '13');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 100);
        $browser->setValue('packPrice', 1);
        //$browser->setValue('discount', 1);
                
        // Записваме артикула и добавяме нов
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '12');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 100);
        $browser->setValue('packPrice', 2);
        //$browser->setValue('discount', 2);
        // Записваме артикула 
        $browser->press('Запис');

        // Записваме артикула и добавяме опционален - услуга
        $browser->press('Опционален артикул');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1);
        $browser->setValue('packPrice', 100);
        //$browser->setValue('discount', 2);
        // Записваме артикула
        $browser->press('Запис');
        
        // Активираме офертата
        $browser->press('Активиране');
         
    }
    
    /**
     * 6.Запитване от съществуваща фирма с папка
     */
    //http://localhost/unit_MinkPbgERP/CreateInq/
    function act_CreateInq()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
         
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('N');
        $Company = "NEW INTERNATIONAL GMBH";
        $browser->click($Company);
    
        $browser->press('Папка');
    
        // ново запитване
        $browser->press('Нов...');
        $browser->press('Запитване');
        //$browser->hasText('Създаване на запитване в');
        $browser->press('Чернова');
        $browser->setValue('inqDescription', 'Торбички');
        $browser->setValue('measureId', '1');
        $browser->setValue('quantity1', '1000');
        $browser->setValue('name', 'Peter Neumann');
        $browser->setValue('country', 'Германия');
        $browser->setValue('email', 'pneumann@gmail.com');
        $browser->press('Чернова');
        $browser->press('Артикул');
        $browser->setValue('name', 'Артикул по запитване');
        $browser->press('Запис');
        
    }
    
    
    /**
     * 5.Търсим фирма, ако я има - отваряме и редактираме, ако не - създаваме нова фирма
     */
    //http://localhost/unit_MinkPbgERP/CreateEditCompany/
    function act_CreateEditCompany()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
           
        $browser->click('Визитник');
        // търсим фирмата
        $browser->click('N');
        //$browser->hasText( $Company);
    
        $Company = "NEW INTERNATIONAL GMBH";
    
        //if(strpos($browser->gettext(), $Company)  && 0) {  - не работи
        if(strpos($browser->gettext(), $Company)) {
            //bp($browser->gettext());
            //има такава фирма - редакция
            $browser->click($Company);
            $browser->press('Редакция');
    
            //Проверка дали сме в редакция
            //$browser->hasText('Редактиране на запис в "Фирми"');
    
        } else {
             
            // Правим нова фирма
            $browser->press('Нова фирма');
            //Проверка дали сме в добавяне
            //$browser->hasText('Добавяне на запис в "Фирми"');
    
        }
        $browser->setValue('name', $Company);
        $browser->setValue('country', 'Германия');
        $browser->setValue('place', 'Stuttgart');
        $browser->setValue('pCode', '70376');
        $browser->setValue('address', 'Brückenstraße 44А');
        //$browser->setValue('fax', '086711123');
        //$browser->setValue('tel', '086111111');
        $browser->setValue('uicId', '564749');
        $browser->setValue('website', 'http://www.new-international.com');
        $browser->setValue('Клиенти', '1');
        $browser->setValue('info', 'Фирма за тестове');
    
        $browser->press('Запис');
    
        // Създаване на папка на нова фирма/отваряне на папката на стара
        if(strpos($browser->gettext(), $Company)) {
            $browser->press('Папка');
        }
         
    }
    /**
     * 5.Търсим фирма, ако я има - отваряме и редактираме, ако не - създаваме нова фирма. Ако има повече от една страница, не работи добре.  Да се търси по буква!!!
     */
    //if(strpos($browser->gettext(), $Company)  && 0) {  - не намира съществуваща фирма
    //if(strpos($browser->gettext(), $Company)) {намира фирмата, но дава грешка при търсене на несъществуваща,  заради търсенето
    //http://localhost/unit_MinkPbgERP/TestFirm/
    function act_TestFirm()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
         
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
    
        $browser->click('Визитник');
        // търсим фирмата
        //$browser->click('P');
        $Company = "Пролет ООД";
        //$browser->open("/crm_Companies/?id=&Rejected=&alpha=&Cmd[default]=1&search={$Company}&users=all_users&order=alphabetic&groupId=&Cmd[default]=Филтрирай");
    
        //if(strpos($browser->gettext(), $Company)  && 0) {  - не намира съществуваща фирма
        //if(strpos($browser->gettext(), $Company)) { намира фирмата, но дава грешка при търсене на несъществуваща, заради търсенето
        if(strpos($browser->gettext(), $Company)) {
            //bp($browser->gettext());
            //има такава фирма - редакция
            $browser->click($Company);
            $browser->press('Редакция');
            //Проверка дали сме в редакция
            //$browser->hasText('Редактиране');
            //$browser->hasText('Фирма');
             
        } else {
             
            // Правим нова фирма
             
            $browser->press('Нова фирма');
             
            //$browser->hasText('Добавяне на запис');
            //$browser->hasText('Фирма');
        }
        $browser->setValue('name', $Company);
        $browser->setValue('place', 'Плевен');
        $browser->setValue('pCode', '7800');
        $browser->setValue('address', 'ул.Днепър, №11');
        $browser->setValue('fax', '086898989');
        $browser->setValue('tel', '086799999');
        $browser->setValue('info', 'Тази фирма е редактирана');
        $browser->setValue('Клиенти', '1');
        $browser->press('Запис');
        // Създаване на папка
    
        $browser->press('Папка');
        
        }
         
    /**
     * 5.редакция на фирма OK
     */
    //http://localhost/unit_MinkPbgERP/EditCompany/
    function act_EditCompany()
    {
        // редакция на фирма OK
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
         
        //Отваряме папката на фирма Фирма bgErp
        $browser->click('Визитник');
        $browser->click('F');
        //$browser->hasText('Фирма bgErp');
    
        $browser->click('Фирма bgErp');
    
        //Проверка дали сме в Фирма bgErp
        //$browser->hasText('Фирма bgErp - .....');
         
        $browser->press('Редакция');
        //Проверка дали сме в редакция
    
        //$browser->hasText('Редактиране на запис в "Фирми"');
        $browser->setValue('address', 'ул.Първа');
        $browser->setValue('pCode', '5400');
        $browser->setValue('fax', '333333');
        $browser->setValue('tel', '222222');
        $browser->setValue('uicId', '200021786');
        $browser->press('Запис');
         
        return ' Фирма-запис на редакцията';
         
    }
    
    /**
     * 5.Създаване на нова фирма и папка към нея, допуска дублиране - ОК
     */
    //http://localhost/unit_MinkPbgERP/CreateCompany/
    function act_CreateCompany()
    {
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        
        // Правим нова фирма
    
        $browser->click('Визитник');
        $browser->press('Нова фирма');
         
        //$browser->hasText('Добавяне на запис в "Фирми"');
        //$browser->hasText('Фирма');
    
        $browser->setValue('name', 'Фирма bgErp');
        $browser->setValue('place', 'Ст. Загора');
        $browser->setValue('pCode', '6400');
        $browser->setValue('address', 'ул.Бояна, №122');
        $browser->setValue('fax', '036111111');
        $browser->setValue('tel', '036111111');
        $browser->setValue('uicId', '110001322');
        $browser->setValue('Клиенти', '1');
        $browser->press('Запис');
    
        if (strpos($browser->getText(),"Предупреждение:")){
            $browser->setValue('Ignore', 1);
            $browser->press('Запис');
        }
    
        // Създаване на папка на нова фирма
    
        $browser->press('Папка');
        //bp($browser->getText());
    
    }
    
    /**
     * 4.Създава нов артикул - продукт през папката - Добавяне рецепти - Дава грешка за мярката на артикулите към рецептата
     */
    //http://localhost/unit_MinkPbgERP/CreateProduct1/
    function act_CreateProduct1()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
    
        // Правим нов артикул - продукт
        $browser->click('Каталог');
        $browser->click('Продукти');
        $browser->press('Артикул');
        $browser->setValue('name', 'Плик 7 л');
        $browser->setValue('code', 'plik7');
        $browser->setValue('measureId', '9');
        $browser->press('Запис');
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            $browser->click('Плик 7 л');
            //Return $browser->getHtml();
        }
        //Добавяне рецепта
        $browser->click('Рецепти');
        $browser->click('Добавяне на нова търговска технологична рецепта');
        //Return $browser->getHtml();
        //$browser->hasText('Добавяне на търговска рецепта към');
        $browser->setValue('expenses', '13');
        $browser->setValue('quantityForPrice', '166');
    
        $browser->press('Чернова');
        $browser->press('Влагане');
    
        $browser->setValue('resourceId', '3');
         
        if(strpos($browser->gettext(), 'Други външни услуги')) {
            //return 'Други външни услуги';
        } else {
            return 'артикул';
        }
    
        //$browser->setValue('packagingId', '9');
        $browser->setValue('propQuantity', '19');
    
        if(strpos($browser->gettext(), 'packagingId')) {
            //return 'packagingId' ;
        } else {
            return 'мярка';
        }
        //Return $browser->getHtml();
    
        ///// Дава грешка за мярката!!!
        //Return $browser->getHtml();
        $browser->press('Запис и нов');
        $browser->setValue('resourceId', '1');
        $browser->setValue('propQuantity', '1 + $Начално= 10');
        $browser->press('Запис');
        $browser->press('Активиране');
        $browser->press('OK');
    }
    
    
    /**
     * 4.Създава нов артикул - продукт, ако го има - редакция
     */
    //http://localhost/unit_MinkPbgERP/CreateProduct/
    function act_CreateProduct()
    {
         
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        // проверка потребител/парола
        //Грешка:Грешна парола или ник!
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
        // Правим нов артикул - продукт
    
        $browser->click('Каталог');
        $browser->press('Нов запис');
         
        //$browser->hasText('Избор на папка');
        //$browser->hasText('Категория');
    
        $browser->setValue('catcategorieId', '7');
        $browser->press('Напред');
    
        $browser->setValue('name', 'Чувал голям');
        $browser->setValue('code', 'smet1');
        $browser->setValue('measureId', '9');
        //$browser->setValue('groups[8]', 'On');
        $browser->press('Запис');
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            $browser->click('Продукти');
            $browser->click('Чувал голям');
            $browser->press('Редакция');
            $browser->setValue('info', 'черен');
            $browser->press('Запис');
            $browser->click('Добавяне на нов параметър');
            $browser->setValue('paramId', 'Дължина (см)');
    
            ///// Дава грешка на стойността на параметъра!!!
            $browser->setValue('paramValue', '12');
            Return $browser->getHtml();
            $browser->press('Запис');
        }
        //Добавяне рецепти?
        //$browser->click('Рецепти');
    }
    
    /**
     * 3.Създава нова каса
     */
    ///http://localhost/unit_MinkPbgERP/CreateCase/
    function act_CreateCase()
    {
    
        $browser = cls::get('unit_Browser');
    
        // bgERP
        $url = 'http://localhost/';
        $nick = 'Pavlinka';
        $pass = '111111';
    
        // Reload
        //$url = 'http://reload.bgerp.com/';
        //$nick = 'Ceo1';
        //$pass = '123456';
    
        $browser->start($url);
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', $nick);
        $browser->setValue('pass', $pass);
        $browser->press('Вход');
        // проверка потребител/парола
        //Грешка:Грешна парола или ник!
    
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
    
        // Правим нова каса
        $browser->click('Каси');
        $browser->press('Нов запис');
         
        //$browser->hasText('Добавяне на запис в "Фирмени каси"');
    
        $browser->setValue('name', 'КАСА 2');
         
        $browser->setValue('Pavlinka', '1');
        //return  $browser->getHtml();
        $browser->press('Запис');
         
        if (strpos($browser->getText(),'Непопълнено задължително поле')){
            $browser->press('Отказ');
            Return Грешка;
        }
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            Return Дублиране;
        }
    
    }
    
    
    /**
     * 2.Създава нова банкова сметка
     */
    //http://localhost/unit_MinkPbgERP/CreateBankAcc/
    function act_CreateBankAcc()
    {
         
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        // проверка потребител/парола
        //Грешка:Грешна парола или ник!
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
        // Правим нова банка
        $browser->click('Банки');
        $browser->press('Нов запис');
         
        //$browser->hasText('Добавяне на запис в "Банкови сметки на фирмата"');
    
        $browser->setValue('iban', 'BG21 CREX 9260 3114 5487 01');
        $browser->setValue('currencyId', '1');
        $browser->setValue('Pavlinka', '1');
        //$browser->setValue('Оператори....', 'On');
        $browser->press('Запис');
    
        if (strpos($browser->getText(),'Непопълнено задължително поле')){
            $browser->press('Отказ');
            Return Грешка;
        }
    
        if (strpos($browser->getText(),"Вече има наша сметка с този IBAN")){
            $browser->press('Отказ');
            Return Дублиране;
        }
    
    }
    
    /**
     * 1. Създава нов склад
     */
    //http://localhost/unit_MinkPbgERP/CreateStore/
    function act_CreateStore()
    {
         
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
        //return  $browser->getHtml();
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
         
        $browser->press('Вход');
        // проверка потребител/парола
        //Грешка:Грешна парола или ник!
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
        // Правим нов склад
        //return  $browser->getHtml();
        $browser->click('Склад');
        $browser->click('Складове');
        $browser->press('Нов запис');
         
        //$browser->hasText('Добавяне на запис в "Складове"');
         
        $browser->setValue('name', 'Склад 1');
        //$browser->setValue('Екип "Главен офис"', '1');
        $browser->setValue('Pavlinka', '1');
         
        $browser->press('Запис');
    
        if (strpos($browser->getText(),'Непопълнено задължително поле')){
            $browser->press('Отказ');
            Return Грешка;
        }
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            Return Дублиране;
        }
    
    }
     
    
}
   