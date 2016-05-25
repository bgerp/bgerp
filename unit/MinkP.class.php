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
    
    /////http://localhost/unit_MinkP/FirmReload/

    //bp($browser->getText());
    
    /**
     * Създава нова каса
     */
    function act_CreateCase()
    {
    
        $browser = cls::get('unit_Browser');
    
        // bgERP
        //$url = 'http://Localhost/';
        //$nick = 'Pdainovska';
        //$pass = '111111';
    
        // Reload
        $url = 'http://reload.bgerp.com/';
        $nick = 'Ceo1';
        $pass = '123456';
    
        $browser->start($url);
    
        // Логваме се
        $browser->click('Вход');
        $browser->setValue('nick', $nick);
        $browser->setValue('pass', $pass);
        $browser->press('Вход');
        
        //return  $browser->getHtml();
    
        //$browser->hasText('Известия');
        //$browser->hasText('Pdainovska');
    
        // Правим нова каса
        $browser->click('Каси');
        $browser->press('Нов запис');
         
        //$browser->hasText('Добавяне на запис в "Фирмени каси"');
    
        $browser->setValue('name', 'КАСА 2');
        $browser->setValue('Pdainovska', '2');
        $browser->press('Запис');
         
        if (strpos($browser->getText(),'Непопълнено задължително поле')){
            
             return  $browser->getHtml();
             $browser->press('Отказ');
        }
    
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            Return Дублиране;
        }
    
    }
    
    function act_Test1()
    {
        //добавяне и редакция Reload
        
        $browser = cls::get('unit_Browser');
        $browser->start('http://reload.bgerp.com/');
        //return  $browser->getHtml();
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
                
    }
   
    function act_FirmReload()
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
    
        $browser->setValue('name', 'Фирма-тест');
        $browser->setValue('place', 'Сливен');
        $browser->setValue('pCode', '6400');
        $browser->setValue('address', 'ул.Бояна, №56');
        $browser->setValue('uicId', '110001322');
        $browser->setValue('fax', '036666666');
        $browser->setValue('tel', '036787878');
         
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