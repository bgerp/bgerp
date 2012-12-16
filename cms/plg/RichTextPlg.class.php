<?php



/**
 * Клас 'cms_plg_RichTextPlg' - замества [img=#...] в type_RichText
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_plg_RichTextPlg extends core_Plugin
{
    /**
     * Обработваме елементите линковете, които сочат към докъментната система
     */
    function on_BeforeCatchRichElements($mvc, &$html)
    {
       
        $this->mvc = $mvc;
        
        //Ако намери съвпадение на регулярния израз изпълнява функцията
        // Обработваме елементите [images=????]  
        $html = preg_replace_callback("/\[img(=\#([^\]]*)|)\]\s*/si", array($this, 'catchImages'), $html);
        $html = preg_replace_callback("/\[gallery(=\#([^\]]*)|)\]\s*/si", array($this, 'catchGallery'), $html);
    }

    
    /**
     * Обработва тагове от вида [#gallery=#xyz#], които са имена на групи от галерията
     * и показва всички изображения от тази група в таблица
     */
    function catchGallery($match)
    {
    	$vid = $match[2];
        $groupRec = cms_GalleryGroups::fetch(array("#vid = '[#1#]'", $vid));
    	if(!$groupRec) return "[img=#{$groupRec}]";
    	
    	$tArr = array($groupRec->tWidth ? $groupRec->tWidth : 128, $groupRec->tHeight ? $groupRec->tHeight : 128);
        $mArr = array($groupRec->width ? $groupRec->width : 600, $groupRec->height ? $groupRec->width : 600);
        
        $imgagesRec = cms_GalleryImages::getQuery();
        $imgagesRec->where("#groupId={$groupRec->id}");
        $tpl = new ET(getFileContent('cms/tpl/gallery.shtml'));
        $tpl->replace($groupRec->tWidth,'width');
        
        $Fancybox = cls::get('fancybox_Fancybox');
        $table = new ET();

        // Задаваме броя на колонките по подразбиране
        setIfNot($groupRec->columns, 3);

        // извличаме изображенията от групата и генерираме шаблона им
        $count = 1;
        while($img = $imgagesRec->fetch()) {
        	 $res = $Fancybox->getImage($img->src, $tArr, $mArr, $img->title, array('style' => $img->style));
        	 $row = $tpl->getBlock('ROW');;
        	 
        	 $row->replace($res, 'TPL');
        	 if($count % $groupRec->columns == 0) {
        	 	$row->append("</tr><tr>");
        	 }
        	 $row->removeBlocks;
        	 $row->append2master();
        	 $count++;
         }
         
         $place = $this->mvc->getPlace();
         $this->mvc->_htmlBoard[$place] = $tpl;
        
         return "[#{$place}#]";
     }
    
    
    /**
     * Заменяме линковете от система с абсолютни URL' та
     *
     * @param array $match - Масив с откритите резултати
     *
     * @return string $res - Ресурса, който ще се замества
     */
    function catchImages($match)
    {
        $vid = $match[2];
        
		
        $imgRec = cms_GalleryImages::fetch(array("#vid = '[#1#]'", $vid));
        
        if(!$imgRec) return "[img=#{$vid}]";

        $groupRec =  cms_GalleryGroups::fetch($imgRec->groupId);
        
        $tArr = array($groupRec->tWidth ? $groupRec->tWidth : 128, $groupRec->tHeight ? $groupRec->tHeight : 128);
        $mArr = array($groupRec->width ? $groupRec->width : 600, $groupRec->height ? $groupRec->width : 600);
            
        $Fancybox = cls::get('fancybox_Fancybox');

        $res = $Fancybox->getImage($imgRec->src, $tArr, $mArr, $imgRec->title, array('style' => $imgRec->style));
        
        $place = $this->mvc->getPlace();

        $this->mvc->_htmlBoard[$place] = $res;

        return "[#{$place}#]";
    }
}
