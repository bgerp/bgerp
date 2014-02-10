<?php

/**
 * Описание на един аспект от бизнес сделка, напр. запитване, офериране, договор, експедиция, 
 * плащане, фактуриране и (вероятно) други. 
 * 
 * @category  bgerp
 * @package   bgerp
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class bgerp_iface_DealAspect
{
    /**
     * Списък (масив) от характеристики на продукти
     *
     * @var array of bgerp_iface_DealProduct
     */
    public $products = array();


    /**
     * 3-буквен ISO код на валута
     *
     * @var string
     */
    public $currency;


    /**
     * Валутен курс
     *
     * @var double
     */
    public $rate;
    
    
    /**
     * Дата
     *
     * @var double
     */
    public $valior;
    
    
    /**
     * Дали да се начислява или не ддс
     *
     * @var enum(yes=Включено, no=Отделно, freed=Освободено, export=Без ддс)
     */
    public $vatType;
    
    
    /**
     * Обща сума на съответната част от сделката - пари във валутата `$currency`
     *
     * @var double
     */
    public $amount = 0.0;


    /**
     * Обща сума на авансовото плащане (ако има)
     *
     * @var double
     */
    public $downpayment = NULL;
    
    
    /**
     * Информация за доставката
     *
     * @var bgerp_iface_DealDelivery
     */
    public $delivery;


    /**
     * Информация за плащането
     *
     * @var bgerp_iface_DealPayment
     */
    public $payment;
    
    
	public function __construct()
	{
		$this->delivery = new bgerp_iface_DealDelivery();
		$this->payment = new bgerp_iface_DealPayment();
	}


    /**
     * Добавя друг аспект (детайл) на сделката към текущия аспект
     * 
     * Използва се при изчисляване на обобщена информация по сделка
     * 
     * @param bgerp_iface_DealAspect $aspect
     */
    public function push(bgerp_iface_DealAspect $aspect)
    {
    	$this->currency = $aspect->currency;
        $this->vatType  = $aspect->vatType;
        $this->rate     = $aspect->rate;
        if(empty($this->valior)){
        	$this->valior = $aspect->valior;
        } else {
        	$this->valior = min(array($aspect->valior, $this->valior));
        }
        
    	foreach ($aspect->products as $p) {
            $this->pushProduct($p);
        }
        
        if (isset($aspect->delivery)) {
            $this->delivery = $aspect->delivery;
        }

        if (isset($aspect->payment)) {
            $this->payment = $aspect->payment;
        }

        if (isset($aspect->currency)) {
            $this->currency = $aspect->currency;
        }
        
        $this->amount += $aspect->amount;
        if($aspect->downpayment){
        	$this->downpayment += $aspect->downpayment;
        }
    }
    
    
    public function pop(bgerp_iface_DealAspect $aspect)
    {
        foreach ($aspect->products as $p) {
            $this->popProduct($p);
        }
        
    }
    
    protected function pushProduct(bgerp_iface_DealProduct $p)
    {
        $found = $this->findProduct($p->productId, $p->getClassId(), $p->packagingId);
        
        if ($found) {
            $found->quantity += $p->quantity;
        } else {
            $this->products[] = clone $p;
        }
    }
    
    protected function popProduct(bgerp_iface_DealProduct $p)
    {
        $found = $this->findProduct($p->productId, $p->getClassId(), $p->packagingId);
        
        if ($found) {
            $found->quantity -= $p->quantity;
        } else {
            $q = $p->quantity;
            
            $p = clone $p;
            $p->quantity = -$q;
            
            $this->products[] = $p;
        }
    }
    
    public function findProduct($productId, $classId, $packagingId)
    {
        /* @var $p bgerp_iface_DealProduct */
        foreach ($this->products as $i=>$p) {
            if ($p->isIdentifiedBy($productId, $classId, $packagingId)) {
                return $p;
            }
        }
        
        return NULL;
    }
    
    
	/**
	 * Помощен метод за строеж на select-списък с продукти, зададени чрез bgerp_iface_DealAspect 
     * 
	 * @param array $dealAspectOriginProducts - продуктите които идват от ориджина
	 * @param array $dealAspectThisProducts - вече вкараните продукти
	 * @param mixed $filter - Кои продукти да се филтрират ('storable', 'services', 'all')
	 * @param ibt $productId - ид на продукт
	 * @param int $classId - класа на продукта
	 * @param int $classId - ид на опаковката
	 * 
	 * @return array едномерен масив с ключове от вида `classId`|`productId`, където `classId` е
     *                ид на мениджър на продуктов клас, а `productId` е ид на продукт в рамките
     *                на този продуктов клас.
	 */
    public static function buildProductOptions($dealAspectOriginProducts, $dealAspectThisProducts, $filter = 'all', $productId = NULL, $classId = NULL, $packagingId = NULL)
    {
        $options = array();
        expect(in_array($filter, array('storable', 'services', 'all')));
        
        if($productId && $classId){
        	$options["{$classId}|{$productId}|{$packagingId}"] = cls::get($classId)->getTitleById($productId);
        	
        	return $options;
        }
        
        foreach ($dealAspectOriginProducts->products as $p) {
        	$info = cls::get($p->classId)->getProductInfo($p->productId);
        	if($filter != 'all'){
	        	$skip = ($filter == 'storable') ? !isset($info->meta['canStore']) : isset($info->meta['canStore']);
	        	if($skip) continue;
        	}
        	
        	if($dealAspectThisProducts->findProduct($p->productId, $p->classId, $p->packagingId)) continue;
        	
            $ProductManager = cls::get($p->classId);
        	$title = $ProductManager->getTitleById($p->productId);
        	if($p->packagingId){
        		$title .= " - " . cat_Packagings::getTitleById($p->packagingId);
        	}
            
            // Използваме стойността на select box-а за да предадем едновременно две стойности -
            // ид на политика и ид на продукт.
            $options["{$p->classId}|{$p->productId}|{$p->packagingId}"] = $title;
        }
       
        return (count($options)) ? $options : FALSE;
    }
}
