<?php


/**
 * Помощен клас-имплементация на интерфейса bgerp_PersonalizationSourceIntf за класа voucher_Types
 *
 * @category  bgerp
 * @package   voucher
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see label_SequenceIntf
 *
 */
class voucher_interface_BlastPersonalizationSourceImpl
{
    /**
     * Инстанция на класа
     */
    public $class;


    /**
     * Връща вербално представяне на събщението на дадения източник за персонализирани данни
     *
     * @param int  $id
     * @param bool $verbal
     *
     * @return string
     */
    public function getPersonalizationBody($id, $verbal = false)
    {
        return voucher_Setup::get('BLAST_DEFAULT_EMAIL_BODY');
    }


    /**
     * Връща вербално представяне на заглавието на дадения източник за персонализирани данни
     *
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string|object $id
     * @param bool          $verbal
     *
     * @return string
     */
    public function getPersonalizationTitle($id, $verbal = true)
    {
        $rec = $this->class->fetchRec($id);

        return "Ваучери: " . $this->class->getTitleById($rec);
    }


    /**
     * Връща езика за източника на персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param int $id
     * @return string
     */
    public function getPersonalizationLg($id)
    {
    }


    /**
     * Връща линк, който сочи към източника за персонализация
     *
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     *
     * @return core_ET
     */
    public function getPersonalizationSrcLink($id)
    {
        $link = $this->getPersonalizationTitle($id, true);
        if(voucher_Types::haveRightFor('single', $id)){
            $link = ht::createLink($link, array('voucher_Types', 'single', $id));
        }

        return $link;
    }


    /**
     * Връща масив с ключове - уникални id-та и ключове - масиви с данни от типа place => value
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     * @param int    $limit
     *
     * @return array
     */
    public function getPresonalizationArr($id, $limit = 0)
    {
        $rec = $this->class->fetchRec($id);
        $query = voucher_Cards::getQuery();
        $query->where("#typeId = {$rec->id} AND #referrer IS NOT NULL");
        $query->EXT('email', 'crm_Persons', 'externalName=email,externalKey=referrer');
        $query->EXT('buzEmail', 'crm_Persons', 'externalName=buzEmail,externalKey=referrer');
        $query->EXT('name', 'crm_Persons', 'externalName=name,externalKey=referrer');
        $query->where("#email IS NOT NULL OR #buzEmail IS NOT NULL");
        $query->orderBy('id', 'ASC');

        $res = array();
        while ($rec = $query->fetch()) {
            $emails = !empty($rec->email) ? $rec->email : $rec->buzEmail;
            $emails = type_Emails::toArray($emails);
            if(!array_key_exists($rec->referrer, $res)){
                $res[$rec->referrer] = array('emails' => $emails[0], 'person' => $rec->name, 'vouchers' => array());
            }
            $res[$rec->referrer]['vouchers'][] = $rec->number;
        }

        array_walk($res, function(&$a){$a['vouchers'] = implode("\n", $a['vouchers']);});

        if($limit){
            $res = array_slice($res, 0, $limit, true);
        }

        return $res;
    }


    /**
     * Връща масив за SELECT с всички възможни източници за персонализация от даден клас,
     * за съответния запис, които са достъпни за посочения потребител
     *
     * @see bgerp_PersonalizationSourceIntf
     * @param int $id
     * @return array
     */
    public function getPersonalizationOptionsForId($id)
    {
        $res = array();
        if($this->canUsePersonalization($id)){
            $res[$id] = voucher_Types::getTitleById($id, false);
        }

        return $res;
    }


    /**
     * Връща масив с ключове имената на плейсхолдърите и съдържание - типовете им
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     * @return array
     */
    public function getPersonalizationDescr($id)
    {
        $resArr = array();
        $resArr['emails'] = cls::get('type_Emails');
        $resArr['person'] = core_Type::getByName('varchar');
        $resArr['vouchers'] = core_Type::getByName('text');

        return $resArr;
    }


    /**
     * Дали потребителя може да използва дадения източник на персонализация
     * @see bgerp_PersonalizationSourceIntf
     *
     * @param string $id
     * @param int    $userId
     * @return bool
     */
    public function canUsePersonalization($id, $userId = null)
    {
        // Всеки който има права до сингъла на записа, може да го използва
        if (isset($id)) {
            $referrersWithEmail = voucher_Types::getReferrersCountHavingField($id, 'email,buzEmail');
            if(!$referrersWithEmail) return false;

            if (voucher_Types::haveRightFor('single', $id, $userId)) return true;
        }

        return false;
    }
}