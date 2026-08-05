<?php


/**
 * Клас 'doc_plg_SingleActiveDoc'
 *
 * Плъгин, който позволява само 1 активен документ в дадена група едновременно -
 * при активиране (или възстановяване/повторно отваряне) на документ
 * автоматично затваря (state=closed) останалите активни документи от същата
 *
 * Групата на документа се определя от `$mvc->getSingleActiveGroupIds($rec)` -
 * връща масив от id-та на ВСИЧКИ документи в групата (вкл. текущия). За
 * най-честия случай - група по едно поле - е достатъчно мениджърът да зададе
 * `$singleActiveDocRefField` (@see cat_DisassemblyBoms - 'productId'), а
 * дефолтната имплементация на плъгина върши останалото. Мениджъри с по-сложна
 * група си дефинират собствен `getSingleActiveGroupIds_($rec)`
 * (@see cat_Boms - по productId и type).
 * Ако няма нито едно от двете, групата е празна и нищо не се затваря.
 *
 * Плъгинът трупа записите, станали активни/спрели да са активни в текущия
 * request директно в мениджъра ($mvc->_activatedRecs / $mvc->_stoppedRecs),
 * и при shutdown праща по едно събитие за всеки от тях, за да могат
 * консуматори с допълнителна бизнес логика да реагират без да пазят
 * собствени опашки и собствен on_Shutdown:
 * - `AfterSingleActiveDocActivated($mvc, $rec)` - за _activatedRecs
 * - `AfterSingleActiveDocStopped($mvc, $rec)`   - за _stoppedRecs
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_plg_SingleActiveDoc extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setPartIfNot($mvc, '_activatedRecs', array());
        setPartIfNot($mvc, '_stoppedRecs', array());
    }


    /**
     * Функция, която прихваща след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        $rec = $mvc->fetchRec($rec);
        $mvc->_activatedRecs[$rec->id] = $rec;
    }


    /**
     * Реакция при възстановяване на оттеглен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterRestore($mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if ($rec->state == 'active') {
            $mvc->_activatedRecs[$rec->id] = $rec;
        }
    }


    /**
     * След промяна на състоянието (напр. затваряне на активен документ, или
     * повторно отваряне на затворен документ)
     */
    public static function on_AfterChangeState($mvc, $rec, $state)
    {
        $rec = $mvc->fetchRec($rec);
        if ($state == 'closed' && $rec->brState == 'active') {
            $mvc->_stoppedRecs[$rec->id] = $rec;
        } elseif ($state == 'active' && $rec->brState == 'closed') {
            $mvc->_activatedRecs[$rec->id] = $rec;
        }
    }


    /**
     * Реакция при оттегляне на документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterReject($mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if ($rec->brState == 'active') {
            $mvc->_stoppedRecs[$rec->id] = $rec;
        }
    }


    /**
     * Дефолтна имплементация на getSingleActiveGroupIds($rec) - групата са
     * всички документи с еднакви стойности във всички полета, изброени в
     * $mvc->singleActiveDocRefField (@see cat_DisassemblyBoms - 'productId',
     * cat_Boms - 'productId,type'). Мениджъри, при които групата не се свежда
     * до равенство по полета, си дефинират собствен
     * getSingleActiveGroupIds_($rec)
     */
    public static function on_AfterGetSingleActiveGroupIds($mvc, &$res, $rec)
    {
        if (!isset($res)) {
            if (empty($mvc->singleActiveDocRefField)) return;

            $query = $mvc->getQuery();
            foreach (arr::make($mvc->singleActiveDocRefField, true) as $refField) {

                // Празна стойност не групира - иначе биха се затворили всички
                // останали документи с празно същото поле
                if (empty($rec->{$refField})) return;

                $query->where(array("#{$refField} = '[#1#]'", $rec->{$refField}));
            }
            $query->show('id');

            $res = arr::extractValuesFromArray($query->fetchAll(), 'id');
        }
    }


    /**
     * За всеки активиран в текущия request документ - затваря останалите
     * активни документи от групата му и известява мениджъра със събитието
     * `AfterSingleActiveDocActivated`. За всеки спрял да е активен
     * документ - със събитието `AfterSingleActiveDocStopped`.
     *
     * @param core_Mvc $mvc
     */
    public static function on_Shutdown($mvc)
    {
        if (!empty($mvc->_activatedRecs)) {
            foreach ($mvc->_activatedRecs as $rec) {
                $groupIds = $mvc->getSingleActiveGroupIds($rec);

                if (is_array($groupIds) && countR($groupIds)) {
                    $query = $mvc->getQuery();
                    $query->where("#state = 'active' AND #id != {$rec->id}");
                    $query->in('id', $groupIds);

                    $idCount = 0;
                    while ($otherRec = $query->fetch()) {
                        $otherRec->state = 'closed';
                        $otherRec->brState = 'active';
                        $otherRec->modifiedOn = dt::now();
                        $mvc->save_($otherRec, 'state,brState,modifiedOn');
                        $mvc->logWrite('Затваряне при активиране на друг документ от групата', $otherRec->id);

                        doc_DocumentCache::cacheInvalidation($otherRec->containerId);
                        $idCount++;
                    }

                    if ($idCount) {
                        $title = mb_strtolower($mvc->title);
                        core_Statuses::newStatus("|Затворени|* |{$title}|*: {$idCount}");
                    }
                }

                $mvc->invoke('AfterSingleActiveDocActivated', array($rec));
            }

            unset($mvc->_activatedRecs);
        }

        if (!empty($mvc->_stoppedRecs)) {
            foreach ($mvc->_stoppedRecs as $rec) {
                $mvc->invoke('AfterSingleActiveDocStopped', array($rec));
            }

            unset($mvc->_stoppedRecs);
        }
    }
}
