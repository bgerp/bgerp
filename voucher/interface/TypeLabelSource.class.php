<?php


/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа voucher_Types
 *
 * @category  bgerp
 * @package   voucher
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see label_SequenceIntf
 *
 */
class voucher_interface_TypeLabelSource extends label_ProtoSequencerImpl
{
    /**
     * Връща наименованието на етикета
     *
     * @param int $id
     * @param string $series
     * @return string
     */
    public function getLabelName($id, $series = 'label')
    {
        $rec = $this->class->fetchRec($id);

        return "Ваучер: {$rec->name}";
    }


    /**
     * Връща масив с данните за плейсхолдерите
     *
     * @param int|NULL $objId
     * @param string $series
     *
     * @return array
     *               Ключа е името на плейсхолдера и стойностите са обект:
     *               type -> text/picture - тип на данните на плейсхолдъра
     *               len -> (int) - колко символа макс. са дълги данните в този плейсхолдер
     *               readonly -> (boolean) - данните не могат да се променят от потребителя
     *               hidden -> (boolean) - данните не могат да се променят от потребителя
     *               importance -> (int|double) - тежест/важност на плейсхолдера
     *               example -> (string) - примерна стойност
     */
    public function getLabelPlaceholders($objId = null, $series = 'label')
    {
        $placeholders = array();
        $placeholders['NUMBER'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['PERSON_NAME'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['QR_CODE_100'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['IMAGE_200_100'] = (object) array('type' => 'picture');
        $placeholders['VALID_TO'] = (object) array('type' => 'text', 'hidden' => true);

        return $placeholders;
    }


    /**
     * Връща масив с всички данни за етикетите
     *
     * @param int  $id
     * @param int  $cnt
     * @param bool $onlyPreview
     * @param stdClass $lRec
     * @param string $series
     *
     * @return array - масив от масив с ключ плейсхолдера и стойността
     */
    public function getLabelData($id, $cnt, $onlyPreview = false, $lRec = null, $series = 'label')
    {
        static $resArr = array();
        $lg = core_Lg::getCurrent();

        $key = $id . '|' . $cnt . '|' . $onlyPreview . '|' . $lg;

        if (isset($resArr[$key])) return $resArr[$key];

        expect($rec = voucher_Types::fetchRec($id));

        $query = voucher_Cards::getQuery();
        $query->where("#typeId = {$rec->id} AND #state != 'closed'");
        $query->orderBy('id', 'DESC');
        $recs = array_values($query->fetchAll());

        $arr = array();
        for ($i = 0; $i < $cnt; $i++) {
            $dRec = $recs[$i];

            $referrerName = ($dRec->referrer) ? crm_Persons::fetchField($dRec->referrer, 'name') : null;
            $res = array('NUMBER' => $dRec->number, 'QR_CODE_100' => $dRec->number, 'PERSON_NAME' => $referrerName, 'VALID_TO' => $dRec->validTo);
            $arr[] = $res;
        }

        $resArr[$key] = $arr;

        return $resArr[$key];
    }


    /**
     * Броя на етикетите, които могат да се отпечатат
     *
     * @param int $id
     * @param string $series
     * @return int
     */
    public function getLabelEstimatedCnt($id, $series = 'label')
    {
        return voucher_Cards::count("#typeId = {$id} AND #state != 'closed'");
    }
}
