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
        //Провярява дали сме логнат потребител. Ако не сме редиректва в страницата за вход.
//         requireRole('user');
        
        //Вземаме номера на контейнера
        $cid = Request::get('id', 'int');
        
        //Вземаме документа
        $doc = doc_Containers::getDocument($cid);
        
        //Инстанцията на документа
        $instance = $doc->instance;
                
        //id' то на документа
        $that = $doc->that;
        
        //Проверяваме дали имаме права за разглеждане на документа
        if ($instance->haveRightFor('single', $that)) {
            
            //Подготвяме URL' то където ще редиректнем
            $retUrl = array($instance, 'single', $that);
            
            //Спираме режима за принтиране
            Mode::set('printing', FALSE);
            
            //Редиректваме към sinlgle' a на документа
            redirect($retUrl);
        } else {
            //
            // Проверка за право на достъп според MID
            //
            
            // Вземаме манипулатора на записа от този модел (bgerp_L)
            $mid     = Request::get('m');
            $history = array();

            if ($mid) {
                $parent = doc_Log::fetchHistoryFor($cid, $mid);
            }
            
            if (!empty($parent)) {
                // Има запис в историята - MID-a е валиден, генерираме HTML съдържанието на 
                // документа за показване
                
                $details = array(); // @TODO попълване с IP и пр.
                
                doc_Log::pushAction(
                    array(
                        'containerId' => $cid,
                        'action'      => doc_Log::ACTION_OPEN, 
                        'parentId'    => $parent->id, 
                        'details'     => $details
                    )
                );
                
                $html   = $doc->getDocumentBody('xhtml');
                
                doc_Log::popAction();
                
                Mode::set('wrapper', 'page_External');
                
                return $html;
            }
            
            //Ако нямаме права, показваме съобщение за грешка
            expect(NULL, 'Нямате права за разглеждане на документа.');
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
