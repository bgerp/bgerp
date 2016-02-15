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
class unit_Mink extends core_Manager {


    function act_Test()
    {
        // Отваряме bgERP-а
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/root/bgerp/');
        
        // Логваме се
        $browser->click('Enter');
        $browser->setValue('nick', 'milen');
        $browser->setValue('pass', 'parola');
        $browser->press('Вход');
        
        // Отваряме папката на фирма Експерта
        $browser->click('Всички');
        $browser->hasText('Експерта ООД - В. Търново');
        $browser->click('Експерта ООД - В. Търново');
        
        // Правим нова продажба
        $browser->press('Нов...');
        $browser->press('Продажба');
        
        // Попълваме някои полета
        $browser->setValue('deliveryTime[d]', date('d-m-Y'));
        $browser->setValue('deliveryTime[t]', '08:30');
        $browser->setValue('shipmentStoreId', 'Основен');

        // Записваме дотук черновата на продажбата
        $browser->press('Чернова');

        // Пак я редактираме
        $browser->press('Редакция');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->press('Чернова');
        
        // Добавяме нов артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'ЕП 22 (13)');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 10);
        $browser->setValue('packPrice', 2);
        
        // Запосваме артикула
        $browser->press('Запис');

        // Игнорираме предупреждението за липсваща стока
        $browser->setValue('Ignore', 1);
        $browser->press('Запис');


        return $browser->getText();
    }

}