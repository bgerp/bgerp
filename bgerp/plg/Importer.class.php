<?php

/**
 * Плъгин помагащ при импортирането на данни от външни формати
 *
 * Плъгина дефинира реализация по подразбиране на екшъна act_Import на мениджъра домакин. Освен
 * това той създава форма за импортиране, съдържаща по подразбиране едно поле-файл. Домакина
 * може да променя / допълва импорт-формата на on_AfterPrepareImportForm().
 *
 * Всеки мениджър, който зарежда този плъгин трябва да реализира метод
 *
 * <code>
 * public static function import($rec)
 * {
 * // ...
 * }
 * </code>
 *
 * където да направи същинското импортиране на данните в своя модел. Параметъра $rec съдържа
 * запис от субмитването на импорт формата.
 *
 * @category  bgerp
 * @package   plg
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_Importer extends core_Plugin
{
    
    /**
     * Име на екшъна / командата за импортиране
     *
     * @var string
     */
    protected static $importVerb = 'import';
    
    
    /**
     * Този хендлър е почти 1:1 копие на core_Manager::act_Manage(), но реализиран в плъгин
     *
     * Ефекта е все едно, че мениджъра-домакин е имплементирал екшъна act_Import
     *
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     * @param string $action
     * @return void|boolean
     */
    function on_BeforeAction($mvc, &$tpl, $action)
    {
        if (strtolower($action) != static::$importVerb) {
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
        $data->cmd = static::$importVerb;
        
        // Очакваме до този момент във формата да няма грешки
        expect(!$data->form->gotErrors(), 'Има грешки в silent полетата на формата', $data->form->errors);
        
        // Дали имаме права за импорт?
        $mvc->requireRightFor($data->cmd, NULL, NULL, $retUrl);
        
        // Зареждаме формата
        $data->form->input();
        
        $rec = &$data->form->rec;
        
        // Генерираме събитие в mvc, след въвеждането на формата, ако е именувана
        $mvc->invoke('AfterInputImportForm', array($data->form));
        
        // Ако формата е успешно изпратена - запис, лог, редирект
        if ($data->form->isSubmitted()) {
            
            // Импортираме данните
            $feedback = $mvc::import($rec);
            
            // Правим запис в лога
            $mvc->logWrite($data->cmd, $feedback);
            
            // Подготвяме адреса, към който трябва да редиректнем,
            // при успешно записване на данните от формата
            $mvc->prepareRetUrl($data);
            
            // Редиректваме към предварително установения адрес
            $tpl = new Redirect($data->retUrl, '|' . $feedback);
            
            return FALSE;
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
    
    
    /**
     * Подготовка на бутоните на импорт-формата
     * Необходимо е да има такъв метод, дори и да е празен, защото няма изискване / гаранции,
     * че мениджъра-домакин го е имплементирал.
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param stdClass $data
     */
    function on_AfterPrepareImportToolbar($mvc, &$res, $data)
    {
    
    }
    
    
    /**
     * Създава кофа за импорт-файлове и подготвя импорт форма
     *
     * Мениджъра-домакин също има шанс да се изкаже за импорт формата реализирайки метод
     * on_AfterPrepareImportForm().
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareImportForm($mvc, &$res, $data)
    {
        // Създаваме (ако няма) кофа за качените файлове за експорт
        fileman_Buckets::createBucket(
            'imports',
            'Файлове използвани за импортиране на данни',
            '',      // могат да се качват файлове със всякакви разширения
            '5M',    // макс. размер
            'admin', // само роля admin може да сваля такива файлове
            NULL,    // всеки може да импортира (стига да има права според мениджъра домакин)
            1        // колко време да "живеят" файловете, преди да бъдат автоматично изтрити?
            //
            // Изглежда разумно това число да е съобразено с потенциално най-продължителния
            // импорт - 10-15 мин?
            // @TODO Изглежда този параметър не се използва никъде засега и няма следа
            // в каква мерна единица трябва да се зададе това време за живот!
        );
        
        // Създаваме форма с едно поле-файл за качване.
        $data->form = cls::get('core_Form');
        $data->form->FNC('file', 'fileman_FileType(bucket=imports)', 'input,caption=Файл');
        $data->form->title = 'Импорт';
        
        $data->form->toolbar = new core_Toolbar();
        $data->form->toolbar->addSbBtn('Импорт', array('Ctr' => $mvc, 'Act' => 'Import'), 'id=btnImport', 'ef_icon = img/16/table-import-icon.png,title=Импортиране на ' . mb_strtolower($mvc->title), array('order' => 19));
    }
    
    
    /**
     * Добавяне на бутон за Импорт
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        // Ако няма права за съответния екшън, да не се добавя бутона
        if ($mvc->haveRightFor(static::$importVerb)) {
            $data->toolbar->addBtn('Импорт', array('Ctr' => $mvc, 'Act' => 'Import'), 'id=btnImport', 'ef_icon = img/16/table-import-icon.png,title=Импортиране на ' . mb_strtolower($mvc->title), array('order' => 19));
        }
    }
}
