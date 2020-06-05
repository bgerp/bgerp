<?php
/**
 * Мениджър на вербализация на позиция
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 
 * @title     Заетост на стелажите в склад
 */
class rack_OccupancyOfRacks extends core_Manager
{
    public $title = 'Заетост на стелажите в склад';
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'rack_Wrapper,plg_Created, plg_RowTools2';
    
    
    /**
     * Кой има право да чете?
     *
     * @var string|array
     */
    public $canRead = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';
    
    
    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     *
     * @var string|array
     */
    public $canView = 'ceo,admin';
    
    
    /**
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        
        $this->FLD('storeId', 'int', 'caption=Склад');
        $this->FLD('rackId', 'int', 'caption=Стелаж');
        $this->FLD('palleteId', 'int', 'caption=Място');
        $this->FLD('productId', 'varchar', 'caption=Артикул');
        $this->FLD('position', 'rack_PositionType', 'caption=Позиция');
        $this->FLD('date', 'datetime', 'caption=Създаден');
        $this->FLD('quantity', 'double(decimals=2)', 'caption=Количество');
        
        $this->setDbIndex('productId');
        $this->setDbIndex('productId,storeId');
        $this->setDbIndex('rackId');
        $this->setDbIndex('storeId');
        $this->setDbIndex('position');
        
    }
    
    public function cron_GetOccupancyOfRacks()
    {
        $this->getOccupancyOfRacks();
    }
    
    
    private function getOccupancyOfRacks()
    {
        
        $pQuery = rack_Pallets::getQuery();
        $pQuery->where("#state = 'active'");
        while ($pRec = $pQuery->fetch()){
            
            $pRecArr = (object)array('storeId' => $pRec->storeId,
                                    'rackId' => $pRec->rackId,
                                    'palleteId' =>$pRec->id,
                                    'productId' => $pRec->productId,
                                    'position' => $pRec->position,
                                    'date' => $pRec->createdOn,
                                    'quantity' => $pRec->quantity,
                                
                            );
            
            $this->save($pRecArr);
        
        }
    }
    

    
 

  
    
    
    
}