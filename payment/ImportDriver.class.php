<?php


/**
 * Драйвер за импортиране на трансакции от банков XML
 *
 * @category  bgerp
 * @package   payment
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class payment_ImportDriver extends import2_AbstractDriver
{
    public $oldClassName = 'iso20022_ImportDriver';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'bank_ImportTransactionsIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Банков XML Файл';
    
    
    /**
     * Добавя специфични полета към формата за импорт на драйвера
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function addImportFields($mvc, core_FieldSet $form)
    {
        $form->FLD('xmlFile', 'fileman_FileType(bucket=import)', 'caption=XML файл,mandatory');
    }
    
    
    /**
     * Проверява събмитнатата форма
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function checkImportForm($mvc, core_FieldSet $form)
    {
        $xml = fileman::extractStr($form->rec->xmlFile);
        
        core_App::setTimeLimit(30 + round(strlen($xml) / 100000));
        
        if (strpos($xml, 'iso:20022') !== false && strpos($xml, 'iso:20022') < 50) {
            $res = payment_ParserIso20022::getRecs($xml, 'Import ISO20022');
        } elseif (strpos($xml, 'APAccounts') !== false && strpos($xml, 'APAccounts') < 50) {
            $res = payment_ParserUC::getRecs($xml, 'Import Unicredit');
        } else {
            $form->setError('xmlFile', 'Непознат файлов формат');
        }
        
        if (count($res->warnings)) {
            $form->setWarning('xmlFile', '|*' . implode('<br>', $res->warnings));
        }
        
        if (count($res->errors)) {
            $form->setError('xmlFile', '|*' . implode('<br>', $res->errors));
        }
        
        $form->rec->recs = $res->recs;
    }
    
    
    /**
     * Връща записите, подходящи за импорт в детайла.
     * Съответстващия 'importRecs' метод, трябва да очаква
     * същите данни (@see import_DestinationIntf)
     *
     * @see import_DriverIntf
     *
     * @param object $rec
     *                    o xmlFile        - ид на файл от филеман
     *
     * @return void
     */
    public function doImport(core_Manager $mvc, $rec)
    {
        $status = bank_Register::importRecs($rec->recs);
        
        return $status;
    }
}
