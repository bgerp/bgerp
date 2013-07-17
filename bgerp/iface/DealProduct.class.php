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
    
    /**
     * Първичния ключ на мениджъра на продукта
     * 
     * @return int key(mvc=core_Classes)
     */
    public function getClassId()
    {
        return cls::get($this->classId)->getClassId();
    }
    
    /**
     * Проверява дали два продукта от сделка са съпоставими
     * 
     * Съпоставими са продуктите от един и същ мениджър и първичен ключ и се търгуват в една и
     * съща опаковка.  
     * 
     * @param bgerp_iface_DealProduct $p продукта, с който сравняваме
     * @return boolean
     */
    public function isEqual(bgerp_iface_DealProduct $p)
    {
        return $p->getClassId() == $this->getClassId() &&
            $p->productId == $this->productId &&
            $p->packagingId == $this->packagingId;
    }
}
