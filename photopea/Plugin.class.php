<?php


/**
 * Бутон за отваряне на файлове в Photopea.
 *
 * @category  vendors
 * @package   photopea
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class photopea_Plugin extends core_Plugin
{
    public static function on_AfterPrepareSingleToolbar($mvc, &$res, &$data)
    {
        $rec = $data->rec ?? null;
        if (is_object($rec) && $mvc->haveRightFor('single', $rec) && self::isSupported($rec)) {
            $toolbar = $data->toolbar ?? null;
            if ($toolbar) {
                $toolbar->addBtn('Photopea', array($mvc, 'Photopea', 'fileHnd' => $rec->fileHnd ?? ''),
                    'id=btn-photopea,ef_icon=img/16/palette.png,title=Редактиране на файла с Photopea',
                    array('target' => '_blank', 'order' => 29));
            }
        }
    }


    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action != 'photopea') {
            return;
        }

        $mvc->requireRightFor('single');
        $fileHnd = Request::get('fileHnd');
        expect($fileHnd);
        expect($rec = fileman_Files::fetchByFh($fileHnd));
        $mvc->requireRightFor('single', $rec);
        expect(self::isSupported($rec));

        // Браузърът предава байтовете на редактора, без Photopea да изтегля URL от bgERP.
        $downloadUrl = fileman_Download::getDownloadUrl($fileHnd, 1, 'handler', false);
        expect($downloadUrl);
        Mode::set('wrapper', 'page_Empty');
        $res = getTplFromFile('photopea/tpl/Editor.shtml');
        $res->replace(ht::escapeAttr($downloadUrl), 'FILE_URL');
        $res->replace(core_Type::escape($rec->name ?? ''), 'FILE_NAME');
        $res->replace(ht::createLink('Назад', array($mvc, 'single', $fileHnd)), 'BACK');
        $res->push('photopea/js/editor.js', 'JS');
        jquery_Jquery::run($res, 'photopeaInit();');
        $mvc->logRead('Отваряне с Photopea', $rec->id ?? null);

        return false;
    }


    protected static function isSupported($rec)
    {
        return in_array(fileman_Files::getExt($rec->name ?? ''),
            arr::make('jpg,jpeg,png,gif,bmp,webp,psd,psb,svg,pdf,ai,eps,tif,tiff,ico,avif,heic'));
    }
}
