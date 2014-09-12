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
        foreach ($data->products as $p){
            $block = clone $itemBlock;
            
            $p->name = str_replace('"', "'", $p->name);
            $p->price = round($p->price * (1 + $p->vat), 2);
            
            // Кое число отговаря данъчната група
            $p->vatGroup = self::$vatGroups[$p->vatGroup];
            
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
     * 
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