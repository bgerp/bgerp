<?php


/**
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_LinkedPostponed extends core_Mvc
{
    
    
    /**
     *
     * @var string
     */
    public $interfaces = 'doc_LinkedIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Създаване на отложена задача от връзка';
    
    
    /**
     * Връща дейности, които са за дадения документ
     *
     * @param integer $cId
     *
     * @return array
     */
    public function getActivitiesForDocument($cId)
    {
        return $this->getActivitiesFor();
    }
    
    
    /**
     * Връща дейности, които са за дадения файл
     *
     * @param integer $cId
     *
     * @return array
     */
    public function getActivitiesForFile($cId)
    {
        return $this->getActivitiesFor('file');
    }
    
    
    /**
     * Подготвяне на формата за документ
     *
     * @param core_Form $form
     * @param integer   $cId
     * @param string    $activity
     */
    public function prepareFormForDocument(&$form, $cId, $activity)
    {
        return $this->prepareFormFor($form, $cId, $activity);
    }
    
    
    /**
     * Подготвяне на формата за файл
     *
     * @param core_Form $form
     * @param integer   $cId
     * @param string    $activity
     */
    public function prepareFormForFile(&$form, $cId, $activity)
    {
        return $this->prepareFormFor($form, $cId, $activity, 'file');
    }
    
    
    /**
     * След субмитване на формата за документ
     *
     * @param core_Form $form
     * @param integer   $cId
     * @param string    $activity
     *
     * @return mixed
     */
    public function doActivityForDocument(&$form, $cId, $activity)
    {
        return $this->doActivityFor($form, $cId, $activity, 'doc');
    }
    
    
    /**
     * След субмитване на формата за файл
     *
     * @param core_Form $form
     * @param integer   $cId
     * @param string    $activity
     *
     * @return mixed
     */
    public function doActivityForFile(&$form, $cId, $activity)
    {
        return $this->doActivityFor($form, $cId, $activity, 'file');
    }
    
    
    /**
     * Помощна функция за вземане на шаблоните
     *
     * @param core_Query   $query
     * @param NULL|integer $userId
     *
     * @return array
     */
    protected function getActivitiesFor($type = 'doc')
    {
        return array(get_called_class() => 'Отложена задача');
    }
    
    
    /**
     * Подготвяне на формата за документ
     *
     * @param core_Form $form
     * @param integer   $cId
     * @param string    $activity
     */
    protected function prepareFormFor(&$form, $cId, $activity, $type = 'doc')
    {
        if ($activity != get_called_class()) {
            
            return ;
        }
        
        $key = $cId . '|' . $activity;
        
        static $preparedArr = array();
        
        if ($preparedArr[$key]) {
            
            return ;
        }
        
        $form->FNC('date', 'date', 'caption=Дата,class=w100, input=input, silent');
        $form->setDefault('date', cal_Calendar::nextWorkingDay());
        
        $form->FNC('folderId', 'key2(mvc=doc_Folders, restrictViewAccess=yes, allowEmpty)', 'caption=Папка,class=w100, input=input, silent');
        $form->setDefault('folderId', doc_Folders::getDefaultFolder(core_Users::getCurrent()));
        
        $preparedArr[$key] = true;
    }
    
    
    /**
     * Помощна функця за след субмитване на формата
     *
     * @param core_Form $form
     * @param integer   $cId
     * @param string    $activity
     * @param string    $type
     *
     * @return mixed
     */
    protected function doActivityFor(&$form, $cId, $activity, $type = 'doc')
    {
        if ($activity != get_called_class()) {
            
            return ;
        }
        
        if (!$form->isSubmitted()) {
            
            return ;
        }
        
        $cu = core_Users::getCurrent();
        
        $rec = $form->rec;
        
        $redirectUrl = array('cal_Tasks', 'add', 'foreignId' => $cId, 'ret_url' => true);
        
        try {
            $document = doc_Containers::getDocument($cId);
            $dRow = $document->getDocumentRow();
            $title = $dRow->recTitle ? $dRow->recTitle : $dRow->title;
            
            $redirectUrl['title'] = tr('За') . ': ' . $title;
        } catch (core_exception_Expect $e) {
            reportException($e);
        }
        
        $haveFolder = false;
        
        if ($rec->folderId) {
            $redirectUrl['folderId'] = $rec->folderId;
            $haveFolder = true;
        }
        
        // Ако има дата
        if ($rec->date) {
            Mode::push('text', 'plain');
            $date = dt::mysql2verbal($rec->date, 'd.m.Y');
            $wDay = dt::mysql2verbal($rec->date, 'N');
            $wDayStr = tr(core_DateTime::$weekDays[$wDay - 1]);
            $nick = core_Users::getCurrent('nick');
            Mode::pop('text');
            
            $redirectUrl['title'] = tr('Задачи за') . ' ' . $date . '/' . $wDayStr . '/' . $nick;
            $redirectUrl['timeStart'] = dt::verbal2mysql($date . ' 08:00:00');
            
            // Проверяваме дали същата задача не е създадена
            $query = cal_Tasks::getQuery();
            $query->where("#state != 'rejected'");
            $query->where(array("#createdBy = '[#1#]'", $cu));
            $query->where(array("#title = '[#1#]'", $redirectUrl['title']));
            $query->where(array("#timeStart = '[#1#]'", $redirectUrl['timeStart']));
            if ($haveFolder) {
                $query->where(array("#folderId = '[#1#]'", $redirectUrl['folderId']));
            }
            
            $query->orderBy('createdOn', 'DESC');
            
            $lastRec = null;
            while ($oRec = $query->fetch()) {
                // Ако към същата задача се добавя същия документ
                if (doc_Linked::fetch(array("#outType = 'doc' AND #outVal = '[#1#]' AND #inType = 'doc' AND #inVal = '[#2#]'", $cId, $oRec->containerId))) {
                    $singleUrl = cal_Tasks::getSingleUrlArray($oRec->id);
                    if (!empty($singleUrl)) {
                        $retUrl = $singleUrl;
                    }
                    
                    return new Redirect($retUrl, '|Документът вече е бил добавен към задачата');
                }
                
                if (!$lastRec && ($cId != $oRec->containerId)) {
                    $lastRec = $oRec;
                }
            }
            
            // Когато ще се добавя към съществуваща задача
            if ($lastRec) {
                $Linked = cls::get('doc_Linked');
                $form->rec->linkContainerId = $lastRec->containerId;
                $Linked->onSubmitFormForAct($form, 'linkDoc', 'doc', $cId, $form->rec->act, $redirectUrl);
                
                if ($form->isSubmitted()) {
                    $retUrl = getRetUrl();
                    if (empty($retUrl)) {
                        $retUrl = $document->getSingleUrlArray();
                    }
                    
                    return new Redirect($retUrl, '|Успешно добавихте документа към|* ' . cal_Tasks::getLinkToSingle($lastRec->id));
                }
            }
        } else {
            // Ако е нова задача без попълнени данни - ще е в нишката на оригиналния документ
            if (!$rec->folderId) {
                $dRec = $document->fetch();
                
                if ($dRec) {
                    $redirectUrl['threadId'] = $dRec->threadId;
                    $haveFolder = true;
                }
            }
        }
        
        if (!$haveFolder) {
            $redirectUrl['folderId'] = doc_Folders::getDefaultFolder($cu);
        }
        
        $redirectUrl['DefUsers'] = '|' . $cu . '|';
        
        $Linked = cls::get('doc_Linked');
        $res = $Linked->onSubmitFormForAct($form, 'newDoc', 'doc', $cId, $form->rec->act, $redirectUrl);
        
        return $res;
    }
}
