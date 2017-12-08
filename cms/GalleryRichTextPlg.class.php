<?php


/**
 * Клас 'cms_GalleryRichTextPlg' - замества [img=#...] в type_Richtext
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_GalleryRichTextPlg extends core_Plugin
{

    /**
     * Регулярен израз за картинките
     */
    const IMG_PATTERN = "/\[img=\#(?'title'[^\]]*)\](?'end'\s*)/si";


    /**
     * Регулярен израз за галериите
     */
//    const GALLERY_PATTERN = "/\[gallery(=\#([^\]]*))\](\s*)/si";


    /**
     * Обработваме елементите линковете, които сочат към докоментната система
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {
       
        $this->mvc = $mvc;
        
        //Ако намери съвпадение на регулярния израз изпълнява функцията
        // Обработваме елементите [images=????]  
        $html = preg_replace_callback(self::IMG_PATTERN, array($this, 'catchImages'), $html);
//        $html = preg_replace_callback(self::GALLERY_PATTERN, array($this, 'catchGallery'), $html);
    }

    
    // TODO - няма да се добавя цялата група, а отделни картинки, в отделен блок, който ще се показва по подобен начин
//    /**
//     * Обработва тагове от вида [#gallery=#xyz#], които са имена на групи от галерията
//     * и показва всички изображения от тази група в таблица
//     */
//    function catchGallery($match)
//    {
//    	$title = $match[2];
//        $groupRec = cms_GalleryGroups::fetch(array("#title = '[#1#]'", $title));
//    	if(!$groupRec) return "[img=#{$groupRec}]";
//    	
//    	$tArr = array($groupRec->tWidth ? $groupRec->tWidth : 128, $groupRec->tHeight ? $groupRec->tHeight : 128);
//        $mArr = array($groupRec->width ? $groupRec->width : 600, $groupRec->height ? $groupRec->width : 600);
//        
//        $imgagesRec = cms_GalleryImages::getQuery();
//        $imgagesRec->where("#groupId={$groupRec->id}");
//        $tpl = new ET(getFileContent('cms/tpl/gallery.shtml'));
//        $tpl->replace($groupRec->tWidth,'width');
//        
//        $Fancybox = cls::get('fancybox_Fancybox');
//        $table = new ET();
//
//        // Задаваме броя на колонките по подразбиране
//        setIfNot($groupRec->columns, 3);
//
//        // извличаме изображенията от групата и генерираме шаблона им
//        $count = 1;
//        
//        while($img = $imgagesRec->fetch()) {
//            
//            $attr = array();
//
//            if($img->style || $groupRec->style) {
//                $attr['style'] = $img->style . ';' . $groupRec->style;
//            }
//
//            $res = $Fancybox->getImage($img->src, $tArr, $mArr, $img->title, $attr);
//            $row = $tpl->getBlock('ROW');;
//        	 
//            $row->replace($res, 'TPL');
//            if(!$groupRec->columns || $count % $groupRec->columns == 0) {
//                $row->append("</tr><tr>");
//            }
//            $row->removeBlocks;
//            $row->append2master();
//            $count++;
//         }
//         
//         $place = $this->mvc->getPlace();
//         $this->mvc->_htmlBoard[$place] = $tpl;
//        
//         return "[#{$place}#]";
//    }
    
    
    /**
     * Заменяме линковете от система с абсолютни URL' та
     *
     * @param array $match - Масив с откритите резултати
     *
     * @return string $res - Ресурса, който ще се замества
     */
    function catchImages($match)
    {
        $title = $match['title'];
        
        $imgRec = cms_GalleryImages::fetch(array("#title = '[#1#]'", $title));
        
        if(!$imgRec) return $match[0];

        $groupRec = cms_GalleryGroups::fetch($imgRec->groupId);
        
        $tArr = array($groupRec->tWidth ? $groupRec->tWidth : 128, $groupRec->tHeight ? $groupRec->tHeight : 128);
        $mArr = array($groupRec->width ? $groupRec->width : 600, $groupRec->height ? $groupRec->width : 600);
            
        $Fancybox = cls::get('fancybox_Fancybox');
        
        $attr = array();

        if($groupRec->style) {
            $attr['style'] = $groupRec->style;
        }
        
        //Ако принтираме или пращаме документа
        if ((Mode::is('text', 'xhtml')) || (Mode::is('text', 'plain'))) {
            
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

        return "[#{$place}#]" . $match['end'];
    }
    
    
    /**
     * Връща всички картинки в подадения ричтекст
     * 
     * @param string $rt
     * 
     * @return array
     */
    static function getImages($rt)
    {
        preg_match_all(static::IMG_PATTERN, $rt, $matches);
        
        $imagesArr = array();
        
        if(count($matches['title'])) {
            foreach($matches['title'] as $name) {
                $imagesArr[$name] = $name;
            }
        }
        
        return $imagesArr;
    }
    
    /**
     * Добавя бутон за качване на документ
     */
    function on_AfterGetToolbar($mvc, &$toolbarArr, &$attr)
    {
        // Ако има група
        if (cms_GalleryGroups::fetch("1=1") && cms_GalleryImages::haveRightFor('add')) {
            
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
            $url = cms_GalleryImages::getUrLForAddImg($callbackName);
            
            // JS фунцкията, която отваря прозореца
            $js = "
            var url = '{$url}';
            
            var richtext = get$('{$id}');
            
            var selText = getSelectedText(richtext);
            
            url += '&selText=' + encodeURIComponent(selText);
            
            openWindow(url, '{$windowName}', '{$args}'); return false;";
            
            // Бутон за отвяряне на прозореца
            $documentUpload = new ET("<a class=rtbutton title='" . tr("Добавяне на картинка") . "' onclick=\"{$js}\">" . tr("Картинка") . "</a>");
            
            // JS функцията
            $callback = "function {$callbackName}(title) {
                var ta = get$('{$id}');
                rp(\"[img=#\" + title + \"]\", ta, 1);
                return true;
            }";
            
            // Добавяме скрипта
            $documentUpload->appendOnce($callback, 'SCRIPTS');
            
            // Добавяне в групата за добавяне на документ
            $toolbarArr->add($documentUpload, 'filesAndDoc', 1000.050);
        }
    }
}
