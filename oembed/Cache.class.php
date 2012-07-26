<?php
class oembed_Cache extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created';
    
    
    /**
     * Заглавие
     */
    var $title = "Oembed кеш";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,url,createdOn,expires';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'admin';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin';
    
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'url';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Кеш';
    
    
    const DEFAULT_CACHE_AGE = 2592000; // 30 дни в секунди 
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('url' , 'varchar(128)', 'caption=URL');
        $this->FLD('html' , 'text', 'caption=HTML за вграждане');
        $this->FLD('provider' , 'varchar(255)', 'caption=Източник');
        
        // Брой секунди, през които този запис е валиден
        $this->FLD('expires' , 'int', 'caption=Валидност');
    
        $this->setDbUnique('url');
    }
    
    
    public static function getCachedHtml($url)
    {
        $rec = static::fetch(array("#url = '[#1#]'", $url));
        
        if ($rec) {
            $createdStamp = strtotime($rec->createdOn);
            $nowStamp     = time();
            
            if ($createdStamp + $rec->expires < $nowStamp) {
                // Този кеширан запис е изтекъл, изтриваме го
                static::delete($rec->id);
                unset($rec);
            }
        }
        
        if (!empty($rec->html)) {
            return $rec->html;
        }
        
        return FALSE;
    }
}