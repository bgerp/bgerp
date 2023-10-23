<?php


/**
 * Прокси на 'doc_Search' позволяващ на партньор да търси в споделените му документи в достъпните му нишки
 *
 * @category  bgerp
 * @package   colab
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.12
 */
class colab_Search extends doc_Search
{
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'cms_ExternalWrapper, plg_Search, plg_State';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title=Заглавие,author=Автор,partnerDocLast=Последно,hnd=Номер,partnerDocCnt=Документи,createdOn=Създаване';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title';


    /**
     * Кой има право да чете?
     */
    public $canRead = 'partner';


    /**
     * Кой има право да чете?
     */
    public $canSingle = 'partner';


    /**
     * Кой има право да листва всички профили?
     */
    public $canList = 'partner';


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Ако пакета 'colab' не е инсталиран, никой не може нищо
        if (!core_Packs::isInstalled('colab')) {
            $requiredRoles = 'no_one';
            return;
        }

        if ($action == 'list') {
            $sharedFolders = colab_Folders::getSharedFolders($userId);
            if(!countR($sharedFolders)) return;
        }
    }


    /**
     * Лист на папките на колабораторите
     */
    public function act_List()
    {
        if (core_Users::isPowerUser()) {
            if (doc_Search::haveRightFor('list')) {

                return new Redirect(array('doc_Search', 'list'));
            }
        }

        Mode::set('currentExternalTab', 'cms_Profiles');

        Mode::push('colabSearch', true);
        $res = parent::act_List();
        Mode::pop('colabSearch');

        return $res;
    }


    /**
     * След като се поготви заявката за модела
     */
    protected static function on_AfterGetQuery($mvc, $query)
    {
        $cu = core_Users::getCurrent();

        $sharedFolders = colab_Folders::getSharedFolders($cu);

        $query->EXT('firstContainerId', 'doc_Threads', 'externalKey=threadId');
        $query->EXT('threadVisibleForPartners', 'doc_Threads', 'externalName=visibleForPartners,externalKey=threadId');
        $query->EXT('threadCreatedBy', 'doc_Threads', 'externalName=createdBy,externalKey=threadId');
        $query->in('folderId', $sharedFolders);

        $query->where("#visibleForPartners = 'yes' AND #threadVisibleForPartners = 'yes'");
        if(!haveRole('powerPartner', $cu)){
            $query->where("#threadCreatedBy = {$cu}");
        }
    }
}