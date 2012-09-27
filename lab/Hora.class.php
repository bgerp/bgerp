<?php 


class lab_Hora extends core_Master
{
    var $interfaces = 'email_SentFaxIntf';
    var $canRead = 'every_one';
    var $title = 'Хора';
    var $loadList = '';
    var $singleLayoutFile = 'fileman/tpl/SingleLayoutFile.shtml';
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("email", "email", "caption=Имейл");
//        $this->FLD("enum", "enum(0.0, 0,0,0=0)", "caption=Енум");
        $this->FLD("varTrim", "varchar", "caption=varTrim,mandatory");
        $this->FLD("varNoTrom", "varchar(noTrim)", "caption=varNoTrom,mandatory");
        
    }

    function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        bp(log_Documents::getActionRecForMid('Eirxdekh'));
//        bp(docoffice_Office::getStartedOfficePid());
//        bp(docoffice_Office::killOffice());
        (docoffice_Office::prepareOffice());
//        $s = cls::get('docoffice_Jodconverter');
//        $s->convertDoc('bA9m38', 'pdf');
//    for($i=8099;$i<=8115;$i++) {
//        $conn = @fsockopen("localhost", $i);bp(@fsockopen("localhost", 8100));
//        if ($conn) {
//            echo "Port $i is open.n";
//            fclose($conn);
//        }
//    }
//    bp();
//        bp(csstoinline_CssToInline::convert('html', 'css'));
        $conf = core_Packs::getConfig('csstoinline');
        $CssToInline = $conf->CSSTOINLINE_CONVERTER_CLASS;
        $inst = cls::get($CssToInline);
        bp($inst->convert('html', 'css'), $inst);
        
//        bp(exec("netstat -tln | grep ':{$port}[^0-9]'"));
//        bp(docoffice_Office::findEmptyPort());
        bp(docoffice_Office::killOffice());
        bp(docoffice_Office::prepareOffice());
//        mkdir('/home/developer/Desktop/aaaaaa', 0777, TRUE);
        $file = 'bA9m38';
        bp(fileman_Files::fetchField("#fileHnd='{$file}'"));
//        bp(exec("unoconv -f txt '/home/developer/Desktop/bg.doc'"));
        $shell = '/home/developer/Desktop/www/ef_root/temp/bgerp/fconv/mr0bx74y/mr0bx74y.sh';
        bp(pclose(popen($shell, "r")));
        bp(docoffice_Office::startOffice());
       
        bp(apachetika_Detect::convertDoc('TZH917', 'meta'));    
        
        
        
        bp(fileman_webdrv_Pdf::getContent(fileman_Files::fetchByFh('Bph3Qg')));
        bp(fileman_Indexes::prepare($data, 'Bph3Qg'));
        bp(basename('/var/www/er.ss'));
//        bp(static::getFirstMonday(7, 2012));
//        bp(docoffice_Office::killOffice());
        $cong = core_Packs::getConfig('docoffice');
        $Conv = $cong->OFFICE_CONVERTER_CLASS;
        $Conv::convertDoc('AaXmTw', 'txt', array('callBack' => 'lab_Hora::get', 'ext' => 'rtf', 'fileInfoId' => 1, 'asynch' => FALSE));
//        bp(core_Locks::get('Yusein', 15, 5));
//        bp(docoffice_Office::killProcess(15611));
//        bp(docoffice_Office::getStartedOfficePid());
//        bp(docoffice_Office::emptyConvertCount());
//        bp(docoffice_Office::increaseConvertCount());
        bp(docoffice_Office::checkRestartOffice());
//        bp(docoffice_Office::startOffice());
//        bp(exec('/usr/lib/openoffice/program/soffice', $a, $b), $a, $b);
        bp(exec("ps aux | grep soffice | grep -v grep | awk '{ print $2 }' | head -1", $a, $b), $a, $b);
        bp(file_get_contents('/home/developer/Desktop/sampleabstract.rtf'));
//        bp(zbar_Reader::getBarcodesFromFile('TNyzdi'));
//        bp(bgerp_FileInfo::getFileName('zu9QNz'));
//    doc_Incomings::createFromScannedFile('zu9QNz', 39);
//        bp(bgerp_FileInfo::canReadBarcodes(183, 'pdf'));
        //bp(fileman_Files::fetchField(1887, 'fileHnd'));
//        bp(log_Documents::getDocumentCidFromURL('http://localhost/ef_root/webroot/L/S/39/?m=Nlbveryu'));
        
//        bp(zbar_Reader::getBarcodesFromFile('TmZr2m'));
//        bp(bgerp_FileInfo::getFileName('ViB7qy'));
        bp(bgerp_FileInfo::startProcess());
        bp(bgerp_FileInfo::getKeywords('ViB7qy')); //scanned
        bp(bgerp_FileInfo::getKeywords('r9ehLp')); //down
        bp(bgerp_FileInfo::getKeywords('a515KU')); //generate
        bp(bgerp_FileInfo::getExtension('.htaccess'));
        //1397 N6TNqi
        //1398 Aw24kH
        //1399 pEpVSx
        //1400 frS5kP
        //
        //http://localhost/ef_root/webroot/L/S/33/?m=Otsqefhw
        doc_Incomings::createFromScannedFile('p3HtKb', 30);
        doc_Incomings::createFromScannedFile('rGAUYx', 33);
        doc_Incomings::createFromScannedFile('uc6gjl', 30);
//        bp(log_Documents::getDocumentCidFromURL('http://localhost/ef_root/webroot/L/S/33/?m=Otsqefhw'));
//        bp(log_Documents::getDocumentCidFromURL('http://localhost/ef_root/webroot/L/B/33/?m=Otsqefhw'));
//        bp(log_Documents::getDocumentCidFromURL('http://localhost/ef_root/webroot/L/B/29/?m=Malokyzo'));
//        bp(log_Documents::getDocumentCidFromURL('http://localhost/ef_root/webroot/L/B/31/?m=Btotkrqx'));
        bp(log_Documents::getDocumentCidFromURL('http://localhost/ef_root/webroot/L/B/30/?m=Wrbcmqza'));
        
//        bp(log_Documents::fetchHistoryFor(29,'Malokyzo'));
        bp(log_Documents::fetchHistoryFor(30,'Wrbcmqza'), log_Documents::fetchHistoryFor(29,'Malokyzo'));
//        bp(core_App::getBoot(TRUE));
//        bp(doc_Incomings::createFromScannedFile('eY2ACt', 24));
//        $barcodes = zbar_Reader::getBarcodesFromFile('pEpVSx');
        bp($barcodes);
        $CssToInline = exec("pdftotext -nopgbrk '/home/developer/Desktop/ELI7023.pdf'", $a, $b);
//        $d = getFileContent('/home/developer/Desktop/Untitled 2.ods');
        $d = getFileContent('ELI7023.txt');
        bp($CssToInline, $a, $b, $d    );
//        require_once 'HTTP/Request2.php';
//        echo phpinfo();
//        bp();
//        
        core_Classes::getInterfaceCount('email_SentFaxIntf');
        //9999999999999
        //0000000000000
        //1111111111111
        barcode_Generator::printImg('ean13', '3800077003778', 'medium', NULL, $output);
//        barcode_Generator::printImg('qr','1111111111111', NULL, array('pixelPerPoint' => 5, 'angle'=>190));
        
        phpinfo();
      $client = new SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");  
      bp($client);  
        //$s = '1321';
    bp(core_Roles::expand(80));
    $CssToInline[1] = 1;
    $CssToInline[2] = 2;
    
    $d[3] = 3;
    $d[2] = 2;
    
    bp(array_diff($CssToInline, $d), array_diff($d, $CssToInline));
    bp(core_Users::getInheritRoles(67), core_Users::getInheritRoles(1), core_Users::getRolesArr(70));
    bp((stripos($CssToInline, '?') === FALSE));
        bp(date('Y-m-d', mktime(0, 0, 0, 12, 1, (int)1902)), mktime(0, 0, 0, 01, 25, 0));
        bp(core_Os::deleteDir('/var/www/ef_root/webroot/sbf/bgerp/_dl_/AjlR4H'));
        phpinfo();
        $domain = 's@bgerp.com';
        bp(email_Inboxes::isGroupDomain($domain));
        
        core_Classes::add('lab_Hora');
        bp(email_Inboxes::findFirstInbox('yusein@gmail.com'));
//        bp(email_Inboxes::forceFolder('yusein@gmail.com'));
//$emailArr['yusein@bgerp.com'] +=1;
//$emailArr['yusein@bgerp.com'] +=1;
//$emailArr['yusein@bgerp.coms'] +=1;
//        (email_Incomings::addUserStatusCntEmails($emailArr));
        $r = rand(1, 3);
        for($i=0; $i<$r; $i++) {
            $rand = rand(1, 4);
            $link = ht::createLink('тук', array($mvc,'s'));
            core_Statuses::add("Съобщение как е? - ".time());    
        }
//        bp(Mode::get('statusArr'));
        
//        $res = core_Statuses::show();
//        bp($res);
    }
    
    function act_S()
    {
        
//        bp(zbar_Reader::getBarcodesFromFile('fAqUWH'));
        bp(docoffice_Office::killOffice());
        $s = array('asd' => 'asd');
//        $s = 'txtttttt';
        $se = base64_encode(gzcompress(serialize($s)));
        $sd = unserialize(gzuncompress(base64_decode('eJwrtrK0UrKHAi4lawAhrwOi')));
        bp($se, $sd);
//        $lid = 'asd111';
//        core_Locks::get($lid, 5, 0, FALSE);
//        core_Locks::get($lid . 'asd', 5, 0, TRUE);
//        bp(docoffice_Office::checkRestartOffice());
//        bp(core_Locks::isLocked('ss'));
//        bp(fileman_Mimes::addCorrectFileExt('asd', 'application/cdr'));
//        bp(fileman_Mimes::addCorrectFileExt('asd', 'application/octet-stream'));
//        fileman_webdrv_Pdf::getContent(fileman_Files::fetchByFh('Bph3Qg'));
        
//        $url = toUrl(array('lab_Hora', 'act', 'id'), TRUE);
//        return "<div> <iframe src='http://localhost/ef_root/webroot/lab_Hora'> </iframe> </div>";
//        $r = rand(1, 3);
////        $r = 1;
//        for($i=0; $i<$r; $i++) {
//            $rand = rand(1, 4);
//            $link = ht::createLink('тук', array($mvc,'s'));
//            core_Statuses::add("Съобщение как е? - {$rand}", $rand);    
//        }
        
//        $tpl = core_Statuses::show();
        
//        return ($tpl);
    }
    
    
    function sendFax($data, $faxТо)
    {//return TRUE;
        $conf = core_Packs::getConfig('fax');
        
        $rec = $data->rec;
        
        //Очаква да има факс на изпращача
        expect(($faxSender = $conf->FAX_SENDER_BOX), 'Не сте дефинирали факс на изпращача.');
        
        //Броя на прикачените файлове и документи
        $attachCnt = count($rec->documentsFh) + count($rec->attachmentsFh);
        
        expect(!($attachCnt > $conf->MAX_ALLOWED_ATTACHMENTS_IN_FAX), 'Надвишили сте максималния брой за прикачени файлове: ' . $conf->MAX_ALLOWED_ATTACHMENTS_IN_FAX);
        
        //Енкодинг на факса
        $options['encoding'] = 'utf-8';
        
        //Дали да се добави манипулатора на нишката пред заглавието
//        $options['no_thread_hnd'] = 'no_thread_hnd';

        //Указва дали е факс или не
        $options['is_fax'] = 'is_fax';
        
        //Преобразуваме всеки факс номер към имейл
        //Факса на получателя
        $recipientFaxEmail = $faxТо . '@efaxsend.com';
        
$faxSender = 'yusein@bgerp.com';
$recipientFaxEmail = 'bgerptest@gmail.com';
        //Ако не сме дефинирали id на факса, а имейл
        if (!is_numeric($conf->FAX_SENDER_BOX)) {
            
            //Вземаме id' то на получателя
            $faxSender = email_Inboxes::fetchField("#email='$faxSender'");
            
            //Очакваме да има такъв имейл
            expect($faxSender, 'Няма такъв имейл в системата Ви.');
        }
        
        
        
        //Изпращаме факса
        $res = email_Sent::sendOne($faxSender, $recipientFaxEmail, $rec->subject, $rec, $options);
        
        return $res;
    }
    
    static function getFirstMonday($month,$year) {
        
        $num = date("w",mktime(0,0,0,$month,1,$year));
        
        if ($num==1) {
            return date("Y-M-d H:i:s",mktime(0,0,0,$month,1,$year));    
        } elseif ($num>1) {
            return date("Y-M-d H:i:s",mktime(0,0,0,$month,1,$year)+(86400*(8-$num)));
        } else {
            return date("Y-M-d H:i:s",mktime(0,0,0,$month,1,$year)+(86400*(1-$num)));
        }
    }
    
    function on_AfterPrepareSingle($mvc, &$tpl, $data)
    {
        fileman_Indexes::prepare($data, 'Bph3Qg');
    }
    function on_AfterRenderSingle($mvc, &$tpl, &$data)
    {
        
        $data->currentTab = Request::get('currentTab');
        $fileInfo = fileman_Indexes::render($data, 'Bph3Qg');
        $tpl->append($fileInfo, 'drug');
        
//        bp($data);
    }
    
    function act_Enc()
    {
        $text = 'À Â Ò Î Á È Î Ã Ð À Ô È ß

            íà Þëèàí Õðèñòîâ Òðè÷êîâ
            
            þëèàí òðè÷êîâ þëèàí òðè÷êîâ
            
            Äàòà íà ðàæäàíå Ìÿñòî íà ðàæäàíå Ïîñòîÿíåí àäðåñ Îáðàçîâàíèå
            
            24 íîåìâðè 1961 ãîäèíà ãðàä Ñîôèÿ 1000 Ñîôèÿ, óë. "Ëàâåëå" 32 ñðåäíî: Ñðåäíî Õóäîæåñòâåíî Ó÷èëèùå çà Ïðèëîæíè Èçêóñòâà (ÑÕÓÏÈ) - Ñîôèÿ âèñøå: Õóäîæåñòâåíà Àêàäåìèÿ - Ñîôèÿ ñïåöèàëíîñò "Ïëàêàò è ïðèëîæíà ãðàôèêà" (Ãðàôè÷åí äèçàéí)
            
            Ðàáîòà
            
            â. "Âå÷åðíè íîâèíè", â. "Ðèòúì", â. "1000 äíè", ñï. "Äîìàøåí æóðíàë", ñï. "Áðèäæ ïëþñ", ñï. "Áúëãàðè", ñàìîñòîÿòåëíà ðàáîòà â îáëàñòòà íà âúíøíàòà ðåêëàìà è ãðàôè÷íèÿ äèçàéí. Ðàáîòà ñ êîìïþòúð: ïðîãðàìè çà âåêòîðíà è ðàñòåðíà ãðàôèêà (íàé-âå÷å CorelDraw, Photoshop), ïðåäïå÷àòíà ïîäãîòîâêà (PageMaker, QuarkXpress, InDesign), íàáîð è ðàáîòà ñ òåêñò è åë. òàáëèöè (MS Office, MS Excel), OCR ïðîãðàìè (ABBY FineReader), çà ðàáîòà ñ øðèôòîâå (Fontographer), HTML ðåäàêòîðè (DreamWeaver), Flash ðåäàêòîðè (KoolMoves, Corel R.A.V.E.), áðàóçúðè, ïëåéúðè, âþúðè, àíòèâèðóñíè è ïîìîùíè ïðîãðàìè, ðàáîòà ñ ïåðèôåðèÿ - (ïðèíòåð, ñêåíåð, ðåæåù ïëîòåð), ñàìîñòîÿòåëeí úïãðåéä (õàðäóåðåí è ñîôòóåðåí) íà êîìïþòúðà. Àíãëèéñêè: ðàáîòíî íèâî Ðóñêè: ïåðôåêòíî Íåìñêè: èçó÷àâàí â ñðåäíîòî ó÷èëèùå Èòàëèàíñêè: èçó÷àâàí â Àêàäåìèÿòà ôîòîãðàôèÿ - ðàáîòà ñ àíàëîãîâà è öèôðîâà òåõíèêà, ïîëèãðàôèÿ - ïîçíàíèÿ è ïðàêòèêà æåíåí, ñ 1 äåòå òåë. 987 33 31; 088/983 42 48 e-mail: yulian@trichkov.info
            
            Óìåíèÿ
            
            Åçèöè
            
            Äðóãè óìåíèÿ Ñåì. ïîëîæåíèå Çà êîíòàêòè';
        bp(lang_Encoding::repairText($text));
        $enc = cls::get('lang_Encoding');
        $charset = 'CP1251';
        
//        $text = (iconv("UTF-8", 'ISO-8859-1', $text));
//        $text = (iconv("CP1251", 'UTF-8', $text));
//        bp($text);
//        bp(iconv("UTF-8", "{$charset}", $text));
        bp($enc->analyze2Charsets($text));
    }
}