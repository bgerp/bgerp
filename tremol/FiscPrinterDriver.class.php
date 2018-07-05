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
class tremol_FiscPrinterDriver extends core_Manager
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'sales_FiscPrinterIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Драйвър за фискален принтер на Тремол';
    
    
    /**
     * Шаблон за продукта в кратката бележка
     */
    private $shortRowTpl = 'Продажби *[#group#]';
    
    
    /**
     * Кои кодове от фискалния принтер отговарят на данъчните групи
     */
    private function getVatGroupKeys()
    {
        $res = array();
        
        $conf = core_Packs::getConfig('tremol');
        foreach (array('А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G') as $title => $sysId) {
            $res[$title] = $conf->{"TREMOL_GROUP_{$sysId}"};
        }
        
        return $res;
    }
    
    
    /*
     * Имплементация на pos_FiscalPrinterIntf
     */
    
    
    /**
     * Връща съдържанието на файла
     *
     * @param  object $data
     * @return string - съдържанието на бъдещия файл
     */
    private function makeFileContent($data)
    {
        // Взимаме шаблона
        $contentTpl = getTplFromFile('tremol/tpl/Receipt.shtml');
        
        // Добавяме към шаблона всеки един продаден продукт
        $itemBlock = $contentTpl->getBlock('ITEM');
        
        $conf = core_Packs::getConfig('tremol');
        $vatGroups = self::getVatGroupKeys();
        $defaultGroup = ($data->hasVat) ? $conf->TREMOL_BASE_GROUP_WITH_VAT : $conf->TREMOL_BASE_GROUP_WITH_ZERO_VAT;
        $defaultGroup = acc_VatGroups::fetchField("#sysId = '{$defaultGroup}'", 'title');
             
        $products = $data->products;
        
        // Попълваме ддс групите на артикулите
        foreach ($data->products as $p) {
            if (empty($p->vatGroup)) {
                $p->vatGroup = $defaultGroup;
            } else {
                $p->vatGroup = ($data->hasVat) ? $p->vatGroup : $defaultGroup;
            }
        }
        
        if ($data->short) {
            $newProducts = array();
            foreach ($products as $p) {
                if (empty($newProducts[$p->vatGroup])) {
                    $newProducts[$p->vatGroup] = new stdClass();
                    $nameTpl = new core_ET($this->shortRowTpl);
                    
                    $nameTpl->replace($p->vatGroup, 'group');
                    $newProducts[$p->vatGroup]->name = $nameTpl->getContent();
                    $newProducts[$p->vatGroup]->vatGroupId = $vatGroups[$p->vatGroup];
                    $newProducts[$p->vatGroup]->quantity = 1;
                }
                
                $noVatAmount = round($p->price * $p->quantity, 2);
                
                if ($p->discount) {
                    $withoutVatAndDisc = round($noVatAmount * (1 - $p->discount), 2);
                } else {
                    $withoutVatAndDisc = $noVatAmount;
                }
                
                $vatRow = round($withoutVatAndDisc * $p->vat, 2);
                $newProducts[$p->vatGroup]->price += round($withoutVatAndDisc + $vatRow, 2);
            }
            
            $data->products = $newProducts;
        } else {
            foreach ($data->products as $p) {
                $p->name = str_replace('"', "'", $p->name);
                $p->price = $p->price * (1 + $p->vat);
                
                $p->vatGroupId = $vatGroups[$p->vatGroup];
                $p->price = round($p->price, 2);
                if ($p->discount) {
                    $p->discountPercent = (round($p->discount, 2) * 100) . '%';
                }
            }
        }
        
        foreach ($data->products as $p) {
            $block = clone $itemBlock;
            $block->placeObject($p);
            $block->removeBlocks();
            $contentTpl->append($block, 'ITEMS');
        }
        
        // Добавяме към шаблона направените плащания
        $itemBlock = $contentTpl->getBlock('PAY');
        foreach ($data->payments as $p) {
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
     * @param  object $data
     * @return void
     */
    public function createFile($data)
    {
        // Създаваме съдържанието на файла
        $content = $this->makeFileContent($data);
        $now = dt::now(true);
        
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
