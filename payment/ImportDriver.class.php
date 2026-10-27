<?php


/**
 * Драйвер за импортиране на трансакции от банков XML
 *
 * @category  bgerp
 * @package   payment
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2026 Experta EOOD
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
        $form->FLD('xmlFile', 'fileman_FileType(bucket=import)', 'caption=XML или ZIP файл,mandatory');
        $form->FLD('archivePassword', 'password(allowEmpty)', 'caption=ZIP архив->Парола,hint=При повторно изпращане на формата въведете паролата отново');
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
        $form->rec->recs = $form->rec->importBatches = array();
        $password = $form->rec->archivePassword ?? null;
        if (strpos($password ?? '', "\0") !== false) {
            $form->setError('archivePassword', 'Паролата съдържа недопустим нулев байт');

            return;
        }
        $res = payment_ImportFiles::getRecs($form->rec->xmlFile ?? null, $password);
        if (($res->status ?? null) == 'unsupported') {
            $form->setError('xmlFile', 'Непознат файлов формат');
        }
        if (!empty($res->warnings)) {
            $form->setWarning('xmlFile', '|*' . type_Varchar::escape(implode("\n", $res->warnings)));
        }
        if (!empty($res->errors)) {
            $fields = !empty($res->passwordError) ? 'xmlFile,archivePassword' : 'xmlFile';
            $form->setError($fields, '|*' . type_Varchar::escape(implode("\n", $res->errors)));
        }
        if (!empty($res->recognized) && empty($res->recs) && empty($res->warnings) && empty($res->errors)) {
            $form->setWarning('xmlFile', 'Извлечението не съдържа операции за импорт');
        }
        $form->rec->recs = $res->recs ?? array();
        $form->rec->importBatches = $res->batches ?? array();
    }
    
    
    /**
     * Връща записите, подходящи за импорт в детайла.
     * Съответстващия 'importRecs' метод, трябва да очаква
     * същите данни (@see import_DestinationIntf)
     *
     * @param object $rec
     *                    o xmlFile        - ид на файл от филеман
     *
     * @return void
     */
    public function doImport(core_Manager $mvc, $rec)
    {
        $statuses = array();
        foreach ($rec->importBatches ?? array($rec->recs ?? array()) as $batch) {
            $statuses[] = bank_Register::importRecs($batch);
        }

        return implode('; ', $statuses);
    }
}
