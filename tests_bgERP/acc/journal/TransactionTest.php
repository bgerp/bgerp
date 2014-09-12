<?php

class acc_journal_TransactionTest extends framework_TestCase
{
    protected function setUp()
    {
        /*
         * Подготовка на фикстурите
         */
        
        // Почистваме данните
        $this->truncate('doc_Folders, doc_Threads, doc_Containers, crm_Companies, cash_Pko, 
            acc_Items, cash_Cases');
    }
    
    public function testBuild()
    {
        // 1. Контрагент
        $contragentId = crm_Companies::save(
            (object)array(
                'name' => 'Тестова фирма',
            )
        );
        
        // 2. Перо за контрагента в номенкл. Доставчици (systemId: suppliers) 
       // acc_Lists::updateItem('crm_Companies', $contragentId, 'suppliers');
        
        // 3. Тестова каса (перото за нея се създава автоматично)
        $caseId = cash_Cases::save(
            (object)array(
                'name' => 'Тестова каса',
            )
        );
        
        $currencyId = currency_Currencies::getIdByCode('BGN');

        $rec = (object)array(
            'valior' => dt::now(),
            'amount' => 100,
            'currencyId' => currency_Currencies::getIdByCode('BGN'),
            'rate' => 1,
        );
        
        // Транзакция: Доставчик плаща на каса 100 лв.
        $transaction = new acc_journal_Transaction(
            array(
                'valior' => $rec->valior,   // датата на ордера
                'entries' =>array( 
                    (object)array(
                        'amount' => $rec->amount * $rec->rate,	// равностойноста на сумата в основната валута
                        'debit' => array(
                            '501', // Каси,
                                array('cash_Cases', $caseId),
                                array('currency_Currencies', $currencyId),
                            'quantity' => $rec->amount,
                        ),
                        'credit' => array(
                            '401', // Задължения към доставчици
                                array('crm_Companies', $contragentId),
                                array('currency_Currencies', $currencyId),
                            'quantity' => $rec->amount,
                        )
                    )
                )
            )
        );
        
        $this->assertTrue($transaction->check());
    }

    public function xtestBuildSteps()
    {
        $id = 1;
        
        expect($rec = cash_Pko::fetch($id));
        
        // Намираме класа на контрагента
        $contragentId = doc_Folders::fetchCoverId($rec->folderId);
        $contragentClass = doc_Folders::fetchCoverClassName($rec->folderId);
        
        $transaction = new acc_journal_Transaction();
        
        $transaction
            ->add()
            ->setDebit(
                array(
                    'account' => '501',
                    'items' => array(
                        array('cash_Cases', $rec->peroCase),
                        array('currency_Currencies', $rec->currencyId),
                    ),
                    'quantity' => $rec->amount,
                    'price'    => $rec->rate,
                )
            )
            ->setCredit(
                array(
                    'account' => acc_journal_Account::byId($rec->creditAccounts),
                    'items'   => array(
                        array($contragentClass, $contragentId),
                        array('currency_Currencies', $rec->currencyId),
                    ),
                    'quantity' => $rec->amount,
                    'price'    => $rec->rate,
                )
            );
        
        $this->assertTrue($transaction->check());
        
    }
}