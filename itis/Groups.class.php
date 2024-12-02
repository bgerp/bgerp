<?php


/**
 * Групи на IT устройствата
 *
 *
 * @category  bgerp
 * @package   itis
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 */
class itis_Groups extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, itis_Wrapper, plg_Sorting, plg_Created';
    
    
    /**
     * Заглавие
     */
    public $title = 'Групи IT устройства';
    
    
    /**
     * Права за запис
     */
    public $canWrite = 'ceo,itis,admin';
    
    
    /**
     * Права за четене
     */
    public $canRead = 'ceo,itis,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,itis';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,itis';
    
 
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('sysId', 'varchar(16)', 'caption=System ID');
        $this->FLD('name', 'varchar(32)', 'caption=Наименование');
        $this->FLD('image', 'fileman_FileType(bucket=pictures)', 'caption=Илюстрация');

        $this->setDbUnique('name');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'itis/csv/Groups.csv';
        $fields = array(0 => 'sysId', 1 => 'name', 2 => 'image');
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }

    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, $rec)
    {
        if ($rec->image) {
            $rec->image = fileman_Files::absorb(getFullPath($rec->image), 'pictures');
        }
    }

}
