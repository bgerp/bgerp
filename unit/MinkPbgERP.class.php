<?php


/**
 *  Клас  'unit_MinkPbgERP' - PHP тестове - стандартни
 *
 * @category  bgerp
 * @package   tests
 * @author    Pavlinka Dainovska <pdainovska@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPbgERP extends core_Manager {
   
   
   public static function reportErr($text, $type = 'warning')
   {
       $text = 'ГРЕШКА В ' .debug_backtrace()['1']['function'] . ': ' . $text;
       
       if ($type == 'warning') {
           self::logWarning($text);
           wp($text);
       } elseif ($type == 'err') {
           self::logErr($text);
           bp($text);
       } else {
           self::logInfo($text);
       }
       
       return $text;
   }
   
    /**
     * Стартира последователно всички тестове от Unit 
     */
    //http://localhost/unit_MinkPbgERP/All/
    //http://127.0.0.1:8080/unit_MinkPbgERP/All/
    
    public function act_All()
    {
        set_time_limit(600);
        $res = '';
        $res .= $this->act_Run();
        $inst = cls::get('unit_MinkPSales');
        $res .= $inst->act_Run();
        $inst = cls::get('unit_MinkPPurchases');
        $res .= $inst->act_Run();
        $inst = cls::get('unit_MinkPPayment');
        $res .= $inst->act_Run();
        $inst = cls::get('unit_MinkPProducts');
        $res .= $inst->act_Run();
        $inst = cls::get('unit_MinkBom');
        $res .= $inst->act_Run();
        $inst = cls::get('unit_MinkPColab');
        $res .= $inst->act_Run();
        $inst = cls::get('unit_MinkPPrices');
        $res .= $inst->act_Run();
        $inst = cls::get('unit_MinkPTcost');
        $res .= $inst->act_Run();
        $inst = cls::get('unit_MinkPListProduct');
        $res .= $inst->act_Run();
        $inst = cls::get('unit_MinkPGroups');
        $res .= $inst->act_Run();
        return $res;
    }
    
    /**
     * Стартира последователно тестовете от MinkPbgERP 
     */
    //http://localhost/unit_MinkPbgERP/Run/
    //http://127.0.0.1:8080/unit_MinkPbgERP/Run/
    public function act_Run()
    {
//         try {
            
//         } catch (Exception $e) {
//             self::reportErr($e->getMessage());
//         }
        if (!TEST_MODE) {
            return;
        }
        
        $res = '';
        $res .= 'MinkPbgERP ';
        $res .= " 0.".$this->act_DeinstallSelect2();
        $res .= " 1.".$this->act_AddAddrBgerp();
        $res .= " 1.".$this->act_AddRoleCat();
        $res .= " 2.".$this->act_ModifySettings();
        $res .= "  23.".$this->act_EditMyCompany();
        $res .= " 3.".$this->act_CreateUser1();
        $res .= "  4.".$this->act_CreateUser2();
        $res .= "  5.".$this->act_CreateStore();
        $res .= "  6.".$this->act_CreateBankAcc1();
        $res .= "  7.".$this->act_CreateBankAcc2();
        $res .= "  8.".$this->act_CreateCase();
        $res .= "  9.".$this->act_EditCms();
        $res .= "  10.".$this->act_GetCurrencies();
        $res .= "  11.".$this->act_CreateCategory();
        $res .= "  12.".$this->act_CreateParam();
        $res .= "  13.".$this->act_CreateMeasure();
        $res .= "  14.".$this->act_CreatePackage();
        $res .= "  15.".$this->act_CreateGroup();
        $res .= "  16.".$this->act_CreateProject();
        $res .= "  17.".$this->act_CreateCycle();
        $res .= "  18.".$this->act_CreateDepartment1();
        $res .= "  19.".$this->act_CreateDepartment2();
        $res .= "  20.".$this->act_CreateProduct();
        $res .= "  21.".$this->act_CreateEditPerson();
        $res .= "  22.".$this->act_CreateCompany();
        $res .= "  23.".$this->act_EditCompany();
        //$res .= "  24.".$this->act_CreateLocation1();
        $res .= "  25.".$this->act_CreateLocation2();
        $res .= "  26.".$this->act_CreateEditCompany();
        $res .= "  27.".$this->act_CreateInq();
        $res .= "  28.".$this->act_CreateQuotation();
        $res .= "  29.".$this->act_CreatePurchase();
        $res .= "  30.".$this->act_CreatePurchaseC();
        $res .= "  31.".$this->act_CreateSale();
        $res .= "  32.".$this->act_CreateSaleC();
        $res .= "  33.".$this->act_CreateTask();
        $res .= "  34.".$this->act_CreateProductVAT9();
        $res .= "  35.".$this->act_CreatePersonUSA();
        $res .= "  36.".$this->act_CreateSupplier();
        $res .= "  37.".$this->act_CreateContractorGroup();
        $res .= "  38.".$this->act_CreatePaymentMethod();
        $res .= "  39.".$this->act_CreateCondParameter();
        
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
        //$browser->start('http://' . $_SERVER['HTTP_HOST']);
        //if(strpos($browser->gettext(), 'Ако приемате лиценза по-долу, може да продължите') !== FALSE) {
        //$browser->click('☒ Ако приемате лиценза по-долу, може да продължите »');
        //$browser->click('Продължаване без обновяване »');
        //$browser->click('✓ Всичко е наред. Продължете с инициализирането »');
        //$browser->click('Стартиране инициализация »');
        //$browser->click('Стартиране bgERP »');
        //$browser->click('Вход');
        //}
        //$this->reportErr($browser->gettext());
        
        if(strpos($browser->gettext(), 'Първоначална регистрация на администратор') !== FALSE) {
        //$this->reportErr('Първоначална регистрация на администратор');
        //Проверка Първоначална регистрация на администратор - създаване на потребител bgerp  
            $browser->setValue('nick', unit_Setup::get('DEFAULT_USER'));
            $browser->setValue('passNew', unit_Setup::get('DEFAULT_USER_PASS'));
            $browser->setValue('passRe', unit_Setup::get('DEFAULT_USER_PASS'));
            $browser->setValue('names', unit_Setup::get('DEFAULT_USER_NAME'));
            $browser->setValue('email', 'bgerp@experta.bg');
            $browser->setValue('country', 'България');
            $browser->press('Запис');
            
        }    
        //Потребител DEFAULT_USER (bgerp)
        $browser->click('Вход');
        $browser->setValue('nick', unit_Setup::get('DEFAULT_USER'));
        $browser->setValue('pass', unit_Setup::get('DEFAULT_USER_PASS'));
        $browser->press('Вход');
        //return $browser->getHtml();
        return $browser;
    }
     
    /**
     * Деактивиране на Select2 
     */
    //http://localhost/unit_MinkPbgERP/DeinstallSelect2/
    function act_DeinstallSelect2()
    {
        // Логване
        $browser = $this->SetUp();
        $browser->click('Админ');
        $browser->setValue('search', 'select2');
        $browser->press('Филтрирай');
        $browser->open($host.'/core_Packs/deinstall/?pack=select2');
        //$browser->open('http://localhost/core_Packs/deinstall/?pack=select2');
        //return $browser->getHtml();
    }
    
    /**
     * 1. Добавяне на адресни данни на Тестов потребител
     */
    //http://localhost/unit_MinkPbgERP/AddAddrBgerp/
    function act_AddAddrBgerp()
    {
        // Логване
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('Лица');
        $browser->click('Тестов потребител');
        $browser->setValue('pCode', '5140');
        $browser->setValue('place', 'Лясковец');
        $browser->setValue('address', 'ул.Янтра, №12');
        $browser->press('Запис');
    
    }
    
    /**
     * 1. Добавяне на роли cat и seePrice на потребител bgerp
     */
    //http://localhost/unit_MinkPbgERP/AddRoleCat/
    function act_AddRoleCat()
    {
        // Логване
        $browser = $this->SetUp();
        // Създаване на потребител
        $browser->click('Админ');
        $browser->click('Потребители');
        $browser->click('Редактиране');
        $browser->setValue('cat', True);
        $browser->setValue('seePrice', True);
        $browser->press('Запис');
        
    }
    
    /**
     * 1. Персонализиране на портала на потребител bgerp
     */
    //http://localhost/unit_MinkPbgERP/ModifySettings/
    function act_ModifySettings()
    {
        // Логване
        $browser = $this->SetUp();
        // Създаване на потребител
        $browser->click('Админ');
        $browser->click('Потребители');
        $browser->click('Профил');
        $browser->press('Персонализиране');
        $browser->setValue('CORE_PORTAL_ARRANGE', 'Последно - Известия - Задачи и Календар');
        $browser->press('Запис');
     
    }
    
    /**
     * 1. Добавяне на адресни данни на Моята фирма
     */
    //http://localhost/unit_MinkPbgERP/EditMyCompany/
    function act_EditMyCompany()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нова фирма
        $browser->click('Визитник');
        $browser->click('Моята Фирма ООД');
        $browser->press('Редакция');
        $browser->setValue('place', 'Велико Търново');
        $browser->setValue('pCode', '5000');
        $browser->setValue('address', 'ул. Царевец, №31');
        $browser->setValue('fax', '062111111');
        $browser->setValue('tel', '062111111');
        $browser->press('Запис');
        if (strpos($browser->getText(),"Предупреждение:")){
            $browser->setValue('Ignore', 1);
            $browser->press('Запис');
        }
        // Създаване на папка на новата фирма
        $browser->press('Папка');
        //return $browser->getHtml();
    }
    
    /**
     * 1. Създаване на потребител от Админ
     */
    //http://localhost/unit_MinkPbgERP/CreateUser1/
    function act_CreateUser1()
    {
        // Логване
        $browser = $this->SetUp();
        // Създаване на потребител
        $browser->click('Админ');
        $browser->click('Потребители');
        $browser->press('Нов запис');
        $browser->setValue('nick', 'User1');
        $browser->setValue('passNew', '123456');
        $browser->setValue('passRe', '123456');
        $browser->setValue('names', 'Потребител 1');
        $browser->setValue('email', 'u1@abv.bg');
        $browser->setValue('roleRank', 'ceo');
        $browser->refresh('Запис');
        //$browser->setValue('roleTeams[13]', '13');
        //$browser->setValue('Headquarter', '13');
        //Повтаряне на паролите, 
        $browser->setValue('passNew', '123456');
        $browser->setValue('passRe', '123456');
        $browser->press('Запис');
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на потребител', 'info');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 1. Създаване на потребител от Визитник - профили
     */
    //http://localhost/unit_MinkPbgERP/CreateUser2/
    function act_CreateUser2()
    {
        // Логване
        $browser = $this->SetUp();
        // Създаване на потребител
        $browser->click('Визитник');
        $browser->click('Профили');
        $browser->press('Нов потребител');
        $browser->setValue('nick', 'User2');
        $browser->setValue('passNew', '123456');
        $browser->setValue('passRe', '123456');
        $browser->setValue('names', 'Потребител 2');
        $browser->setValue('email', 'u2@abv.bg');
        $browser->setValue('roleRank', 'officer');
        $browser->refresh('Запис');
        //$browser->setValue('roleTeams[13]', '13');
        $browser->setValue('Дилър', True);
        //Повтаряне на паролите, 
        $browser->setValue('passNew', '123456');
        $browser->setValue('passRe', '123456');
         
        $browser->press('Запис');
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на потребител', 'info');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 1. Създаване на склад
     */
    //http://localhost/unit_MinkPbgERP/CreateStore/
    function act_CreateStore()
    {
        // Логване
        $browser = $this->SetUp();
        
        // Създаване на нов склад
        $browser->click('Склад');
        $browser->click('Складове');
        $browser->press('Нов запис');
        //$browser->hasText('Добавяне на запис в "Складове"');
        $browser->setValue('name', 'Склад 1');
        $browser->setValue('chiefs_13_1', '13_1');
        $browser->press('Запис');
        //if (strpos($browser->getText(),'Непопълнено задължително поле')){
        //    $browser->press('Отказ');
        //    Return Грешка;
        //}
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на склад', 'info');
        }
        //return $browser->getHtml();
    }  
    
    /**
    * 1. Създаване на банкова сметка от Финанси 
    */
    //http://localhost/unit_MinkPbgERP/CreateBankAcc1/
    function act_CreateBankAcc1()
    {
        // Логване
        $browser = $this->SetUp();
            
        // Създаване на банкова сметка
        $browser->click('Банки');
        $browser->press('Нов запис');
        //$browser->hasText('Добавяне на запис в "Банкови сметки на фирмата"');
        $browser->setValue('iban', '#BG11CREX92603114548401');
        $browser->setValue('currencyId', '1');
        $browser->setValue('operators_13_1', '13_1');
        $browser->press('Запис');
        //if (strpos($browser->getText(),'Непопълнено задължително поле')){
        //    $browser->press('Отказ');
        //    Return Грешка;
        //}
        if (strpos($browser->getText(),"Вече има наша сметка с този IBAN")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на банкова сметка', 'info');
            
        }
        //return $browser->getHtml();
    }
    
    /**
     * 1. Създаване на банкова сметка от Визитник - фирма
     */
    //http://localhost/unit_MinkPbgERP/CreateBankAcc2/
    function act_CreateBankAcc2()
    {
        // Логване
        $browser = $this->SetUp();
        //Отваряне на папката на фирмата
        $browser->click('Визитник');
        $Company = "Моята Фирма ООД";
        $browser->click($Company);
        // Създаване на банкова сметка
        $browser->click('Банка');
        $browser->click('Добавяне на нова наша банкова сметка');
        //$browser->setValue('iban', '#BG33UNCR70001519562303');
        $browser->setValue('iban', '#BG22UNCR70001519562302');
        $browser->setValue('currencyId', 'EUR');
        $browser->setValue('Bgerp', True);
        //$browser->setValue('operators_13_1', '13_1');
        $browser->press('Запис');
        //if (strpos($browser->getText(),'Непопълнено задължително поле')){
        //    $browser->press('Отказ');
        //    Return Грешка;
        //}
        if (strpos($browser->getText(),"Вече има наша сметка с този IBAN")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на банкова сметка', 'info');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 1.Създаване на каса
     */
    ///http://localhost/unit_MinkPbgERP/CreateCase/
    function act_CreateCase()
    {
        // Логване
        $browser = $this->SetUp();
        
        // Създаване на нова каса
        $browser->click('Каси');
        $browser->press('Нов запис');
        //$browser->hasText('Добавяне на запис в "Фирмени каси"');
        $browser->setValue('name', 'КАСА 1');
        $browser->setValue('cashiers_13_1', '13_1');
        $browser->press('Запис');
        //if (strpos($browser->getText(),'Непопълнено задължително поле')){
        //    $browser->press('Отказ');
        //    Return Грешка;
        //}

        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на каса', 'info');
        }
        //return $browser->getHtml();
    }   
    
    
    /**
     * 1. Cms - настройки
     */
    //http://localhost/unit_MinkPbgERP/EditCms/
    function act_EditCms()
    {
        // Логване
        $browser = $this->SetUp();
        $browser->click('Админ');
        $browser->click('Пакети');
        $browser->setValue('search', 'cms');
        $browser->press('Филтрирай');
        $browser->click('Настройки');
        if (strpos($browser->getText(),"Стандартна публична страница")){
        } else {
            return $this->reportErr('Липсва избор за Класове', 'warning');
        }
    }
    /**
     * 1. Зареждане на валутни курсове 
     */
    //http://localhost/unit_MinkPbgERP/GetCurrencies/
    function act_GetCurrencies()
    {
        // Логване
        $browser = $this->SetUp();
        $browser->click('Валути');
        $browser->click('Валутни курсове');
        $browser->press('Зареди от ECB');
         
    }
    
    /**
     * 1. Създаване на категория.
     */
    //http://localhost/unit_MinkPbgERP/CreateCategory/
    function act_CreateCategory()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нова категория
        $browser->click('Каталог');
        $browser->click('Категории');
        $browser->press('Нов запис');
        $browser->setValue('name', 'Шаблони');
        $browser->setValue('useAsProto', 'Да');
        $browser->setValue('meta_canStore', 'canStore');
        $browser->setValue('meta_canConvert', 'canConvert');
        $browser->setValue('meta_canManifacture', 'canManifacture');
        $browser->setValue('meta_canSell', 'canSell');
        $browser->press('Запис');
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на категория', 'info');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 1. Създаване на параметър.
     */
    //http://localhost/unit_MinkPbgERP/CreateParam/
    function act_CreateParam()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нов параметър
        $browser->click('Каталог');
        $browser->click('Параметри');
        $browser->press('Нов запис');
        $browser->setValue('driverClass', 'Символи');
        $browser->refresh('Запис');
        $browser->setValue('group', 'Състояние');
        $browser->setValue('name', 'Външен вид');
        $browser->setValue('lenght', '15'); 
        $browser->press('Запис');
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на параметър', 'info');
        }
        if (strpos($browser->getText(),"Състояние » Външен вид")){
        }   else {
            return $this->reportErr('Неуспешно добавяне на параметър', 'info');
        }
        //return $browser->getHtml();
    }
    /**
     * 1. Създаване на мярка.
     */
    //http://localhost/unit_MinkPbgERP/CreateMeasure/
    function act_CreateMeasure()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нова мярка
        $browser->click('Каталог');
        $browser->click('Мерки');
        $browser->press('Нов запис');
        $browser->setValue('name', 'Човекочас');
        $browser->setValue('shortName', 'Чч');
        $browser->setValue('defQuantity', '1');
        $browser->setValue('round', '2');
        $browser->press('Запис');
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на мярка', 'info');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 1. Създаване на опаковка.
     */
    //http://localhost/unit_MinkPbgERP/CreatePackage/
    function act_CreatePackage()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нова опаковка
        $browser->click('Каталог');
        $browser->click('Мерки');
        $browser->click('Опаковки');
        $browser->press('Нов запис');
        $browser->setValue('name', 'Контейнер');
        $browser->setValue('shortName', 'Контейнер');
        $browser->setValue('baseUnitId', 'литър');
        $browser->setValue('baseUnitRatio', '1000');
        $browser->setValue('defQuantity', '1');
        $browser->setValue('round', '0');
        $browser->press('Запис');
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на опаковка', 'info');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 1. Създаване на група
     */
    //http://localhost/unit_MinkPbgERP/CreateGroup/
    function act_CreateGroup()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нова група
        $browser->click('Каталог');
        $browser->click('Групи');
        $browser->press('Нов запис');
        $browser->setValue('name', 'Промоция');
        $browser->setValue('parentId', 'Ценова група');
        $browser->press('Запис');
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на група', 'info');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 1. Създаване на проект
     */
    //http://localhost/unit_MinkPbgERP/CreateProject/
    function act_CreateProject()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на проект
        $browser->click('Всички');
        $browser->click('Проекти');
        $browser->press('Нов запис');
        $browser->setValue('name', 'Други проекти');
        $browser->setValue('Бележки', True);
        $browser->press('Запис');
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на проект', 'info');
        }
        //return $browser->getHtml();
    }
    /**
     * 1. Създаване на цикъл
     */
    //http://localhost/unit_MinkPbgERP/CreateCycle/
    function act_CreateCycle()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на звено
        $browser->click('Персонал');
        $browser->click('Структура');
        $browser->click('Цикли');
        $browser->press('Нов запис');
        $browser->setValue('name', 'Редовен');
        $browser->setValue('cycleDuration', '5');
        $browser->press('Запис');
        if (strpos($browser->getText(),'Непопълнено задължително поле')){
            $browser->press('Отказ');
            return $this->reportErr('Непопълнено задължително поле', 'warning');
        }
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на запис', 'info');
        }
        //return $browser->getHtml();
    }
    /**
     * 2. Създаване на първо звено
     */
    //http://localhost/unit_MinkPbgERP/CreateDepartment1/
    function act_CreateDepartment1()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на звено
        $browser->click('Персонал');
        $browser->click('Структура');
        $browser->press('Нов запис');
        //$browser->hasText('Добавяне на запис в "Организационна структура"');
        $browser->setValue('name', 'Завод');
        $browser->setValue('schedule','Дневен график'); 
        $browser->press('Запис');
        if (strpos($browser->getText(),'Непопълнено задължително поле')){
            $browser->press('Отказ');
            return $this->reportErr('Непопълнено задължително поле - звено', 'warning');
        }
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на звено', 'info');
        }
        //return $browser->getHtml();
    }
    /**
     * 3. Създаване на второ звено
     */
    //http://localhost/unit_MinkPbgERP/CreateDepartment2/
    function act_CreateDepartment2()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на звено
        $browser->click('Персонал');
        $browser->click('Структура');
        $browser->press('Нов запис');
        //$browser->hasText('Добавяне на запис в "Организационна структура"');
        $browser->setValue('name', 'Производство 1');
        $browser->setValue('parentId', 'Завод');
        $browser->setValue('schedule','Дневен график');
        $browser->setValue('type', 'Цех');
        $browser->setValue('shared_13_2', '13_2');
        $browser->press('Запис');
         if (strpos($browser->getText(),'Непопълнено задължително поле')){
            $browser->press('Отказ');
            return $this->reportErr('Непопълнено задължително поле - звено', 'warning');
        }
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            return $this->reportErr('Дублиране на звено', 'info');
        }
        //return $browser->getHtml();
    }
    
    /*
     * 1. Създаване на артикул - продукт с параметри
     */
    //http://localhost/unit_MinkPbgERP/CreateProduct/
    function act_CreateProduct()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нов артикул - продукт
        $browser->click('Каталог');
        $browser->press('Нов запис');
        $browser->setValue('catcategorieId', 'Продукти');
        $browser->press('Напред');
        $browser->setValue('name', 'Чувал голям 50 L');
        $browser->setValue('code', 'smet50big');
        $browser->setValue('measureId', 'брой');
        $browser->setValue('info', 'черен');
        $browser->setValue('meta_canBuy', 'canBuy');
        $browser->press('Запис');
        $browser->click('Добавяне на нов параметър');
        $browser->setValue('paramId', 'Дължина');
        $browser->refresh('Запис');
        $browser->setValue('paramValue', '50');
        $browser->press('Запис и Нов');
        $browser->setValue('paramId', 'Широчина');
        $browser->refresh('Запис');
        $browser->setValue('paramValue', '26');
        $browser->press('Запис');
        //return $browser->getHtml();
    }
    
     /**
     * 3. Създаване на лице
     * Select2 трябва да е деинсталиран
     */
    //http://localhost/unit_MinkPbgERP/CreateEditPerson/
    function act_CreateEditPerson()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на лице
        $browser->click('Визитник');
        $browser->click('Лица');
        $person = "Стела Донева";
        if(strpos($browser->gettext(), $person)) {
            //има такова лице - редакция
            $browser->click($person);
            $browser->press('Редакция');
            $browser->setValue('place', 'Русе');
            $browser->setValue('address', 'ул.Дунав, №2');
            $browser->press('Запис');
            
        } else {
            // Създаване на лице
            $browser->press('Ново лице');
            $browser->setValue('name', $person);
            //$browser->setValue('groups[5]', '5');
            $browser->setValue('Служители', '5');
            $browser->press('Запис');
            if (strpos($browser->getText(),"Предупреждение:")){
                $browser->setValue('Ignore', 1);
                $browser->press('Запис');
            }
            // Добавяне на код и звено
            $browser->click('HR');
            $browser->click('Добавяне на служебни данни');
            $browser->setValue('code', 'STD');
            $browser->setValue('Завод » Производство','3');
            $browser->press('Запис');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 1. Създаване на фирма и папка към нея, допуска дублиране - ОК
     * Select2 трябва да е деинсталиран
     */
    //http://localhost/unit_MinkPbgERP/CreateCompany/
    function act_CreateCompany()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нова фирма
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
        $browser->setValue('vatId', 'BG814228908');
        $browser->setValue('Клиенти', '1');
        $browser->setValue('Доставчици', '2');
        $browser->press('Запис');
        if (strpos($browser->getText(),"Предупреждение:")){
            $browser->setValue('Ignore', 1);
            $browser->press('Запис');
        }
        // Създаване на папка на новата фирма
        $browser->press('Папка');
        //return $browser->getHtml();
    }
    
    /**
     * 2. Редакция на фирма
     */
    //http://localhost/unit_MinkPbgERP/EditCompany/
    function act_EditCompany()
    {
        // Логване
        $browser = $this->SetUp();
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
        $browser->setValue('address', 'ул.Втора, №2');
        $browser->setValue('fax', '042333333');
        $browser->setValue('tel', '042222222');
        $browser->press('Запис');
        //return $browser->getHtml();
    }
    
    /**
     * 2. Локация от фирма
     */
    //http://localhost/unit_MinkPbgERP/CreateLocation1/
    function act_CreateLocation1()
    {
        // Логване
        $browser = $this->SetUp();
        $browser->click('Визитник');
        // търсим фирмата
        $browser->click('F');
        //$browser->hasText('Фирма bgErp');
        $Company = 'Фирма bgErp';
        //Проверка дали сме в Фирма bgErp
        if(strpos($browser->gettext(), $Company)) {
            //има такава фирма - редакция
            $browser->click($Company);
            $browser->click('Локации');
            $browser->click('Добавяне на нова локация');
            //return $browser->getHtml();
            $browser->setValue('title', 'Офис Пловдив');
            $browser->setValue('type', 'Офис');
            $browser->setValue('place', 'Пловдив');
            $browser->setValue('pCode', '4000');
            $browser->setValue('address', 'ул.Родопи, №52');
            $browser->press('Запис');
        } else {
            return $this->reportErr('Няма такава фирма', 'info');
        }
       
    } 
    /**
     * 2. Локация от таб Локации
     */
    //http://localhost/unit_MinkPbgERP/CreateLocation2/
    function act_CreateLocation2()
    {
        // Логване
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('Локации');
        $browser->press('Нов търговски обект');
        $browser->setValue('name', 'Фирма с локация');
        $browser->setValue('uicId', '200093985');
        $browser->setValue('place', 'Варна');
        $browser->setValue('address', 'ул.Морска, №122');
        $browser->setValue('title', 'Централен офис');
        $browser->setValue('type', 'Главна квартира');
        $browser->setValue('dateFld', date('d-m-Y'));
        $browser->setValue('repeat', '1 мес.');
        $browser->press('Запис');
        //Създаване на папка на фирмата
        $Company = 'Фирма с локация';
        $browser->click($Company);
        $browser->press('Папка');
        
          
    }
    /**
     * 1. Фирма - чуждестранна, ако я има - отваряме и редактираме, ако не - създаваме я
     */
    //http://localhost/unit_MinkPbgERP/CreateEditCompany/
    function act_CreateEditCompany()
    {
        // Логване
        $browser = $this->SetUp();
    
        $browser->click('Визитник');
        // търсим фирмата
        $browser->click('N');
        $Company = "NEW INTERNATIONAL GMBH";
        if(strpos($browser->gettext(), $Company)) {
            //има такава фирма - редакция
            $browser->click($Company);
            $browser->press('Редакция');
            //Проверка дали сме в редакция
            //$browser->hasText('Редактиране на запис в "Фирми"');
        } else {
            // Създаване на нова фирма
            $browser->press('Нова фирма');
            //Проверка дали сме в добавяне
            //$browser->hasText('Добавяне на запис в "Фирми"');
        }
        $browser->setValue('name', $Company);
        $browser->setValue('country', 'Германия');
        $browser->setValue('place', 'Stuttgart');
        $browser->setValue('pCode', '70376');
        $browser->setValue('address', 'Brückenstraße 44А');
        $browser->setValue('vatId', 'DE813647335');
        $browser->setValue('website', 'http://www.new-international.com');
        $browser->setValue('Клиенти', '1');
        $browser->setValue('Доставчици', '2');
        $browser->setValue('info', 'Фирма за тестове');
        $browser->press('Запис');
        // Създаване на папка на нова фирма/отваряне на папката на стара
        if(strpos($browser->gettext(), $Company)) {
            $browser->press('Папка');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 2.Запитване, артикул от него и оферта във валута
     */
    //http://localhost/unit_MinkPbgERP/CreateInq/
    function act_CreateInq()
    {
        // Логване
        $browser = $this->SetUp();
        //Отваряне на папката на фирмата
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
        $browser->setValue('measureId', 'брой');
        $browser->setValue('quantity1', '1000');
        $browser->setValue('name', 'Peter Neumann');
        $browser->setValue('country', 'Германия');
        $browser->setValue('email', 'pneumann@gmail.com');
        $browser->press('Чернова');
        // Създаване на нов артикул по запитването
        $browser->press('Артикул');
        $browser->setValue('name', 'Артикул по запитване1');
        $browser->press('Запис');
        $browser->press('Оферта');
        
        $browser->setValue('Цена', '3,1234');
        //$browser->setValue('row1[cR]', '3,1234');
        $browser->setValue('validFor', '10 дни');
        $browser->press('Чернова');
        $browser->press('Добавяне');
        $browser->setValue('productId', 'Артикул по запитване');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 100);
        $browser->setValue('packPrice', 4);
        $browser->press('Запис');
        $browser->press('Активиране');
        //return $browser->getHtml();
    }
    
    /**
     * 2.Нова оферта в лева на съществуваща фирма с папка
     */
    ///http://localhost/unit_MinkPbgERP/CreateQuotation/
    function act_CreateQuotation()
    {
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
        $browser->click('Визитник');
        $browser->click('F');
        $Company = "Фирма bgErp";
        $browser->click($Company);
        $browser->press('Папка');
        // нова оферта
        $browser->press('Нов...');
        $browser->press('Оферта');
        //$browser->setValue('others', 'MinkPTestCreateQuotation');
        //$browser->hasText('Създаване на оферта в');
        $browser->press('Чернова');
        // Добавяне на артикул 
        $browser->press('Добавяне');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 100);
        $browser->setValue('packPrice', '0,06');
        // Записваме артикула
        $browser->press('Запис');
        // Записване на артикула и добавяне на опционален - услуга
        $browser->press('Опционален артикул');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1);
        $browser->setValue('packPrice', 100);
        // Записване на артикула
        $browser->press('Запис');
        // Активиране на офертата
        $browser->press('Активиране');
        $browser->press('Продажба');
        ////Опционален артикул - не сработва
        ////$browser->setValue('9|yes|9|1', 1);
        //echo $browser->getHtml();
        $browser->press('Създай');
        $browser->press('Активиране');
        //return $browser->getHtml();
    }
    
    /**
     * 2. Нова покупка от съществуваща фирма с папка
     */
     
    //http://localhost/unit_MinkPbgERP/CreatePurchase/
    function act_CreatePurchase()
    {
        // Логваме се
        $browser = $this->SetUp();
    
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
         
        //$browser->setValue('bankAccountId', '');
        $browser->setValue('deliveryTermId', 'EXW');
        //$browser->setValue('deliveryLocationId', '1');
        $browser->setValue('note', 'MinkPTestCreatePurchase');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->press('Чернова');
    
        // Записваме черновата на покупката
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '15');
        $browser->setValue('packPrice', '1,66');
        $browser->setValue('discount', 4);
    
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други външни услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1);
        $browser->setValue('packPrice', '6');
        $browser->setValue('discount', 5);
        // Записваме артикула
        $browser->press('Запис');
             
        // активираме покупката
        $browser->press('Активиране');
        //return  $browser->getHtml();
        $browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 5,92')) {
        } else {
            return $this->reportErr('Грешно ДДС', 'warning');
        }
        if(strpos($browser->gettext(), 'Тридесет и пет BGN и 0,52')) {
        } else {
            return $this->reportErr('Грешна обща сума', 'warning');
        }
        if(strpos($browser->gettext(), 'Доставка: EXW: 4000 Пловдив, ул.Родопи, №52')) {
        } else {
            return $this->reportErr('Грешнo условие на доставка', 'warning');
        }
        // Складова разписка
        // Когато няма автом. избиране
        //$browser->press('Засклаждане');
        //$browser->setValue('template', 'Складова разписка с цени');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Двадесет и осем BGN и 0,68')) {
        //} else {
        //    return $this->reportErr('Грешна сума в складова разписка', 'warning');
        //}
    
        // протокол
        // Когато няма автом. избиране
        //$browser->press('Приемане');
        //$browser->setValue('template', 'Приемателен протокол за услуги с цени');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Шест BGN и 0,84')) {
        //} else {
        //    return $this->reportErr('Грешна сума в протокол за услуги', 'warning');
        //}
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '11776');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Данъчна основа 20%: BGN 29,60')) {
        } else {
            return $this->reportErr('Грешна данъчна основа', 'warning');
        }
    
        // РКО
        $browser->press('РКО');
        $browser->setValue('beneficiary', 'Иван Петров');
        $browser->setValue('amountDeal', '10');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->press('Чернова');
        $browser->press('Контиране');
        //Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        //Проверка на статистиката
        if(strpos($browser->gettext(), '35,52 35,52 35,52 35,52')) {
        } else {
            return $this->reportErr('Грешни суми в мастера', 'warning');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 2. Нова покупка - валута от съществуваща фирма с папка
     */
     
    //http://localhost/unit_MinkPbgERP/CreatePurchaseC/
    function act_CreatePurchaseC()
    {set_time_limit(55);
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser->click('Визитник');
        $browser->click('N');
        $Company = "NEW INTERNATIONAL GMBH";
        $browser->click($Company);
        $browser->press('Папка');
    
        // нова покупка - проверка има ли бутон
        if(strpos($browser->gettext(), 'Покупка')) {
            $browser->press('Покупка');
        } else {
            $browser->press('Нов...');
            $browser->press('Покупка');
        }
         
        //$browser->setValue('bankAccountId', '');
        $browser->setValue('deliveryTermId', 'EXW');
        $browser->setValue('note', 'MinkPTestCreatePurchaseC');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        //$browser->setValue('chargeVat', "Oсвободено от ДДС"); //// Ако контрагентът е от България дава грешка.
        $browser->setValue('chargeVat', 'exempt');
        //$browser->setValue('chargeVat', "Без начисляване на ДДС");
        $browser->setValue('template', "Purchase contract");
       
        $browser->press('Чернова');
        
        // Записваме черновата на покупката
        // Добавяме артикул
        //wp(getCurrentUrl());
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '15');
        $browser->setValue('packPrice', '1,66');
        $browser->setValue('discount', 4);
    
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други външни услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1);
        $browser->setValue('packPrice', '6');
        $browser->setValue('discount', 5);
        // Записваме артикула
        $browser->press('Запис');
         
        // активираме покупката
        $browser->press('Активиране');
        //Автом. избиране
        $browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), 'Discount: EUR 1,30')) {
        } else {
            return $this->reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Twenty-nine EUR and 0,60')) {
        } else {
            return $this->reportErr('Грешна обща сума', 'warning');
        }
    
        // Складова разписка
        // Когато няма автом. избиране
        //$browser->press('Засклаждане');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->setValue('template', 'Складова разписка с цени');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Двадесет и три EUR и 0,90')) {
        //} else {
        //    return $this->reportErr('Грешна сума в складова разписка', 'warning');
        //}
    
        // протокол
        // Когато няма автом. избиране
        //$browser->press('Приемане');
        //$browser->setValue('template', 'Приемателен протокол за услуги с цени');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Пет EUR и 0,70')) {
        //} else {
        //    return $this->reportErr('Грешна сума в протокол за услуги', 'warning');
        //}
    
        // Фактура
        $browser->press('Вх. фактура');
        $browser->setValue('number', '16');
        $browser->setValue('vatReason', 'чл.53 от ЗДДС – ВОД');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Данъчна основа 0%: BGN 57,89')) {
        } else {
            return $this->reportErr('Грешна данъчна основа', 'warning');
        }
        
        // РКО
        $browser->press('РКО');
        $browser->setValue('beneficiary', 'Tom Frank');
        $browser->setValue('amountDeal', '10');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // РБД
        $browser->press('РБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->press('Чернова');
        $browser->press('Контиране');
        //Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        //Проверка на статистиката
        if(strpos($browser->gettext(), '29,60 29,60 29,60 29,60')) {
        } else {
            return $this->reportErr('Грешни суми в мастера', 'warning');
        }
        //return $browser->getHtml();
    }
      
    /**
     * 2. Нова продажба на съществуваща фирма с папка (DDP)
     */
     
    //http://localhost/unit_MinkPbgERP/CreateSale/
    function act_CreateSale()
    {
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
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
        $enddate=strtotime("+2 Days");
        $browser->setValue('deliveryTime[d]', date('d-m-Y', $enddate));
        $browser->setValue('deliveryTime[t]', '10:30');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '#BG11CREX92603114548401');
        $browser->setValue('note', 'MinkPbgErpCreateSale');
        $browser->setValue('deliveryTermId', 'DDP');
        //$browser->setValue('deliveryLocationId', '1');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '23');
        $browser->setValue('packPrice', '1,12');
        $browser->setValue('discount', 3);
    
        // Записване артикула и добавяне нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 10);
        $browser->setValue('packPrice', 1.1124);
        $browser->setValue('discount', 1);
    
        // Записване на артикула
        $browser->press('Запис');
        // Игнорираме предупреждението за липсваща стока
        //$browser->setValue('Ignore', 1);
        //$browser->press('Запис');
    
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 7,20')) {
        } else {
            return $this->reportErr('Грешно ДДС', 'warning');
        }
        if(strpos($browser->gettext(), 'Четиридесет и три BGN и 0,20')) {
        } else {
            return $this->reportErr('Грешна обща сума', 'warning');
        }
        if(strpos($browser->gettext(), 'Доставка: DDP: 4000 Пловдив, ул.Родопи, №52')) {
        } else {
            return $this->reportErr('Грешнo условие на доставка', 'warning');
        }
        // Проформа
        $browser->press('Проформа');
        $browser->press('Чернова');
        $browser->press('Активиране');
        
        // експедиционно нареждане
        // Когато няма автом. избиране
        //$browser->press('Експедиране');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->setValue('template', 'Експедиционно нареждане с цени');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Двадесет и девет BGN и 0,99 ')) {
        //} else {
        //    return $this->reportErr('Грешна сума в ЕН', 'warning');
        //}
             
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        //echo $browser->getHtml();
        //$browser->setValue('paymentType', 'По банков път');
        $browser->press('Контиране');
        
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '10');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        //Проверка на статистиката
        if(strpos($browser->gettext(), '43,20 43,20 43,20 43,20')) {
        } else {
            return $this->reportErr('Грешни суми в мастера', 'warning');
        }
        //return $browser->getHtml();
    }  
    
    /**
     * 2. Нова продажба във валута на съществуваща фирма с папка
     */
     
    //http://localhost/unit_MinkPbgERP/CreateSaleC/
    function act_CreateSaleC()
    {
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
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
         
        //$browser->hasText('Създаване на продажба');
        $enddate=strtotime("+2 Days");
        $browser->setValue('deliveryTime[d]', date('d-m-Y', $enddate));
        $browser->setValue('deliveryTime[t]', '10:30');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '#BG22UNCR70001519562302');
        $browser->setValue('note', 'MinkPbgErpCreateSaleC');
        //$browser->setValue('pricesAtDate', date('d-m-Y'));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', 'exempt');
        //$browser->setValue('chargeVat', "Освободено от ДДС"); //// ДАВА ГРЕШКА!
        //$browser->setValue('chargeVat', "Без начисляване на ДДС");
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '47');
        $browser->setValue('packPrice', '1,12');
        $browser->setValue('discount', 3);
    
        // Записване артикула и добавяне нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '010');
        $browser->setValue('packPrice', '1,0202');
        $browser->setValue('discount', 1);
    
        // Записване на артикула
        $browser->press('Запис');
        // Игнорираме предупреждението за липсваща стока
        //$browser->setValue('Ignore', 1);
        //$browser->press('Запис');
    
        // активиране на продажбата
        $browser->press('Активиране');
        //return  $browser->getHtml();
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Discount: EUR 1,68')) {
        } else {
            return $this->reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Sixty-one EUR and 0,16')) {
        } else {
            return $this->reportErr('Грешна обща сума', 'warning');
        }
    
        // експедиционно нареждане
        // Когато няма автом. избиране
        //$browser->press('Експедиране');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->setValue('template', 'Експедиционно нареждане с цени');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Двадесет и четири EUR и 0,99')) {
        //} else {
        //    return $this->reportErr('Грешна сума в ЕН', 'warning');
        //}
         
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('numlimit', '0 - 2000000');
        $browser->press('Чернова');
        //return 'paymentType';
        //$browser->setValue('paymentType', 'По банков път');
        $browser->press('Контиране');
    
        // ПКО
        $browser->press('ПКО');
        $browser->setValue('depositor', 'Иван Петров');
        $browser->setValue('amountDeal', '10');
        $browser->setValue('peroCase', 'КАСА 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        //$browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        //Проверка на статистиката
        if(strpos($browser->gettext(), '61,16 61,16 61,16 61,16')) {
        } else {
            return $this->reportErr('Грешни суми в мастера', 'warning');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 1. Създаване на задача
     */
    //http://localhost/unit_MinkPbgERP/CreateTask/
    function act_CreateTask()
    {
        // Логване
        $browser = $this->SetUp();
        // Създаване на задача
        $browser->click('Добавяне на нова Задача');
        $browser->press('Напред');
        $browser->setValue('title', 'Инвентаризация');
        $browser->setValue('description', 'Да се проведе инвентаризация');
        $startdate=strtotime("+2 Days");
        $enddate=strtotime("+12 Days");
        $browser->setValue('timeStart[d]', date('d-m-Y', $startdate));
        $browser->setValue('timeStart[t]', '08:00');
        $browser->setValue('timeEnd[d]', date('d-m-Y', $enddate));
        $browser->setValue('timeEnd[t]', '16:00');
        $browser->setValue('User1', True);
        $browser->press('Чернова');
        $browser->press('Активиране');
        //return $browser->getHtml();
    }
    /**
     * 1. Създаване на артикул - ДДС група 9%, ако го има - редакция.
     */
    //http://localhost/unit_MinkPbgERP/CreateProductVAT9/
    function act_CreateProductVAT9()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нов артикул - продукт
        $browser->click('Каталог');
        $browser->press('Нов запис');
        $browser->setValue('catcategorieId', 'Продукти');
        $browser->press('Напред');
        $browser->setValue('name', 'Артикул ДДС 9');
        $browser->setValue('code', 'dds9');
        $browser->setValue('measureId', 'брой');
        $browser->setValue('info', 'черен');
        $browser->setValue('meta_canBuy', 'canBuy');
        $browser->setValue('Ценова група » Промоция',  True);
        $browser->press('Запис');
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            $browser->click('Продукти');
            $browser->click('Артикул ДДС 9');
            $browser->press('Редакция');
        } else {
            $browser->click('Цени');
            $browser->click('Избор на ДДС група');
            //не зарежда ДДС 9% от днешна дата
            $browser->setValue('vatGroup', 'Г - 9,00 %');
            $browser->press('Запис');
        }
        //return $browser->getHtml();
    }
    /**
     * 1. Създаване на лице - клиент
     * Select2 трябва да е деинсталиран
     */
    //http://localhost/unit_MinkPbgERP/CreatePersonUSA/
    function act_CreatePersonUSA()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на лице
        $browser->click('Визитник');
        $browser->click('Лица');
        $person = "Sam Wilson";
        $browser->press('Ново лице');
        $browser->setValue('name', $person);
        $browser->setValue('country', 'САЩ');
        $browser->setValue('place', 'Dallas');
        $browser->setValue('address', 'Hatcher St 123');
        $browser->setValue('egn', '9999999999');
        $browser->setValue('Клиенти', '1');
        $browser->press('Запис');
        if (strpos($browser->getText(),"Предупреждение:")){
            $browser->setValue('Ignore', 1);
            $browser->press('Запис');
        }
        // Създаване на папка на лицето
        $browser->press('Папка');
        //return $browser->getHtml();
    }
    
    /**
     * 1. Създаване на фирма-доставчик 
     */
    //http://localhost/unit_MinkPbgERP/CreateSupplier/
    function act_CreateSupplier()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нова фирма
        $browser->click('Визитник');
        $browser->press('Нова фирма');
        $browser->setValue('name', 'Фирма доставчик');
        $browser->setValue('place', 'Смолян');
        $browser->setValue('pCode', '6400');
        $browser->setValue('address', 'ул.Родопи, №3');
        $browser->setValue('fax', '0301111111');
        $browser->setValue('tel', '0301211111');
        $browser->setValue('uicId', '102223519');
        $browser->setValue('Доставчици', '2');
        $browser->press('Запис');
        // Създаване на папка на новата фирма
        //$browser->press('Папка');
        //return $browser->getHtml();
    }
    
    /**
     * 1. Създаване на група контрагенти
     */
    //http://localhost/unit_MinkPbgERP/CreateContractorGroup/
    function act_CreateContractorGroup()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на нова група
        $browser->click('Визитник');
        $browser->click('Групи');
        $browser->press('Нов запис');
        $browser->setValue('name', 'Доставчици - основни');
        $browser->setValue('parentId', 'Доставчици');
        $browser->press('Запис');
    }        
    
    /**
     * 1. Създаване на метод на плащане
     */
    //http://localhost/unit_MinkPbgERP/CreatePaymentMethod/
    function act_CreatePaymentMethod()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на метод на плащане
        $browser->click('Дефиниции');
        $browser->click('Плащания');
        $browser->press('Нов запис');
        //$browser->setValue('title', 'До 14 дни след фактуриране');
        $browser->setValue('type', 'По банков път');
        $browser->setValue('eventBalancePayment', 'след датата на фактурата');
        $browser->setValue('timeBalancePayment', '14 дни');
        $browser->setValue('discountPercent', '2');
        $browser->setValue('discountPeriod', '5');
        $browser->press('Запис');
        //return $browser->getHtml();
    
    }
    
    /**
     * 1. Създаване на търговско условие
     */
    //http://localhost/unit_MinkPbgERP/CreateCondParameter/
    function act_CreateCondParameter()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на търговско условие
        $browser->click('Дефиниции');
        $browser->click('Търговски условия');
        $browser->press('Нов запис');
        $browser->setValue('country', 'Германия');
        $browser->setValue('conditionId', 'Начин на плащане (4)');
        $browser->setValue('value', '16');
        $browser->press('Запис');
        if (strpos($browser->getText(),"До 1 месец след фактуриране")){
        } else {
            return $this->reportErr('Грешка при създаване на търговско условие', 'warning');
        }
        //return $browser->getHtml();
    
    }
}