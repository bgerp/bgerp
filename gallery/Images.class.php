<?php



/**
 * Клас 'gallery_Images' - картинки в галерията
 *
 *
 * @category  vendors
 * @package   gallery
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class gallery_Images extends core_Manager {
    
    
    var $canRead = 'admin,ceo,cms';
    
    
    var $canWrite = 'admin,ceo,cms';
    
    
    /**
     * Заглавие
     */
    var $title = 'Групи от картинки';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = "plg_RowTools,gallery_Wrapper,plg_Created,plg_Vid";
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = "admin,cms,ceo";
    
    var $listFields = 'id,vid=Код,groupId,src,createdOn,createdBy';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
     
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,mandatory');

        $this->FLD('groupId', 'key(mvc=gallery_Groups,select=title)', 'caption=Група');
        
        $this->FLD('src', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Картинка,mandatory');
    }

    
    /**
     *
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