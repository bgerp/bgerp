<?php


/**
 * Клас 'fileman_GalleryRichTextPlg' - замества [img=#...] в type_RichText
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_GalleryRichTextPlg extends core_Plugin
{
    
    
    /**
     * Обработваме елементите линковете, които сочат към докоментната система
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {
       
        $this->mvc = $mvc;
        
        //Ако намери съвпадение на регулярния израз изпълнява функцията
        // Обработваме елементите [images=????]  
        $html = preg_replace_callback("/\[img=\#(([^\]]*)|)\]\s*/si", array($this, 'catchImages'), $html);
        $html = preg_replace_callback("/\[gallery(=\#([^\]]*)|)\]\s*/si", array($this, 'catchGallery'), $html);
    }

    
    /**
     * Обработва тагове от вида [#gallery=#xyz#], които са имена на групи от галерията
     * и показва всички изображения от тази група в таблица
     */
    function catchGallery($match)
    {
    	$vid = $match[2];
        $groupRec = fileman_GalleryGroups::fetch(array("#vid = '[#1#]'", $vid));
    	if(!$groupRec) return "[img=#{$groupRec}]";
    	
    	$tArr = array($groupRec->tWidth ? $groupRec->tWidth : 128, $groupRec->tHeight ? $groupRec->tHeight : 128);
        $mArr = array($groupRec->width ? $groupRec->width : 600, $groupRec->height ? $groupRec->width : 600);
        
        $imgagesRec = fileman_GalleryImages::getQuery();
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
            
            $attr = array();

            if($img->style || $groupRec->style) {
                $attr['style'] = $img->style . ';' . $groupRec->style;
            }

            $res = $Fancybox->getImage($img->src, $tArr, $mArr, $img->title, $attr);
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
        
		
        $imgRec = fileman_GalleryImages::fetch(array("#vid = '[#1#]'", $vid));
        
        if(!$imgRec) return "[img=#{$vid}]";

        $groupRec = fileman_GalleryGroups::fetch($imgRec->groupId);
        
        $tArr = array($groupRec->tWidth ? $groupRec->tWidth : 128, $groupRec->tHeight ? $groupRec->tHeight : 128);
        $mArr = array($groupRec->width ? $groupRec->width : 600, $groupRec->height ? $groupRec->width : 600);
            
        $Fancybox = cls::get('fancybox_Fancybox');
        
        $attr = array();

        if($img->style || $groupRec->style) {
            $attr['style'] = $img->style . ';' . $groupRec->style;
        }
        
        //Ако принтираме или пращаме документа
        if ((Mode::is('text', 'xhtml')) || (Mode::is('printing'))) {
            
            // Добавяме атрибута за да използваме абсолютни линкове
            $attr['isAbsolute'] = TRUE;
        }

        $res = $Fancybox->getImage($imgRec->src, $tArr, $mArr, $imgRec->title, $attr);
        
        if($groupRec->position && $groupRec->position != 'none') { 
        	$tpl = ($groupRec->tpl) ? $groupRec->tpl : "<div class='clear-{$groupRec->position}'>[#1#]</div>";
        	$res = new ET($tpl, $res);
        }
        
        $place = $this->mvc->getPlace();

        $this->mvc->_htmlBoard[$place] = $res;

        return "[#{$place}#]";
    }
    
    
    /**
     * Добавя бутон за качване на документ
     */
    function on_AfterGetToolbar($mvc, &$toolbarArr, &$attr)
    {
        // Ако има група
        if (fileman_GalleryGroups::fetch("1=1")) {
            
            // id
            $id = $attr['id'];
            
            // Име на функцията и на прозореца
            $windowName = $callbackName = 'placeImg_' . $id;
            
            // Ако е мобилен/тесем режим
            if(Mode::is('screenMode', 'narrow')) {
                
                // Парамтери към отварянето на прозореца
                $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            } else {
                $args = 'width=400,height=600,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            }
            
            // URL за добавяне на документи
            $url = fileman_GalleryImages::getUrLForAddImg($callbackName);
            
            // JS фунцкията, която отваря прозореца
            $js = "openWindow('{$url}', '{$windowName}', '{$args}'); return false;";
            
            // Бутон за отвяряне на прозореца
            $documentUpload = new ET("<a class=rtbutton title='" . tr("Добавяне на картинка") . "' onclick=\"{$js}\">" . tr("Картинка") . "</a>");
            
            // JS функцията
            $callback = "function {$callbackName}(vid) {
                var ta = get$('{$id}');
                rp(\"\\n[img=#\" + vid + \"]\", ta);
                return true;
            }";
            
            // Добавяме скрипта
            $documentUpload->appendOnce($callback, 'SCRIPTS');
            
            // Добавяне в групата за добавяне на документ
            $toolbarArr->add($documentUpload, 'filesAndDoc', 1000.085);
        }
    }
}
