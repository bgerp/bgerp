<?php
/**
 * Плъгин помагащ при импортирането на данни от външни формати
 *
 *
 * @category  ef
 * @package   plg
 * @author    Stefan Stefanov <stefan.bg@gmail.com
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_Importer extends core_Plugin
{


    function on_BeforeAction($mvc, &$tpl, $action)
    {
        if (strtolower($action) != 'import') {
            return;
        }

        $data = new stdClass();

        // Създаване и подготвяне на формата
        $mvc->prepareImportForm($data);

        // Подготвяме адреса за връщане, ако потребителя не е логнат.
        // Ресурса, който ще се зареди след логване обикновено е страницата,
        // от която се извиква екшън-а act_Manage
        $retUrl = getRetUrl();

        // Определяме, какво действие се опитваме да направим
        $data->cmd = 'Import';

        // Очакваме до този момент във формата да няма грешки
        expect(!$data->form->gotErrors(), 'Има грешки в silent полетата на формата', $data->form->errors);

        // Дали имаме права за това действие към този запис?
        $mvc->requireRightFor($data->cmd, $data->form->rec, NULL, $retUrl);

        // Зареждаме формата
        $data->form->input();

        $rec = &$data->form->rec;

        // Проверка дали входните данни са уникални
        if($rec) {
            if($data->form->isSubmitted() && !$mvc->isUnique($rec, $fields)) {
                $data->form->setError($fields, "Вече съществува запис със същите данни");
            }
        }

        // Генерираме събитие в mvc, след въвеждането на формата, ако е именувана
        $mvc->invoke('AfterInputImportForm', array($data->form));

        // Дали имаме права за това действие към този запис?
        $mvc->requireRightFor($data->cmd, $rec, NULL, $retUrl);

        // Ако формата е успешно изпратена - запис, лог, редирект
        if ($data->form->isSubmitted()) {

            // Импортираме данните
            $feedback = $mvc::import($rec);

            // Правим запис в лога
            $mvc->log($data->cmd, $feedback);

            // Подготвяме адреса, към който трябва да редиректнем,
            // при успешно записване на данните от формата
            $mvc->prepareRetUrl($data);

            // Редиректваме към предварително установения адрес
            redirect($data->retUrl, FALSE, $feedback);
        } else {
            // Подготвяме адреса, към който трябва да редиректнем,
            // при успешно записване на данните от формата
            $mvc->prepareRetUrl($data);
        }

        // Подготвяме лентата с инструменти на формата
        $mvc->prepareImportToolbar($data);

        // Получаваме изгледа на формата
        $tpl = $data->form->renderHtml();

        // Опаковаме изгледа
        $tpl = $mvc->renderWrapping($tpl);

        return FALSE;
    }


    function on_AfterPrepareImportForm($mvc, &$res, $data)
    {
        fileman_Buckets::createBucket(
            'imports',
            'Файлове използвани за импортиране на данни',
            '',
            '5M',
            'admin');

        $data->form = new core_Form();
        $data->form->FNC('file', 'fileman_FileType(bucket=imports)', 'input,caption=Файл');
        $data->form->title = 'Импорт';
    }


    function on_AfterPrepareImportToolbar($mvc, &$res, $data)
    {
        $data->form->toolbar = new core_Toolbar();
        $data->form->toolbar->addSbBtn('Импорт', array('Ctr' => $mvc, 'Act' => 'Import'), 'id=btnImport,class=btn-import');
    }

}