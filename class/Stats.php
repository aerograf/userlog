<?php namespace XoopsModules\Userlog;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/
/**
 *  userlog module
 *
 * @copyright       XOOPS Project (https://xoops.org)
 * @license         GNU GPL 2 (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 * @package         userlog class
 * @since           1
 * @author          irmtfan (irmtfan@yahoo.com)
 * @author          XOOPS Project <www.xoops.org> <www.xoops.ir>
 */

use XoopsModules\Userlog;

defined('XOOPS_ROOT_PATH') || die('Restricted access');
require_once __DIR__ . '/../include/common.php';
xoops_loadLanguage('admin', USERLOG_DIRNAME);

/**
 * Class Stats
 */
class Stats extends \XoopsObject
{
    /**
     * @var string
     */
    public $helper = null;
    public $period = ['all' => 0, 'today' => 1, 'week' => 7, 'month' => 30];
    public $type   = [
        'log'      => _AM_USERLOG_STATS_LOG,
        'logdel'   => _AM_USERLOG_STATS_LOGDEL,
        'set'      => _AM_USERLOG_STATS_SET,
        'file'     => _AM_USERLOG_STATS_FILE,
        'fileall'  => _AM_USERLOG_STATS_FILEALL,
        'referral' => _AM_USERLOG_STATS_REFERRAL,
        'browser'  => _AM_USERLOG_STATS_BROWSER,
        'OS'       => _AM_USERLOG_STATS_OS,
        'views'    => _AM_USERLOG_STATS_VIEWS
    ];

    /**
     *
     */
    public function __construct()
    {
        $this->helper = Userlog\Helper::getInstance();
        $this->initVar('stats_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('stats_type', XOBJ_DTYPE_TXTBOX, null, false, 10);
        $this->initVar('stats_link', XOBJ_DTYPE_TXTBOX, null, false, 100);
        $this->initVar('stats_value', XOBJ_DTYPE_INT, null, false);
        $this->initVar('stats_period', XOBJ_DTYPE_INT, null, false);
        $this->initVar('time_update', XOBJ_DTYPE_INT, null, false);
    }

    /**
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        $arg = isset($args[0]) ? $args[0] : null;

        return $this->getVar($method, $arg);
    }

    /**
     * @return Stats
     */
    public static function getInstance()
    {
        static $instance;
        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * @return bool|string
     */
    public function time_update()
    {
        return $this->helper->formatTime($this->getVar('time_update'));
    }
    // $type = null or array() => get all types

    /**
     * @param array  $type
     * @param int    $start
     * @param int    $limit
     * @param string $sort
     * @param string $order
     * @param null   $otherCriteria
     *
     * @return mixed
     */
    public function getAll(
        $type = [],
        $start = 0,
        $limit = 0,
        $sort = 'stats_value',
        $order = 'DESC',
        $otherCriteria = null
    ) {
        $criteria = new \CriteriaCompo();
        if (!empty($type)) {
            $typeArr = is_array($type) ? $type : [$type];
            foreach ($typeArr as $tt) {
                $criteria->add(new \Criteria('stats_type', $tt), 'OR');
            }
        }
        if (!empty($otherCriteria)) {
            $criteria->add($otherCriteria);
        }
        $criteria->setStart($start);
        $criteria->setLimit($limit);
        $criteria->setSort($sort);
        $criteria->setOrder($order);
        $statsObj = $this->helper->getHandler('stats')->getAll($criteria);
        if (empty($statsObj)) {
            return false;
        } // if no result nothing in database
        foreach ($statsObj as $sObj) {
            $link = $sObj->stats_link();
            // if there is a link and only one type just index link
            $index1 = (!empty($link) && (is_array($type) && 1 == count($type))) ? $link : $sObj->stats_type() . $link;
            $index2 = $sObj->stats_period();
            if (!isset($ret[$index1])) {
                $ret[$index1] = [];
            }
            if (!isset($ret[$index1][$index2])) {
                $ret[$index1][$index2] = [];
            }
            $ret[$index1][$index2]['value']       = $sObj->stats_value();
            $ret[$index1][$index2]['time_update'] = $sObj->time_update();
        }

        return $ret;
    }

    /**
     * @param string $type
     * @param int    $prob
     *
     * @return bool
     */
    public function updateAll($type = 'log', $prob = 11)
    {
        if (!$this->helper->probCheck($prob)) {
            return false;
        }
        switch ($type) {
            case 'set':
                // total
                $sets = $this->helper->getHandler('setting')->getCount();
                $this->update('set', 0, $sets);
                break;
            case 'file':
                list($allFiles, $totalFiles) = $this->helper->getAllLogFiles();
                foreach ($allFiles as $path => $files) {
                    $log_file = $path . '/' . $this->helper->getConfig('logfilename') . '.' . $this->helper->logext;
                    $this->update('file', 0, count($files), false, $log_file); // update working file in all paths (now 2)
                }
                // update all files in db link='all'
                $this->update('file', 0, $totalFiles, false, 'all');
                break;
            case 'views':
                break;
            case 'log':
                // if logs exceed the maxlogsperiod delete them
                if (0 != $this->helper->getConfig('maxlogsperiod')) {
                    $criteriaDel = new \CriteriaCompo();
                    $until       = time() - $this->helper->getSinceTime($this->helper->getConfig('maxlogsperiod'));
                    $criteriaDel->add(new \Criteria('log_time', $until, '<'), 'AND');
                    $numDelPeriod = $this->delete('log', 0, 0, $criteriaDel); // all time = maxlogsperiod
                }
                foreach ($this->period as $per) {
                    $criteria = new \CriteriaCompo();
                    if (!empty($per)) {
                        // today, week, month
                        $since = $this->helper->getSinceTime($per);
                        $criteria->add(new \Criteria('log_time', time() - $since, '>'), 'AND');
                    }
                    $logs   = $this->helper->getHandler('log')->getLogsCount($criteria);
                    $exceed = $logs - $this->helper->getConfig('maxlogs');
                    // if logs exceed the maxlogs delete them
                    if ($exceed > 0) {
                        $numDel = $this->delete('log', $per, $exceed, null, true);
                        $logs   -= $numDel;
                    }
                    $this->update('log', $per, $logs);
                }
                break;
            case 'referral':
                $criteria = new \CriteriaCompo();
                $criteria->add(new \Criteria('referer', XOOPS_URL . '%', 'NOT LIKE'));
                $criteria->setGroupBy('referer');
                $outsideReferers = $this->helper->getHandler('log')->getCounts($criteria);
                $referrals       = [];
                foreach ($outsideReferers as $ref => $views) {
                    if (empty($ref)) {
                        continue;
                    }
                    $outRef = parse_url($ref, PHP_URL_HOST);
                    if (!isset($referrals[$outRef])) {
                        $referrals[$outRef] = 0;
                    }
                    $referrals[$outRef] += $views;
                }
                foreach ($referrals as $ref => $views) {
                    $this->update('referral', 0, $views, false, $ref);
                }
                $this->deleteExpiredStats('referral');
                break;
            case 'browser':
            case 'OS':
                $criteria = new \CriteriaCompo();
                $criteria->setGroupBy('user_agent');
                $agents   = $this->helper->getHandler('log')->getCounts($criteria);
                $browsers = [];
                $OSes     = [];
                foreach ($agents as $agent => $views) {
                    if (empty($agent)) {
                        continue;
                    }
                    $browserArr    = $this->helper->getBrowsCap()->getBrowser($agent, true);
                    $browserParent = !empty($browserArr['Parent']) ? (!empty($browserArr['Crawler']) ? 'crawler: ' : '') . $browserArr['Parent'] : 'unknown';
                    if (!isset($browsers[$browserParent])) {
                        $browsers[$browserParent] = 0;
                    }
                    $browsers[$browserParent] += $views;
                    if (!isset($OSes[$browserArr['Platform']])) {
                        $OSes[$browserArr['Platform']] = 0;
                    }
                    $OSes[$browserArr['Platform']] += $views;
                }
                foreach ($browsers as $browser => $views) {
                    $this->update('browser', 0, $views, false, $browser);
                }
                foreach ($OSes as $OS => $views) {
                    $this->update('OS', 0, $views, false, $OS);
                }
                $this->deleteExpiredStats(['browser', 'OS']);
                break;
        }

        return true;
    }

    /**
     * @param string $type
     * @param int    $period
     * @param int    $limitDel
     * @param null   $criteria
     * @param bool   $asObject
     *
     * @return int
     */
    public function delete($type = 'log', $period = 0, $limitDel = 0, $criteria = null, $asObject = false)
    {
        switch ($type) {
            case 'log':
                if ($asObject) {
                    $logsObj = $this->helper->getHandler('log')->getLogs($limitDel, 0, $criteria, 'log_id', 'ASC');
                    $numDel  = 0;
                    foreach (array_keys($logsObj) as $key) {
                        $numDel += $this->helper->getHandler('log')->delete($logsObj[$key], true) ? 1 : 0;
                    }
                    if ($numDel > 0) {
                        $this->update('logdel', $period, $numDel, true); // increment
                    }
                    unset($logsObj);

                    return $numDel;
                }
                $numDel = $this->helper->getHandler('log')->deleteAll($criteria, true, $asObject);
                if ($numDel > 0) {
                    $this->update('logdel', $period, $numDel, true); // increment
                }

                return $numDel;
                break;
        }
    }

    /**
     * @param string $type
     * @param int    $period
     * @param null   $value
     * @param bool   $increment
     * @param string $link
     *
     * @return mixed
     */
    public function update($type = 'log', $period = 0, $value = null, $increment = false, $link = '')
    {
        // check if version is 115 => unique index is added
        if ($this->helper->getModule()->getVar('version') < 115) {
            return false;
        }
        // if there is nothing to add to db
        if (empty($value) && !empty($increment)) {
            return false;
        }
        // for file,referral,browser,OS we should have a link
        if (in_array($type, ['file', 'referral', 'browser', 'OS']) && empty($link)) {
            return false;
        }
        $statsObj = $this->helper->getHandler('stats')->create();

        $statsObj->setVar('stats_type', $type);
        $statsObj->setVar('stats_period', $period);
        $statsObj->setVar('stats_link', $link);
        $statsObj->setVar('stats_value', $value);
        $statsObj->setVar('time_update', time());
        // increment value if increment is true
        $ret = $this->helper->getHandler('stats')->insertUpdate($statsObj, [
            'stats_value' => empty($increment) ? $value : "stats_value + {$value}",
            'time_update' => time()
        ]);
        $this->unsetNew();

        return $ret;
    }

    /**
     * Delete expired statistics for types when time_update < expire time
     *
     * @access public
     *
     * @param array $types  - types ($this->type)
     * @param int   $expire - delete all records exist in the table before expire time - positive for days and negatice for hours - 0 = never expired
     *
     * @return int count of deleted rows
     */
    public function deleteExpiredStats($types = ['browser'], $expire = 1)
    {
        if (empty($expire)) {
            return false;
        } // if $expire = 0 dont delete
        $criteriaDel = new \CriteriaCompo();
        $until       = time() - $this->helper->getSinceTime($expire);
        if (!empty($types)) {
            $criteriaTypes = new \CriteriaCompo();
            $types         = is_array($types) ? $types : [$types];
            foreach ($types as $type) {
                $criteriaTypes->add(new \Criteria('stats_type', $type, '='), 'OR');
            }
            $criteriaDel->add($criteriaTypes, 'AND');
        }
        $criteriaTime = new \CriteriaCompo();
        $criteriaTime->add(new \Criteria('time_update', $until, '<'), 'AND');
        $criteriaDel->add($criteriaTime, 'AND');

        return $this->helper->getHandler('stats')->deleteAll($criteriaDel); // function deleteAll($criteria = null, $force = true, $asObject = false)
    }
}
