<?php
/**
 * Стандартна тема за външната част
 * 
 * @title     Стандартна CMS тема
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_DefaultTheme extends core_ProtoInner {
    

    /**
     * Поддържан интерфейс
     */
    public $interfaces = 'cms_ThemeIntf';
    

    /**
     * Дали темата носи собствени заглавни картинки
     */
    public $haveOwnHeaderImages = FALSE;

    /**
     * Допълване на формата за домейна със специфични полета за кожата
     */
    public function addEmbeddedFields($form)
    {
        $form->FLD('wImg1', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 1");
        $form->FLD('wImg2', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 2");
        $form->FLD('wImg3', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 3");
        $form->FLD('wImg4', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 4");
        $form->FLD('wImg5', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 5");
        $form->FLD('fadeDelay', 'int', "caption=Превключване на картинките->Задържане,suggestions=3000|5000|7000");
        $form->FLD('fadeTransition', 'int', "caption=Превключване на картинките->Транзиция,suggestions=500|1000|1500");
        $form->FLD('nImg', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавна картинка за мобилен (360x104px)->Изображение 1");
        $form->FLD('title', 'varchar(20)', "caption=Заглавие на сайта->Текст");
        $form->FLD('titleColor', 'color_Type', "caption=Заглавие на сайта->Цвят");

        // Фон на менюто 
        $form->FLD('baseColor', 'color_Type', "caption=Цветове за темата->Базов цвят");

        // Фон на избраното меню
        $form->FLD('activeColor', 'color_Type', "caption=Цветове за темата->Активен цвят");
        
        // Фон на избраното меню
        $form->FLD('bgColor', 'color_Type', "caption=Цветове за темата->Фон на страницата");
    }

    
    public function prepareWrapper($tpl)
    {   
        // Добавяме заглавната картика
        $tpl->replace($this->getHeaderImg(), 'HEADER_IMG');
        
        // Добавяме заглавния текст
        $title = $this->formRec->title;
        if(!$this->haveOwnHeaderImages && !$title) {
            $conf = core_Packs::getConfig('core');
            $title = $conf->EF_APP_TITLE;
        } elseif($title) {
            $title = "<span style='color:{$this->formRec->titleColor}'>" . $title . "</span>";
        }

        if($title) {
            $tpl->replace($title, 'CORE_APP_NAME');
        } 
        
        // цвят на фона на страницата
        if ($this->formRec->bgColor){
        	$bgcolor = $color = ltrim($this->formRec->bgColor, "#");
        	
        }
        // за основния цвят
        if ($this->formRec->baseColor){
        	if(phpcolor_Adapter::checkColor($this->formRec->baseColor)) {
        		// стилове за светъл цвят
        		$css .= "\n    .foorterAdd, #cmsMenu a {color:#000 !important; text-shadow: 0px 0px 1px #fff}";
        		$css .= "\n    .vertical .formTitle, .vertical .formGroup, .vertical form[method=post] input[type=submit], form[method=post] input:first-child[type=submit] {color:#000 !important;}";
        	} else {
        		// стилове за тъмен цвят
        		$css .= "\n    .foorterAdd, #cmsMenu a {color:#fff !important; text-shadow: 2px 2px 2px #000}";
        	}
        	$color = ltrim($this->formRec->baseColor, "#");
        	
        	// ако не е зададен фон на страницата го изчисляваме
        	if(!$bgcolor) {
        		$bordercolor = phpcolor_Adapter::changeColor($color, 'lighten', 40);
        		$bgcolor = phpcolor_Adapter::changeColor($bordercolor, 'mix', 1, '#fff');
        	}
        	
        	// стилове за меню и футър
        	$css .= "\n    #cmsMenu {background-color:#{$color};}";
        	$css .= "\n    #cmsBottom {background-color:#{$color};}";
        	
        	// в зависимост дали е светъл или тъмен, изчисляваме по различен начин
        	if(phpcolor_Adapter::checkColor($this->formRec->baseColor, 'dark')) {
        		$formcolor = phpcolor_Adapter::changeColor($color, 'darken', 10);
        		$formSubcolor = phpcolor_Adapter::changeColor($color, 'lighten', 10);
        	} else {
        		$formcolor = phpcolor_Adapter::changeColor($color, 'mix', 1, '666');;
        		$color = phpcolor_Adapter::changeColor($color, 'darken', 10);
        		$formSubcolor = phpcolor_Adapter::changeColor($color, 'lighten', 5);
        	}
        	
        	// цветове на формите в зависимост от основния цвят
        	$css .= "\n    .vertical form[method=post] input[type=submit], form[method=post] input:first-child[type=submit] {background-color:#{$color} !important; border: 1px solid #{$formcolor} !important}";
        	$css .= "\n    .vertical .formTitle {background-color:#{$color} !important; border-color:#{$formcolor}}";
        	$css .= "\n    .vertical .formGroup {background-color:#{$formSubcolor} !important;}";
        	
    	}
    	
    	// фон на страницата
    	$css .= "\n    body {background-color:#{$bgcolor};}";
    	
    	// за активния цвят
    	if ($this->formRec->activeColor){
    		$css .= "\n    #cmsMenu a.selected, #cmsMenu a:focus, #cmsMenu a:hover {background-color:{$this->formRec->activeColor} !important;}";
    		
    		$color = ltrim($this->formRec->activeColor, "#");
    		$bordercolor = phpcolor_Adapter::changeColor($color, 'lighten', 50);
    		
    		// изчисления за фон и рамка на линковете
    		if(phpcolor_Adapter::checkColor($color, 'dark')) {
    			$bgcolorActive = phpcolor_Adapter::changeColor($bordercolor, 'mix', 1, '#fff');
    			$css .= "\n    #cmsMenu a.selected, #cmsMenu a:focus, #cmsMenu a:hover {color:#fff !important; text-shadow: 2px 2px 2px #000}";
    		} else {
    			$bgcolorActive = phpcolor_Adapter::changeColor($bordercolor, 'darken', 10);
    		}
    		
    		// цвят на буквите
    		$fontcolor = phpcolor_Adapter::changeColor($color, 'darken', 15);
    		
    		// ако след изчисленията не сме получили цвят за фон, пробваме да го изчислим по друг начин
    		if ($bgcolorActive == 'ffffff'){
    			$bgcolorActive = phpcolor_Adapter::changeColor($color, 'lighten', 40); 
    		}
    		
    		// Цвятове за линковете и h2 заглавията
    		$css .= "\n    #cmsNavigation .nav_item a { color: #{$fontcolor};}";
    		$css .= "\n    #cmsNavigation .nav_item a.sel_page {background-color: #{$bgcolorActive}; border: 1px solid #{$bordercolor}; color: #{$fontcolor};}";
    		$css .= "\n    a:hover, .eshop-group-button:hover .eshop-group-button-title a {color: #{$fontcolor};}";
    		$css .= "\n    .richtext h2 {background-color:#{$bgcolorActive} !important; padding: 5px 10px; border: 1px solid #{$bordercolor};}";
    	}
 
        if($css) {
            $tpl->append($css, 'STYLES');
        }
        
        // Добавяме дефолт темата за цветове
        $tpl->push('css/default-theme.css', 'CSS');

    }
    

    /**
     * Връща img-таг за заглавната картинка
     */
    function getHeaderImg()
    {
        if(!Mode::is('screenMode', 'narrow')) {
            for($i = 1; $i <=5; $i++) {
                $imgName = 'wImg' . $i;
                if($this->formRec->{$imgName}) {
                    $imgs[$i] = $this->formRec->{$imgName};
                }
            }

            if(count($imgs) > 1) {
                $conf = core_Packs::getConfig('core');
                $baner = "<div class=\"fadein\">"; 
                foreach($imgs as $iHash) {
                    $img = new thumb_Img(array($iHash, 1000, 288, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'large-no-change'));
                    $imageURL = $img->getUrl('forced');
                    $hImage = ht::createElement('img', array('src' => $imageURL, 'width' => 1000, 'height' => 288, 'alt' => $conf->EF_APP_TITLE, 'class' => 'headerImg', 'style' => $style));
                    $baner .= "\n{$hImage}";
                    $style = 'display:none;';
                }
                $baner .= "</div>";
                $baner = new ET($baner);
                $fadeTransition = $this->formRec->fadeTransition ? $this->formRec->fadeTransition : 1500;
                $fadeDelay = $this->formRec->fadeDelay ? $this->formRec->fadeDelay : 5000;
                $baner->append(".fadein { position:relative; display:block; max-height:100%; max-width:100%} .fadein img {position:relative; left:0; top:0;}", "STYLES");
                $baner->appendOnce("\n runOnLoad(function(){ $(function(){ $('.fadein img:gt(0)').hide(); setInterval(function(){ $('.fadein :first-child').css({position: 'absolute'})." .
                    "fadeOut({$fadeTransition}).next('img').css({position: 'absolute'}).fadeIn(1500).end().appendTo('.fadein');$('.fadein :first-child').css({position: 'relative'});}, {$fadeDelay});});});", 'SCRIPTS');
                
                $this->haveOwnHeaderImages = TRUE;

                return $baner;
            }

        } else {
            if ($this->formRec->nImg) {
                $imgs[1] = $this->formRec->nImg;
            }
            
        }
        
        $imgsCnt = count($imgs);
        
        if($imgsCnt) {
            
            // Ключа да започава от 1 до броя
            $imgs = array_combine(range(1, $imgsCnt), array_values($imgs));
            
            $img = $imgs[rand(1, count($imgs))];
            
            if ($img) {
                if(!Mode::is('screenMode', 'narrow')) {
                    $img = new thumb_Img(array($img, 1000, 288, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'large-no-change'));
                } else {
                    $img = new thumb_Img(array($img, 360, 104, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'large-no-change'));
                }
                $imageURL = $img->getUrl('forced');
                $this->haveOwnHeaderImages = TRUE;
            }
        }
         
        // Да покаже дефолт картинките, ако няма зададени
        if(!$imageURL) {
            $imageURL = sbf($this->getDefaultHeaderImagePath(), '');
        }

        $conf = core_Packs::getConfig('core');
        $hImage = ht::createElement('img', array('src' => $imageURL, 'alt' => $conf->EF_APP_TITLE, 'class' => 'headerImg'));
        
        return $hImage;
    }

    /**
     * Връща пътя до картинката за главата на публичната страница
     */
    private function getDefaultHeaderImagePath()
    {
    	if(!Mode::is('screenMode', 'wide')) {
    		$screen = '-narrow';
    	} else {
    		$screen = '';
    	}
    	
    	$lg = '-' . cms_Content::getLang();
    	
    	$path = "cms/img/header{$screen}{$lg}.jpg";
    	
    	if(!getFullPath($path)) {
    		$path = "cms/img/header{$screen}.jpg";
    		if(!getFullPath($path)) {
    			$path = "cms/img/header.jpg";
    			if(!getFullPath($path)) {
    				if(Mode::is('screenMode', 'wide')) {
    					$path = "cms/img/bgERP.jpg";
    				} else {
    					$path = "cms/img/bgERP-small.jpg";
    				}
    			}
    		}
    	}
        
        // Дали си носим картинките по друг начин?
        $conf = core_Packs::getConfig('core');
        if ($conf->EF_PRIVATE_PATH && file_exists($conf->EF_PRIVATE_PATH . "/" . $path)) {
            $this->haveOwnHeaderImages = TRUE;
        }

    	return $path;
    }
}