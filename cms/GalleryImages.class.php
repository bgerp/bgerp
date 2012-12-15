<?php



/**
 * Клас 'cms_GalleryImages' - картинки в галерията
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_GalleryImages extends core_Manager {
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'admin,ceo,cms';
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'admin,ceo,cms';
    
    
    /**
     * Заглавие
     */
    var $title = 'Картинки в Галерията';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = "plg_RowTools,cms_Wrapper,cms_GalleryWrapper,plg_Created,plg_Vid";
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'gallery_Images';
    
    
    /**
     * Полета за изглед
     */
    var $listFields = 'id,vid=Код,groupId,src,createdOn,createdBy';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
     
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,mandatory');
        
        $this->FLD('style', 'varchar(128)', 'caption=Стил');

        $this->FLD('groupId', 'key(mvc=cms_GalleryGroups,select=title)', 'caption=Група');
        
        $this->FLD('src', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Картинка,mandatory');
    }


    /**
     * Подреждаме картинките, като най-новите са първи
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy("#createdOn", "DESC");
    }

    
    /**
     * допълнение към подготовката на вербално представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields)
    {
        $tArr = array(128, 128);
        $mArr = array(600, 450);
            
        $Fancybox = cls::get('fancybox_Fancybox');
        
        if($rec->src) {
            $row->src = $Fancybox->getImage($rec->src, $tArr, $mArr, $rec->title);
        }

        $row->vid = "[img=#" . $rec->vid . "]";
    }

}