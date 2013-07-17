<?php

/**
 * Описание на продукт, участващ в сделка
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class bgerp_iface_DealProduct
{
    /**
     * Продукт-мениджър (наследник на @link core_Manager)
     *
     * @var int|string|core_Manager
     */
    public $classId;


    /**
     * Първичен ключ на продукт (в рамките на мениджъра му)
     *
     * @var int
     * @see $classId
     */
    public $productId;


    /**
     * Опаковка
     *
     * @var int key(mvc=cat_Packagings)
     * @see cat_Packagings
     */
    public $packagingId;


    /**
     * Количество
     *
     * @var double
     */
    public $quantity;


    /**
     * Цена
     *
     * @var double
     */
    public $price;


    /**
     * Отстъпка
     *
     * @var double в интервала [0..1]
     */
    public $discount;


    /**
     * Продукта е неотменна част от сделката (FALSE) или е опция (TRUE)
     *
     * @var boolean
     */
    public $isOptional;
}
