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
     * Шаблона на бележката
     */
    private static $tpl = '<?xml version="1.0" encoding="utf-8" ?>
<TremolFpServer Command="Receipt" Description="*** Описание в дневникa ***">
                                [#ITEMS#]
                                <!--ET_BEGIN ITEM-->
                                    [#ITEM#]<Item Description=\'[#name#]\' Price="[#price#]" Quantity="[#quantity#]" VatInfo="2" UnitName="[#measureId#]" <!--ET_BEGIN discount-->Discount="[#discount#]"<!--ET_END discount--> />
                                <!--ET_END ITEM-->
                                [#PAYMENTS#]
                                <!--ET_BEGIN PAY-->
                                    [#PAY#]<Payment Type="[#type#]" Amount="[#amount#]" />
                                <!--ET_END PAY-->
</TremolFpServer>';
    
    
   /*
    * Имплементация на pos_FiscalPrinterIntf
    */
    
    
    /**
     * Връща съдържанието на файла
     * 
     * @param int $id - ид на бележка
     * @return string - съдържанието на бъдещия файл
     */
    private function makeFileContent($id)
    {
        // Взимаме шаблона
        $contentTpl = new ET(static::$tpl);
        
        // Извличаме детайлите на бележката
        $payments = $products = array();
        $query = pos_ReceiptDetails::getQuery();
        $query->where("#receiptId = '{$id}'");
        
        // Разделяме детайлите на плащания и продажби
        while($rec = $query->fetch()){
            if(strpos($rec->action, 'sale') !== false){
                $products[] = $rec;
            } elseif(strpos($rec->action, 'payment') !== false) {
                $payments[] = $rec;
            }
        }
        
        // Добавяме към шаблона всеки един продаден продукт
        $itemBlock = $contentTpl->getBlock('ITEM');
        foreach ($products as $p){
            $block = clone $itemBlock;
            $block->placeObject($this->getRow($p, 'sale'));
            $block->removeBlocks();
            $contentTpl->append($block, 'ITEMS');
        }
        
        /*$newPayments = array();
        foreach ($payments as $p){
            list(, $type) = explode('|', $p->action);
            $code = pos_Payments::fetchField($type, 'code');
            if(empty($newPayments[$code])){
                $newPayments[$code] = (object)array('code' => $code);
            }
            $newPayments[$code]->amount += $p->amount;
        }
        bp($newPayments,$payments);*/
        
        // Добавяме към шаблона направените плащания
        $itemBlock = $contentTpl->getBlock('PAY');
        foreach ($payments as $p){
            $block = clone $itemBlock;
            $block->placeObject($this->getRow($p, 'payment'));
            $block->removeBlocks();
            $contentTpl->append($block, 'PAYMENTS');
        }
        
        // Връщаме чистото съдържание
        return $contentTpl->getContent();
    }
    
    
    /**
     * Връща вербалното представяне
     * 
     * @param stdClass $rec - запис от бележката
     * @param enum(sale, payment) $type - тип продажба или плащане
     * @return stdClass $row - вербалното представяне на реда за принтера
     */
    private function getRow($rec, $type)
    {
        $row = new stdClass();
        
        if($type == 'sale'){
            $row->price = round($rec->price * (1 + $rec->param), 2);
            if($rec->discountPercent){
                $row->discount = (round($rec->discountPercent, 2) * 100) . "%";
            }
            $pRec = cat_Products::fetch($rec->productId);
            $row->name = cat_Products::getVerbal($pRec, 'name');
            
            $row->quantity = $rec->quantity;
            $row->measureId = ($rec->value) ? cat_Packagings::getTitleById($rec->value) : cat_UoM::getShortName($pRec->measureId);
            
        } elseif($type == 'payment'){
            list(, $type) = explode('|', $rec->action);
            $row->type = pos_Payments::fetchField($type, 'code');
            $row->amount = round($rec->amount, 2);
        }
        
        return $row;
    }
    
    
    /**
     * Форсира изтегляне на файла за фискалния принтер
     * 
     * @param int $id - ид на бележка
     * @return void
     */
    public function createFile($id)
    {
        // Създаваме съдържанието на файла
        $content = $this->makeFileContent($id);
        
        // Задаваме нужните хедъри за форсиране на изтегляне от браузъра
        header('Content-Description: File Transfer');
        header('Content-Type: application/xhtml+xml');
        header("Content-Disposition: attachment; filename=receipt{$id}.xml");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Аутпут на съдържанието
        echo $content;
        
        // Сприраме изпълнението на скрипта
        shutdown();
    }
    
    
    /**
     * @TODO ТЕСТОВО
     */
    public function act_test(){
        $id = '158';
        $this->createFile($id);
    }
}