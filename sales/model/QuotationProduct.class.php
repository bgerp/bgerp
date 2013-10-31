<?php

class sales_model_QuotationProduct
{
    
    /**
     * Клас на продуктовия мениджър(@see core_Classes)
     */
    public $classId;
    
    
    /**
     * ИД на продукт
     * 
     * @var int
     */
    public $productId;
    

    /**
     * Мярка
     *
     * @var int key(mvc=cat_UoM)
     */
    public $uomId;
    
    
    /**
     * Опаковка (ако има)
     * 
     * @var int key(mvc=cat_Packagings)
     */
    public $packagingId;
    
        
    /**
     * Количество (в основна мярка)
     * 
     * @var double
     */
    public $quantity;
        
    
    /**
     * Дали е опционален
     * 
     * @var boolean
     */
    public $isOptional;

    
    /**
     * Цена за единица продукт в основна мярка
     * 
     * @var double
     */
    public $price;
        
    /**
     * Процент отстъпка (0..1 => 0% .. 100%)
     * 
     * @var double
     */
    public $discount;
    
    
    /**
     * @param stdClass $rec - запис от sales_QuotationsDetails
     */
    public function __construct(stdClass $rec)
    {
    	$Class = cls::get($rec->productManId);
    	$this->classId     = $rec->productManId;
        $this->productId   = $rec->productId;
        $this->packagingId = NULL;
        $this->discount    = $rec->discount;
        $this->isOptional  = FALSE;
        $this->quantity    = $rec->sumProduct;
        $this->price       = $rec->price;
        $this->uomId = $Class->getProductInfo($rec->productId)->productRec->measureId;
    }
}