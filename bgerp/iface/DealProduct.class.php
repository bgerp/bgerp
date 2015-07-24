<?php


/**
 * Описание на продукт, участващ в сделка
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
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
     * Мярка
     *
     * @var int key(mvc=cat_UoM)
     */
    public $uomId;
    
    /**
     * Опаковка
     *
     * @var int key(mvc=cat_Uom)
     * @see cat_UoM
     */
    public $packagingId;
    
    /**
     * Количество
     *
     * @var double
     */
    public $quantity;
    
    /**
     * Количество
     *
     * @var double
     */
    public $quantityDelivered;
    
    /**
     * Количество
     *
     * @var double
     */
    public $quantityInPack;
    
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
     * Тегло на продукта (ако има)
     *
     * @var int
     * @see $classId
     */
    public $weight;
    
    /**
     * Обем на продукта (ако има)
     *
     * @var int
     * @see $classId
     */
    public $volume;
    
    /**
     * Срок на продукта
     *
     * @var time
     * @see $classId
     */
    public $term;
    
    
    /**
     * Забележки
     */
    public $notes;
    
    
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
        return $this->isIdentifiedBy($p->productId, $p->getClassId(), $p->packagingId);
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
    public function isIdentifiedBy($productId, $classId, $packagingId)
    {
        return $classId == $this->getClassId() &&
        $productId == $this->productId &&
        $packagingId == $this->packagingId;
    }
}
