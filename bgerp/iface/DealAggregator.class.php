<?php

/**
 * Клас Агрегатор на бизнес данни по сделките, Инстанцира се в Модел с интерфейс 'bgerp_DealAggregatorIntf',
 * и се предава в документите от неговата нишка, те му сетват пропъртита ако няма
 * 
 * 
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class bgerp_iface_DealAggregator
{
	const TYPE_PURCHASE = 'purchase';
	const TYPE_SALE     = 'sale';
	const TYPE_DEAL     = 'deal';
	
	
	/**
	 * Вид на сделката
	 */
	public $dealType;
	
	
	/**
	 * Договорената сума по сделката
	 */
	public $amount;
	
	
	/**
	 * Платено до момента
	 */
	public $amountPaid;
	
	
	/**
	 * Сума на доставеното
	 */
	public $deliveryAmount;
	
	
	/**
	 * Датата на сключване на договора
	 */
	public $agreedValior;
	
	
	/**
	 * Дата на първото експедиране
	 */
	public $shippedValior;
	
	
	/**
	 * Дата на първото фактуриране
	 */
	public $invoicedValior;
	
	
	/**
	 * Валута на сделката
	 */
	public $currency;
	
	
	/**
	 * Валутен курс на сделката
	 */
	public $rate;
	
	
	/**
	 * Тип ддс
	 */
	public $vatType;
	
	
	/**
	 * Направено авансово плащане в основната валута за периода
	 */
	public $downpayment;
	
	
	/**
	 * Очаквано авансово плащане в основната валута
	 */
	public $agreedDownpayment;
	
	
	/**
	 * Приспаднато ддс на авансово плащане
	 */
	public $downpaymentDeducted;
	
	
	/**
	 * Общата сума на фактурираното
	 */
	public $amountInvoiced;
	
	
	/**
	 * Условие на доставка
	 */
	public $deliveryTerm;
	
	
	/**
	 * Място на доставка
	 */
	public $deliveryLocation;
	
	
	/**
	 * Дата на доставка
	 */
	public $deliveryTime;
	
	
	/**
	 * Склад
	 */
	public $storeId;
	
	
	/**
	 * Наша банкова сметка
	 */
	public $bankAccountId;
	
	
	/**
	 * Каса
	 */
	public $caseId;
	
	
	/**
	 * Начин на плащане
	 */
	public $paymentMethodId;
	
	
	/**
	 * Позволени операции за плащане
	 */
	public $allowedPaymentOperations = array();
	
	
	/**
	 * Позволени операции за експедиране
	 */
	public $allowedShipmentOperations = array();
	
	
	/**
	 * Засегнати контрагенти
	 */
	public $involvedContragents = array();
	
	
	/**
	 * Договорени продукти от сделката
	 */
	public $products = array();
	
	
	/**
	 * Експедирани продукти
	 */
	public $shippedProducts = array();
	
	
	/**
	 * Магически метод, позволява да се извикват функции 'setIfNot' и 'get'
	 */
	public function __call($name, $args)
	{
		// Ако метода е 'setIfNot', задава веднъж стойност на пропърти от класа, ако няма
		if($name == 'setIfNot'){
			
			// Трябва да са подадени точно два параметъра
			expect(count($args) == 2);
			
			// В обекта трябва да го има зададеното пропърти
			expect(property_exists($this, $args[0]));
			expect(!is_array($this->$args[0]));
			
			// Ако няма стойност пропъртито, задаваме му първата стойност
			if(empty($this->$args[0])){
				$this->$args[0] = $args[1];
			} 
		}
		
		// Ако метода е 'get'
		if($name == 'get'){
			
			// Трябва да е подаден точно един параметър
			expect(count($args) == 1);
			
			// Трябва да го има въпросното пропърти
			expect(property_exists($this, $args[0]));
			
			// Връщаме стойността на пропъртито
			return $this->$args[0];
		}
	}
	
	
	/**
	 * Задава позволените операции за експедиране
	 */
	public function setShippmentOperations($array)
	{
		expect(is_array($array));
		$this->allowedShipmentOperations = $array;
	}
	
	
	/**
	 * Задава контрагенти, замесени в сделката
	 */
	public function setContragents($array)
	{
		expect(is_array($array));
		$this->involvedContragents = $array;
	}
	
	
	/**
	 * Задава позволените операции за плащане
	 */
	public function setPayOperations($array)
	{
		expect(is_array($array));
		$this->allowedPaymentOperations = $array;
	}
	
	
	/**
	 * Добавя продукт към договорените
	 */
	public function addProduct(bgerp_iface_DealProduct $product)
	{
		$this->products[] = $product;
	}
	
	
	/**
	 * Добавя експедиран продукт
	 */
	public function addShippedProduct(bgerp_iface_DealProduct $product)
	{
		$this->shippedProducts[] = $product;
	}
	
	
	/**
	 * Връща продуктите оставащи за експедиране
	 */
	public function getRemainingToShip()
	{
		$remaining = $this->products;
		if(count($this->shippedProducts)){
			foreach ($this->shippedProducts as $p) {
				$found = $this->findProduct($p->productId, $p->getClassId(), $p->packagingId);
				if ($found) {
					$found->quantity -= $p->quantity;
				} else {
					$q = $p->quantity;
					$p = clone $p;
					$p->quantity = -$q;
					$remaining[] = $p;
				}
			}
		}
		
		return $remaining;
	}
	
	
	/**
	 * Намира договорен продукт по негови уникални данни
	 */
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
}