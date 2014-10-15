<?php
/**
 * Имплементация на 'pos_FiscalPrinterIntf' за работа с фискални принтери на "Тремол"
 *
 * @category  vendors
 * @package   tremol
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 * @see pos_FiscalPrinterIntf
 *
 */
class tremol_FiscPrinterDriver extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'sales_FiscPrinterIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Драйвър за фискален принтер на Тремол";
    
    
    /**
     * На коя ддс група кое ид от касовия апарат съответства
     */
    private static $vatGroups = array('A' => 1, 'Б' => 2, 'В' => 3, 'Г' => 4);
    
    
    /**
     * Коя данъчна група отговаря на 0 ставка на ддс-то
     */
    private $groupNoVat = 'A';
    
    
    /**
     * Шаблон за продукта в кратката бележка
     */
    private $shortRowTpl = "Артикули *[#group#]";
    
    
   /*
    * Имплементация на pos_FiscalPrinterIntf
    */
    
    
    /**
     * Връща съдържанието на файла
     * 
     * @param int $id - ид на бележка
     * @return string - съдържанието на бъдещия файл
     */
    private function makeFileContent($data)
    {
        // Взимаме шаблона
        $contentTpl = getTplFromFile('tremol/tpl/Receipt.shtml');
        
        // Добавяме към шаблона всеки един продаден продукт
        $itemBlock = $contentTpl->getBlock('ITEM');
        
        $products = $data->products;
        if($data->short){
        	$newProducts = array();
        	foreach ($products as $p){
        		if(empty($newProducts[$p->vatGroup])){
        			$newProducts[$p->vatGroup] = new stdClass();
        			$nameTpl = new core_ET($this->shortRowTpl);
        			
        			$vatGroup = ($data->hasVat === TRUE) ? $p->vatGroup : $this->groupNoVat;
        			$nameTpl->replace($vatGroup, 'group');
        			$newProducts[$p->vatGroup]->name = $nameTpl->getContent();
        			$newProducts[$p->vatGroup]->vatGroupId = self::$vatGroups[$vatGroup];
        			$newProducts[$p->vatGroup]->quantity = 1;
        		}
        		
        		$noVatAmount = round($p->price * $p->quantity, 2);
        		
        		if($p->discount){
        			$withoutVatAndDisc = round($noVatAmount * (1 - $p->discount), 2);
        		} else {
        			$withoutVatAndDisc = $noVatAmount;
        		}
        		
        		$vatRow = round($withoutVatAndDisc * $p->vat, 2);
        		$newProducts[$p->vatGroup]->price += round($withoutVatAndDisc + $vatRow, 2);
        	}
        	
        	$data->products = $newProducts;
        } else {
        	foreach ($data->products as $p){
        		$p->name = str_replace('"', "'", $p->name);
        		$p->price = $p->price * (1 + $p->vat);
        		
        		$vatGroup = ($data->hasVat === TRUE) ? $p->vatGroup : $this->groupNoVat;
        		$p->vatGroupId = self::$vatGroups[$vatGroup];
        		$p->price = round($p->price, 4);
        		if($p->discount){
        			$p->discountPercent = (round($p->discount, 2) * 100) . "%";
        		}
        	}
        }
        
        foreach ($data->products as $p){
        	
        	$block = clone $itemBlock;
        	$block->placeObject($p);
        	$block->removeBlocks();
        	$contentTpl->append($block, 'ITEMS');
        }
        
        // Добавяме към шаблона направените плащания
        $itemBlock = $contentTpl->getBlock('PAY');
        foreach ($data->payments as $p){
            $block = clone $itemBlock;
            $block->placeObject($p);
            $block->removeBlocks();
            $contentTpl->append($block, 'PAYMENTS');
        }
        
        // Връщаме чистото съдържание
        return $contentTpl->getContent();
    }
    
    
    /**
     * Форсира изтегляне на файла за фискалния принтер
     * 	short -> TRUE / FALSE дали да е подробна бележката или не
     * 	[products] = array(
     * 		'id'        => ид на продукт
     * 		'managerId' => ид на мениджър на продукт
     * 		'name'  	=> име
     * 		'quantity'  => к-во
     * 		'discount'  => отстъпка
     * 		'measure'   => име на мярка/опаковка
     * 		'price'		=> цена в основна валута без ДДС
     * 		'vat'		=> ДДС %
     * 		'vatGroup'	=> Група за ДДС (А, Б, В, Г)
     * );
     *  [payments] = array(
     *  	'type' => код за начина на плащане в фискалния принтер
     *  	'amount => сума в основна валута без ддс
     *  );
     * 
     * 
     * @param int $id - ид на бележка
     * @return void
     */
    public function createFile($data)
    {
        // Създаваме съдържанието на файла
        $content = $this->makeFileContent($data);
        $now = dt::now(TRUE);
        
        // Задаваме нужните хедъри за форсиране на изтегляне от браузъра
        header('Content-Description: File Transfer');
        header('Content-Type: application/tremolFP');
        header("Content-Disposition: inline; filename=receipt{$now}.tremolFP");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Аутпут на съдържанието
        echo $content;
        
        // Сприраме изпълнението на скрипта
        shutdown();
    }
}