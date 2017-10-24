<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class trz_SalaryIndicatorNames extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Имена на индикатори';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Индикатор';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created';
 
     
    /**
     * Кой има право да чете?
     */
    public $canRead = 'debug,admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'admin,debug';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_onew';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'admin,debug';

    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name',    'varchar', 'caption=Наименование,mandatory');
    }

    
    /**
     * Връща id-то на дадения индикатор. Ако липсва - добавя го.
     *
     * @param string $indicator
     */
    public static function getId($name)
    {
        $indArr;

        if(!isset($indArr)) {
            $indArr = array();
            $query = self::getQuery();
            while($rec = $query->fetch()) {
                $indArr[$rec->name] = $rec->id;
            }
        }

        if(!($id = $indArr[$name])) {
            $rec = (object) array('name' => $name);
            self::save($rec);
            $id = $indArr[$rec->name] = $rec->id;
        }

        return $id;
    }
}
