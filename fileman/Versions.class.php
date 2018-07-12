<?php


/**
 * Клас 'fileman_Versions' -
 *
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_Versions extends core_Manager
{
    /**
     * Заглавие на модула
     */
    public $title = 'Версии';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    public $canAdd = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        cls::get('fileman_Files');
        
        // Файлов манипулатор - уникален 8 символен низ от малки лат. букви и цифри
        // Генериран случайно, поради което е труден за налучкване
        $this->FLD('fileHnd', 'varchar(' . strlen(FILEMAN_HANDLER_PTR) . ')', array('notNull' => true, 'caption' => 'Манипулатор'));
        
        // Версия на данните на файла
        $this->FLD('dataId', 'key(mvc=fileman_Data, select=id)', array('caption' => 'Данни Id'));
        
        // От кога са били валидни тези данни
        $this->FLD('from', 'datetime(format=smartTime)', array('caption' => 'Валидност->от'));
        
        // До кога са били валидни тези данни
        $this->FLD('to', 'datetime(format=smartTime)', array('caption' => 'Валидност->до'));
        
        // Състояние на файла
        $this->FLD('state', 'enum(draft=Чернова,active=Активен,rejected=Оттеглен)', array('caption' => 'Състояние'));
        
        $this->FLD('name', 'varchar(collate=ascii_bin)', 'caption=Име на файла, notNull');
        
        $this->FLD('versionName', 'varchar', 'caption=Версия');
        
        // Кой е изпратил тази версия в историята
        $this->load('plg_Created,fileman_Wrapper');
        
        $this->setDbIndex('fileHnd');
        $this->setDbIndex('fileHnd, dataId');
    }
    
    
    /**
     * Създава нова версия на файла
     *
     * @param string $fileHnd - Манипулатора на файла
     * @param fileman_Data - id на данните
     *
     * @return fileman_Versions $id - id' то на записа
     */
    public static function createNew($fileHnd, $newDataId, $versionName = null)
    {
        $query = self::getQuery();
        $query->where(array("#fileHnd = '[#1#]'", $fileHnd));
        $query->limit(1);
        $query->orderBy('createdOn', 'DESC');
        
        $rec = $query->fetch();
        
        // Определяме името на версията
        if (!$versionName) {
            if ($rec && $rec->versionName) {
                list($version, $subVersion) = explode('.', $rec->versionName);
                if ($subVersion && is_numeric($subVersion)) {
                    $nVersion = $version . '.' . ++$subVersion;
                } else {
                    $subVersion = $rec->versionName . '.1';
                }
            } else {
                $nVersion = '0.1';
            }
        } else {
            $nVersion = $versionName;
        }
        
        $fRec = fileman::fetchByFh($fileHnd);
        
        expect($fRec);
        
        // Създаваме нов запис
        $nRec = new stdClass();
        $nRec->fileHnd = $fileHnd;
        $nRec->dataId = $fRec->dataId;
        $nRec->state = 'active';
        $nRec->versionName = $nVersion;
        if ($rec) {
            $nRec->from = $rec->to;
        } else {
            $nRec->from = $fRec->createdOn;
        }
        $nRec->to = dt::now();
        
        $nRec->name = $fRec->name;
        
        $id = static::save($nRec);
        
        if ($nRec) {
            
            // Изтриваме предишния файл от свалянията
            fileman_Download::deleteFileFromSbf($fRec->id);
            
            $fRec->dataId = $newDataId;
            
            fileman::save($fRec, 'dataId');
            
            // Нотифицираме създателя на документа за промянатата
            if ($fRec->createdOn > 0 && ($fRec->createdOn != core_Users::getCurrent())) {
                $currUserNick = core_Users::getNick($fRec->createdBy);
                $currUserNick = type_Nick::normalize($currUserNick);
                
                $msg = $currUserNick . ' |промени файла|* "' . $fRec->name . '"';
                
                bgerp_Notifications::add($msg, array('fileman_Files', 'single', $fileHnd), $fRec->createdBy);
            }
            
            // Увеличаваме брой на файловете, към които сочат данните
            fileman_Data::increaseLinks($newDataId);
        }
        
        return $id;
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('createdOn', 'DESC');
    }
}
