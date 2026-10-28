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
        $form->FLD('previewHash', 'varchar(64)', 'input=hidden,silent');
        $form->importButtonCaption = 'Преглед';
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
        $file = fileman_Files::fetchByFh($form->rec->xmlFile ?? null);
        if (!$file || !fileman_Files::haveRightFor('single', $file)) {
            $form->rec->previewHash = null;
            $form->setError('xmlFile', 'Файлът липсва или нямате права за преглед');

            return;
        }
        $password = $form->rec->archivePassword ?? null;
        if (strpos($password ?? '', "\0") !== false) {
            $form->rec->previewHash = null;
            $form->setError('archivePassword', 'Паролата съдържа недопустим нулев байт');

            return;
        }
        $res = payment_ImportFiles::getRecs($form->rec->xmlFile ?? null, $password);
        $res->importPreview = bank_Register::previewImport($res->batches ?? array());
        foreach ($res->importPreview as $i => $diagnostic) {
            if (in_array($diagnostic->status ?? null, array('conflict', 'legacy'), true)) {
                $res->warnings[] = 'Ред №' . ($i + 1) . ': ' . self::getPreviewStatus($diagnostic->status ?? '') . '; няма да бъде добавен';
            }
        }
        if (($res->status ?? null) == 'unsupported') {
            $form->setError('xmlFile', 'Непознат файлов формат');
        }
        if (!empty($res->warnings)) {
            $form->setWarning('xmlFile', '|*' . nl2br(self::escapeText(implode("\n", $res->warnings))));
        }
        if (!empty($res->errors)) {
            $fields = !empty($res->passwordError) ? 'xmlFile,archivePassword' : 'xmlFile';
            $form->setError($fields, '|*' . nl2br(self::escapeText(implode("\n", $res->errors))));
        }
        if (!empty($res->recognized) && empty($res->recs) && empty($res->warnings) && empty($res->errors)) {
            $form->setWarning('xmlFile', 'Извлечението не съдържа операции за импорт');
        }
        $form->rec->recs = $res->recs ?? array();
        $form->rec->importBatches = $res->batches ?? array();
        $form->info = $this->renderPreview($res);
        $form->styles['.formTable'] = 'max-width:100%;width:100%;';
        if (empty($res->recs) || !empty($res->errors)) {
            $form->rec->previewHash = null;
            $form->cmd = 'refresh';

            return;
        }

        // Потвърждението е само за показания резултат; файлът се разчита отново при всеки submit.
        $hash = hash_hmac('sha256', serialize(array(core_Users::getCurrent(), $form->rec->xmlFile ?? null, $res)), EF_SALT);
        if (!hash_equals($hash, $form->rec->previewHash ?? '')) {
            $form->cmd = 'refresh';
            $form->ignore = false;
        }
        $form->rec->previewHash = $hash;
        $form->importButtonCaption = 'Импорт';
    }


    /**
     * Прегледът не извиква импорта или съпоставянето на банковия регистър.
     */
    protected function renderPreview($result)
    {
        $count = count($result->recs ?? array());
        $skipped = $result->skippedEntries ?? 0;
        $warnings = count($result->warnings ?? array());
        $errors = count($result->errors ?? array());
        $html = '<h3>' . tr('Преглед преди импорт') . '</h3><p><b>' . tr('Нищо не е записано') . '</b></p>';
        $html .= '<p>' . tr('Разчетени редове') . ": <b>{$count}</b>; " . tr('Пропуснати записи от извлеченията') . ": <b>{$skipped}</b>; " .
            tr('Предупреждения') . ": {$warnings}; " . tr('Грешки') . ": {$errors}.</p>";
        $counts = array_count_values(array_map(function ($row) { return $row->status ?? 'new'; }, $result->importPreview ?? array()));
        $summary = array();
        foreach (array('new', 'duplicate', 'conflict', 'legacy') as $status) {
            $summary[] = tr(self::getPreviewStatus($status)) . ': <b>' . ($counts[$status] ?? 0) . '</b>';
        }
        $html .= '<p>' . implode('; ', $summary) . '.</p>';
        $html .= '<p>' . tr('Групираните операции са разделени на редове. Ще се добавят само новите операции; дубликатите и конфликтите се пропускат. Проверява се отново при запис') . '.</p>';
        $rows = array();
        $amountType = core_Type::getByName('double(decimals=2)');
        $dateType = core_Type::getByName('date');
        foreach (array_slice($result->previewRows ?? array(), 0, 200) as $i => $rec) {
            $row = new stdClass();
            $row->number = $i + 1;
            $diagnostic = $result->importPreview[$i] ?? null;
            $row->importStatus = tr(self::getPreviewStatus($diagnostic->status ?? 'new'));
            if (!empty($diagnostic->existingId)) {
                $id = (int) $diagnostic->existingId;
                $row->importStatus .= '<br>' . (bank_Register::haveRightFor('list')
                    ? ht::createLink(tr('Банков запис') . ' №' . $id, array('bank_Register', 'list', $id))
                    : tr('Банков запис') . ' №' . $id);
            } elseif (!empty($diagnostic->duplicateRow)) {
                $row->importStatus .= '<br>' . tr('Към ред от този импорт') . ' №' . (int) $diagnostic->duplicateRow;
            }
            foreach (array('sourceFile', 'ownIban', 'contragentName', 'contragentIban', 'reason', 'currency') as $field) {
                $row->{$field} = self::escapeText($rec->{$field} ?? '');
            }
            $row->sourceFile .= '<br>' . tr('Операция') . ' №' . (int) ($rec->sourceEntry ?? 0);
            $row->contragentName .= '<br>' . $row->contragentIban;
            $row->valior = $dateType->toVerbal($rec->valior ?? null);
            $row->type = tr(($rec->type ?? null) == 'incoming' ? 'Входящ' : 'Изходящ');
            $row->amount = $amountType->toVerbal($rec->amount ?? null) . ' ' . $row->currency;
            $rows[] = $row;
        }
        if ($count > count($rows)) {
            $html .= '<p>' . tr('Показани са първите 200 реда; при потвърждение ще се обработят всички валидни редове') . '.</p>';
        }
        if ($rows) {
            $table = cls::get('core_TableView');
            $html .= '<div style="overflow-x:auto">' . $table->get($rows, 'number=№,importStatus=Резултат,sourceFile=Файл,ownIban=Наша сметка,valior=Вальор,type=Вид,amount=Сума,contragentName=Контрагент,reason=Основание') . '</div>';
        }

        return $html;
    }


    private static function getPreviewStatus($status)
    {
        $labels = array('new' => 'Нова', 'duplicate' => 'Дубликат', 'conflict' => 'Конфликт на банкова референция', 'legacy' => 'Възможен дубликат без референция');

        return $labels[$status] ?? $status;
    }


    private static function escapeText($text)
    {
        return str_replace('[', '&#91;', type_Varchar::escape($text));
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
