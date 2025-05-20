<?php


/**
 * Клас 'purchase_SparePartsProtocolReturnedDetails'
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov
 * @copyright 2006 - 2025 Experta OOD
 *
 * @since     v 0.1
 */
class purchase_SparePartsProtocolReturnedDetails extends core_Detail
{
    /**
     * Заглавие
     *
     * @var string
     */
    public $title = 'Върнати резервни части или консумативи';


    /**
     * Единично заглавие
     *
     * @var string
     */
    public $singleTitle = 'Върната резервна част или консуматив';


    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'purchase_Wrapper,plg_RowTools2,plg_RowNumbering,plg_SaveAndNew,plg_AlignDecimals2';


    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'ceo, acc, purchase';


    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'ceo, acc, purchase';


    /**
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'ceo, acc, purchase';


    /**
     * Ключ на мастъра
     */
    public $masterKey = 'protocolId';


    /**
     * Ключ на мастъра
     */
    public $listFields = 'productId=Наименование,quantity,notes';


    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'no_one';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('protocolId', 'key(mvc=purchase_SparePartsProtocols,select=id)', 'mandatory,silent,input=none');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'class=w100,caption=Артикул,mandatory,silent,removeAndRefreshForm');
        $this->FLD('quantity', 'double(smartRound)', 'caption=К-во,mandatory,smartCenter,input=none');
        $this->FLD('notes', 'text(rows=4)', 'caption=Забележка,smartCenter');

        $this->setDbIndex('protocolId,productId');
    }


    /**
     * Извиква се след подготовката на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = $form->rec;

        $form->setFieldTypeParams('productId', array('groups' => cat_Groups::getKeylistBySysIds('replacements')));
        if(isset($rec->productId)){
            $measureName = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
            $form->setField('quantity', "input,unit={$measureName}");
        }
    }


    /**
     * Вербализиране на данните
     */
    protected function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->productId = cat_Products::getShortHyperlink($rec->productId);
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (in_array($action, array('add', 'edit', 'delete')) && isset($rec)) {
            $masterState = $mvc->Master->fetchField($rec->protocolId, 'state');
            if(in_array($masterState, array('active', 'rejected'))){
                $requiredRoles = 'no_one';
            }
        }
    }
}
