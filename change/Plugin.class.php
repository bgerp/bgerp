<?php


/**
 * Клас 'change_Plugin' - Плъгин за променя само на избрани полета
 *
 * @category  vendors
 * @package   change
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class change_Plugin extends core_Plugin
{
    
	
	/**
     * След дефиниране на полетата на модела
     * 
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        // Ако няма добавено поле за версия
        if (!$mvc->fields['version']) {
            
            // Добавяме
            $mvc->FLD('version', 'varchar', 'caption=Версия,input=none,width=100%');
        }
        
        // Ако няма добавено поле за подверсия
        if (!$mvc->fields['subVersion']) {
            
            // Добавяме
            $mvc->FLD('subVersion', 'int', 'caption=Подверсия,input=none');
        }
    }
    
    
    /**
     * Добавя бутони за контиране или сторниране към единичния изглед на документа
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Ако не е затворено или не е чернов
        if ($data->rec->state != 'closed' && $data->rec->state != 'draft') {

            // Права за промяна
            $canChange = $mvc->haveRightFor('changerec', $data->rec);
            
            // Ако има права за промяна
            if ($canChange) {
                $changeUrl = array(
                    $mvc,
                    'changeFields',
                    $data->rec->id,
                    'ret_url' => array($mvc, 'single', $data->rec->id),
                );
                
                // Добавяме бутона за промяна
                $data->toolbar->addBtn('Промяна', $changeUrl, 'id=conto,order=20', 'ef_icon = img/16/to_do_list.png');    
            }
        }
    }
    
    
	/**
     *  
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        // Ако екшъна не е changefields, да не се изпълнява
        if (strtolower($action) != 'changefields') return ;
        
        // Ако има права за промяна
        $mvc->requireRightFor('changerec');
        
        // Вземаме формата към този модел
        $form = $mvc->getForm();
        
        // Вземаме всички позволени полета
        $allowedFieldsArr = static::getAllowedFields($form, $mvc->changableFields);
        
        // Очакваме да има зададени полета, които ще се променят
        expect(count($allowedFieldsArr));
        
        // Полетата, които ще записваме в лога
        $fieldsArrLogSave = $allowedFieldsArr;
        
        // Дабавяме версията
        $allowedFieldsArr['version'] = 'version';
        
        // Полетата, които ще се показва
        $fieldsArrShow = $allowedFieldsArr;
        
        // Добавяме подверсията
        $allowedFieldsArr['subVersion'] = 'subVersion';
        
        // Стринга, за инпутване
        $inputFields = implode(',', $allowedFieldsArr);
        
        // Добавяме и id
        $inputFields .= ',id';
        
        // Въвеждаме полетата
        $form->input($inputFields, 'silent');
        
        // Очакваме да има такъв запис
        expect($rec = $mvc->fetch($form->rec->id));
        
        // Очакваме потребителя да има права за съответния запис
        $mvc->requireRightFor('single', $rec);

        // Генерираме събитие в AfterInputEditForm, след въвеждането на формата
        $mvc->invoke('AfterInputEditForm', array($form));
        
        // URL' то където ще се редиректва
        $retUrl = getRetUrl();
        
        // Ако няма такова URL, връщаме към single' а
        $retUrl = ($retUrl) ? ($retUrl) : array($mvc, 'single', $form->rec->id);
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            // Ако не е подадена версия
            if (!$form->rec->version) {
                
                // Да е нула
                $form->rec->version = '0';
            }
            
            // Ако сме променили версията
            if ((string)$form->rec->version != (string)$rec->version) {
                
                // Подверсията
                $subVersion = 0;
                
                // Ако има id
                if ($rec->id) {
                    
                    // Вземаме последните подверсии за съответнате версии
                    $lastSubVersionsArr = change_Log::getLastSubVersionsArr($mvc, $rec->id);
                }
                
                // Ако я има съответната версия
                if ($lastSubVersionsArr[$form->rec->version]) {
                    
                    // Вземаме подверсията
                    $subVersion = $lastSubVersionsArr[$form->rec->version];
                }
            } else {
                
                // Подверсията
                $subVersion = $rec->subVersion;
            }
            
            // Увеличаваме подверсията
            $subVersion++;
            
            // Добавяме подверсията
            $form->rec->subVersion = $subVersion;
            
            // Извикваме фунцкията, за да дадем възможност за добавяне от други хора
            $mvc->invoke('AfterInputChanges', array($rec, $form->rec));
            
            // Записваме промени
            $mvc->save($form->rec);
            
            // Записваме лога на промените
            $savedRecsArr = change_Log::create($mvc->className, $fieldsArrLogSave, $rec, $form->rec);
            
            // Извикваме фунцкия, след като запишем
            $mvc->invoke('AfterSaveLogChange', array($savedRecsArr));
            
            // Редиректваме
            return redirect($retUrl);
        }
        
        // Ако няма грешки
        if (!$form->gotErrors()) {
            
            // Вземаме данните
            $vRec = $rec;
            
            // id на класа
            $classId = core_Classes::getId($mvc);
            
            // Масива с първата и последната версия
            $versionArr = change_Log::getFirstAndLastVersion($classId, $rec->id);
            
            // Ако няма последна версия
            if (!$versionArr['last']) {
                
                // Ако има първа версия
                if ($versionArr['first']) {

                    // Версията, която ще използваме е първата
                    $versionStr = $versionArr['first'];
                }
            } else {
                
                // Версията, която ще използваме е последната
                $versionStr = $versionArr['last'];
            }
            
            // Вземаме записитеи за съответния ред
            $gRecArr = change_Log::getRec($classId, $rec->id, $versionStr, $fieldsArrLogSave);
            
            // Ако има записи
            if ($gRec !== FALSE) {
                
                // Обхождаме масива
                foreach ((array)$gRecArr as $field => $gRec) {
                    
                    // Ако няма запис - прескачаме
                    if (!$gRec) continue;
                    
                    // Добавяме полето към записа
                    $vRec->$field = $gRec->value;
                    
                    // Добавяме версията
                    $vRec->version = $gRec->version;
                }
            }
            
            // Обхождаме стария запис
            foreach ((array)$fieldsArrShow as $field) {
                
                // Добавяме старта стойност
                $form->rec->$field = $vRec->$field;
            }    
        }
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = $fieldsArrShow;
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');
        
        $form->title = 'Промяна';
        
        try {
            // Титлата на документа
            $title = $mvc->getDocumentRow($form->rec->id)->title;
            
            if ($title) {
                // Титлата на формата
                $form->title .= " на|*: <i>{$title}</i>";
            }
        } catch (Exception $e) {}
        
        // Ако има стринг за версията
        if ($versionStr) {
            
            // Вземаме стринга за последната версия
            $lastVersionStr = change_Log::getLastVersionFromDoc($mvc, $form->rec->id);
            
            // Ако стринга не е последната версия
            if ($versionStr != $lastVersionStr) {
                
                // Ескейпваме стринга
                $versionStrRaw = change_Log::escape($versionStr);
                
                // Добавяме към заглавието, съответната версия
                $form->title .= " <b style='color:red;'>{$versionStrRaw}</b>";
            }
        }
        
        // Рендираме изгледа
        $tpl = $mvc->renderWrapping($form->renderHtml());
        
        return FALSE;
    }
    
    
    /**
     * 
     */
    public static function on_AfterPrepareSingle($mvc, $data)
    {
        // id на класа
        $classId = core_Classes::getId($mvc);
        
        // Масив с най - новата и най - старата версия
        $selVerArr = change_Log::getFirstAndLastVersion($classId, $data->rec->id);
        
        // Вземаме формата
        $form = $mvc->getForm();
        
        // Вземаме всички полета, които могат да се променят
        $allowedFieldsArr = (array)static::getAllowedFields($form, $mvc->changableFields);
        
        // Ако има избрана версия
        if ($selVerArr['first']) {
            
            // Вземаме стойността за съответното поле, за първата версия
            $firstArr = change_Log::getVerbalValue($classId, $data->rec->id, $selVerArr['first'], $allowedFieldsArr);
            
            // Ако има последна версия
            if ($selVerArr['last']) {
                
                // Стринга на последната версия
                $lastVersionStr = change_Log::getLastVersionFromDoc($mvc, $data->rec->id);
                
                // Ако последната версия е последния вариант
                if ($selVerArr['last'] == $lastVersionStr) {
                    
                    // Обхождаме всички позволени полеоте, които ще се променят
                    foreach ($allowedFieldsArr as $allowedField) {
                        
                        // Добавяме в масива
                        $lastArr[$allowedField] = $data->row->$allowedField;
                    }
                } else {
                    
                    // Вземаме стойността за съответното поле, за последната версия
                    $lastArr = change_Log::getVerbalValue($classId, $data->rec->id, $selVerArr['last'], $allowedFieldsArr);
                }
                
            } else {
                
                // Флаг, който посочва, че няма последна версия
                $noLast = TRUE;
            }
            
            // Обхождаме всички позволени версии
            foreach ($allowedFieldsArr as $allowedField) {
                
                // Вземаме първата версия
                $first = $firstArr[$allowedField];
                
                // Ако няма последна версия
                if ($noLast) {
                    
                    // Задаваме първата версия
                    $data->row->$allowedField = $first;
                } else {
                    
                    // Вземаме последната версия
                    $last = $lastArr[$allowedField];
                    
                    // Сравняваме двата варианта
                    $data->row->$allowedField = lib_Diff::getDiff($first, $last);
                }
            }
        }
        
        // Последна версия
        $lastVersion = change_Log::getVersionStr($data->row->version, $data->row->subVersion);
        
        // Ако има избрана версия
        if ($selVerArr['first']) {
            
            // Добавяме в променлива
            $data->row->LastSavedVersion = $lastVersion;
        } else {
            
            // Добавяме в друга променлива
            $data->row->LastVersion = $lastVersion;
        }
        
        // Първата избрана версия
        $data->row->FirstSelectedVersion = change_Log::escape($selVerArr['first']);
        
        // Ако последната версия е последния вариант
        if ($lastVersionStr && ($selVerArr['last'] == $lastVersionStr)) {
            
            // Последната избрана версия
            $data->row->LastSelectedVersion = $lastVersion;
        } else {
            
            // Последната избрана версия
            $data->row->LastSelectedVersion = change_Log::escape($selVerArr['last']);
        }
    }
    
    
    /**
     * Връща масив с всички полета, които ще се променят
     * 
     * @param core_Form $form
     * 
     * return array $allowedFieldsArr
     */
    static function getAllowedFields($form, $changableFields=array())
    {
        // Масива, който ще връщаме
        $allowedFieldsArr = array();
        
        // Обхождаме всички полета
        foreach ($form->fields as $field => $filedClass) {
            
            // Ако могат да се променят
            if ($filedClass->changable) {
                
                // Добавяме в масива
                $allowedFieldsArr[$field] = $field;
            }
        }
        
        // Преобразуваме в масив
        $changableFieldsArr = arr::make($changableFields, TRUE);
        
        // Събираме двата масива
        $allowedFieldsArr += $changableFieldsArr;
        
        return $allowedFieldsArr;
        
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            // Ако редактраиме записа
            if ($id = $form->rec->id) {
                
                // Вземаме записа
                $rec = $mvc->fetch($id);
                
                // Ако състоянието не е чернова
                if ($rec->state != 'draft') {
                    
                    // Вземаме всички, полета които могат да се променят
                    $allowedFieldsArr = static::getAllowedFields($form, $mvc->changableFields);
                    
                    // Масив с полетата, които не са се променили
                    $noChangeArr = array();
                    
                    // Обхождаме полетта
                    foreach ((array)$allowedFieldsArr as $field) {
                        
                        // Ако има променя
                        if ($form->rec->$field != $rec->$field) {
                            
                            // Вдигаме флага
                            $haveChange = TRUE;
                        } else {
                            
                            // Добавяме в масива
                            $noChangeArr[] = $field;
                        }
                    }
                    
                    // Ако няма промени
                    if (!$haveChange) {
                        
                        // Сетваме грешка
                        $form->setError($noChangeArr, 'Нямате промена');
                    }
                }
            }
        }
        
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            if (!$form->rec->id) {
                $form->rec->version = '0';
                $form->rec->subVersion = 1;
            }
        }
    }
    
    
    /**
     * Прихваща извикването на GetChangeLink.
     * Създава линк, който води до промяната на записа
     * 
     * @param object $mvc
     * @param string $res
     * @param integer $id
     * @param string $title - Ако е подаден, връща линк с иконата и титлата. Ако липсва, връща само линк с иконата.
     */
    function on_AfterGetChangeLink($mvc, $res, $id, $title=FALSE)
    {
        // Ако нямаме права да редактираме, да не се показва линка
        if (!$mvc->haveRightFor('changerec', $id)) return ;
        
        // URL' то за промяна
        $changeUrl = array($mvc, 'changefields', $id, 'ret_url' => TRUE);
        
        // Иконата за промяна
        $editSbf = sbf("img/16/edit.png");
        
        // Ако е подаде заглавието
        if ($title) {
            
            // Създаваме линк с загллавието
            $attr['class'] = 'linkWithIcon';
            $attr['style'] = 'background-image:url(' . $editSbf . ');';
            
            $res = ht::createLink($title, $changeUrl, NULL, $attr); 
        } else {
            
            // Ако не е подадено заглавиет, създаваме линк с иконата
            $res = ht::createLink('<img src=' . $editSbf . ' width="12" height="12">', $changeUrl);
        }
    }
}
