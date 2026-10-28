<?php


/**
 * Драйвер за импортиране на трансакции от банкови извлечения
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
    public $title = 'Банково извлечение (XML/MT940/ZIP)';
    
    
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
        $form->FLD('xmlFile', 'fileman_FileType(bucket=import)', 'caption=XML|* / MT940 / ZIP,mandatory');
        $form->FLD('archivePassword', 'password(allowEmpty)', 'caption=ZIP архив->Парола,hint=При повторно изпращане на формата въведете паролата отново');
        $form->FLD('previewFilter', 'enum(all=Всички,problems=Проблемни)', 'caption=Преглед->Редове,value=all');
        $form->FLD('previewPage', 'int(min=1)', 'caption=Преглед->Страница,value=1,hint=По 200 реда на страница');
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
        $filter = ($form->rec->previewFilter ?? 'all') === 'problems' ? 'problems' : 'all';
        $page = max(1, (int) ($form->rec->previewPage ?? 1));
        $form->info = $this->renderPreview($res, $filter, $page);
        $form->rec->previewFilter = $filter;
        $form->rec->previewPage = $page;
        $form->toolbar->addSbBtn('Обнови прегледа', 'preview', 'ef_icon=img/16/preview.png,order=9');
        $form->styles['.formTable'] = 'max-width:100%;width:100%;';
        if (empty($res->recs) || !empty($res->errors)) {
            $form->rec->previewHash = null;
            $form->cmd = 'refresh';

            return;
        }

        // Потвърждението е само за показания резултат; файлът се разчита отново при всеки submit.
        $hash = hash_hmac('sha256', serialize(array(core_Users::getCurrent(), $form->rec->xmlFile ?? null, $res, $filter, $page)), EF_SALT);
        if (($form->cmd ?? null) === 'preview' || !hash_equals($hash, $form->rec->previewHash ?? '')) {
            $form->cmd = 'refresh';
            $form->ignore = false;
        }
        $form->rec->previewHash = $hash;
        $form->importButtonCaption = 'Импорт';
    }


    /**
     * Прегледът не извиква импорта или съпоставянето на банковия регистър.
     */
    protected function renderPreview($result, $filter = 'all', &$page = 1)
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
        $selected = $result->previewRows ?? array();
        if ($filter === 'problems') {
            foreach ($selected as $i => $rec) {
                if (!in_array($result->importPreview[$i]->status ?? null, array('conflict', 'legacy'), true)) {
                    unset($selected[$i]);
                }
            }
        }
        $selectedCount = count($selected);
        $pages = max(1, (int) ceil($selectedCount / 200));
        $page = min(max(1, $page), $pages);
        $html .= '<p>' . strtr(tr('Страница %PAGE% от %PAGES%; редове по филтъра: %COUNT%'), array('%PAGE%' => $page, '%PAGES%' => $pages, '%COUNT%' => $selectedCount)) . '.</p>';
        $html .= '<p>' . tr('Филтърът и страницата променят само прегледа. Импортът обработва всички разчетени редове') . '.</p>';
        foreach (array_slice($selected, ($page - 1) * 200, 200, true) as $i => $rec) {
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
            foreach ($diagnostic->differences ?? array() as $field => $values) {
                $labels = array('amount' => 'Сума', 'valior' => 'Вальор', 'contragentIban' => 'IBAN');
                if (!isset($labels[$field])) {
                    continue;
                }
                $old = $values['existing'] ?? '';
                $new = $values['incoming'] ?? '';
                if ($field === 'amount') {
                    $old = $amountType->toVerbal($old);
                    $new = $amountType->toVerbal($new);
                } elseif ($field === 'valior') {
                    $old = $dateType->toVerbal($old);
                    $new = $dateType->toVerbal($new);
                }
                $row->importStatus .= '<br><small>' . strtr(tr('%FIELD%: налично %OLD%; във файла %NEW%'), array(
                    '%FIELD%' => tr($labels[$field]), '%OLD%' => self::escapeText($old ?? ''), '%NEW%' => self::escapeText($new ?? ''),
                )) . '</small>';
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


    /**
     * Отваря формата за повторен преглед; не стартира запис и не пренася пароли.
     */
    public static function getImportUrl($fh)
    {
        return array('bank_Register', 'import2', 'driverClass' => core_Classes::getId('payment_ImportDriver'), 'xmlFile' => $fh, 'Cmd' => 'refresh');
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
