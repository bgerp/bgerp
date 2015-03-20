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
        $form->FLD('nImg', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавна картинка за мобилен (360x104px)->Изображение 1");
        $form->FLD('title', 'varchar(20)', "caption=Заглавие на сайта->Текст");
        $form->FLD('titleColor', 'color_Type', "caption=Заглавие на сайта->Цвят");


        // Фон на страницата
        $form->FLD('bodyB', 'color_Type', "caption=Фон на страницата->Цвят");

        // Фон на менюто 
        $form->FLD('menuB', 'color_Type', "caption=Фон на менюто->Цвят");

        // Фон на избраното меню
        $form->FLD('menuSelB', 'color_Type', "caption=Фон на избраното меню->Цвят");
        
        // Фон на футъра 
        $form->FLD('footerB', 'color_Type', "caption=Фон на футъра->Цвят");
            
        // Връзки от страничната навигация
        $form->FLD('navLinkC', 'color_Type', "caption=Връзки от страничната навигация->Цвят");
        
        // Връзки от страничната навигация - избран
        $form->FLD('navLinkSelC', 'color_Type', "caption=Избрана връзка от страни->Цвят");

        // Фон на селектирания линк от страничната навигация
        $form->FLD('navLinkSelB', 'color_Type', "caption=Избрана връзка от страни->Фон");

        // Фон на заглавие h2 
        $form->FLD('h2B', 'color_Type', "caption=Фон на заглавие h2->Цвят");

        // Фон на заглавна лента на форма
        $form->FLD('formTitleB', 'color_Type', "caption=Фон на заглавна лента на форма->Цвят");
        
        // Rамка на заглавна лента на форма
        $form->FLD('formTitleBorderC', 'color_Type', "caption=Rамка на заглавна лента на форма->Цвят");

        // Фон на разделите във форма
        $form->FLD('formSectionB', 'color_Type', "caption=Фон на разделите във форма->Цвят");

        // Фон на бутоните във форма
        $form->FLD('formButtonB', 'color_Type', "caption=Фон на бутоните във форма->Цвят");
 
        // Рамка на бутоните във форма
        $form->FLD('formButtonBorderC', 'color_Type', "caption=Рамка на бутоните във форма->Цвят");
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
        
        // Добавяме дефолт темата за цветове
        $tpl->push('\n    css/default-theme.css', 'CSS');

        // Фон на страницата
        if($this->formRec->bodyB) {
            $css .= "\n    body {background-color:{$this->formRec->bodyB};}";
        }

        // Фон на менюто 
        if($this->formRec->menuB) {
            $css .= "\n    #cmsMenu {background-color:{$this->formRec->menuB};}";
        }

        // Фон на футъра 
        if($this->formRec->footerB) {
            $css .= "\n    #cmsBottom {background-color:{$this->formRec->footerB};}";
        }
            
        // Фон на линка на текущата страница
        if($this->formRec->menuSelB) {
            $css .= "\n    #cmsMenu a.selected {background-color:{$this->formRec->menuSelB};}";
        }

        // Цвят на линковете от страничната навигация
        if($this->formRec->navLinkC) {
            $css .= "\n    #cmsNavigation .nav_item a {color:{$this->formRec->navLinkC};}";
        }
        
        // Цвят на селектирания линк от страничната навигация
        if($this->formRec->navLinkSelC) {
            $css .= "\n    #cmsNavigation .sel_page a {color:{$this->formRec->navLinkSelC};}";
        }

        // Фон на селектирания линк от страничната навигация
        if($this->formRec->navLinkSelB) {
            $css .= "\n    #cmsNavigation .sel_page a {background-color:{$this->formRec->navLinkSelB};}";
        }

        // Фон на заглавие h2 
        if($this->formRec->h2B) {
            $css .= "\n    .richtext h2 {background-color:{$this->formRec->h2B};}";
        }

        // Фон и рамка на заглавна лента на форма
        if($this->formRec->h2B) {
            $css .= "\n    .richtext h2 {background-color:{$this->formRec->h2B};}";
        }

        // Фон на заглавна лента на форма
        if($this->formRec->formTitleB) {
            $css .= "\n    .vertical .formTitle {background-color:{$this->formRec->formTitleB};}";
        }
        
        // Rамка на заглавна лента на форма
        if($this->formRec->formTitleBorderC) {
            $css .= "\n    .vertical .formTitle {border: solid 1px {$this->formRec->formTitleBorderC};}";
        }

        // Фон на разделите във форма
        if($this->formRec->formSectionB) {
            $css .= "\n    .formGroup {background-color:{$this->formRec->formSectionB};}";
        }

        // Фон на бутоните във форма
        if($this->formRec->formButtonB) {
            $css .= "\n    .vertical form[method=post] input[type=submit], form[method=post] input:first-child[type=submit] {background-color:{$this->formRec->formButtonB};}";
        }

        // Рамка на бутоните във форма
        if($this->formRec->formButtonBorderC) {
            $css .= "\n    .vertical form[method=post] input[type=submit], form[method=post] input:first-child[type=submit] {border: solid 1px {$this->formRec->formButtonBorderC};}";
        }
 
        if($css) {
            $tpl->replace($css, 'STYLES');
        }

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
        } else {
            if ($this->formRec->nImg) {
                $imgs[1] = $this->formRec->nImg;
            }
            
        }
 
        if(count($imgs)) {
            $img = $imgs[rand(1, count($imgs))];
            if(!Mode::is('screenMode', 'narrow')) {
                $img = new thumb_Img(array($img, 1000, 288, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'large-no-change'));
            } else {
                $img = new thumb_Img(array($img, 360, 104, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'large-no-change'));
            }
            $imageURL = $img->getUrl('forced');
            $this->haveOwnHeaderImages = TRUE;
        }
         
        // Да покаже дефолт картинките, ако няма зададени
        if(!$imageURL) {
            $imageURL = sbf($this->getDefaultHeaderImagePath(), '');
        }

        $conf = core_Packs::getConfig('core');
        $hImage = ht::createElement('img', array('src' => $imageURL, 'alt' => $conf->EF_APP_TITLE, 'id' => 'headerImg'));
        
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