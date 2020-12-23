<?php


/**
 *
 *
 * @category  bgerp
 * @package   spas
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2020 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class spas_Helper extends core_Mvc
{


    /**
     * Връща СПАМ точките
     *
     * @param string $body
     *
     * @retun null|double
     */
    public function getScore($body)
    {
        if (!isset($body)) {

            return null;
        }

        static $resArr = array();

        $hash = md5($body);

        if (!isset($resArr[$hash])) {
            $sa = $this->getSa();

            if ($sa) {
                try {
                    $resArr[$hash] = $sa->getScore($body);
                } catch (spas_client_Exception $e) {
                    $this->logWarning($e->getMessage());
                    wp($e->getMessage());
                } catch (Exception $e) {
                    reportException($e);
                } catch (Throwable $t) {
                    reportException($t);
                }
            }
        }

        return $resArr[$hash];
    }


    /**
     * Връща СПАМ точките от репорта
     *
     * @param string $body
     *
     * @retun null|double
     */
    public function getScoreFromReport($body)
    {
        if (!isset($body)) {

            return null;
        }

        static $resArr = array();

        $hash = md5($body);

        if (!isset($resArr[$hash])) {
            $sa = $this->getSa();

            if ($sa) {
                try {
                    $spamReport = $sa->getSpamReport($body);
                    if ($spamReport) {
                        $resArr[$hash] = $spamReport->score;
                    }
                } catch (spas_client_Exception $e) {
                    $this->logWarning($e->getMessage());
                    wp($e->getMessage());
                } catch (Exception $e) {
                    reportException($e);
                } catch (Throwable $t) {
                    reportException($t);
                }
            }
        }

        return $resArr[$hash];
    }


    /**
     * Връща инстанция на драйвера за SA
     *
     * @param array $params
     *
     * @return null|spas_Client
     */
    protected function getSa($params = array())
    {
        if (!core_Packs::isInstalled('spas')) {

            return null;
        }

        static $instArr = array();

        setIfNot($params['hostname'], spas_Setup::get('HOSTNAME'));
        setIfNot($params['port'], spas_Setup::get('PORT'));
        setIfNot($params['user'], spas_Setup::get('USER'));

        $hash = md5(serialize($params));

        if (!isset($instArr[$hash])) {
            try {
                $instArr[$hash] = new spas_Client($params);
             } catch (spas_client_Exception $e) {
                $this->logWarning($e->getMessage());
                wp($e->getMessage());
            } catch (Exception $e) {
                reportException($e);
            } catch (Throwable $t) {
                reportException($t);
            }
        }

        return $instArr[$hash];
    }
}

