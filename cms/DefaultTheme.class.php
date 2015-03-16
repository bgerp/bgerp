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
     * Допълване на формата за домейна със специфични полета за кожата
     */
    public function addEmbeddedFields($form)
    {
        $form->FLD('wImg1', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Големи заглавни картинки (1000x288px)->Изображение 1");
        $form->FLD('wImg2', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Големи заглавни картинки (1000x288px)->Изображение 2");
        $form->FLD('wImg3', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Големи заглавни картинки (1000x288px)->Изображение 3");
        $form->FLD('wImg4', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Големи заглавни картинки (1000x288px)->Изображение 4");
        $form->FLD('wImg5', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Големи заглавни картинки (1000x288px)->Изображение 5");
        $form->FLD('nImg', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Малки заглавни картинки (360x104px)->Изображение 1");
        $form->FLD('nImg2', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Малки заглавни картинки (360x104px)->Изображение 2");
        $form->FLD('nImg3', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Малки заглавни картинки (360x104px)->Изображение 3");
        $form->FLD('nImg4', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Малки заглавни картинки (360x104px)->Изображение 4");
        $form->FLD('nImg5', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Малки заглавни картинки (360x104px)->Изображение 5");
        $form->FLD('title', 'varchar(20)', "caption=Заглавие на сайта->Текст");
        $form->FLD('titleColor', 'color_Type', "caption=Заглавие на сайта->Цвят");

    }

    
    public function prepareWrapper($tpl)
    {
        if($this->formRec->title) {
            $tpl->replace("<span style='color:{$this->formRec->titleColor}'>" . $this->formRec->title . "</span>", 'CORE_APP_NAME');
        }
    }

    function getHeaderImagePath()
    {
        
        if($this->formRec->img1) {
            if(!Mode::is('screenMode', 'narrow')) {
                $prefix = 'wImg';
            } else {
                $prefix = 'nImg';
            }

            for($i = 1; $i <=5; $i++) {
                $imgName = $prefix . $i;
                if($this->formRec->{$imgName}) {
                    $imgs[$i] = $this->formRec->{$imgName};
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
            }            

        }
            
        return $imageURL;
    }

}