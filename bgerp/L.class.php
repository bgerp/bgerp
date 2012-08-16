<?php



/**
 * История на документите
 *
 * Активиране, изпращане по имейл, получаване, връщане, отпечатване, споделяне, виждане ..
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>, Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_L extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Хоронология на действията с на документи';
    

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'bgerp_Wrapper, plg_RowTools, plg_Printing, plg_Created';
    

    /**
     * Дължина на манипулатора 'mid'
     */
    const MID_LEN = 7;


    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('mid', 'varchar(' . self::MID_LEN . ')', 'caption=Манипулатор,notNull');
        $this->FLD('action', 'enum( activate=Активиране,
                                    email=Имейл,
                                    fax=Факс,
                                    pfd=PDF експорт,
                                    print=Печат,
                                    _see=Виждане,
                                    _receive=Получаване,
                                    _show=Показване,
                                    _return=Връщане
                                    )', 'caption=Действие,notNull');
        $this->FLD('refId', 'key(mvc=bgerp_L,select=res)', 'caption=Към');
        $this->FLD('res', 'key(mvc=bgerp_LRes,select=title)', 'caption=Ресурс');
        $this->FLD('cid', 'key(mvc=doc_Containers)', 'caption=Контейнер,notNull,value=0');
        $this->FLD('tid', 'key(mvc=doc_Thread)', 'caption=Нишка,notNull');

        $this->setDbUnique('mid');
        $this->setDbIndex('tid');
        $this->setDbUnique('action,cid,res,createdBy');
    }



    /**
     * Добавя запис в документния лог, за действие направено от потребител на системата
     */
    function add($action, $tid, $cid = 0, $res = NULL, $refId = NULL)
    {   
        $rec = new stdClass();

        $L = cls::get('bgerp_L');

        // Очакваме само дайствие, допустимо за извършване от регистриран потребител
        $actType = $L->fields['action']->type;
        expect(isset($actType->options[$action]));
        $rec->action = $action;

        // Ако нямаме зададен ресурс, той се попълва с IP-то на текущия потребител
        if(!isset($res)) {
            $rec->res = core_Users::getRealIpAddr();
        }

        $rec->tid   = $tid;
        $rec->cid   = $cid;
        $rec->refId = $refId;
    }


    /**
     * Добавя запис в документния лог, за действие, производно на друго действие, записано в този лог
     */
    static function addRef($action, $refMid, $res = NULL)
    {
        // Очакваме действието да започва с долна чера, защото по този начин означаваме действията
        // Които 
        // Трябва да имаме референтен 'mid'.
        // Чрез него се извлича 'id', 'tid' и 'cid' на референтния запис
        expect($refMid);
        $refRec = static::fetchField("#mid = '{$refMid}'");
        $tid   = $refRec->tid;
        $cid   = $refRec->cid;
        $refId = $refRec->id;
       
        static::add($action, $tid, $cid, $res, $refId);
    }

    
    /**
     * Екшъна за показване на документи
     */
    function act_S()
    {
        //Вземаме номера на контейнера
        $cid = Request::get('id', 'int');
        
        //Вземаме документа
        try {
            $doc = doc_Containers::getDocument($cid);
        } catch (core_exception_Expect $ex) {
            // Опит за зареждане на несъществуващ документ.
            
            // Нелогнатите потребители не трябва да могат да установят наличието / липсата на
            // документ. За тази цел системата трябва да реагира както когато документа е 
            // наличен, но няма достатъчно права за достъп до него, а именно - да покаже
            // логин форма.
            
            requireRole('user'); // Ако има логнат потребител, този ред няма никакъв ефект. 
                                 // Ако няма - това ще форсира потребителя да се логне и ако
                                 // логинът е успешен, управлението ще се върне отново тук
            
            // До тук се стига ако логнат потребител заяви липсващ документ. 
            
            expect(FALSE); // Същото се случва и ако документа съществува, но потребителя няма 
                           // достъп до него.
        }
        
        //Инстанцията на документа
        $instance = $doc->instance;
                
        //id' то на документа
        $that = $doc->that;
        
        //Подготвяме URL' то където ще редиректнем
        $docUrl = array($doc->instance, 'single', $doc->that);
        
        //Спираме режима за принтиране
        Mode::set('printing', FALSE); // @todo Необходимо ли е?
        
        //Проверяваме дали имаме права за разглеждане на документа
        if ($instance->haveRightFor('single', $that)) {
            //Редиректваме към single' a на документа
            redirect($docUrl);
        }
        
        //
        // Проверка за право на достъп според MID
        //
        
        // Вземаме манипулатора на записа от този модел (bgerp_L)
        $mid = Request::get('m');
        
        if ($mid) {
            $parent = log_Documents::fetchHistoryFor($cid, $mid);
        }
        
        if (!empty($parent)) {
            // Има запис в историята - MID-a е валиден, генерираме HTML съдържанието на 
            // документа за показване
            
            $openAction = log_Documents::ACTION_OPEN;
            $parent->data->{$openAction}[] = array(
                'on' => dt::now(true),
                'ip' => core_Users::getRealIpAddr(),
            );
            log_Documents::save($parent);
            
            $html = $doc->getDocumentBody('xhtml', (object)array('__mid'=>$parent->mid));
            
            Mode::set('wrapper', 'page_External');
            
            return $html;
        }
            
        // Няма достъп по MID - липсва или е невалиден
        // Пренасочваме към документа и оставяме контрола на достъпа на документната система 
        requireRole('user');
    }



    /**
     * Показва QR баркод, сочещт към съответния документ
     * Параметъра $id се приема като номер на контейнер
     * Параметъра $l се приема като id на запис в този модел
     */
    function act_B()
    {
        //Вземаме номера на контейнера
        $cid = Request::get('id', 'int');
        $mid = Request::get('m');
        
        $docUrl = static::getDocLink($cid, $mid);

        barcode_Qr::getImg($docUrl, 3, 0, 'L', NULL);
    }


    /**
     * Връща линк към този контролер, който показава документа от посочения контейнер
     *
     * @param integer $cid - containerId
     * @param inreger $mid - Шаблона, който ще се замества
     *
     * @return string $link - Линк към вювъра на документите
     */
    static function getDocLink($cid, $mid)
    {
        $url = toUrl(array('L', 'S', $cid, 'm'=>$mid), 'absolute');
        
        return $url;
    }

}
