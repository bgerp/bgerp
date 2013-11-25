<?php


/**
 * Помощен клас за конвертиране на суми и цени, изпозлван в бизнес документите
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class price_Helper
{
	
	/**
	 * Масив за мапване на стойностите от мениджърите
	 */
	private static $map = array(
		'priceFld' 	    => 'packPrice',
		'quantityFld'   => 'packQuantity',
		'amountFld'     => 'amount',
		'rateFld' 	    => 'currencyRate',
		'classId' 	    => 'classId',
		'productId'	    => 'productId',
		'chargeVat'     => 'chargeVat',
		'valior' 	    => 'valior',
		'currencyId'    => 'currencyId',
		'alwaysHideVat' => FALSE, // TRUE всичко трябва да е без ДДС
	);
	
	
	/**
     * Умно закръгляне на цена
     * @param double $price  - цена, която ще се закръгля
     * @return double $price - закръглената цена
     */
	public static function roundPrice($price)
	{
	    // Минимален брой значещи знаци
	    defIfNot('PRICE_MIN_DIGITS', 4);
	    
	    // Плаваща прецизност
	    $precision =  max(2, PRICE_MIN_DIGITS - round(log10($price)));
	
	    // Изчисляваме закръглената цена
	    $price = round($price, $precision);
		
	    return $price;
	}
	
	
	/**
	 * Пресмята цена с ддс и без ддс
	 * @param double $price      - цената в основна валута без ддс
	 * @param double $vat        - процента ддс
	 * @param double $rate       - курса на валутата
	 * @return stdClass->noVat   - цената без ддс
	 * 		   stdClass->withVat - цената с ддс
	 */
	private static function calcPrice($price, $vat, $rate)
	{
		$arr = array();
        
        // Конвертиране цените във валутата
        $arr['noVat'] = $price / $rate;
		$arr['withVat'] = ($price * (1 + $vat)) / $rate;
		
		$arr['noVat'] = static::roundPrice($arr['noVat']);
		$arr['withVat'] = static::roundPrice($arr['withVat']);
		
        return (object)$arr;
	}
	
	
	/**
	 * Калкулиране на сумата на реда
	 * @param double $price           - цената
	 * @param int $packQuantity       - количеството
	 * @param double $vat             - процента ддс
	 * @param boolean $isPriceWithVat - дали цената е с включено ддс
	 */
	private static function calcAmount($price, $packQuantity, $vat, $isPriceWithVat = TRUE, $currencyCode)
	{
		$arr = array();
		$arr['amount'] = $price * $packQuantity;
		
		if($isPriceWithVat){
			$arr['vatAmount'] = $arr['amount'] * $vat / (1 + $vat);
		} else {
			$arr['vatAmount'] = $arr['amount'] * $vat;
		}
		
		$arr['amount'] = currency_Currencies::round($arr['amount'], $currencyCode);
		
		return (object)$arr;
	}
	
	
	/**
	 * Помощен метод използван в бизнес документите за показване на закръглени цени на редовете
	 * и за изчисляване на общата цена
	 * 
	 * @param array $recs - записи от детайли на модел
	 * @param stdClass $masterRec - мастър записа
	 * @param array $map - масив с мапващи стойностите на полета от фунцкията
	 * с полета в модела, има стойности по подрабзиране (@see static::$map)
	 */
	public function fillRecs(&$recs, $masterRec, $map = array())
	{
		if(!count($recs)) return;
		expect(is_object($masterRec));
		
		// Комбиниране на дефолт стойнсотите с тези подадени от потребителя
		$map = array_merge(static::$map, $map);
		
		// Дали трябва винаги да не се показва ддс-то към цената
		if($map['alwaysHideVat']) {
			$hasVat = FALSE;
		} else {
			$hasVat = ($masterRec->$map['chargeVat'] == 'yes') ? TRUE : FALSE;
		}
		
		$amount = $amountVat = 0;
		
		foreach($recs as &$rec){
			$vat = 0;
        	if ($masterRec->$map['chargeVat'] == 'yes' || $masterRec->$map['chargeVat'] == 'no') {
                $ProductManager = cls::get($rec->$map['classId']);
                $vat = $ProductManager->getVat($rec->$map['productId'], $masterRec->$map['valior']);
            }
           
            // Калкулира се цената с и без ддс и се показва една от тях взависимост трябвали да се показва ддс-то
        	$price = static::calcPrice($rec->$map['priceFld'], $vat, $masterRec->$map['rateFld']);
        	$rec->$map['priceFld'] = ($hasVat) ? $price->withVat : $price->noVat;
        	
        	// Калкулира се сумата на реда
        	$amountObj = static::calcAmount($rec->$map['priceFld'], $rec->$map['quantityFld'], $vat, $hasVat, $masterRec->$map['currencyId']);
        	$rec->$map['amountFld']  = $amountObj->amount;
        	$amount          	+= $amountObj->amount;
        	$amountVat       	+= $amountObj->vatAmount;
        	$a[] 			  	 = $amountObj->amount;
		}
		
		$masterRec->total         = new stdClass();
        $masterRec->total->amount = currency_Currencies::round($amount, $rec->$map['currencyId']);
        $masterRec->total->vat    = currency_Currencies::round($amountVat, $rec->$map['currencyId']);
	}
}
