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

        $activatedOn = $data->rec->activatedOn ?? null;
        if (!isset($activatedOn)) {
            $activatedOn = $mvc->fetchField($masterId, 'activatedOn');
        }

        $changesCnt = 0;

        foreach ($details as $Detail) {
            $oldShowRevisions = $Detail->showDetailRevisions;
            $Detail->showDetailRevisions = true;
            $changesCnt += $Detail->count("#{$Detail->masterKey} = {$masterId} AND #state = 'rejected'");

            // Ръчно добавени редове (не редакции) след активирането - също се броят за промяна
            $newRowsCond = "#{$Detail->masterKey} = {$masterId} AND #state != 'rejected' AND #revisionPrevId IS NULL";
            if (!empty($activatedOn)) {
                $changesCnt += $Detail->count($newRowsCond . " AND (#revisionRootId = 0 OR #createdOn > '{$activatedOn}')");
            } else {
                $changesCnt += $Detail->count($newRowsCond . " AND #revisionRootId = 0");
            }
            $Detail->showDetailRevisions = $oldShowRevisions;
        }

        if (!$showRevisions && !$changesCnt) {
            return;
        }

        $url = getCurrentUrl();
        $requested = arr::make(Request::get(self::REQUEST_PARAM), true);
        $title = "Промени|* ({$changesCnt})";
        if ($showRevisions) {
            unset($requested[$containerId]);
            unset($url['Cid']);
            unset($url['Tab']);
            $icon = 'img/16/checked.png';
            $hint = 'Показване само на текущите редове|*!';
        } else {
            $requested[$containerId] = $containerId;
            $url['Cid'] = $containerId;
            $url['Tab'] = 'History';
            $icon = 'img/16/checkbox_no.png';
            $hint = 'Показване на промените след активирането на документа|*!';
        }

        if (countR($requested)) {
            $url[self::REQUEST_PARAM] = implode(',', $requested);
        } else {
            unset($url[self::REQUEST_PARAM]);
        }

        if (cls::haveInterface('doc_DocumentIntf', $mvc)) {
            $handle = $mvc->getHandle($masterId);
            $url['#'] = $handle;
            if (!$showRevisions) {
                $url['docId'] = $handle;
            }
        }

        $data->toolbar->addBtn($title, $url, "id=btnDetailRevisions_{$containerId},row=1", "ef_icon={$icon},title={$hint}");
    }
}
