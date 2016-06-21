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
class unit_MinkP extends core_Manager {
    
    //Създава рецепта с 3 етапа, преди това - съставните артикули

    /**
     * 1.Създава нов артикул - труд със себестойност 
     * (За да записва себестойността - трябва да е продаваем и да има въведена ценова група, иначе не зарежда името на артикула)
     */
    //http://localhost/unit_MinkP/CreateProductWork/
    function act_CreateProductWork()
    {
         
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        // Правим нов артикул - труд
        $browser->click('Каталог');
        $browser->click('Труд');
        $browser->press('Артикул');
        $browser->setValue('name', 'Труд');
        $browser->setValue('code', 'work');
        $browser->setValue('measureId', 'час');
        $browser->setValue('meta[canSell]', 'canSell');
        $browser->press('Запис');
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            $browser->click('Труд (work)');
        } 
        
        $browser->click('Цени');
        //Задаване на ценова група
        //Ако не е зададена ценова група, не записва себестойността, защото не зарежда името на артикула
        $browser->click('Задаване на ценова група');
        $browser->refresh('Запис');
        $browser->setValue('groupId', '1');
        $browser->press('Запис');
        //Добавяне на мениджърска себестойност
        $browser->click('Цени');
        $browser->click('Добавяне на нова мениджърска себестойност');
        $browser->refresh('Запис');
        $browser->setValue('price', '10');
        $browser->press('Запис');
        
    }
    
    /**
     * 2.Създава нов артикул - Електричество със себестойност
     * (За да записва себестойността - трябва да е продаваем и да има въведена ценова група, иначе не зарежда името на артикула)
     */
    //http://localhost/unit_MinkP/CreateElectricity/
    function act_CreateElectricity()
    {
         
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        // Правим нов артикул - Електричество
        $browser->click('Каталог');
        $browser->click('Външни услуги');
        $browser->press('Артикул');
        $browser->setValue('name', 'Електричество');
        $browser->setValue('code', 'electricity');
        $browser->setValue('measureId', 'киловатчас');
        $browser->setValue('meta[canSell]', 'canSell');
        $browser->press('Запис');
       
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            $browser->click('Електричество');
        } 
        
        //Задаване на ценова група
        //Ако не е зададена ценова група, не записва себестойността, защото не зарежда името на артикула
        $browser->click('Цени');
        $browser->click('Задаване на ценова група');
        $browser->refresh('Запис');
        $browser->setValue('groupId', '1');
        $browser->press('Запис');
        //Добавяне на мениджърска себестойност
        $browser->click('Цени');
        $browser->click('Добавяне на нова мениджърска себестойност');
        $browser->refresh('Запис');
        $browser->setValue('price', '0.60');
        $browser->press('Запис');
        
    }
    
    /**
     * 3.Създава нов артикул - опаковка
     */
    //http://localhost/unit_MinkP/CreatePackage/
    function act_CreatePackage()
    {
         
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        // Правим нов артикул - опаковка
        $browser->click('Каталог');
        $browser->click('Стоки');
        $browser->press('Артикул');
        $browser->setValue('name', 'Опаковка');
        $browser->setValue('code', 'package');
        $browser->setValue('measureId', 'брой');
        $browser->setValue('meta[canConvert]', 'canConvert');
        $browser->press('Запис');
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
        }
        
    }
    
    /**
     * 4.Създава нов артикул - материал 1
    */
    //http://localhost/unit_MinkP/CreateMaterial1/
    function act_CreateMaterial1()
    {
         
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        // Правим нов артикул - материал 1
        $browser->click('Каталог');
        $browser->click('Суровини и материали');
        $browser->press('Артикул');
        $browser->setValue('name', 'Материал 1');
        $browser->setValue('code', 'Mat1');
        $browser->setValue('measureId', 'килограм');
        $browser->press('Запис');
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
        }
           
    }
    
    /**
     * 5.Създава нов артикул - материал 2
     */
    //http://localhost/unit_MinkP/CreateMaterial2/
    function act_CreateMaterial2()
    {
         
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
    
        // Правим нов артикул - материал 2
        $browser->click('Каталог');
        $browser->click('Суровини и материали');
        $browser->press('Артикул');
        $browser->setValue('name', 'Материал 2');
        $browser->setValue('code', 'Mat2');
        $browser->setValue('measureId', 'литър');
        $browser->press('Запис');
        
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
        }
         
     } 
     
     /**
      * 6.Създава нов артикул - отпадък 1
      */
     //http://localhost/unit_MinkP/CreateWaste1/
     function act_CreateWaste1()
     {
          
         $browser = cls::get('unit_Browser');
         $browser->start('http://localhost/');
     
         // Логваме се
         $browser->click('Вход');
         $browser->setValue('nick', 'Pavlinka');
         $browser->setValue('pass', '111111');
         $browser->press('Вход');
     
         // Правим нов артикул - отпадък 1
         $browser->click('Каталог');
         $browser->click('Суровини и материали');
         $browser->press('Артикул');
         $browser->setValue('name', 'Отпадък 1');
         $browser->setValue('code', 'Waste1');
         $browser->setValue('measureId', 'литър');
         $browser->setValue('meta[canSell]', 'canSell');
         $browser->press('Запис');
         if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
             $browser->press('Отказ');
             $browser->click('Отпадък 1');
         }
         //Задаване на ценова група
         $browser->click('Цени');
         $browser->click('Задаване на ценова група');
         $browser->refresh('Запис');
         $browser->setValue('groupId', '1');
         $browser->press('Запис');
         //Добавяне на мениджърска себестойност
         $browser->click('Цени');
         $browser->click('Добавяне на нова мениджърска себестойност');
         $browser->refresh('Запис');
         $browser->setValue('price', '1');
         $browser->press('Запис');
     }
     
     /**
      * 7.Създава нов артикул - отпадък 2
      */
     //http://localhost/unit_MinkP/CreateWaste2/
     function act_CreateWaste2()
     {
     
         $browser = cls::get('unit_Browser');
         $browser->start('http://localhost/');
          
         // Логваме се
         $browser->click('Вход');
         $browser->setValue('nick', 'Pavlinka');
         $browser->setValue('pass', '111111');
         $browser->press('Вход');
          
         // Правим нов артикул - отпадък 2
         $browser->click('Каталог');
         $browser->click('Суровини и материали');
         $browser->press('Артикул');
         $browser->setValue('name', 'Отпадък 2');
         $browser->setValue('code', 'Waste2');
         $browser->setValue('measureId', 'килограм');
         $browser->setValue('meta[canSell]', 'canSell');
         $browser->press('Запис');
         
         if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
             $browser->press('Отказ');
             $browser->click('Отпадък 2');
         }
         //Задаване на ценова група
         $browser->click('Цени');
         $browser->click('Задаване на ценова група');
         $browser->refresh('Запис');
         $browser->setValue('groupId', '1');
         $browser->press('Запис');
         //Добавяне на мениджърска себестойност
         $browser->click('Цени');
         $browser->click('Добавяне на нова мениджърска себестойност');
         $browser->refresh('Запис');
         $browser->setValue('price', '2');
         $browser->press('Запис');
     }
     
     /**
      * 8.Създава нов артикул - отпадък 3
      */
     //http://localhost/unit_MinkP/CreateWaste3/
     function act_CreateWaste3()
     {
          
         $browser = cls::get('unit_Browser');
         $browser->start('http://localhost/');
     
         // Логваме се
         $browser->click('Вход');
         $browser->setValue('nick', 'Pavlinka');
         $browser->setValue('pass', '111111');
         $browser->press('Вход');
     
         // Правим нов артикул - отпадък 3
         $browser->click('Каталог');
         $browser->click('Суровини и материали');
         $browser->press('Артикул');
         $browser->setValue('name', 'Отпадък 3');
         $browser->setValue('code', 'Waste3');
         $browser->setValue('measureId', 'килограм');
         $browser->setValue('meta[canSell]', 'canSell');
         $browser->press('Запис');
          
         if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
             $browser->press('Отказ');
             $browser->click('Отпадък 3');
         }
         //Задаване на ценова група
         $browser->click('Цени');
         $browser->click('Задаване на ценова група');
         $browser->refresh('Запис');
         $browser->setValue('groupId', '1');
         $browser->press('Запис');
         //Добавяне на мениджърска себестойност
         $browser->click('Цени');
         $browser->click('Добавяне на нова мениджърска себестойност');
         $browser->refresh('Запис');
         $browser->setValue('price', '3');
         $browser->press('Запис');
     }
    
     /**
      * 9.Създава нов артикул - машина 1 (машинно време 1 етап)
      */
     //http://localhost/unit_MinkP/CreateMash1/
     function act_CreateMash1()
     {
     
         $browser = cls::get('unit_Browser');
         $browser->start('http://localhost/');
          
         // Логваме се
         $browser->click('Вход');
         $browser->setValue('nick', 'Pavlinka');
         $browser->setValue('pass', '111111');
         $browser->press('Вход');
          
         // Правим нов артикул - машина 1
         $browser->click('Каталог');
         $browser->click('Суровини и материали');
         $browser->press('Артикул');
         $browser->setValue('name', 'Машина 1');
         $browser->setValue('code', 'Mash1');
         $browser->setValue('measureId', 'час');
         $browser->setValue('meta[canSell]', 'canSell');
         $browser->press('Запис');
     
         if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
             $browser->press('Отказ');
             $browser->click('Машина 1');
         }
         //Задаване на ценова група
         //Ако не е зададена ценова група, не записва себестойността, защото не зарежда името на артикула
         $browser->click('Цени');
         $browser->click('Задаване на ценова група');
         $browser->refresh('Запис');
         $browser->setValue('groupId', '1');
         $browser->press('Запис');
         //Добавяне на мениджърска себестойност
         $browser->click('Цени');
         $browser->click('Добавяне на нова мениджърска себестойност');
         $browser->refresh('Запис');
         $browser->setValue('price', '5');
         $browser->press('Запис');
     }
     
     /**
      * 10.Създава нов артикул - машина 2 (машинно време 2 етап)
      */
     //http://localhost/unit_MinkP/CreateMash2/
     function act_CreateMash2()
     {
          
         $browser = cls::get('unit_Browser');
         $browser->start('http://localhost/');
     
         // Логваме се
         $browser->click('Вход');
         $browser->setValue('nick', 'Pavlinka');
         $browser->setValue('pass', '111111');
         $browser->press('Вход');
     
         // Правим нов артикул - машина 2
         $browser->click('Каталог');
         $browser->click('Суровини и материали');
         $browser->press('Артикул');
         $browser->setValue('name', 'Машина 2');
         $browser->setValue('code', 'Mash2');
         $browser->setValue('measureId', 'час');
         $browser->setValue('meta[canSell]', 'canSell');
         $browser->press('Запис');
          
         if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
             $browser->press('Отказ');
             $browser->click('Машина 2');
         }
         //Задаване на ценова група
         //Ако не е зададена ценова група, не записва себестойността, защото не зарежда името на артикула
         $browser->click('Цени');
         $browser->click('Задаване на ценова група');
         $browser->refresh('Запис');
         $browser->setValue('groupId', '1');
         $browser->press('Запис');
         //Добавяне на мениджърска себестойност
         $browser->click('Цени');
         $browser->click('Добавяне на нова мениджърска себестойност');
         $browser->refresh('Запис');
         $browser->setValue('price', '10');
         $browser->press('Запис');
     }
      
     /**
      * 11.Създава нов артикул - машина 3 (машинно време 3 етап)
      */
     //http://localhost/unit_MinkP/CreateMash3/
     function act_CreateMash3()
     {
          
         $browser = cls::get('unit_Browser');
         $browser->start('http://localhost/');
     
         // Логваме се
         $browser->click('Вход');
         $browser->setValue('nick', 'Pavlinka');
         $browser->setValue('pass', '111111');
         $browser->press('Вход');
     
         // Правим нов артикул - машина 3
         $browser->click('Каталог');
         $browser->click('Суровини и материали');
         $browser->press('Артикул');
         $browser->setValue('name', 'Машина 3');
         $browser->setValue('code', 'Mash3');
         $browser->setValue('measureId', 'час');
         $browser->setValue('meta[canSell]', 'canSell');
         $browser->press('Запис');
          
         if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
             $browser->press('Отказ');
             $browser->click('Машина 3');
         }
         //Задаване на ценова група
         //Ако не е зададена ценова група, не записва себестойността, защото не зарежда името на артикула
         $browser->click('Цени');
         $browser->click('Задаване на ценова група');
         $browser->refresh('Запис');
         $browser->setValue('groupId', '1');
         $browser->press('Запис');
         //Добавяне на мениджърска себестойност
         $browser->click('Цени');
         $browser->click('Добавяне на нова мениджърска себестойност');
         $browser->refresh('Запис');
         $browser->setValue('price', '15');
         $browser->press('Запис');
     }
  
     /**
      * 12.Създава нов артикул - заготовка 1 (резултат от 1 етап)
      */
     //http://localhost/unit_MinkP/CreateStage1/
     function act_CreateStage1()
     {
     
         $browser = cls::get('unit_Browser');
         $browser->start('http://localhost/');
          
         // Логваме се
         $browser->click('Вход');
         $browser->setValue('nick', 'Pavlinka');
         $browser->setValue('pass', '111111');
         $browser->press('Вход');
          
         // Правим нов артикул - заготовка 1
         $browser->click('Каталог');
         $browser->click('Заготовки');
         $browser->press('Артикул');
         $browser->setValue('name', 'Заготовка 1');
         $browser->setValue('code', 'Stage1');
         $browser->setValue('measureId', 'килограм');
         $browser->press('Запис');
     
         if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
             $browser->press('Отказ');
             $browser->click('Заготовка 1');
         }
         $browser->click('Добавяне на нов параметър');
         $browser->setValue('paramId', 'Тегло');
         $browser->refresh('Запис');
         $browser->setValue('paramValue', '20');
         $browser->press('Запис');
     }
     
     /**
      * 13.Създава нов артикул - заготовка 2 (резултат от 2 етап)
      */
     //http://localhost/unit_MinkP/CreateStage2/
     function act_CreateStage2()
     {
          
         $browser = cls::get('unit_Browser');
         $browser->start('http://localhost/');
     
         // Логваме се
         $browser->click('Вход');
         $browser->setValue('nick', 'Pavlinka');
         $browser->setValue('pass', '111111');
         $browser->press('Вход');
     
         // Правим нов артикул - заготовка 2
         $browser->click('Каталог');
         $browser->click('Заготовки');
         $browser->press('Артикул');
         $browser->setValue('name', 'Заготовка 2');
         $browser->setValue('code', 'Stage2');
         $browser->setValue('measureId', 'килограм');
         $browser->press('Запис');
          
         if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
             $browser->press('Отказ');
             $browser->click('Заготовка 2');
         }
         $browser->click('Добавяне на нов параметър');
         $browser->setValue('paramId', 'Цвят');
         $browser->refresh('Запис');
         $browser->setValue('paramValue', '4');
         $browser->press('Запис');
          
     }
     
     /**
      * 14.Създава нов артикул - крайно изделие (резултат от 3 етап)
      */
     //http://localhost/unit_MinkP/CreateTestBom/
     function act_CreateTestBom()
     {
     
         $browser = cls::get('unit_Browser');
         $browser->start('http://localhost/');
          
         // Логваме се
         $browser->click('Вход');
         $browser->setValue('nick', 'Pavlinka');
         $browser->setValue('pass', '111111');
         $browser->press('Вход');
          
         // Правим нов артикул - крайно изделие
         $browser->click('Каталог');
         $browser->click('Продукти');
         $browser->press('Артикул');
         $browser->setValue('name', 'Тест рецепта с етапи');
         $browser->setValue('code', 'TestBom');
         $browser->setValue('measureId', 'брой');
         $browser->press('Запис');
     
         if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
             $browser->press('Отказ');
         }
     
     }
     
    /**
     * 15.Създава доставка на материалите
     */
    //http://localhost/unit_MinkP/CreatePurchase/
    function act_CreatePurchase()
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
        
        // нова покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->setValue('bankAccountId', 1);
        $browser->setValue('note', 'MinkTestCreatePurchase');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->press('Чернова');
        
        // Записваме черновата на покупката
        // Добавяме нов артикул - опаковка
            $browser->press('Артикул');
            $browser->setValue('productId', 'Опаковка');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '1008/4+03*08');//276
            $browser->setValue('packPrice', '003+4*0.08/2');//3.16
            $browser->setValue('discount', 3);
            $browser->press('Запис и Нов');
            // Записваме артикула и добавяме нов - материал 1
            $browser->setValue('productId', 'Материал 1');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '010+09*8');//82
            $browser->setValue('packPrice', '010,20-0.6*08');//5.4
            $browser->setValue('discount', 2);
            $browser->press('Запис и Нов');
            // Записваме артикула и добавяме нов - материал 2
            $browser->setValue('productId', 'Материал 2');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '023 + 012*09');//131
            $browser->setValue('packPrice', '0,091 - 0,023*02');//0.045
            $browser->setValue('discount', 4);
            // Записваме артикула
            $browser->press('Запис');
            // активираме покупката
            $browser->press('Активиране');
            //$browser->press('Активиране/Контиране');
             
            if(strpos($browser->gettext(), '257,12')) {
            } else {
                return "Грешно ДДС";
            }
        
            if(strpos($browser->gettext(), 'Хиляда петстотин четиридесет и два BGN и 0,72')) {
            } else {
                return "Грешна обща сума";
            }
        
            // Складова разписка
            $browser->press('Засклаждане');
            $browser->setValue('storeId', 1);
            $browser->press('Чернова');
            $browser->press('Контиране');
        
            // Фактура  № се сменя при повторен тест
            $browser->press('Вх. фактура');
            $browser->setValue('number', '17');
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
            if(strpos($browser->gettext(), 'Чакащо плащане: Не')) {
            } else {
                return "Грешно чакащо плащане";
            }
        
    }
    
    /**
     *16.Добавя рецепта етап 1
     */
    //http://localhost/unit_MinkP/CreateBomStage1/
    function act_CreateBomStage1()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        $browser->click('Каталог');
        $browser->click('Заготовки');
        $browser->click('Заготовка 1');
        $browser->click('Рецепти');
        $browser->click('Добавяне на нова търговска технологична рецепта');
        //$browser->hasText('Добавяне на търговска рецепта към');
        $browser->setValue('notes', 'BomStage1');
        //$browser->setValue('expenses', '8');
        $browser->setValue('quantityForPrice', '1000');
        $browser->press('Чернова');
        $browser->press('Влагане');
         
        $browser->setValue('resourceId', 'Труд (work)');
        $browser->setValue('propQuantity', '0.002 + $Начално= 20');
        $browser->refresh('Запис');
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Електричество');
        $browser->setValue('propQuantity', '0.008 + $Начално= 5');
        $browser->refresh('Запис');
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Машина 1');
        $browser->setValue('propQuantity', '0.002 + $Начално= 21');
        $browser->refresh('Запис');
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Материал 1');
        $browser->setValue('propQuantity', '0.05*$тегло(кг) + $Начално= 1');
        $browser->refresh('Запис');
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Материал 2');
        $browser->setValue('propQuantity', '0.99*$тегло(кг) + $Начално= 20');
        $browser->refresh('Запис');
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Отпадък 1');
        $browser->setValue('propQuantity', '0.04*$тегло(кг) + $Начално= 21');
        $browser->refresh('Запис');
        $browser->press('Запис');
        //return $browser->gethtml();
        $browser->press('Активиране');
         
    }
    /**
     *17.Добавя рецепта етап 2
     */
    //http://localhost/unit_MinkP/CreateBomStage2/
    function act_CreateBomStage2()
    {
    
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', 'Pavlinka');
        $browser->setValue('pass', '111111');
        $browser->press('Вход');
        $browser->click('Каталог');
        $browser->click('Заготовки');
        $browser->click('Заготовка 2');
        $browser->click('Рецепти');
        $browser->click('Добавяне на нова търговска технологична рецепта');
      //$browser->hasText('Добавяне на търговска рецепта към');
        $browser->setValue('notes', 'BomStage2');
        //$browser->setValue('expenses', '8');
        $browser->setValue('quantityForPrice', '1000');
        $browser->press('Чернова');
        $browser->press('Влагане');
         
        $browser->setValue('resourceId', 'Труд (work)');
        $browser->setValue('propQuantity', '0.003 + $Начално= (30 + $цвят*20)');
        $browser->refresh('Запис');
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Електричество');
        $browser->setValue('propQuantity', '0.3 + $Начално= 4 + $цвят');
        $browser->refresh('Запис');
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Машина 2');
        $browser->setValue('propQuantity', '0.003 + $Начално= (30 + $цвят*20)');
        $browser->refresh('Запис');
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Отпадък 2');
        $browser->setValue('propQuantity', '0.0002');
        $browser->refresh('Запис');
        $browser->press('Запис');
        $browser->press('Етап');
        $browser->setValue('resourceId', 'Заготовка 1');
        $browser->setValue('propQuantity', '1');
        $browser->refresh('Запис');
        $browser->press('Запис');
        $browser->press('Активиране');
         
    }
    /**
     *18.Добавя рецепта етап 3 - крайно изделие
     */
    //http://localhost/unit_MinkP/CreateBomStage3/
    function act_CreateBomStage3()
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
        $browser->click('Тест рецепта с етапи');
        $browser->click('Рецепти');
        $browser->click('Добавяне на нова търговска технологична рецепта');
        //$browser->hasText('Добавяне на търговска рецепта към');
        $browser->setValue('notes', 'BomStage3');
        //$browser->setValue('expenses', '8');
        $browser->setValue('quantityForPrice', '10000');
        $browser->press('Чернова');
        $browser->press('Влагане');
         
        $browser->setValue('resourceId', 'Труд (work)');
        $browser->setValue('propQuantity', '0.0005 + $Начално= 20');
        $browser->refresh('Запис');
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Електричество');
        $browser->setValue('propQuantity', '0.5 + $Начално= 3');
        $browser->refresh('Запис');
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Машина 3');
        $browser->setValue('propQuantity', '0.0005 + $Начално= 20');
        $browser->refresh('Запис');
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Опаковка');
        $browser->setValue('propQuantity', '0.001');
        $browser->refresh('Запис');
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Отпадък 3');
        $browser->setValue('propQuantity', '0.0001');
        $browser->refresh('Запис');
        $browser->press('Запис');
        $browser->press('Етап');
        $browser->setValue('resourceId', 'Заготовка 2');
        $browser->setValue('propQuantity', '0,0001');
        $browser->refresh('Запис');
        $browser->press('Запис');
        $browser->press('Активиране');
         
    }
}