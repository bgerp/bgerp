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
abstract class price_Helper extends core_Mvc
{
	/**
     * Умно закръгляне на цена
     * @param double $price - цена, която ще се закръгля
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
	 * @param double $price
	 * @param double $vat
	 * @param unknown_type $rate
	 */
	public static function calcPrice($price, $vat, $rate)
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
	 * 
	 * Enter description here ...
	 * @param unknown_type $price
	 * @param unknown_type $packQuantity
	 * @param unknown_type $vat
	 * @param unknown_type $isPriceWithVat
	 */
	public static function calcAmount($price, $packQuantity, $vat, $isPriceWithVat = TRUE, $currencyCode)
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
	
	
	
	
	public function fillRecs(&$recs, $masterRec, $priceFld = 'packPrice', $quantityFld = 'packQuantity', $amountFld = 'amount', $rateFld = 'currencyRate')
	{
		if(!count($recs)) return;
		
		expect(is_object($masterRec));
		$hasVat = ($masterRec->chargeVat == 'yes') ? TRUE : FALSE;
		$amount = $amountVat = 0;
		
		foreach($recs as &$rec){
			$vat = 0;
        	if ($masterRec->chargeVat == 'yes' || $masterRec->chargeVat == 'no') {
                $ProductManager = cls::get($rec->classId);
                $vat = $ProductManager->getVat($rec->productId, $masterRec->valior);
            }
            
            // Калкулира се цената с и без ддс и се показва една от тях взависимост трябвали да се показва ддс-то
        	$price = static::calcPrice($rec->$priceFld, $vat, $masterRec->$rateFld);
        	$rec->$priceFld = ($hasVat) ? $price->withVat : $price->noVat;
        	
        	// Калкулира се сумата на реда
        	$amountObj = static::calcAmount($rec->$priceFld, $rec->$quantityFld, $vat, $hasVat, $masterRec->currencyId);
        	
        	$rec->$amountFld  = $amountObj->amount;
        	$amount      += $amountObj->amount;
        	$amountVat   += $amountObj->vatAmount;
        	$a[] = $amountObj->amount;
		}
		
		$amount = currency_Currencies::round($amount, $rec->currencyId);
        $amountVat = currency_Currencies::round($amountVat, $rec->currencyId);
		bp($a, $amount,$amountVat);
	}
}