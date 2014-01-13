<?php


/**
 * Клас 'cms_RichTextPlg' - Добавя функционалност за поставяне на картинки
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Yusein Yuseinov <yyuseinov@gmail.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_RichTextPlg extends core_Plugin
{
    
    
    /**
     * Добавя бутон за качване на документ
     */
    function on_AfterGetToolbar($mvc, &$toolbarArr, &$attr)
    {
        // Ако има група
        if (cms_GalleryGroups::fetch("1=1")) {
            
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
            $toolbarArr->add($documentUpload, 'filesAndDoc');
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
        $Cms = cls::get('cms_GalleryImages');
        
        // Вземаме формата към този модел
        $form = $Cms->getForm();
        
        // Добавяме нужните полета
        $form->FNC('imgFile', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Изображение, mandatory");
        $form->FNC('imgGroupId', 'key(mvc=cms_GalleryGroups,select=title)', 'caption=Група, mandatory');
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
