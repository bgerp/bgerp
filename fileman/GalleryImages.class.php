<?php



/**
 * Клас 'fileman_GalleryImages' - картинки в галерията
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_GalleryImages extends core_Manager
{
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'user';
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'user';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'user';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'user';
    
    
    /**
     * Заглавие
     */
    var $title = 'Картинки в Галерията';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = "plg_RowTools,fileman_Wrapper,fileman_GalleryWrapper,plg_Created,cms_VerbalIdPlg";
    
    
    /**
     * 
     */
    var $vidFieldName = 'vid';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'cms_GalleryImages';
    
    
    /**
     * Полета за изглед
     */
    var $listFields = 'id,vid=Код,groupId,src,createdOn,createdBy';

    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 20;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
     
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,mandatory');
        
        $this->FLD('style', 'varchar(128)', 'caption=Стил');

        $this->FLD('groupId', 'key(mvc=fileman_GalleryGroups,select=title)', 'caption=Група');
        
        $this->FLD('src', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Картинка,mandatory');
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
    
    
    /**
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('titleSearch', 'varchar', 'caption=Заглавие,input,silent');
        $data->listFilter->FNC('groupSearch', 'key(mvc=fileman_GalleryGroups,select=title, allowEmpty)', 'caption=Група,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo|cms, rolesForTeams=ceo|cms|manager)', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'titleSearch, groupSearch, usersSearch';
        
        $data->listFilter->input('groupSearch, usersSearch, titleSearch', 'silent');
    }

    
    /**
     * 
     * 
     * @param unknown_type $mvc
     * @param unknown_type $res
     * @param unknown_type $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // По - новите добавени да са по - напред
        $data->query->orderBy("#createdOn", "DESC");
        
        // Ако не е избран потребител по подразбиране
        if(!$data->listFilter->rec->usersSearch) {
            
            // Да е текущия
            $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
        }
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако филтъра е по потребители
            if($filter->usersSearch) {
                
    			// Ако се търси по всички и има права admin или ceo
    			if ((strpos($filter->usersSearch, '|-1|') !== FALSE) && (haveRole('ceo, cms, admin'))) {
    			    // Търсим всичко
                } else {
                    
                    // Масив с потребителите
                    $usersArr = type_Keylist::toArray($filter->usersSearch);
                    
                    // Търсим по създатели
                    $data->query->orWhereArr('createdBy', $usersArr);
                }
    		}
    		
    		// Ако се търси по група
    		if ($filter->groupSearch) {
    		    
    		    // Търсим групата
    		    $data->query->where(array("#groupId = '[#1#]'", $filter->groupSearch));
    		}
    		
    		// Тримваме заглавието
    		$title = trim($filter->titleSearch);
    		
    		// Ако има съдържание
    		if (strlen($title)) {
    		    
    		    // Търсим в заглавието
    		    $data->query->where(array("LOWER(#title) LIKE LOWER('%[#1#]%')", $title));
    		}
        }
    }
}
