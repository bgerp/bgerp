<?php


/**
 * Източник на етикети от CSV файлове
 *
 * @category  bgerp
 * @package   labels
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 * @title     Източник на етикети от CSV
 *
 * @since     v 0.1
 */
class label_CsvFileProxy extends label_ProtoSequencerImpl
{
    /**
     * Интерфейсни методи
     */
    public $interfaces = 'fileman_FileActionsIntf,label_SequenceIntf';


    /**
     * Интерфейсен метод на fileman_FileActionsIntf
     *
     * Връща масив с действия, които могат да се извършат с дадения файл
     *
     * @param stdClass $rec - Обект са данни от модела
     * @return array $arr - Масив с данните
     *               $arr['url'] - array URL на действието
     *               $arr['title'] - Заглавието на бутона
     *               $arr['icon'] - Иконата
     */
    public static function getActionsForFile($rec)
    {
        // Ако разширението е в допустимите, имамем права за добваня и имаме права за single' а на файла
        $res = null;
        $ext = fileman_Files::getExt($rec->name);

        $me = cls::get(get_called_class());
        $series = self::getLabelSeries($rec);

        if ($ext != 'csv' || !fileman_Files::haveRightFor('single', $rec)) return $res;

        foreach ($series as $series => $caption) {
            $templates = $me->getLabelTemplates($rec, $series, false);
            if(countR($templates)){
                if (label_Prints::haveRightFor('add', (object) array('classId' => $me->getClassid(), 'objectId' => $rec->id, 'series' => $series))) {
                    core_Request::setProtected(array('classId,objectId,series'));
                    $url = array('label_Prints', 'add', 'classId' => $me->getClassid(), 'objectId' => $rec->id, 'series' => $series, 'ret_url' => true);
                    $url = toUrl($url);
                    core_Request::removeProtected(array('classId,objectId,series'));

                    $res[$series]['url'] = $url;
                    $res[$series]['title'] = $caption;
                    $res[$series]['icon'] = '/img/16/price_tag_label.png';
                }
            }
        }

        return $res;
    }


    /**
     * Кои са достъпните шаблони за печат на етикети
     *
     * @param int $id                             - ид на обекта
     * @param string $series                      - серии
     * @param boolean $ignoreWithPeripheralDriver - да се избира ли периферния драйвер
     * @return array $res                         - списък със шаблоните
     */
    public function getLabelTemplates($id, $series = 'label', $ignoreWithPeripheralDriver = true)
    {
        return label_Templates::getTemplatesByClass($this, $series, $ignoreWithPeripheralDriver);
    }


    /**
     * Връща наличните серии за етикети от източника
     *
     * @param null|stdClass $rec
     * @return array
     */
    public static function getLabelSeries($rec = null)
    {
        return array('label' => 'Етикети');
    }


    /**
     * Връща нормализирано името на файловете
     */
    private static function getPlaceholderName($name)
    {
        $name = preg_replace('/\s+/', '_', $name);
        $name = str_replace('/', '_', $name);
        $name = str_replace('.', '_', $name);
        $name = str_replace(':', '_', $name);
        $name = str_replace('->', '_', $name);
        $name = trim($name, '_');

        return mb_strtoupper($name);
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
        if(isset($objId)){
            $fileRec = fileman_Files::fetch($objId);
            $fileData = fileman_Files::extractStr($fileRec->fileHnd);
            $columNames = csv_Lib::getCsvColNames($fileData);
            foreach ($columNames as $name){
                $placeholderName = self::getPlaceholderName($name);
                $placeholders[$placeholderName] = (object) array('type' => 'text', 'hidden' => true);
            }

            // Задаване на дефолтните данни на превюто
            $labelData = $this->getLabelData($objId, 1, true, null, $series);
            if (isset($labelData[0])) {
                foreach ($labelData[0] as $key => $val) {
                    if (!array_key_exists($key, $placeholders)) {
                        $placeholders[$key] = (object) array('type' => 'text');
                    }
                    $placeholders[$key]->example = $val;
                }
            }
        }

        return $placeholders;
    }


    /**
     * Кой е дефолтния шаблон за печат към обекта
     *
     * @param $id
     * @param string $series
     * @return int|null
     */
    public function getDefaultLabelTemplateId($id, $series = 'label')
    {
        return null;
    }


    /**
     * Връща наименованието на етикета
     *
     * @param int $id
     * @param string $series
     * @return string
     */
    public function getLabelName($id, $series = 'label')
    {
        $fileRec = fileman_Files::fetchRec($id);

        return $fileRec->name;
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
        $fileRec = fileman_Files::fetchRec($id);
        $fileData = fileman_Files::extractStr($fileRec->fileHnd);
        $rows = csv_Lib::getCsvRows($fileData);

        return countR($rows);
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

        if (isset($resArr[$key])) {

            return $resArr[$key];
        }

        $fileRec = fileman_Files::fetchRec($id);
        $ext = fileman_Files::getExt($fileRec->name);

        $rows = $columnNames = array();
        if($ext == 'csv'){
            $fileData = fileman_Files::extractStr($fileRec->fileHnd);
            $fileData = i18n_Charset::convertToUtf8($fileData);
            $rows = csv_Lib::getCsvRows($fileData);
            $columnNames = csv_Lib::getCsvColNames($fileData);
        }

        if($onlyPreview === false){
            core_App::setTimeLimit(round($cnt / 8, 2), false, 100);
        }

        $arr = array();
        for ($i = 0; $i <= $cnt - 1; $i++) {
            $rowArr = $rows[$i];
            $res = array();
            if(is_array($rowArr)){
                foreach ($rowArr as $index => $value){
                    $caption = $columnNames[$index];
                    $placeholder = self::getPlaceholderName($caption);
                    $res[$placeholder] = $value;
                }
                $arr[] = $res;
            }
        }

        $resArr[$key] = $arr;

        return $resArr[$key];
    }


    /**
     * Заглавие от източника на етикета
     *
     * @param mixed    $id
     * @return void
     */
    public static function getLabelSourceLink($id)
    {
        return cls::get('fileman_Files')->getFormTitleLink($id);
    }
}