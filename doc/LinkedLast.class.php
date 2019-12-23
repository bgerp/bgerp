<?php


/**
 * Показване на последните свързани документи
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_LinkedLast extends core_Mvc
{
    /**
     *
     * @var string
     */
    public $interfaces = 'doc_LinkedIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Показване на последните документи за връзка';
    
    
    /**
     * Колко секунди назад да се показват документите/файловете
     * 7 дни
     */
    protected $showBeforeSec = 604800;
    
    
    /**
     * До колко документа да се показват
     */
    protected $showLimit = null;
    
    
    /**
     * Връща дейности, които са за дадения документ
     *
     * @param int $cId
     *
     * @return array
     */
    public function getActivitiesForDocument($cId)
    {
        return $this->getActivitiesFor($cId);
    }
    
    
    /**
     * Връща дейности, които са за дадения файл
     *
     * @param int $cId
     *
     * @return array
     */
    public function getActivitiesForFile($cId)
    {
        return $this->getActivitiesFor($cId, 'file');
    }
    
    
    /**
     * Подготвяне на формата за документ
     *
     * @param core_Form $form
     * @param int       $cId
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
     * @param int       $cId
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
     * @param int       $cId
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
     * @param int       $cId
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
     * @param int      $id
     * @param NULL|int $userId
     *
     * @return array
     */
    protected function getActivitiesFor($id, $type = 'doc')
    {
        setIfNot($this->showLimit, doc_Setup::get('LINKED_LAST_SHOW_LIMIT'));
        
        $resArr = array();
        
        if (!$this->showLimit) {
            
            return $resArr;
        }
        
        $query = doc_Linked::getQuery();
        $query->where(array("#createdBy = '[#1#]'", core_Users::getCurrent()));
        $query->where(array("#createdOn >= '[#1#]'", dt::subtractSecs($this->showBeforeSec)));
        $query->where(array("#outType = '[#1#]'", $type));
        
        $query->orderBy('createdOn', 'DESC');
        
        $addTo = tr('Добавяне към') . ' ';
        
        $lQuery = doc_Linked::getQuery();
        $lQuery->where(array("#outType = '[#1#]'", $type));
        $lQuery->where(array("#outVal = '[#1#]'", $id));
        
        while ($lRec = $lQuery->fetch()) {
            $lStr = $lRec->inType . '|' . $lRec->inVal;
            $skipArr[$lStr] = $lStr;
        }
        
        while ($rec = $query->fetch()) {
            if (($rec->inType == $type) && ($rec->inVal == $id)) {
                continue;
            }
            if (($rec->outType == $type) && ($rec->outVal == $id)) {
                continue;
            }
            
            $sStr = $rec->inType . '|' . $rec->inVal;
            if ($skipArr[$sStr]) {
                continue;
            }
            
            if ($rec->inType == 'file') {
                $fName = fileman::fetchField($rec->inVal, 'name');
                if ($resArr['last_file_' . $rec->inVal]) {
                    continue;
                }
                $resArr['last_file_' . $rec->inVal] = $addTo . tr('файл') . ' ' . str::limitLen($fName, 32);
            } elseif ($rec->inType == 'doc') {
                $doc = doc_Containers::getDocument($rec->inVal);
                if (!$doc->haveRightFor('single')) {
                    continue;
                }
                $hnd = '#' . $doc->getHandle();
                $dRow = $doc->getDocumentRow();
                $title = $dRow->recTitle ? $dRow->recTitle : $dRow->title;
                if ($resArr['last_doc_' . $rec->inVal]) {
                    continue;
                }
                $resArr['last_doc_' . $rec->inVal] = $addTo . $hnd . ' - ' . $title;
            }
            
            if (!--$this->showLimit) {
                break;
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Подготвяне на формата за документ
     *
     * @param core_Form $form
     * @param int       $cId
     * @param string    $activity
     */
    protected function prepareFormFor(&$form, $cId, $activity, $type = 'doc')
    {
    }
    
    
    /**
     * Помощна функця за след субмитване на формата
     *
     * @param core_Form $form
     * @param int       $cId
     * @param string    $activity
     * @param string    $type
     *
     * @return mixed
     */
    protected function doActivityFor(&$form, $cId, $activity, $type = 'doc')
    {
        if (stripos($activity, 'last_') !== 0) {
            
            return ;
        }
        
        if (!$form->isSubmitted()) {
            
            return ;
        }
        
        list(, $typeStr, $id) = explode('_', $form->rec->act);
        
        if ($typeStr == 'doc') {
            $form->rec->linkContainerId = $id;
            $linkType = 'linkDoc';
        } elseif ($typeStr == 'file') {
            $linkType = 'linkFile';
            $form->rec->linkFileId = fileman::fetchField($id, 'fileHnd');
        } else {
            
            return ;
        }
        
        $res = cls::get('doc_Linked')->onSubmitFormForAct($form, $linkType, $type, $cId, $form->rec->act);
        
        return $res;
    }
}
