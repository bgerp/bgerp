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
     * Запис за разходи
     */
    public $expenseRecId;
}
