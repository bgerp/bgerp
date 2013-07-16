<?php

/**
 * 
 * @author developer
 * @property core_Manager $productClass клас (мениджър) на продукта, описан с този ред
 */
class sales_model_InvoiceProduct
{
    /**
     * @var int
     */
    public $id;
    
    /**
     * @var int key(mvc=sales_Sales)
     */
    public $invoiceId;
    
    /**
     * Ценова политика
     * 
     * @var int class(interface=price_PolicyIntf)
     */
    public $policyId;
    
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
     * Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
     * няма стойност, приема се за единица.
     * 
     * @var double
     */
    public $quantityInPack;
        
    /**
     * Количество (в основна мярка)
     * 
     * @var double
     */
    public $quantity;
        
    /**
     * Цена за единица продукт в основна мярка
     * 
     * @var double
     */
    public $price;
        
    /**
     * Забележка
     * 
     * @var double
     */
    public $note;
        
    /**
     * Сума
     * 
     * @var double
     */
    public $amount;
    
    
    public function __construct($id = NULL, $mvc = 'sales_InvoiceDetails')
    {
        $this->_mvc = cls::get($mvc);
        
        if (isset($id)) {
            $rec = $this->fetch($id);
            $this->init($rec);
        }
    }
    
    public function fetch($id)
    {
        return $this->_mvc->fetchRec($id);
    }
        
    
    public function init(stdClass $rec)
    {
        foreach (get_class_vars($this) as $prop) {
            if (isset($rec->{$prop})) {
                $this->{$prop} = $rec->{$prop};
            }
        }
    }
    
    public function __get($property)
    {
        if (method_exists($this, "calc_{$property}")) {
            return $this->{"calc_{$property}"}();
        }
        
        expect(FALSE);
    }
    
    protected function calc_productClass()
    {
        return cls::get($this->policyId)->getProductMan();
    }
}
