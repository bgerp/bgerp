
<?php


/**
 * Клас  'tests_Test' - Разни тестове на PHP-to
 *
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
     
    //bgERP 16-05-2016
    ///http://localhost/unit_MinkPbgERP/CreateSale/
    //return  $browser->getHtml();

    /**
     * Нова продажба на съществуваща фирма с папка
     */
    function act_CreateSale()
    {

        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');

        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        //проверка потребител/парола
        //Грешка:Грешна парола или ник!
        //return  $browser->getHtml();
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');

        //Отваряме папката на фирмата
        $browser->click('Визитник');
        //$Company = 'Детелина';
        $browser->click('F');
         
        $Company = 'Фирма bgErp';
        //$browser->hasText($Company);

        $browser->click($Company);
         
        //Проверка дали сме в папката на фирмата
        //$browser->hasText($Company );

        $browser->press('Папка');

        // нова продажба - има ли бутон?

        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');

        $endhour=strtotime("+5 hours");
        $enddate=strtotime("+1 Day");
        //$enddate = strtotime("+3 weeks");
         
        $browser->setValue('deliveryTime[d]', date('d-m-Y', $enddate));
        //$browser->setValue('deliveryTime[d]', date('d-m-Y'));

        $browser->setValue('deliveryTime[t]', date('h:i:sa', $endhour));
        //$browser->setValue('deliveryTime[t]', '10:30');

        $browser->setValue('reff', 'А1234');
        $browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'С куриер');
        $browser->setValue('pricesAtDate', date('d-m-Y'));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме дотук черновата на продажбата
        $browser->press('Чернова');

        // Добавяме нов артикул
        $browser->press('Артикул');
        $browser->setValue('productId', '7');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 133);
        $browser->setValue('packPrice', 179.693);
        $browser->setValue('discount', 3);

        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', '9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1887);
        $browser->setValue('packPrice', 1.6987);
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
         
        if(strpos($browser->gettext(), '749,03')) {
        } else {
            return Err1;
        }

        if(strpos($browser->gettext(), 'Тридесет и една хиляди шестстотин двадесет и шест')) {
             
        } else {
            return Err2;
        }

        //if(strpos($browser->gettext(), '26 355,59')) {

        ////if(strpos($browser->gettext(), '5 271,12')) {

        //} else {
        //  return Err2;
        //}

        //if(strpos($browser->gettext(), '31 626,71')) {

        //} else {
        //  return Err3;
        //}

        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('storeId', 1);
        $browser->press('Чернова');
        $browser->press('Контиране');
        // тази проверка не работи
        //if(strpos($browser->gettext(), 'Контиране')) {
        //$browser->press('Контиране');
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
        //return  $browser->getHtml();
        //return 'paymentType';
        //$browser->setValue('paymentType', "bank");

        $browser->press('Контиране');

        //if(strpos($browser->gettext(), '23 182,19')) {

        //} else {
        //    return Err7;
        //}
         
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

        // Приключване
        //$browser->selectNode("#Sal89 > td:nth-child(2) > div:nth-child(1) > div:nth-child(1) > input:nth-child(1)");
        //$browser->click();

        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        //return  $browser->getHtml();
        $browser->press('Контиране');

    }
  
    /**
     *Рецепта - не приема мярката, като я няма - също не записва
     *
     */
    function act_CreateBom()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        //проверка потребител/парола
        //Грешка:Грешна парола или ник!
        //return  $browser->getHtml();
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
    
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
        //
      
        }
         
    
    
    /**
     * Запитване от съществуваща фирма с папка
     */
    function act_CreateInq()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
                 
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        
        //return  $browser->getHtml();
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $Company = "NEW INTERNATIONAL GMBH";
        
        //$browser->hasText($Company);
        $browser->click($Company);
    
        //Проверка дали сме в папката на фирмата
        //$browser->hasText($Company );
    
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
        return  $browser->getHtml();
    }    
    
    /**
     * Нова оферта на съществуваща фирма с папка
     */
    function act_CreateQuotation()
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
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $Company = "NEW INTERNATIONAL GMBH";
        //$browser->hasText($Company);
        $browser->click($Company);
    
        //Проверка дали сме в папката на фирмата
        //$browser->hasText($Company );
    
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
     * Създава нова каса
     */
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
    
        //return  $browser->getHtml();
    
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
     * Създава нова банкова сметка
     */
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
     * Създава нов склад
     */
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
   
    
    /**
     * Създава нов артикул - продукт през папката - Добавяне рецепти?
     */
    
    function act_CreateProduct1()
    {
        //http://localhost/unit_MinkPbgERP/CreateProduct1/
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
     * Създава нов артикул - продукт, ако го има - редакция
     */
    
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
        $browser->click('Рецепти');
  
        
    }
    
   /**
     * Търсим фирма, ако я има - отваряме и редактираме, ако не - създаваме нова фирма
     */
    
    function act_CreateEditCompany()
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
    
        $browser->click('Визитник');
        // търсим фирмата
    
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
            $browser->setValue('info', 'Фирма за Mink тестове ');
    
            $browser->press('Запис');
            
            // Създаване на папка на нова фирма/отваряне на папката на стара
            if(strpos($browser->gettext(), $Company)) {
            $browser->press('Папка');
            }
           
    }
       
    /**
     * Търсим фирма, ако я има - отваряме и редактираме, ако не - създаваме нова фирма. Не работи добре.
     */
    //if(strpos($browser->gettext(), $Company)  && 0) {  - не намира съществуваща фирма
    //if(strpos($browser->gettext(), $Company)) {намира фирмата, но дава грешка при търсене на несъществуваща,
       
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
       
        $Company = "Песен";
        $browser->open("/crm_Companies/?id=&Rejected=&alpha=&Cmd[default]=1&search={$Company}&users=all_users&order=alphabetic&groupId=&Cmd[default]=Филтрирай");
        
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
            
            $browser->setValue('place', 'Пирот');
            $browser->setValue('fax', '77777');
           
            $browser->press('Запис');
            return 'Фирма-редакция';  
        } else {
         
            // Правим нова фирма
             
            $browser->press('Нова фирма');
             
            //$browser->hasText('Добавяне на запис');
            //$browser->hasText('Фирма');
        
            $browser->setValue('name', $Company);
            $browser->setValue('place', 'Плевен');
            $browser->setValue('pCode', '7800');
            $browser->setValue('address', 'ул.Днепър, №11');
            $browser->setValue('fax', '086898989');
            $browser->setValue('tel', '086777777');
            $browser->setValue('info', 'Тази фирма е ...');
            $browser->setValue('Клиенти', '1');
            
            $browser->press('Запис');
            // Създаване на папка на нова фирма
            
            $browser->press('Папка');
            return 'Фирма-добавяне';
          }
    
    }
     
    
    /**
     * редакция на фирма OK
     */
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
        // проверка потребител/парола
        //Грешка:Грешна парола или ник!
    
        //$browser->hasText('Известия');
        //$browser->hasText('Pavlinka');
    
       
        //Отваряме папката на фирма Фирма-тест 3
        $browser->click('Визитник');
    
        //$browser->hasText('Фирма-тест 3');
    
        $browser->click('Фирма-тест 3');
    
        //Проверка дали сме в Фирма-тест 3
        //$browser->hasText('Фирма-тест 3 - Самоводене');
         
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
     * Създаване на нова фирма и папка към нея, допуска дублиране - ОК
     */
    function act_CreateCompany()
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
        
        // Правим нова фирма
        
        $browser->click('Визитник');
        $browser->press('Нова фирма');
           
        //$browser->hasText('Добавяне на запис в "Фирми"');
        //$browser->hasText('Фирма');      
        
        $browser->setValue('name', 'Фирма bgErp');
        //$browser->setValue('country', 'Франция');
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
    
    
    function act_TestReload()
    {
        //добавяне и редакция Reload
        
        $browser = cls::get('unit_Browser');
        $browser->start('http://reload.bgerp.com/');
        
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Ceo1');
        $browser->setValue('pass', '123456');
        $browser->press('Вход');
        
       // $browser->hasText('Известия');
       // $browser->hasText('Ceo1');
       // $browser->hasText('Reload ERP');
        
        
        //Опит за добавяне 
        $browser->click('Визитник');
        $browser->press('Нова фирма');
        
        //return  $browser->getHtml();
        //$browser->hasText('Държава');
        //$browser->hasText('Фирма');      
        
        $browser->setValue('name', 'Фирма-тест 3');
        $browser->setValue('place', 'Сливен');
        $browser->setValue('pCode', '6400');
        $browser->setValue('address', 'ул.Бояна, №122');     
        $browser->setValue('uicId', '110001322');
        $browser->setValue('fax', '036111111');
        $browser->setValue('tel', '036111111');
     
        $browser->press('Запис');
        if (strpos($browser->getText(),"Предупреждение:")){
            $browser->setValue('Ignore', 1);
            $browser->press('Запис');
        }
        return ' Фирма-запис';
        
        //Опит за редакция
        //Отваряме папката на фирма Полис Купър - Мая Станчева ЕТ
        $browser->click('Визитник');
                
        //$browser->hasText('Полис Купър - Мая Станчева ЕТ');
        
        $browser->click('Полис Купър - Мая Станчева ЕТ');
        
        //Проверка дали сме в Полис Купър - Мая Станчева ЕТ
        //$browser->hasText('Полис Купър - Мая Станчева ЕТ - Севлиево');
       
        $browser->press('Редакция');
        // Press дава грешка, Click не дава грешка, но не отваря 
        //Проверка дали сме в редакция
        
        //$browser->hasText('Редактиране');
        //$browser->hasText('Фирма');
        
        $browser->setValue('fax', '999999');
        $browser->setValue('tel', '999999');
        $browser->press('Запис');
       
         // Правим нова продажба
               
        $browser->press('Папка');
    
        // Press дава грешка        
        $browser->press('Продажба');
        
        //$browser->hasText('Създаване');
      
        // Попълваме някои полета
        $browser->setValue('deliveryTime[d]', date('d-m-Y'));
        $browser->setValue('deliveryTime[t]', '08:30');
    
        // Записваме дотук черновата на продажбата
        $browser->press('Чернова');
        //return OK;
        
    }
    
}
   