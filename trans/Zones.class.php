<?php


/**
 * Модел "Транспортни зони"
 *
 *
 * @category  bgerp
 * @package   survey
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_Zones extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = "Транспортни зони";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, trans_Wrapper";


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "zoneId, countryId, pCode, createdOn, createdBy";


    /**
     * Ключ към core_Master
     */
    public $masterKey = 'zoneId';


    /**
     * Единично заглавие
     */
    public $singleTitle = "Зона";


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('zoneId', 'key(mvc=trans_FeeZones, select=name)', 'caption=Зона, recently, mandatory');
        $this->FLD('countryId', 'key(mvc = drdata_Countries, select = letterCode2)', 'caption=Държава, mandatory');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode');
        $this->setDbUnique("countryId, pCode");
    }


    /**
     * Добавяне на бутон за изчисление
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn("Изчисление", array("trans_Zones", "calcFee"), "ef_icon=img/16/arrow_out.png, title=Изчисляване на разходи по транспортна зона");
    }


    /**
     * Изчисление на транспортни разходи
     */
    public function act_calcFee()
    {
        //Дос на потребителите
        requireRole('admin, ceo');

        //Тестовни примери
        /*
         * $a[] = trans_Fees::calcFee(5, 262, 8000, 0);
         * $a[] = trans_Fees::calcFee(5, 262, 8000, -1);
         * $a[] = trans_Fees::calcFee(5, 262, 8000, 1000000);
         * $a[] = trans_Fees::calcFee(5, 262, 8000, 400);
         * $a[] = trans_Fees::calcFee(5, 262, 8000, 2000);
         * $a[] = trans_Fees::calcFee(5, 262, 8000, "Chris");
         */

        // Вземаме съответстващата форма на този модел
        $form = self::getForm();
        $form->FLD('totalWeight', 'double(Min=0)', 'caption=Тегло за изчисление,recently');
        $form->FLD('singleWeight', 'double(Min=0)', 'caption=Брой за връщане');

        // Премахваме полето "name", защото то тррябва да е резултат от теста, а не да се въвежда
        unset($form->fields['zoneId']);

        // Въвеждаме формата от Request (тази важна стъпка я бяхме пропуснали)
        $form->input();
        $form->setDefault('singleWeight', 1);
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            try {
                $result = trans_Fees::calcFee($rec->countryId, $rec->pCode, $rec->totalWeight, $rec->singleWeight);
                $zoneName = trans_FeeZones::getVerbal($result[2], 'name');
                $form->info = "Цената за " . $rec->singleWeight . " на " . $rec->totalWeight . " броя от този пакет ще струва ". round($result[1], 4).
                    ", a всички ".  $rec->totalWeight . " ще струват " . round($result[0], 4) . ". Пратката попада в " . $zoneName ;

            } catch(core_exception_Expect $e) {
                $form->setError("zoneId, countryId", "Не може да се изчисли по зададените данни, вашата пратка не попада в никоя зона");
            }
        }

        $form->title = 'Пресмятане на налва';
        $form->toolbar->addSbBtn('Запис');
        return $this->renderWrapping($form->renderHTML());
    }


    /**
     * Връща името на транспортната зона според държавата, усложието на доставката и п.Код
     * @param int       $countryId      id на съотверната държава
     * @param string    $pCode          пощенски код
     * @return array['zoneId']          id на намерената зона
     * @return array['zoneName']        име на намерената зона
     * @return array['deliveryTermId']  Условие на доставка
     */
    public static function getZoneIdAndDeliveryTerm($countryId, $pCode)
    {
        //Обхождане на trans_zones базата и намиране на най-подходящата зона
        $query = self::getQuery();
        $query->where(array('#countryId = [#1#]', $countryId));
        $bestSimilarityCount = 0;
        while($rec = $query->fetch()) {

            $similarityCount = self::strNearPCode((string)$pCode, $rec->pCode);

            if ($similarityCount > $bestSimilarityCount) {
                $bestSimilarityCount = $similarityCount;
                $bestZone = $rec;
            }
        }

        //Намиране на името на намерената зона
        $zoneName = trans_FeeZones::getVerbal($bestZone->zoneId, 'name');

        //Намиране на условието на доставка на зоната
        $zoneDeliveryTerm = trans_FeeZones::getVerbal($bestZone->zoneId, 'deliveryTermId');

        return array('zoneId' => $bestZone->zoneId, 'zoneName' => $zoneName, 'deliveryTermId' => $zoneDeliveryTerm);
    }


    /**
     * Намиране на поредни еднакви знака в 2 кода
     */
    private static function strNearPCode($pc1, $pc2)
    {
        // Finding the smaller length of the two
        $cycleNumber = min(strlen($pc1), strlen($pc2));

        for($i= 0; $i<$cycleNumber; $i++)
        {
            if($pc1{$i} != $pc2{$i}) {
                return $i;
            }
        }
        return strlen($pc1);
    }
}