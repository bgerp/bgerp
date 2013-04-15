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
     * Добавя бутони за контиране или сторниране към единичния изглед на документа
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Ако не е затворено или не е чернов
        if ($data->rec->state != 'closed' && $data->rec->state != 'draft') {

            // Права за промяна
            $canChange = $mvc->haveRightFor('change', $data->rec);
            
            // Ако има права за промяна
            if ($canChange) {
                $changeUrl = array(
                    $mvc,
                    'changeFields',
                    $data->rec->id,
                    'ret_url' => array($mvc, 'single', $data->rec->id),
                );
                
                // Добавяме бутона за промяна
                $data->toolbar->addBtn('Промяна', $changeUrl, 'id=conto,class=btn-change,order=20');    
            }
        }
    }
    
    
	/**
     *  
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        // Ако екшъна не е changefields
        if (strtolower($action) != 'changefields') return ;
        
        // Ако има права за едитване
        $mvc->requireRightFor('edit');
        
        // Ако има права за промяна
        $mvc->requireRightFor('change');
        
        // Вземаме формата към този модел
        $form = $mvc->getForm();
        
        // Обхождаме всички полета
        foreach ($form->fields as $field => $filedClass) {
            
            // Ако могат да се променят
            if ($filedClass->changable) {
                
                // Добавяме в масива
                $allowedFieldsArr[$field] = $field;
                
                // Добавяме в стинга
                $inputFields .= "{$field},";
            }
        }
        
        // Очакваме да има зададени полета, които ще се променят
        expect(count($allowedFieldsArr));
        
        // Към стринга добавяме и id' то
        $inputFields .= 'id';
        
        // Въвеждаме полетата
        $form->input($inputFields, 'silent');
        
        // Очакваме да има такъв запис
        expect($rec = $mvc->fetch($form->rec->id));
        
        // Очакваме потребителя да има права за съответния запис
        $mvc->requireRightFor('single', $rec);

        // Генерираме събитие в $this, след въвеждането на формата
        $mvc->invoke('AfterInputEditForm', array($form));
        
        // URL' то където ще се редиректва
        $retUrl = getRetUrl();
        
        // Ако няма такова URL, връщаме към single' а
        $retUrl = ($retUrl) ? ($retUrl) : array($mvc, 'single', $form->rec->id);
        
        // Ако формата е изпратена без грешки, то активираме, ... и редиректваме
        if($form->isSubmitted()) {
            
            // Извикваме фунцкията, за да дадем възможност за добавяне от други хора
            $mvc->invoke('AfterInputChanges', array($rec, $form->rec));
            
            // Записваме промени
            $mvc->save($form->rec, $allowedFieldsArr);
            
            // Записваме лога на промените
            change_Log::create($mvc->className, $allowedFieldsArr, $rec, $form->rec);
            
            // Редиректваме
            return redirect($retUrl);
        }
        
        // Ако няма грешки
        if (!$form->gotErrors()) {
            
            // Обхождаме стария запис
            foreach ((array)$rec as $key => $value) {
                
                // Ако е в полетата, които ще се променята
                if (!$allowedFieldsArr[$key]) continue;
                
                // Добавяме старта стойност
                $form->rec->{$key} = $value;
            }    
        }

        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = $allowedFieldsArr;
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
        
        // Титлата на документа
        $title = $mvc->getDocumentRow($form->rec->id)->title;
        
        // Титлата на формата
        $form->title = "Промяна на|*: <i>{$title}</i>";

        // Рендираме изгледа
        $tpl = $mvc->renderWrapping($form->renderHtml());
        
        return FALSE;
    }
    
    
    /**
     * 
     */
    public function on_AfterRenderSingle(core_Mvc $mvc, &$tpl, $data)
    {
        // Подготвяме масива с лога
        $logArr = change_Log::prepareLog($mvc->className, $data->rec->id);
        
        // Рендираме изгледа
        $tpl->append(change_Log::renderLog($logArr), 'changeLog');
    }
}