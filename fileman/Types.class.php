<?php



/**
 * Клас 'fileman_Types' -
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_Types extends core_Manager {
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,broker,designer,exvan';
    
    
    /**
     * @todo Чака за документация...
     */
    var $tableName = 'file_types';
    
    
    /**
     * @todo Чака за документация...
     */
    var $className = 'FileTypes';
    
    
    /**
     * Заглавие на модула
     */
    var $title = 'Файлови типове';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Общ тип на файла
                $this->FLD("genericType", "varchar", 'caption=Тип');
        $this->FLD("title", "varchar", 'caption=Заглавие');
        $this->FLD("trid", "varchar", 'caption=TrID');
        $this->FLD("extension", "varchar(16)", 'caption=Разширение,notNull');
        $this->FLD("vendor", "varchar", 'caption=Доставчик');
        $this->FLD("info", "richtext", 'caption=Информация');
        $this->FLD("icon", "varchar(10)", 'caption=Икона');
        $this->FLD("commonRate", "varchar(10)", 'caption=Известност');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm($invoker, $data)
    {
        $data->form->addAttr('genericType,title,trid,extension,vendor,icon', 'width:100%');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getRecTitle($rec)
    {
        return $rec->title ? $rec->title : $rec->trid;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getTagInfo($xmlAsArr, $tag, $attr = NULL)
    {
        foreach($xmlAsArr as $e) {
            if($e['tag'] == $tag) {
                if($attr) {
                    if($e['attributes'][$attr]) {
                        return $e['attributes'][$attr];
                    }
                } else {
                    if($e['value']) {
                        return $e['value'];
                    }
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getGenericType($trId, $ext)
    {
        $gt['presentation'] = array('presentation'=>5, 'presentations'=>3);
        $gt['cad'] = array('cad'=>3, 'solidworks'=>2, 'autocad'>2 , 'assembly' => 3, 'solid'=>3);
        $gt['drawing'] = array('draw'=>3, 'drawing'=>5, 'vector'=>3 , 'solidworks'=>2, 'autocad'>2 , 'coreldraw document' => 6);
        $gt['game'] = array('save game'=>5, 'game'=>3);
        $gt['dosexe'] = array('dos com'=>5, 'dos exe'=>5, 'dos executable'=>5);
        $gt['winexe'] = array('win/dos executable'=>5, 'win exe'=>5, 'win executable'=>5, 'Win32' => 3, 'executable' => 3);
        $gt['macexe'] = array('mac executable'=>5, 'mac' =>3, 'macintosh'=>3, 'executable'=>3);
        $gt['linexe'] = array('linux executable'=>5, 'linux' =>3, 'executable'=>3);
        $gt['executable'] = array('executable'=>5, 'driver'=>4, 'pugin'=>4, 'dll'=>3, 'component' => 3, 'dll' =>4, 'exe' => 4);
        $gt['binary'] = array('compiled'=>4, 'binary'=>3, 'object code'=>4);
        $gt['xml'] = array('xml'=>4);
        $gt['text'] = array('text'=>4);
        $gt['spreadsheet'] = array('spreadsheet'=>5, 'exel' => 1);
        $gt['code'] = array('script' => 5, 'source code' => 5, 'code'=>1);
        $gt['3d'] = array('3d'=>5);
        $gt['document'] = array('document'=>5, 'rich text format' => 6);
        $gt['compressed'] = array('compressed'=>3, 'iso image' => 6);
        $gt['bitmap'] = array('image'=>2, 'bitmap'=>5, 'raster'=>4);
        $gt['audio'] = array('audio'=>5, 'sound'=>3, 'voice'=>2, 'song'=>3, 'mp3' => 3, 'farandole composer'=>4);
        $gt['video'] = array('video'=>5, 'media'=>3, 'movie'=>3, 'animation'=>3);
        $gt['map'] = array('map'=>3, 'google earth' => 3, 'gps' =>3);
        $gt['database'] = array('index'=>3, 'database' => 5);
        $gt['data'] = array('data' => 3, 'archive' => 2, 'syntax file'=>3, 'dictionary' => 3, 'disk image' => 6, 'data image' => 6, 'iso image' => 6, 'tape image' => 6, 'cd image' => 6, 'os image' => 6);
        $gt['font'] = array('font'=>3);
        $gt['certificate'] = array('certificate'=>3);
        
        $text = strtolower(" " . $trId . " " . $ext . " ");
        $text = preg_replace('/[^a-z]+/', ' ', "{$text}");
        
        $max->type = 'other';
        $max->points = 0;
        
        foreach($gt as $id => $type) {
            $pt = 0;
            
            foreach($type as $keyWord => $points) {
                if(strpos($text, ' ' . $keyWord . ' ') !== FALSE) {
                    $pt += $points;
                }
            }
            
            if($pt > $max->points) {
                $max->points = $pt;
                $max->type = $id;
            }
        }
        
        return $max->type;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_ExtractTrID()
    {
        set_time_limit(300);
        cls::load('core_Os');
        // Къде е директорията с XML-ите на trID
                $dir = "c:\\trid\\xml";
        
        $dh = opendir($dir);
        
        while (false !== ($filename = readdir($dh))) {
            $files[] = $filename;
        }
        
        // Парсираме всеки файл
                foreach($files as $fileName) {
            if(strlen($fileName) <= 2) continue;
            $data = file_get_contents($dir . "\\" . $fileName);
            $parser = xml_parser_create('UTF-8');
            xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
            xml_parse_into_struct($parser, $data, $vals, $index);
            xml_parser_free($parser);
            $extList = $this->getTagInfo($vals, 'EXT');
            $extArr = explode('/', $extList);
            
            foreach($extArr as $ext) {
                
                $extl = strtolower($ext);
                
                $trId = $this->getTagInfo($vals, 'FILETYPE');
                $genericType = $this->getGenericType($trId, $ext);
                $info = $this->getTagInfo($vals, 'REM') . " " . $this->getTagInfo($vals, 'REFURL');
                
                $tpl = '';
                
                $tpl = @file_get_contents("c:\\trid\\templates\\{$genericType}-{$extl}.svg");
                
                if(!$tpl) {
                    $tpl = @file_get_contents("c:\\trid\\templates\\{$genericType}.svg");
                }
                
                if($tpl) {
                    
                    $tpl = str_replace("[#ext#]", strtoupper($ext), $tpl);
                    
                    $width = 32;
                    $x = 15.873898;
                    $len = 0;
                    $x1 = 0;
                    
                    $len = (strlen($ext) - 3) * 9.6;
                    
                    $width = $width + $len;
                    $x = $x - $len;
                    $x1 = $x + 4;
                    $tpl = str_replace("[#x#]", $x, $tpl);
                    $tpl = str_replace("[#x1#]", $x1, $tpl);
                    $tpl = str_replace("[#width#]", $width, $tpl);
                    
                    if($genericType == 'document' || $genericType == 'code' || $genericType == 'text') {
                        
                        $dark = cls::get('color_Color');
                        $dark->randInit(20, 100);
                        
                        $fillColor = $dark->getCSS();
                        $strokeColor = $dark->getCSS();
                        $textColor = '#ffffff';
                    } else {
                        $dark = cls::get('color_Color');
                        $dark->randInit(6, 20);
                        $light = cls::get('color_Color');
                        $light->randInit(190, 250);
                        $fillColor = $light->getCSS();
                        $strokeColor = $dark->getCSS();
                        $textColor = $dark->getCSS();
                    }
                    
                    $tpl = str_replace("[#fill#]", $fillColor, $tpl);
                    $tpl = str_replace("[#stroke#]", $strokeColor, $tpl);
                    $tpl = str_replace("[#textColor#]", $textColor, $tpl);
                    
                    // Генерираме името на новия файл
                                        $newFile = str::utf2ascii("c:\\trid\\svg\\{$genericType}-{$extl}.svg");
                    
                    // Записваме новия файл
                                        $handle = fopen($newFile, "w");
                    $numbytes = fwrite($handle, $tpl);
                    fclose($handle);
                    
                    // Генерираме икона 48х48
                                        $iconFile = "c:\\trid\\icons\\{$genericType}-{$extl}";
                    $command = "\"C:\Program Files\Inkscape\inkscapec.exe\" \"{$newFile}\" --export-png={$iconFile}.png -w48 -h48 --export-background-opacity=1.0";
                    
                    //    OS::exec($command); 
                    
                    
                    // OS::exec( "\"C:\Program Files\ImageMagick-6.4.8-Q16\convert.exe\" {$iconFile}.png {$iconFile}.gif" );
                
                }
                
                $types[] = $ext . " | " . $trId . " | " . $genericType . " | " . $info ;
            }
        }
        
        asort($types);
        
        foreach($types as $line) {
            $html .= $line . "<br>";
        }
        
        return $html;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function httpImport()
    {
        
        $form = $this->getForm();
        $form->FNC('csv', 'text', 'caption=CSV');
        $form->setField('type,title,trid,extension,vendor,info,icon', 'input=none');
        $rec = $form->input();
        
        if($rec->cmd == 'import') {
            
            // Изтриваме сегашните данни
                        $this->delete("#title IS NULL");
            
            $lines = explode("\n", $rec->csv);
            
            foreach($lines as $l) {
                $data = explode('|', $l);
                
                $rec = NULL;
                $rec->genericType = $this->getGenericTypeId($data[0]);
                $rec->trid = trim($data[1]);
                $rec->extension = trim($data[2]);
                $rec->info = trim($data[3]);
                
                $this->save($rec);
            }
            
            return new redirect(array('FileTypes'));
        }
        
        $form->toolbar->addSbBtn('Импорт', 'import');
        
        return $form->get();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function httpTest() {
        
        $outputs = shell_exec("file ../uploads/Tasche_PhilHenson_410.ai");
        print_r($outputs);
        die;
        
        $rec = $this->fetch($this->getTrId("../uploads/Email_51131272.eml"));
        
        return $rec->trid;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getTrId($file) {
        
        static $tridDB;
        
        if(!$fileTypesDB) {
            $tridDB = $this->makeArray4Select("trid");
        }
        
        $trIdBin = "../../../trId/trid -d:../../../trId/triddefs.trd ";
        
        exec($trIdBin . $file, $output);
        
        foreach($output as $line) {
            
            if(round($line)) {
                $arr = explode('%', $line);
                
                if(count($arr) == 2) {
                    
                    // Проверка за съвпадение с базата данни
                                        $fts = $tridDB;
                    
                    foreach($fts as $id => $trid) {
                        if(strpos($arr[1], $trid) !== FALSE) {
                            $result[$id] = (float) $arr[0];
                            break;
                        }
                    }
                }
            }
            
            // Проверка за предупреждението за грешка
                        if(strpos($line, "Warning: file seems to be plain text/ASCII") !== FALSE) {
                $rec = $this->fetch("#trid = 'Plain text/ASCII'");
                $result[$rec->id] = 20;  // даваме 20% служебно на този файл
            }
        }
        
        // Даваме служенбо 10% на онези типове, които имат същото разширение, като това на файла
                $info = pathinfo($file);
        
        if($ext = strtolower($info['extension'])) {
            $query = $this->getQuery();
            $query->orderBy('#commonRate', 'DESK');
            
            while($rec = $query->fetch("LOWER(#extension) = '{$ext}'")) {
                $result[$rec->id] += 10;  // даваме служебно 10$ заради разширението
            }
        }
        
        if(count($result)) {
            $max = 0;
            
            foreach($result as $id => $p) {
                if($p > $max) {
                    $maxId = $id;
                    $max = $p;
                }
            }
            
            return $maxId;
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function httpAjaxGetOptions() {
        
        if(!haveRight('admin')) die;
        
        $select = new ET("[#OPTIONS#]");
        
        $q = Request::get('q');
        
        if($q) {
            
            $this->log($q);
            
            $options = $this->fetchOptions($q);
            
            if(is_array($options)) {
                
                foreach($options as $id=>$title) {
                    $attr = array();
                    $element = 'option';
                    
                    if(is_object($title)) {
                        
                        if($title->group) {
                            if($openGroup) {
                                // затваряме групата                
                                                                $select->append("</optgroup>", 'OPTIONS');
                            }
                            $element = 'optgroup';
                            $attr = $title->attr;
                            $attr['label'] = $title->title;
                            $option = ht::createElement($element, $attr);
                            $select->append($option, 'OPTIONS');
                            $openGroup = TRUE;
                            continue;
                        } else {
                            $attr = $title->attr;
                            $title = $title->title;
                        }
                    }
                    $attr['value'] = $id;
                    
                    if($id == $selected) {
                        $attr['selected'] = 'selected';
                    }
                    $option = ht::createElement($element, $attr, $title);
                    $select->append($option, 'OPTIONS');
                }
            }
        }
        
        $options = $select->getContent();
        
        die("$options");
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function fetchOptions($q)
    {
        $q = strtolower(str::utf2ascii($q));
        
        $q = trim(preg_replace('/[^a-zа-я0-9]+/', ' ', $q));
        
        $query = $this->getQuery();
        
        $q = explode(' ', $q);
        
        foreach($q as $str) {
            
            $str = ltrim (trim($str) , '0');
            
            if($str) {
                $query->where("CONCAT(' ', #id, ' ', LOWER(#trid), ' ', lower(#extension))  LIKE  '% $str%'");
            }
        }
        
        $query->limit(50);
        $query->show('id,trid');
        
        $options[''] = '';
        
        while($rec = $query->fetch()) {
            $this->addVerbalOption(&$options, $rec);
        }
        
        return $options;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function addVerbalOption(&$options, $rec) {
        
        $value = $this->getVerbalName($rec);
        
        if(haveRight2Company($rec->id)) {
            $options[$value] = $value;
        } else {
            if(Mode::is('screenMode', 'narrow')) {
                $options[$value] = $value . " *";
            } else {
                $options[$value]->title = $value;
                $options[$value]->attr = array('style' => 'color:black');
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getVerbalName($rec, $pad = 5) {
        $id = str_pad($rec->id, $pad, '0', STR_PAD_LEFT);
        
        return "$id $rec->trid";
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_GenerateIcons()
    {
        set_time_limit(30);
        
        $Curl = cls::get('vendor_Curl');
        $res = $Curl->getInfo("http://printed-bags.net/uploads/Email_21657371.eml");
        
        if($res !== FALSE) {
            return implode('<br>', $res);
        } else {
            return 'ERROR!';
        }
        
        $query = $this->getQuery();
        //$query->where("#extension='TTF'");
                $query->limit(20000);
        
        while($rec = $query->fetch()) {
            if(rand(1, 100) == 5) {
                $this->generateIcon($rec);
            }
        }
    }
    
    
    /**
     * Генериране на икона за съответния тип
     */
    function generateIcon($rec)
    {
        global $ready;
        
        cls::load('color_Color');
        
        $OS = cls::get('core_Os');
        
        // Вземаме SVG шаблон
                if($rec->extension) {
            $iconName = "{$rec->genericType}-" . strtolower($rec->extension) . ".png";
        } else {
            $iconName = "{$rec->genericType}.png";
        }
        
        $iconFile = EF_ROOT_PATH . "/webroot/files/icons/" . $iconName;
        
        if($ready[$iconFile]) return;
        $ready[$iconFile] = TRUE;
        
        $file = EF_EF_PATH . "/file/icon-templates/{$rec->genericType}-" . strtolower($rec->extension) . ".png";
        
        if(file_exists($file)) {
            $command = "C:/Program Files/GraphicsMagick-1.3.5-Q8/gm convert -size 128x128   {$file} {$iconFile} ";
            $OS->exec($command);
        } else {
            
            $svg = @file_get_contents(EF_EF_PATH . "/file/icon-templates/{$rec->genericType}-{$rec->extension}.svg");
            
            if(!$svg) {
                $svg = @file_get_contents(EF_EF_PATH . "/file/icon-templates/{$rec->genericType}.svg");
            }
            
            if($svg) {
                
                $width = 31;
                $x = 16;
                $len = 0;
                $x1 = 0;
                
                $len = (strlen($rec->extension) - 3) * 9;
                
                $width = $width + $len;
                $x = $x - $len;
                
                if($x<2) {
                    $delta = 2 - $x;
                    $x = $x + $delta;
                    $width = $width - $delta;
                    $fontSize = 14;
                } else {
                    $fontSize = 15;
                }
                
                $x1 = $x + 3.3;
                
                // Определяме дали ще се изписва разширението
                                if(strlen($rec->extension) > 0 && strlen($rec->extension) < 6) {
                    $ext = strtoupper($rec->extension);
                } else {
                    $ext = '';
                    $width = 0;
                }
                
                if($rec->genericType == 'document' || $rec->genericType == 'code' || $rec->genericType == 'text' || $rec->genericType == 'spreadsheet' || $rec->genericType == 'presentation' || $rec->genericType == 'compressed') {
                    $dark = new color_Color();
                    $dark->randInit(20, 100);
                    $fillColor = $dark->getCSS();
                    $strokeColor = $dark->getCSS();
                    $textColor = '#ffffff';
                    $dark->resize(2, 5);
                    $fillLightColor = $dark->getCSS();
                } else {
                    $dark = new color_Color();
                    $dark->randInit(15, 30);
                    $light = new color_Color();
                    $light->randInit(210, 250);
                    $fillColor = $light->getCSS();
                    $strokeColor = $dark->getCSS();
                    $textColor = $dark->getCSS();
                    $fillLightColor = $light->getCSS();
                }
                
                $svg = str_replace('[#extn#]', $this->getSvgLabel($x, $width, $x1, $ext, $fillColor, $textColor, $strokeColor, $fontSize, $fillLightColor), $svg);
                
                // Генерираме името на новия файл
                                $tempFile = EF_TEMP_PATH . "\icon-temp.svg";
                
                // Записваме новия файл
                                $handle = fopen($tempFile, "w");
                $numbytes = fwrite($handle, $svg);
                fclose($handle);
                
                // Генерираме икона 48х48
                
                $command = "\"C:\Program Files\Inkscape\inkscapec.exe\" \"{$tempFile}\" --export-png={$iconFile} -w128 -h128 --export-background-opacity=1.0";
                
                $OS->exec($command);
                
                //    unlink($tempFile);
            
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getSvgLabel($x, $width, $x1, $ext, $fillColor, $textColor, $strokeColor, $fontSize, $fillLightColor)
    {
        return "
            <defs>
            <linearGradient id=\"orange_red\" x1=\"0%\" y1=\"0%\" x2=\"0%\" y2=\"100%\">
            <stop offset=\"0%\" style=\"stop-color:$fillColor;
            stop-opacity:1\"/>
            <stop offset=\"40%\" style=\"stop-color:$fillLightColor;
            stop-opacity:1\"/>
            <stop offset=\"100%\" style=\"stop-color:$fillColor;
            stop-opacity:1\"/>
            </linearGradient>
            </defs>
           <rect
       ry=\"2\"
       y=\"33.820927\"
       x=\"{$x}\"
       height=\"13.550652\"
       width=\"{$width}\"
       id=\"rect2585\"
       style=\"fill:url(#orange_red);stroke:{$strokeColor};stroke-width:0.8;stroke-miterlimit:4;\" />
    <text
       transform=\"scale(0.9517857,1.0506567)\"
       sodipodi:linespacing=\"125%\"
       id=\"text2587\"
       y=\"43.00378\"
       x=\"{$x1}\"
       style=\"font-size:{$fontSize}px;font-style:normal;font-variant:normal;font-weight:normal;font-stretch:normal;text-align:start;line-height:125%;writing-mode:lr-tb;text-anchor:start;fill:{$textColor};fill-opacity:1;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;font-family:Courier New;-inkscape-font-specification:Courier New\"
       xml:space=\"preserve\"><tspan
         style=\"font-weight:bold;-inkscape-font-specification:Courier New Bold\"
         y=\"43.00378\"
         x=\"{$x1}\"
         id=\"tspan2589\"
         sodipodi:role=\"line\">{$ext}</tspan></text>
        ";
    }
}


/**
 * @todo Чака за документация...
 */
function _exec($cmd)
{
    $WshShell = new COM("WScript.Shell");
    
    $oExec = $WshShell->Run($cmd, 0, true);
    
    return $oExec == 0 ? true : false;
}