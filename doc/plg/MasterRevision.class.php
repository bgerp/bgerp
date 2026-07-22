<?php


/**
 * Добавя в тулбара на мастъра превключвател за ревизиите на детайлите му.
 * Работи с детайли, към които е прикачен doc_plg_DetailRevisions.
 *
 * @category bgerp
 * @package doc
 *
 * @copyright 2006 - 2026 Experta OOD
 * @license GPL 3
 *
 * @since v 0.1
 */
class doc_plg_MasterRevision extends core_Plugin
{
    /**
     * Име на параметъра, с който се избира показването на ревизиите
     */
    const REQUEST_PARAM = 'DetailRevisions';


    /**
     * Кои записи на посочения мастър са поискани с включени ревизии.
     * Request параметърът съдържа глобално уникалните containerId-та.
     */
    public static function getRequestedMasterIds($Master)
    {
        $Master = cls::get($Master);
        if (!$Master->getField('containerId', false)) {
            return array();
        }

        $containerIds = array();
        foreach (arr::make(Request::get(self::REQUEST_PARAM), true) as $containerId) {
            $containerId = (int) $containerId;
            if ($containerId) {
                $containerIds[$containerId] = $containerId;
            }
        }

        if (!countR($containerIds)) {
            return array();
        }

        $res = array();
        $query = $Master->getQuery();
        $query->in('containerId', $containerIds);
        $query->show('id');

        while ($rec = $query->fetch()) {
            $res[$rec->id] = $rec->id;
        }

        return $res;
    }


    /**
     * Детайлите на мастъра, които пазят ревизии
     */
    private static function getRevisionDetails($mvc)
    {
        $res = array();

        foreach (arr::make($mvc->details, true) as $alias => $class) {
            $Detail = $mvc->{$alias} ?? cls::get($class);
            if ($Detail->hasPlugin('doc_plg_DetailRevisions')) {
                $res[] = $Detail;
            }
        }

        return $res;
    }


    /**
     * След подготовка на тулбара на мастъра
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if (Mode::is('printing') || empty($data->rec->id) || empty($data->rec->containerId)) {
            return;
        }

        $details = self::getRevisionDetails($mvc);
        if (!countR($details)) {
            return;
        }

        $masterId = $data->rec->id;
        $containerId = $data->rec->containerId;
        $requestedIds = self::getRequestedMasterIds($mvc);
        $showRevisions = isset($requestedIds[$masterId]);
        $rejectedCnt = 0;

        foreach ($details as $Detail) {
            $oldShowRevisions = $Detail->showDetailRevisions;
            $Detail->showDetailRevisions = true;
            $rejectedCnt += $Detail->count("#{$Detail->masterKey} = {$masterId} AND #state = 'rejected'");
            $Detail->showDetailRevisions = $oldShowRevisions;
        }

        if (!$showRevisions && !$rejectedCnt) {
            return;
        }

        $url = getCurrentUrl();
        $requested = arr::make(Request::get(self::REQUEST_PARAM), true);
        if ($showRevisions) {
            unset($requested[$containerId]);
            $title = 'Текущи редове';
            $icon = 'img/16/application_view_list.png';
            $hint = 'Показване само на текущите редове';
        } else {
            $requested[$containerId] = $containerId;
            $title = "Промени|* ({$rejectedCnt})";
            $icon = 'img/16/bin_closed.png';
            $hint = 'Показване на промените след активирането на документа';
        }

        if (countR($requested)) {
            $url[self::REQUEST_PARAM] = implode(',', $requested);
        } else {
            unset($url[self::REQUEST_PARAM]);
        }

        if (cls::haveInterface('doc_DocumentIntf', $mvc)) {
            $url['#'] = $mvc->getHandle($masterId);
        }

        $data->toolbar->addBtn($title, $url, "id=btnDetailRevisions_{$containerId},row=1", "ef_icon={$icon},title={$hint}");
    }
}
