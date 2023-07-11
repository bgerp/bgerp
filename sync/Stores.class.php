<?php


/**
 * Складове във външни системи
 *
 *
 * @category  bgerp
 * @package   sync
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2020 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Складове в отдалечени системи
 */
class sync_Stores extends sync_Helper
{
    /**
     * Заглавие
     */
    public $title = 'Складове във външни системи';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_State2, plg_SaveAndNew, store_Wrapper';


    /**
     * Единично заглавие
     */
    public $singleTitle = 'Складове във външна системи';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'admin';


    /**
     * Кой може да сменя състоянието?
     */
    public $canChangestate = 'admin';


    /**
     * Полета, които се виждат
     */
    public $listFields = ' url, remoteId, remoteName, syncTime, createdBy=Добавено->От, createdOn=Добавено->На, state';


    /**
     * Дефолтно време за синхронизиране
     */
    const DEFAULT_SYNC_TIME = 5 * 60;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FNC('authorizationId', 'varchar', 'caption=Система,class=w100,silent,removeAndRefreshForm=remoteId|remoteName|url,input');
        $this->FLD('remoteId', 'int', 'caption=Номер, mandatory,input=none,tdClass=small-field');
        $this->FLD('remoteName', 'varchar', 'caption=Външно име,input=none');
        $this->FLD('url', 'url', 'caption=Услуга,input=hidden');
        $this->FLD('syncTime', 'time', 'caption=Синхронизиране на,input=hidden');

        $this->setDbUnique('remoteId,url');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param sales_Sales $mvc
     * @param stdClass    $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        // Кои са наличните системи за избор
        $systemOptions = remote_Authorizations::getSystemOptions();

        $form->setOptions('authorizationId', array('' => '') + $systemOptions);
        if(isset($rec->id)){
            $form->setDefault('authorizationId', static::getUserAuthorizationIdByUrl($rec->url));
        }

        // Ако е избрана система
        if(isset($rec->authorizationId)){

            // Извличат се складовете от посочената система
            $authorizationRec = remote_Authorizations::fetch($rec->authorizationId);
            $form->setDefault('url', $authorizationRec->url);
            $remoteOptions = $mvc->getRemoteStoreOptions($authorizationRec);

            // Ако има се показват, ако не показва се съобщение
            if(countR($remoteOptions)){
                $rec->_remoteOptions = $remoteOptions;
                $form->setField('remoteId', 'input');
                $form->setField('syncTime', 'input');
                $form->setDefault('syncTime', static::DEFAULT_SYNC_TIME);
                $form->setOptions('remoteId', array('' => '') + $remoteOptions);
            } else {
                $form->setError('authorizationId', 'Няма дефинирани складове във външната система');
            }
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            $rec->remoteName = $rec->_remoteOptions[$rec->remoteId];
        }
    }


    /**
     * Има ли потребителя оторизация за системата
     *
     * @param string $url      - урл към системата
     * @param int|null $userId - ид на потребител или null за текущия
     * @return null|stdClass   - запис на аутентикацията или null ако няма
     */
    public static function getUserAuthorizationIdByUrl($url, $userId = null)
    {
        $userId = isset($userId) ? $userId : core_Users::getCurrent();
        if(empty($userId)) return null;

        $systemOptions = remote_Authorizations::getSystemOptions('remote_BgerpDriver', $userId);
        if(!countR($systemOptions)) return null;

        $rQuery = remote_Authorizations::getQuery();
        $rQuery->in('id', array_keys($systemOptions));
        $rQuery->where(array("#url = '[#1#]'", $url));
        $rQuery->show('id');
        $rec = $rQuery->fetch();

        return is_object($rec) ? $rec->id : null;
    }


    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'edit' && isset($rec)){

            // Ако потребителя има аутентикация за системата от която е създаден записа, ще може да го редактира
            if(!static::getUserAuthorizationIdByUrl($rec->url, $userId)){
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'add'){

            // Ако потребителя няма оторизирани системи не може да добавя
            $systemOptions = remote_Authorizations::getSystemOptions('remote_BgerpDriver', $userId);
            if(!countR($systemOptions)){
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Връща масив с опции за избор на складове
     *
     * @param int|stdClass $authorizationId
     * @return array $options
     */
    private function getRemoteStoreOptions($authorizationId)
    {
        $options = array();

        // Ако оторизацията е валидна
        $authRec = remote_Authorizations::fetchRec($authorizationId);
        if ($authRec->data->lKeyCC && $authRec->data->rId) {

            // Прави се запитване до услугата от оторизацията
            $storeData = remote_BgerpDriver::sendQuestion($authRec, 'store_Stores', 'getStoresData');
            if (is_array($storeData) && countR($storeData)) {
                foreach ($storeData as $storeDataArr) {
                    $options[$storeDataArr['id']] = $storeDataArr['name'];
                }
            }
        }

        // Връщат се наличните за избор складове от външни системи
        return $options;
    }


    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getDisplayTitle($rec, $verbal = false)
    {
        $rec = static::fetchRec($rec);
        $explode = explode('//', $rec->url);
        $pureUrl = countR($explode) == 2 ? $explode[1] : $explode[0];
        if($verbal){
            return "{$rec->remoteName} [<span style='color:green'>{$pureUrl}</span>]";
        }

        return "{$rec->remoteName} [{$pureUrl}]";
    }


    /**
     * Връща наличните за избор опции
     * @param null|mixed $exIds
     * @return array $options
     */
    public static function getStoreOptions($exIds = null)
    {
        $options = array();
        $query = static::getQuery();
        $query->where("#state = 'active'");
        if(isset($exIds)){
            $exIds = is_array($exIds) ? $exIds : keylist::toArray($exIds);
            $exIdsStr = implode(',', $exIds);
            $query->orWhere("#id IN ({$exIdsStr})");
        }

        while($rec = $query->fetch()){
            $options[$rec->id] = static::getDisplayTitle($rec, $verbal = false);
        }

        return $options;
    }
}