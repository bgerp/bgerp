<?php


/**
 * 
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_LinkedTemplates extends core_Master
{
    
    
    /**
     * 
     * @var string
     */
    public $interfaces = 'doc_LinkedIntf';
    
    
    /**
     * Заглавие
     */
    public $title = "Шаблони за връзки между документи";
    
    
    /**
     * Сингъл заглавие
     */
    public $singleTitle = "Шаблон за връзки между документи";
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
	
	
    /**
     * Кой има право да оттегле?
     */
    public $canReject = 'admin';
	
    
    /**
     * Кой има право да възстановява?
     */
    public $canRestore = 'admin';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cond_Wrapper, plg_Created, plg_State2, plg_Rejected, plg_RowTools2, plg_Modified';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar', 'caption=Заглавие, class=w100, mandatory');
        $this->FLD('docType', 'keylist(mvc=core_Classes,select=title,allowEmpty)', 'caption=Вид, placeholder=Вид на изходящия документ, class=w100');
        $this->FLD('fileType', 'varchar(128)', 'caption=Тип, placeholder=Тип на файла, class=w50');
        $this->FLD('users', 'users(rolesForAll=admin, rolesForTeams=admin)', 'caption=Потребители, allowEmpty');
        $this->FLD('roles', 'keylist(mvc=core_Roles, select=role)', 'caption=Роли');
        
        $actTypeArr = doc_Linked::$actArr;
        $enumInst = cls::get('type_Enum');
        $enumInst->options = $actTypeArr;
        $this->FLD('formAct', $enumInst, 'caption=Настройки на формата->Действие, class=w50, mandatory, removeAndRefreshForm=formFolder');
        $this->FLD('formDocType', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Настройки на формата->Вид документ, class=w100, removeAndRefreshForm=formFolder');
        $this->FLD('formFolder', 'key2(forceAjax, mvc=doc_Folders, titleFld=title, maxSuggestions=100, selectSourceArr=doc_Linked::prepareFoldersForDoc, allowEmpty, showWithDocs)', 'caption=Настройки на формата->Папка, class=w100');
        $this->FLD('formComment', 'varchar', 'caption=Настройки на формата->Пояснения');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $suggestions = core_Classes::getOptionsByInterface('doc_DocumentIntf', 'title');
        $data->form->setSuggestions('docType', $suggestions);
        
        if (!$data->form->rec->id) {
            $data->form->setDefault('users', 'all_users');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->rec->formDocType && $form->rec->formAct != 'newDoc') {
            $form->fields['formFolder']->type->params['docType'] = $form->rec->formDocType;
        }
    }
    
    
    /**
     * Връща дейности, които са за дадения документ
     *
     * @param integer $cId
     *
     * @return array
     */
    function getActivitiesForDocument($cId)
    {
        $res = array();
        
        if (!$cId) return $res;
        
        $document = doc_Containers::getDocument($cId);
        
        if (!$document) return $res;
        
        $clsId = $document->instance->getClassId();
        
        if (!$clsId) return $res;
        
        $query = self::getQuery();
        
        $query->likeKeylist('docType', $clsId);
        $query->orWhere("#docType IS NULL");
        
        $res = $this->getResForActivities($query);
        
        return $res;
    }
    
    
    /**
     * Връща дейности, които са за дадения файл
     *
     * @param integer $cId
     *
     * @return array
     */
    function getActivitiesForFile($cId)
    {
        $res = array();
        
        if (!$cId) return $res;
        
        $fRec = fileman_Files::fetch($cId);
        
        if (!$fRec) return $res;
        
        $query = self::getQuery();
        
        $ext = fileman_Files::getExt($fRec->name);
        
        $mimeType = fileman_Mimes::getMimeByExt($ext);
        
        // Подобни файлове - от миме типа
        $extArr = array();
        if ($mimeType) {
            $extArr = fileman_Mimes::getExtByMime($mimeType);
            
            if (!isset($extArr)) {
                $extArr = array();
            }
        }
        
        if (array_search($ext, $extArr) === FALSE) {
            $extArr[] = $ext;
        }
        
        $query->where("#fileType IS NULL");
        $query->orWhere("#fileType = ''");
        
        foreach ($extArr as $ext) {
            
            $ext = trim($ext);
            
            if (!$ext) continue;
            
            $ext = strtolower($ext);
            
            if (preg_match('/[^a-z]/', $ext)) continue;
            
            $query->orWhere("#fileType REGEXP '(([^a-z])|\s|^)+{$ext}(([^a-z])|\s|$)+'");
        }
        
        $res = $this->getResForActivities($query);
        
        return $res;
    }
    
    
    /**
     * Подготвяне на формата за документ
     *
     * @param core_Form $form
     * @param integer $cId
     * @param string $activity
     */
    function prepareFormForDocument(&$form, $cId, $activity)
    {
        
        return $this->prepareFormFor($form, $cId, $activity);
    }
    
    
    /**
     * Подготвяне на формата за файл
     *
     * @param core_Form $form
     * @param integer $cId
     * @param string $activity
     */
    function prepareFormForFile(&$form, $cId, $activity)
    {
        
        return $this->prepareFormFor($form, $cId, $activity, 'file');
    }
    
    
    /**
     * След субмитване на формата за документ
     *
     * @param core_Form $form
     * @param integer $cId
     * @param string $activity
     * 
     * @return mixed
     */
    function doActivityForDocument(&$form, $cId, $activity)
    {
        
        return $this->doActivity($form, $cId, $activity, 'doc');
    }
    
    
    /**
     * След субмитване на формата за файл
     *
     * @param core_Form $form
     * @param integer $cId
     * @param string $activity
     * 
     * @return mixed
     */
    function doActivityForFile(&$form, $cId, $activity)
    {
        
        return $this->doActivity($form, $cId, $activity, 'file');
    }
    
    
    /**
     * Помощна функция за вземане на шаблоните
     * 
     * @param core_Query $query
     * @param NULL|integer $userId
     * 
     * @return array
     */
    protected function getResForActivities(&$query, $userId = NULL)
    {
        $res = array();
        
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $rolesArr = core_Users::getRoles($userId);
        
        $query->where("#state = 'active'");
        
        $query->likeKeylist('users', $userId);
        $query->orLikeKeylist('users', -1);
        $query->orWhere("#users IS NULL");
        
        $query->orLikeKeylist('roles', $rolesArr);
        
        while ($rec = $query->fetch()) {
            $res[get_called_class() . '|' . $rec->id] = $rec->title;
        }
        
        return $res;
    }
    
    
    /**
     * Връща запис за съответното активити
     *
     * @param string $activity
     *
     * @return  NULL|stdObject
     */
    protected function getRecForActivity($activity)
    {
        if (!$activity) return ;
        
        $actArr = explode('|', $activity);
        
        if ($actArr[0] != get_called_class()) return ;
        
        if (!$actArr[1]) return ;
        
        if (!is_numeric($actArr[1])) return ;
        
        $rec = $this->fetch($actArr[1]);
        
        if (!$rec) return ;
        
        return $rec;
    }
    
    
    /**
     * Подготвяне на формата за документ
     *
     * @param core_Form $form
     * @param integer $cId
     * @param string $activity
     */
    protected function prepareFormFor(&$form, $cId, $activity, $type = 'doc')
    {
        $key = $cId . '|' . $activity;
        
        static $preparedArr = array();
        
        if ($preparedArr[$key]) return ;
        
        $rec = $this->getRecForActivity($activity);
        
        if (!$rec) return ;
        
        $preparedArr[$key] = TRUE;
        
        if ($rec->formDocType) {
            $form->setDefault('linkDocType', $rec->formDocType);
        }
        
        if ($rec->formFolder) {
            $form->setDefault('linkFolderId', $rec->formFolder);
        }
        
        if ($rec->formComment) {
            $form->setDefault('comment', $rec->formComment);
        }
        
        if ($rec->formAct) {
            doc_Linked::prepareFormForAct($form, $rec->formAct, $type);
        }
    }
    
    
    /**
     * Помощна функця за след субмитване на формата
     * 
     * @param core_Form $form
     * @param integer $cId
     * @param string $activity
     * @param string $type
     * 
     * @return mixed
     */
    protected function doActivity(&$form, $cId, $activity, $type = 'doc')
    {
        $rec = $this->getRecForActivity($activity);
        
        if (!$rec) return ;
        
        $linkedInst = cls::get('doc_Linked');
        
        $res = $linkedInst->onSubmitFormForAct($form, $rec->formAct, $type, $cId, $activity);
        
        return $res;
    }
}
