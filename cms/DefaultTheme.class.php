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
        } else {
            $imgs[1] = $this->formRec->nImg;
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