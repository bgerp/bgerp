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
                                    pdf=PDF експорт,
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
        
        try {
            //Вземаме номера на контейнера
            expect($cid = Request::get('id', 'int'));
            
            // Вземаме документа
            expect($doc = doc_Containers::getDocument($cid));
            
            //Спираме режима за принтиране
            Mode::set('printing', FALSE); // @todo Необходимо ли е?

            //
            // Проверка за право на достъп според MID
            //
            
            // Вземаме манипулатора на записа от този модел (bgerp_L)
            expect($mid = Request::get('m'));
            expect(log_Documents::opened($cid, $mid));
            
            $options = array();

            // Трасираме стека с действията докато намерим SEND екшън
            $i = 0;
            while ($action = log_Documents::getAction($i--)) {
                if ($action->action == log_Documents::ACTION_SEND) {
                    $options['__toEmail'] = $action->data->to;
                }
            }
            
            // Има запис в историята - MID-a е валиден, генерираме HTML съдържанието на 
            // документа за показване
            $html = $doc->getDocumentBody('xhtml', (object) $options);
            
            Mode::set('wrapper', 'page_External');
            
            return $html;
            
        } catch (core_exception_Expect $ex) {
            // Опит за зареждане на несъществуващ документ или документ с невалиден MID.
            
            // Нелогнатите потребители не трябва да могат да установят наличието / липсата на
            // документ. За тази цел системата трябва да реагира както когато документа е 
            // наличен, но няма достатъчно права за достъп до него, а именно - да покаже
            // логин форма.
            
            requireRole('user'); // Ако има логнат потребител, този ред няма никакъв ефект. 
                                 // Ако няма - това ще форсира потребителя да се логне и ако
                                 // логинът е успешен, управлението ще се върне отново тук
            
            // До тук се стига ако логнат потребител заяви липсващ документ или документ с 
            // невалиден MID. 

            // Ако потребителя има права до треда на документа, то той му се показва
            if($doc) {
                $rec = $doc->fetch();
                
                if($doc->instance->haveRightFor('single', $rec) || doc_Threads::haveRightFor('single', $rec->threadId)) {

                    return new Redirect(array($doc->instance, 'single', $rec->id));
                }
            }
            
            expect(FALSE); // Същото се случва и ако документа съществува, но потребителя няма 
                           // достъп до него.
        }
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
        
        // При отваряне на имейла от получателя, отбелязваме като видян.
        if ($mid) log_Documents::received($mid);

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
