<?php


/**
 * Драйвер за работа с .avif файлове.
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_webdrv_Avif extends fileman_webdrv_Image
{


    /**
     * Екшън за показване превю
     */
    public function act_Preview()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('view');

        // Манипулатора на файла
        $fileHnd = Request::get('id');

        if (!$fileHnd) {
            $fileHnd = Request::get('fileHnd');
        }

        expect($fileHnd);

        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);

        expect($fRec);

        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('view', $fRec);

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_Empty');

        // Вземаме височината и широчината
        $thumbWidthAndHeightArr = static::getPreviewWidthAndHeight();

        // Атрибути на thumbnail изображението
        $attr = array('class' => 'webdrv-preview', 'style' => 'margin: 0 auto 5px auto; display: block;');

        // Background' а на preview' то
        $bgImg = sbf('fileman/img/Preview_background.jpg');

        $style = Mode::is('screenMode', 'wide') ? "display: table-cell; vertical-align: middle;" : "";

        // Създаваме шаблон за preview на изображението
        $preview = new ET("<div id='imgBg' style='background-image:url(" . $bgImg . "); padding: 8px; height: 598px; display: table;width: 100%;'><div  style='margin: 0 auto;" . $style . "'>[#THUMB_IMAGE#]</div></div>");

        $preview->append(ht::createImg(array('src' => fileman_Download::getDownloadUrl($fileHnd))), 'THUMB_IMAGE');

        return $preview;
    }
}
