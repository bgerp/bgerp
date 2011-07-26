<?php

/**
 *
 * Мениджър на отстъпки за <клиент, продукт, към дата>
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class acc_Discounts extends core_Manager
{
    static public function getDiscount($clientId, $productId, $atDate)
    {
        return 0.0;
    }
}