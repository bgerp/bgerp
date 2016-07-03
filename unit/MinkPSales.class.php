<?php


/**
 *  Клас  'unit_MinkPSales' - PHP тестове за проверка на продажби с некоректни данни
 *
 * @category  bgerp
 * @package   tests
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPSales extends core_Manager {
   
       
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
     * Проверка за отрицателно количество
     */
    //http://localhost/unit_MinkPSales/SaleQuantityMinus/
    function act_SaleQuantityMinus()
    {
    
        // Логваме се
        $browser = $this->SetUp();
         
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
    
        $browser->setValue('reff', 'QuantityMinus');
        $browser->setValue('note', 'MinkPSaleQuantityMinus');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '-2');
        $browser->setValue('packPrice', '3');
        // Записваме артикула
        $browser->press('Запис');
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Количество\'!')) {
        } else {
            return "Грешка - не дава грешка при отрицателно количество";
        }
        
        if(strpos($browser->gettext(), 'Не е над - \'0,0000\'')) {
        } else {
            return "Грешка 1 - не дава грешка при отрицателно количество";
        }
        return $browser->getHtml();
    
    }
    /**
     * Проверка за нулево количество
     */
    //http://localhost/unit_MinkPSales/SaleQuantityZero/
    function act_SaleQuantityZero()
    {
    
        // Логваме се
        $browser = $this->SetUp();
         
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
    
        $browser->setValue('reff', 'QuantityMinus');
        $browser->setValue('note', 'MinkPSaleQuantityZero');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0');
        $browser->setValue('packPrice', '3');
        // Записваме артикула
        $browser->press('Запис');
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Количество\'!')) {
        } else {
            return "Грешка - не дава грешка при отрицателно количество";
        }
    
        if(strpos($browser->gettext(), 'Не е над - \'0,0000\'')) {
        } else {
            return "Грешка 1 - не дава грешка при отрицателно количество";
        }
        return $browser->getHtml();
    
    }
    /**
     * Проверка за отрицателна цена (още няма контрол при въвеждането)
     */
    //http://localhost/unit_MinkPSales/SalePriceMinus/
    function act_SalePriceMinus()
    {
    
        // Логваме се
        $browser = $this->SetUp();
         
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
    
        $browser->setValue('reff', 'PriceMinus');
        $browser->setValue('note', 'MinkPSalePriceMinus');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '2');
        $browser->setValue('packPrice', '-3');
        // Записваме артикула
        $browser->press('Запис');
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Цена\'!')) {
        } else {
            return "Грешка - не дава грешка при отрицателна цена";
        }
    
        if(strpos($browser->gettext(), 'Не е над - \'0,0000\'')) {
        } else {
            return "Грешка 1 - не дава грешка при отрицателна цена";
        }
        return $browser->getHtml();
    
    }
    
    /**
     * Проверка за отрицателна отстъпка
     */
    //http://localhost/unit_MinkPSales/SaleDiscountMinus/
    function act_SaleDiscountMinus()
    {
    
        // Логваме се
        $browser = $this->SetUp();
       
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
        
        $browser->setValue('reff', 'DiscountMinus');
        $browser->setValue('note', 'MinkPSaleDiscountMinus');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '2');
        $browser->setValue('packPrice', '2');
        $browser->setValue('discount', -3);
        // Записваме артикула
        $browser->press('Запис');
       
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Отстъпка\'!')) {
        } else {
            return "Грешка - не дава грешка при отрицателна отстъпка";
        }
        //return $browser->getHtml();
        
        if(strpos($browser->gettext(), 'Не е над - \'0,00 %\'')) {//не го разпознава
        } else {
            return "Грешка 1 - не дава грешка при отрицателна отстъпка";
        }
        
        return $browser->getHtml();
        
    }
    
    /**
     * Проверка за отстъпка, по-голяма от 100%
     */
    //http://localhost/unit_MinkPSales/SaleDiscount101/
    function act_SaleDiscount101()
    {
    
        // Логваме се
        $browser = $this->SetUp();
         
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
    
        $browser->setValue('reff', 'DiscountMinus');
        $browser->setValue('note', 'MinkPSaleDiscountMinus');
        $browser->setValue('paymentMethodId', "На момента");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '2');
        $browser->setValue('packPrice', '2');
        $browser->setValue('discount', '101,55');
        // Записваме артикула
        $browser->press('Запис');
         
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Отстъпка\'!')) {
        } else {
            return "Грешка - не дава грешка при отстъпка над 100%";
        }
        //return $browser->getHtml();
    
        if(strpos($browser->gettext(), 'Над допустимото - \'100,00 %\'')) {//не го разпознава
        } else {
            return "Грешка 1 - не дава грешка при отстъпка над 100%";
        }
    
        return $browser->getHtml();
    
    } 
    
    /**
     * Продажба - включено ДДС в цените
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleVatInclude/
    function act_CreateSaleVatInclude()
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
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleVatInclude');
        $browser->setValue('pricesAtDate', date('d-m-Y'));
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '23');
        $browser->setValue('packPrice', '1,12');
        $browser->setValue('discount', 10);
    
        // Записване артикула и добавяне нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 10);
        $browser->setValue('packPrice', 1.1124);
        $browser->setValue('discount', 10);
    
        // Записване на артикула
        $browser->press('Запис');
        //Игнорираме предупреждението за липсваща стока
        //$browser->setValue('Ignore', 1);
        //$browser->press('Запис');
    
        // активиране на продажбата
        $browser->press('Активиране');
        //return $browser->getHtml();
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '3,69')) {
        } else {
            return "Грешна отстъпка";
        }
        if(strpos($browser->gettext(), 'Тридесет и три BGN и 0,19')) {
        } else {
            return "Грешна обща сума";
        }
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('storeId', 'Склад 1');
        $browser->setValue('template', 'Експедиционно нареждане с цени');
        $browser->press('Чернова');
        $browser->press('Контиране');
        // тази проверка не работи
        //if(strpos($browser->gettext(), 'Контиране')) {
        //}
            if(strpos($browser->gettext(), 'Двадесет и три BGN и 0,18')) {
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
            if(strpos($browser->gettext(), 'Чакащо плащане: Не')) {
            } else {
                return "Грешно чакащо плащане";
            }
            return $browser->getHtml();
        }
        
        /**
         * Продажба - освободено от ДДС
         */
         
        //http://localhost/unit_MinkPSales/CreateSaleVatFree/
        function act_CreateSaleVatFree()
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
            $browser->setValue('bankAccountId', '');
            $browser->setValue('note', 'MinkPSaleVatFree');
            $browser->setValue('pricesAtDate', date('d-m-Y'));
            $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
            //$browser->setValue('chargeVat', "Освободено от ДДС");
            $browser->setValue('chargeVat', "Без начисляване на ДДС");
            // Записване черновата на продажбата
            $browser->press('Чернова');
        
            // Добавяне на артикул
            $browser->press('Артикул');
            $browser->setValue('productId', 'Други стоки');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', '23');
            $browser->setValue('packPrice', '1,12');
            $browser->setValue('discount', 10);
        
            // Записване артикула и добавяне нов - услуга
            $browser->press('Запис и Нов');
            $browser->setValue('productId', 'Други услуги');
            $browser->refresh('Запис');
            $browser->setValue('packQuantity', 10);
            $browser->setValue('packPrice', 1.1124);
            $browser->setValue('discount', 10);
        
            // Записване на артикула
            $browser->press('Запис');
            //Игнорираме предупреждението за липсваща стока
            //$browser->setValue('Ignore', 1);
            //$browser->press('Запис');
        
            // активиране на продажбата
            $browser->press('Активиране');
            //return $browser->getHtml();
            //$browser->press('Активиране/Контиране');
             
            if(strpos($browser->gettext(), '3,69')) {
            } else {
                return "Грешна отстъпка";
            }
            if(strpos($browser->gettext(), 'Тридесет и три BGN и 0,19')) {
            } else {
                return "Грешна обща сума";
            }
        
            // експедиционно нареждане
            $browser->press('Експедиране');
            $browser->setValue('storeId', 'Склад 1');
            $browser->setValue('template', 'Експедиционно нареждане с цени');
            $browser->press('Чернова');
            $browser->press('Контиране');
            // тази проверка не работи
            //if(strpos($browser->gettext(), 'Контиране')) {
            //}
                if(strpos($browser->gettext(), 'Двадесет и три BGN и 0,18')) {
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
                $browser->setValue('vatReason', 'Чл.53 от ЗДДС - ВОД');
                return $browser->getText();
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
                $browser->setValue('ownAccount', '#BG11CREX92603114548401');
                $browser->press('Чернова');
                $browser->press('Контиране');
        
                // Приключване
                $browser->press('Приключване');
                $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
                $browser->press('Чернова');
                $browser->press('Контиране');
                if(strpos($browser->gettext(), 'Чакащо плащане: Не')) {
                } else {
                    return "Грешно чакащо плащане";
                }
                return $browser->getHtml();
            }
}