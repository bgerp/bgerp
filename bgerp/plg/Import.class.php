<?php



/**
 * Плъгин за импорт на данни. Импортирват се csv-данни с помоща на драйвър
 * имплементиращ интерфейса bgerp_ImportIntf.
 * Плъгина добавя бутон за импортиране към мениджъра където е закачен
 * За да се импортират csv данни се минава през няколко стъпки с помощна
 * на експерта (@see expert_Expert).
 *
 * Целта е да се уточни:
 * 1. Кой драйвър ще се използва (клас имплементиращ bgerp_ImportIntf)
 * 2. Как се въвеждат csv данните с ъплоуд на файл или с copy & paste
 * 3. Какви са разделителят, ограждането и първия ред на данните
 * 4. Кои колони от csv-тo на кои полета от мениджъра отговарят.
 *
 * След определянето на тези данни драйвъра се грижи за правилното импортиране
 *
 * Мениджъра в който ще се импортира и кои полета от него ще бъдат попълнени
 * се определя от драйвъра.
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_Import extends core_Plugin
{
    
    /**
     * Работен кеш
     */
    protected static $cache;
    
    
    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    function on_AfterDescription(core_Mvc $mvc)
    {
        // Проверка за приложимост на плъгина към зададения $mvc
        if(!static::checkApplicability($mvc)) return;
    }
    
    /**
     * Проверява дали този плъгин е приложим към зададен мениджър
     *
     * @param core_Mvc $mvc
     * @return boolean
     */
    protected static function checkApplicability($mvc)
    {
        // Прикачане е допустимо само към наследник на cat_Products ...
        if (!$mvc instanceof core_Manager) {
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Обработка на ListToolbar-a
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Добавяне на бутон за импортиране, ако има инсталирани драйвъри
        if($mvc->haveRightFor('import')){
            $url = array($mvc, 'import', 'ret_url' => TRUE);
            $data->toolbar->addBtn('Импорт', $url, NULL, 'row=2,ef_icon=img/16/import.png,title=Импортиране на ' . mb_strtolower($mvc->title));
        }
    }
    
    
    /**
     * Преди всеки екшън на мениджъра-домакин
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        if($action == 'import'){
            $mvc->requireRightFor('import');
            
            // Подготвяме експерта
            $exp = cls::get('expert_Expert', array('mvc' => $mvc));
            $content = static::solveExpert($exp);
            
            if($content == 'SUCCESS') {
                
                // Извличаме уточнените вече стойностти на параметритр
                $driverId = $exp->getValue("#driver");
                $csvData = $exp->getValue("#csvData");
                $delimiter = $exp->getValue("#delimiter");
                $enclosure = $exp->getValue("#enclosure");
                $firstRow = $exp->getValue("#firstRow");
                $onExist = $exp->getValue("#onExist");
                
                // Намиране на коя колона от csv-то на кое поле съответства
                $Driver = cls::get($driverId, array('mvc' => $mvc));
                $fields = $Driver->getFields();
                
                foreach($fields as $name => $arr){
                    $fields[$name] = $exp->getValue("#col{$name}");
                }
                
                // Преобразуване на csv-то в масив, по зададените параметри
                $rows = csv_Lib::getCsvRows($csvData, $delimiter, $enclosure, $firstRow, $cols);
      
                if($mvc->haveRightFor('import')){
                    
                    Mode::push('onExist', $onExist);
                    // Импортиране на данните от масива в зададените полета
                    $msg = $Driver->import($rows, $fields);
                    Mode::pop('onExist');
                    
                    // Редирект кум лист изгледа на мениджъра в който се импортира
                    redirect(array($mvc, 'list'), FALSE, $msg);
                }
            }
            
            if($content == 'DIALOG') {
                $content = $exp->getResult();
            }
            
            if($content == 'FAIL') {
                if($exp->onFail) {
                    $content = $mvc->onFail($exp);
                } else {
                    $exp->setRedirect();
                    setIfNot($exp->midRes->alert, $exp->message, 'Не може да се достигне крайната цел');
                    $content = $exp->getResult();
                }
            }
            
            $tpl = $mvc->renderWrapping($content);
            
            return FALSE;
        }
    }
    
    
    /**
     * Зарежда данни от посочен CSV файл, като се опитва да ги конвертира в UTF-8
     */
    static function getFileContent($fh)
    {
        $csv = fileman_Files::getContent($fh);
        $csv = i18n_Charset::convertToUtf8($csv, array('UTF-8', 'CP1251'));
        
        return $csv;
    }
    
    
    /**
     * Подготовка на експерта за импортирането (@see expert_Expert)
     * @param expert_Expert $exp
     * @return string $res
     */
    public static function solveExpert(expert_Expert &$exp)
    {
        $exp->functions['getfilecontentcsv'] = 'bgerp_plg_Import::getFileContent';
        $exp->functions['getcsvcolnames'] = 'csv_Lib::getCsvColNames';
        $exp->functions['getimportdrivers'] = 'bgerp_plg_Import::getImportDrivers';
        $exp->functions['verifydata'] = 'bgerp_plg_Import::verifyInputData';
        bgerp_plg_Import::$cache = get_class($exp->mvc);
        
        // Избиране на драйвър за импортиране
        $exp->DEF('#driver', 'int', 'caption=Източник,input,mandatory');
        $exp->rule("#driver", "key(getimportdrivers())", "count(getimportdrivers()) == 1");
        $exp->OPTIONS("#driver", "getimportdrivers()");
        $exp->question("#driver", tr("Моля, изберете източник") . ":", TRUE, 'title=' . tr('Какъв е източникът на данни') . '?');
        
        // Избор как ще се въведат данните с copy & paste или с ъплоуд
        $exp->DEF('#source=Начин', 'enum(csvFile=Файл със CSV данни,csv=Copy&Paste на CSV данни)', 'maxRadio=5,columns=1,mandatory');
        $exp->ASSUME('#source', '"csvFile"');
        $exp->question("#source", tr("Моля, посочете начина за вкарването на данните") . ":", TRUE, 'title=' . tr('По какъв начин ще се въведат данните') . '?');
        
        // Поле за ръчно въвеждане на csv данни
        $exp->DEF('#csvData=CSV данни', 'text(1000000)', 'width=100%,mandatory');
        $exp->question("#csvData,#delimiter,#enclosure,#firstRow,#onExist", tr("Моля, поставете данните, и посочете формата на данните") . ":", "#source == 'csv'", 'title=' . tr('Въвеждане на CSV данни за импорт, и уточняване на разделителя и ограждането'));
        
        // Поле за ъплоуд на csv файл
        $exp->DEF('#csvFile=CSV файл', 'fileman_FileType(bucket=csvContacts)', 'mandatory');
        $exp->question("#csvFile,#delimiter,#enclosure,#firstRow,#onExist", tr("Въведете файл в CSV формат, и посочете формата на данните") . ":", "#source == 'csvFile'", 'title=' . tr('Въвеждане на данните от файл, и уточняване на разделителя и ограждането'));
        $exp->rule("#csvData", "getFileContentCsv(#csvFile)");
        
        // Полета за избиране на Разделител, ограждане и вида на първия ред
        $exp->DEF('#delimiter=Разделител', 'varchar(3,size=3)', array('value' => ''), 'placeholder=автоматично');
        $exp->SUGGESTIONS("#delimiter", array('' => '', ',' => ',', ';' => ';', ':' => ':', '|' => '|', 'tab' => 'tab'));
        $exp->DEF('#enclosure=Ограждане', 'varchar(1,size=3)', array('value' => ''), 'placeholder=автоматично');
        $exp->SUGGESTIONS("#enclosure", array('' => '', '"' => '"', '\'' => '\''));
        $exp->DEF('#firstRow=Първи ред', 'enum(columnNames=Имена на колони,data=Данни)', 'mandatory');
        $exp->DEF('#onExist=При съвпадение', 'enum(skip=Пропускане, update=Обновяване, duplicate=Дублиране)', 'mandatory');
        
        // Проверка дали броя на колоните отговаря навсякъде
        $exp->rule("#csvColumnsCnt", "count(getCsvColNames(#csvData,#delimiter,#enclosure, 0, 1))");
        $exp->WARNING(tr("Възможен е проблем с формата на CSV данните, защото е открита само една колона"), '#csvColumnsCnt == 1');
        $exp->ERROR(tr("Има проблем с формата на CSV данните") . ". <br>" . tr("Моля проверете дали правилно сте въвели данните и разделителя"), '#csvColumnsCnt < 1');
        
        $driverId = $exp->getValue('#driver');
        
        if($driverId){
            $Driver = cls::get($driverId , array('mvc' => $exp->mvc));
            $fieldsArr = $Driver->getFields();
            
            // Поставяне на възможност да се направи мачване на 
            // полетата от модела и полетата от csv-то
            foreach($fieldsArr as $name => $fld) {
            	$type = ($fld['type']) ? $fld['type'] : 'int';
                $exp->DEF("#col{$name}={$fld['caption']}", $type, "{$fld['mandatory']}");
                if(!isset($fld['notColumn'])){
                	$exp->OPTIONS("#col{$name}", "getCsvColNames(#csvData,#delimiter,#enclosure,1)");
                	$exp->ASSUME("#col{$name}", "-1");
                }
                
                $qFields .= ($qFields ? ',' : '') . "#col{$name}";
            }
            
            $exp->question($qFields, tr("Въведете съответстващите полета за \"{$exp->mvc->title}\"") . ":", TRUE, 'label=lastQ,title=' . tr('Съответствие между полетата на източника и списъка'));
            
            $res = $exp->solve("#driver,#source,#delimiter,#enclosure,#firstRow,#onExist,#lastQ");
        } else {
            $res = $exp->solve("#driver,#source,#delimiter,#enclosure,#firstRow,#onExist");
        }
        
        // Връщане на резултата
        return $res;
    }
    
    
    /**
     * Функция връщаща опции с всички драйвери които могат да се прикачват
     * към мениджъра
     * @return array $options - масив с възможни драйвъри
     */
    public static function getImportDrivers()
    {
        $options = array();
        $Drivers = core_Classes::getOptionsByInterface('bgerp_ImportIntf');
        
        foreach ($Drivers as $id => $driver){
            $Driver = cls::get($id);
            
            if($Driver->isApplicable(bgerp_plg_Import::$cache)){
                $options[$id] = $Driver->title;
            }
        }
        
        return $options;
    }
}
