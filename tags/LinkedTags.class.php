<?php


/**
 *
 *
 * @category  bgerp
 * @package   tags
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tags_LinkedTags extends core_Mvc
{
    /**
     *
     * @var string
     */
    public $interfaces = 'doc_LinkedIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Добавяне на таг към документ';
    
    
    /**
     * Връща дейности, които са за дадения документ
     *
     * @param int $cId
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
     * @param int $cId
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
     * @param core_Query $query
     * @param NULL|int   $userId
     *
     * @return array
     */
    protected function getActivitiesFor($type = 'doc')
    {
        $resArr = array();

        if ($type) {
            $resArr[get_called_class()] = 'Маркер';
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
        if ($activity != get_called_class()) {
            
            return ;
        }

        if ($type != 'doc') {

            return ;
        }
        
        $key = $cId . '|' . $activity;
        
        static $preparedArr = array();
        
        if ($preparedArr[$key]) {
            
            return ;
        }

        tags_Logs::prepareFormForTag($form, $cId);
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
        if ($activity != get_called_class()) {
            
            return ;
        }
        
        if (!$form->isSubmitted()) {
            
            return ;
        }

        if ($type != 'doc') {

            return ;
        }
        
        $cu = core_Users::getCurrent();
        
        $rec = $form->rec;

        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $document = doc_Containers::getDocument($cId);
            $retUrl = array($document, 'single', $document->that);
        }

        tags_Logs::onSubmitFormForTag($form, $cId);

        return new Redirect($retUrl);
    }
}
