<?php
class oembed_Cache extends core_Manager
{
    /**
     * Дължина на полето URL в модела
     */
    const URL_MAX_LEN = 128;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,plg_RowTools';
    
    
    /**
     * Заглавие
     */
    public $title = 'Oembed кеш';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,url,createdOn,expires';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'admin';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го отхвърли?
     */
    public $canReject = 'admin';
    
    
    /**
     * полета от БД по които ще се търси
     */
    public $searchFields = 'url';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Кеш';
    
    
    const DEFAULT_CACHE_AGE = 2592000; // 30 дни в секунди
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('url', 'varchar(' . self::URL_MAX_LEN . ')', 'caption=URL');
        $this->FLD('html', 'text', 'caption=HTML за вграждане');
        $this->FLD('provider', 'varchar(255)', 'caption=Източник');
        
        // Брой секунди, през които този запис е валиден
        $this->FLD('expires', 'int', 'caption=Валидност');
        
        $this->setDbUnique('url');
    }
    
    
    public static function getCachedHtml($url)
    {
        $url = core_String::convertToFixedKey($url, self::URL_MAX_LEN);
        
        $rec = static::fetch(array("#url = '[#1#]'", $url));
        
        if ($rec) {
            $createdStamp = strtotime($rec->createdOn);
            $nowStamp = time();
            
            if ($createdStamp + $rec->expires < $nowStamp) {
                // Този кеширан запис е изтекъл, изтриваме го
                static::delete($rec->id);
                unset($rec);
            }
        }
        
        if (!empty($rec->html)) {
            
            return $rec->html;
        }
        
        return false;
    }
}
