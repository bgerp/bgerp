<?php
/**
 * Клас 'doc_plg_BusinessDoc'
 *
 * Плъгин реализиращ потребителски интерфейс за въвеждане на основание за създаването на
 * някакъв бизнес документ в случаите, когато това основание не е ясно априори.
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_plg_BusinessDoc extends core_Plugin
{
    /**
     * След инициализирането на модела
     * 
     * @param core_Mvc $mvc
     * @param core_Mvc $data
     */
    public static function on_AfterDescription($mvc)
    {
        // Проверка за приложимост на плъгина към зададения $mvc
        static::checkApplicability($mvc);
    }
    
    
    /**
     * Преди всеки екшън на мениджъра-домакин
     *
     * @param core_Manager $mvc
     * @param core_Et $tpl
     * @param core_Mvc $data
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        if ($action != 'add') {
            // Плъгина действа само при добавяне на документ
            return;
        }
        
        if (!$mvc->haveRightFor($action)) {
            // Няма права за този екшън - не правим нищо - оставяме реакцията на мениджъра.
            return;
        }

        // Има ли вече зададено основание? 
        // Ако в заявката има зададено поне едно от folderId, threadId или originId - да

        if (Request::get('folderId', 'key(mvc=doc_Folders)') ||
            Request::get('threadId', 'key(mvc=doc_Threads)') ||
            Request::get('originId', 'key(mvc=doc_Containers)') ) {
            // Има основание - не правим нищо
            return;
        }
        
        // Генерираме форма за основание и "обличаме" я във wrapper-а на $mvc.
        
        $form = static::prepareReasonForm();
        
        $form->input();
        
        if ($form->isSubmitted()) {
            // @TODO валидиране и редирект с определения folderId или originId
            if ($p = static::getReasonParams($form)) {
                $tpl = new Redirect(
                    toUrl(array($mvc, $action) + $p + array('retUrl'=>static::getRetUrl($mvc)))
                );
                
                // За да прекъснем веригата от събития (on_BeforeAction и act_Action)
                return FALSE;
            }
        }
        
        $form->title = 'Основание за създаване';
        $form->toolbar->addSbBtn('Напред', 'default', array('class'=>'btn-next btn-move'));
        $form->toolbar->addBtn('Отказ', static::getRetUrl($mvc), array('class'=>'btn-cancel'));
        
        $form = $form->renderHtml();
        
        $tpl = $mvc->renderWrapping($form);
        
        // ВАЖНО: спираме изпълнението на евентуални други плъгини
        return FALSE;
    }
    
    
    /**
     * Проверява дали този плъгин е приложим към зададен мениджър
     * 
     * @param core_Mvc $mvc
     * @return boolean
     */
    protected static function checkApplicability($mvc)
    {
        // Прикачане е допустимо само към наследник на core_Manager ...
        if (!$mvc instanceof core_Manager) {
            return FALSE;
        }
        
        // ... към който е прикачен doc_DocumentPlg
        $plugins = arr::make($mvc->loadList);

        if (isset($plugins['doc_DocumentPlg'])) {
            return FALSE;
        } 
        
        return TRUE;
    }
    
    
    /**
     * Форма за въвеждане на основание за създаване на документ
     * 
     * @return core_Form
     */
    protected static function prepareReasonForm()
    {
        $form = cls::get('core_Form');
        
        $form->FNC('originDoc', 'varchar(15)', 'input,caption=Основание->Документ');
        $form->FNC('companyId', 'key(mvc=crm_Companies, select=name, allowEmpty)', 'input,caption=Папка->Фирма');
        $form->FNC('personId', 'key(mvc=crm_Persons, select=name, allowEmpty)', 'input,caption=Папка->Лице');
        
        return $form;
    }
    

    /**
     * Помощен метод за определяне на URL при успешен запис или отказ
     * 
     * @param core_Mvc $mvc
     * @return string
     */
    protected static function getRetUrl($mvc)
    {
        if (!$retUrl = getRetUrl()) {
            $retUrl = toUrl(array($mvc, 'list'));
        }
    
        return $retUrl;
    }
    
    
    /**
     * Намира реалното основание за документа - folderId или originId 
     *  
     * @param core_Form $form
     * @return array FALSE ако има формата не се валидира. Грешките са отбелязани в полетата
     */
    protected static function getReasonParams($form)
    {
        $rec = $form->rec;
        
        // Валидация - точно едно попълнено поле
        $fields = array('originDoc', 'companyId', 'personId');
        $nonEmptyFields = array();
        foreach ($fields as $f) {
            if (!empty($rec->{$f})) {
                $nonEmptyFields[] = $f;
            }
        }
        if (count($nonEmptyFields) !== 1) {
            if (count($nonEmptyFields) == 0) {
                $nonEmptyFields = $fields;
            }
            $form->setError($nonEmptyFields, 'Задължително е попълването на точно едно поле');
            return FALSE;
        }
        
        // "Изчисляване" на folderId или originId в зависимост от това кое поле на формата е 
        // попълнено
        
        $field = reset($nonEmptyFields); // Името на попълненото поле
        $value = $rec->{$field};         // Стойността на попълненото поле 
        
        switch ($field) {
            case 'originDoc':
                $originRef = doc_Containers::getDocumentByHandle($value, 'doc_DocumentIntf');
                $originContainer = $originRef->getContainer();
                
                if (!$originContainer) {
                    $form->setError('originDoc', 'Липсва такъв документ');
                    return FALSE;
                }
                
                //
                // @TODO проверка дали класа на документа-основание ($originRef->className) е
                // допустим като основание за създаване на документи от клас $mvc
                //
                return array('originId' => $originContainer->id);
            case 'companyId':
                return array('folderId' => crm_Companies::forceCoverAndFolder($value));
            case 'personId':
                return array('folderId' => crm_Persons::forceCoverAndFolder($value));
        }
        
        // Не би трябвало да стигаме до тук!
        return FALSE;
    }
}