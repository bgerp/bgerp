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
                $args = 'width=400,height=320,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            }
            
            // URL за добавяне на документи
            $url = $mvc->getUrLForAddImg($callbackName);
            
            // JS фунцкията, която отваря прозореца
            $js = "openWindow('{$url}', '{$windowName}', '{$args}'); return false;";
            
            // Бутон за отвяряне на прозореца
            $documentUpload = new ET("<a class=rtbutton title='" . tr("Добавяне на картинка") . "' onclick=\"{$js}\">" . tr("Картинка") . "</a>");
            
            // JS функцията
            $callback = "function {$callbackName}(vid) {
                var ta = get$('{$id}');
                rp(\"\\n[img=\" + vid + \"]\", ta);
                return true;
            }";
            
            // Добавяме скрипта
            $documentUpload->appendOnce($callback, 'SCRIPTS');
            
            // Добавяне в групата за добавяне на документ
            $toolbarArr->add($documentUpload, 'filesAndDoc', 1000.085);
        }
    }
    
    
	/**
     * Връща URL за добавяне на документи
     * 
     * @param core_Mvc $mvc
     * @param core_Et $res
     * @param string $callback
     */
    function on_AfterGetUrLForAddImg($mvc, &$res, $callback)
    {
        // Защитаваме променливите
        Request::setProtected('callback');
        
        // Създаваме URL' то
        $res = toUrl(array($mvc, 'addImgDialog', 'callback' => $callback));
    }
    
	
	/**
     * Извиква се преди изпълняването на екшън
     * 
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param string $action
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        // Ако екшъна не е дилогов прозорец за добавяне на картинка, да не се изпълнява
        if (strtolower($action) != 'addimgdialog') return ;
        
        // Очакваме да е логнат потребител
        requireRole('user');
        
        // Задаваме врапера
        Mode::set('wrapper', 'page_Dialog');

        // Обект с данните
        $data = new stdClass();
        
        // Вземаме променливите
        $callback = Request::get('callback', 'identifier');
        
        // Инстанция на класа
        $Cms = cls::get('fileman_GalleryImages');
        
        // Вземаме формата към този модел
        $form = $Cms->getForm();
        
        // Добавяме нужните полета
        $form->FNC('imgFile', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Изображение, mandatory");
        $form->FNC('imgGroupId', 'key(mvc=fileman_GalleryGroups,select=title)', 'caption=Група, mandatory');
        $form->FNC('imgTitle', 'varchar(128)', 'caption=Заглавие');
        
        // Въвеждаме полето
        $form->input('imgFile, imgGroupId, imgTitle');
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            // Манипулатор на файла
            $fileHnd = $form->rec->imgFile;
            
            // Ако не е задедено име
            if (!($name = $form->rec->imgTitle)) {
                
                // Да се използва името на файла
                $name = fileman_Files::fetchByFh($fileHnd, 'name');
            }
            
            // Създаваме записите
            $rec = new stdClass();
            $rec->title = $name;
            $rec->groupId = $form->rec->imgGroupId;
            $rec->src = $form->rec->imgFile;
            
            // Записваме
            $Cms->save($rec);
            
            // Вземаме полето
            $vid = $Cms->vidFieldName;
            
            // Очакваме да има стойност
            expect($rec->$vid);
            
            // Променливата
            $vid = '#' . $rec->$vid;
            
            // Създаваме шаблона
            $tpl = new ET();
            
            // Добавяме скрипта, който ще добави надписа и ще затвори прозореца
            $tpl->append("if(window.opener.{$callback}('{$vid}') == true) self.close(); else self.focus();", 'SCRIPTS');
            
            return FALSE;
        }
        
        // Заглавие на шаблона
        $form->title = "Добавяне на картинка";
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'imgFile, imgGroupId, imgTitle';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Добави', 'save', 'ef_icon = img/16/add.png');
        $form->toolbar->addFnBtn('Отказ', 'self.close();', 'ef_icon = img/16/close16.png');
        
        // Рендираме опаковката
        $tpl = $form->renderHtml();
        
        // Бутон X за затваряне
        $tpl->append("<button onclick='javascript:window.close();' class='dialog-close'>X</button>");
        
        // Конфигурация на ядрото
        $conf = core_Packs::getConfig('core');
        
        // Добавяме титлата
        $tpl->prepend(tr("Картинка") . " « " . $conf->EF_APP_TITLE, 'PAGE_TITLE');
        
        return FALSE;
    }
}
