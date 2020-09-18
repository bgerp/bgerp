<?php


/**
 * Модел за продуктови рейтинги
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_ProductRatings extends core_Manager
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'sales_StatisticData';
    
    
    /**
     * Заглавие
     */
    public $title = 'Рейтинг на артикулите според продажбите';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, plg_Sorting, plg_AlignDecimals2';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които се виждат
     */
    public $listFields = 'id,objectId=Обект,classId,key,value,updatedOn';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('classId', 'class(interface=sales_RatingsSourceIntf,select=title,allowEmpty)', 'caption=Източник,mandatory');
        $this->FLD('objectClassId', 'class', 'caption=Обект->Клас,silent');
        $this->FLD('objectId', 'int', 'caption=Обект->Ид,silent,tdClass=leftCol');
        
        $this->FLD('key', 'varchar', 'caption=Ключ');
        $this->FLD('value', 'double', 'caption=Рейтинг,mandatory');
        $this->FLD('updatedOn', 'datetime(format=smartTime)', 'caption=Обновено на,mandatory');
        
        $this->setDbIndex('classId');
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'classId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        
        if($rec = $data->listFilter->rec){
            if(!empty($rec->classId)){
                $data->query->where("#classId = {$rec->classId}");
            }
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($rec->key)){
            if($rec->classId == eshop_Products::getClassId()){
                $row->key = cms_Domains::getHyperlink($rec->key, true);
            } else {
                $row->key = store_Stores::getHyperlink($rec->key, true);
            }
        }
        
        if($rec->objectId){
            $row->objectId = cls::get($rec->objectClassId)->getHyperlink($rec->objectId, true);
        }
        
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Обновяване', array($mvc, 'CalcRating', 'ret_url' => true), null, 'ef_icon = img/16/arrow_refresh.png,title=Обновяване на статистическите данни');
        }
    }
    
    
    /**
     * Екшън за обновяване на статистическите данн
     */
    function act_CalcRating()
    {
        requireRole('debug');
        $this->cron_CalcRating();
        core_Statuses::newStatus('Данните са обновени');
        
        followRetUrl();
    }
    
    
    /**
     * Изчисление на рейтингите на продажба на артикулите
     */
    public function cron_CalcRating()
    {
        $exPosQuery = self::getQuery();
        $exRecs = $exPosQuery->fetchAll();
        
        //$this->truncate();
        
        $newRecs = array();
        $Sources = core_Classes::getOptionsByInterface('sales_RatingsSourceIntf');
        foreach ($Sources as $source){
            $SourceIntf = cls::getInterface('sales_RatingsSourceIntf', $source);
            $ratings = $SourceIntf->getSaleRatingsData();
           
            $newRecs = array_merge($newRecs, $ratings);
        }
        
        $now = dt::now();
        array_walk($newRecs, function (&$a) use($now){$a->updatedOn = $now;});
        $res = arr::syncArrays($newRecs, $exRecs, 'objectClassId,objectId,classId,key', 'value');
        
        $this->saveArray($res['insert']);
        $this->saveArray($res['update'], 'id,value,updatedOn');
        
        if(countR($res['delete'])){
            foreach ($res['delete'] as $deleteId){
                self::delete($deleteId);
            }
        }
    }
}