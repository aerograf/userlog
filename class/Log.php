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

use Xmf\Request;
use XoopsModules\Userlog;

defined('XOOPS_ROOT_PATH') || die('Restricted access');
require_once __DIR__ . '/../include/common.php';

/**
 * Class Log
 */
class Log extends \XoopsObject
{
    /**
     * @var string
     */
    public $helper = null;

    public $store = 0; // store: 0,1->db 2->file 3->both

    public $sourceJSON = [
        'zget',
        'post',
        'request',
        'files',
        'env',
        'session',
        'cookie',
        'header',
        'logger'
    ];// json_encoded fields

    /**
     * constructor
     */
    public function __construct()
    {
        $this->helper = Userlog\Helper::getInstance();
        $this->initVar('log_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('log_time', XOBJ_DTYPE_INT, null, true);
        $this->initVar('uid', XOBJ_DTYPE_INT, null, false);
        $this->initVar('uname', XOBJ_DTYPE_TXTBOX, null, false, 50);
        $this->initVar('admin', XOBJ_DTYPE_INT, null, false);
        $this->initVar('groups', XOBJ_DTYPE_TXTBOX, null, false, 100);
        $this->initVar('last_login', XOBJ_DTYPE_INT, null, true);
        $this->initVar('user_ip', XOBJ_DTYPE_TXTBOX, null, true, 15);
        $this->initVar('user_agent', XOBJ_DTYPE_TXTBOX, null, true, 255);
        $this->initVar('url', XOBJ_DTYPE_TXTBOX, null, true, 255);
        $this->initVar('script', XOBJ_DTYPE_TXTBOX, null, true, 50);
        $this->initVar('referer', XOBJ_DTYPE_TXTBOX, null, true, 255);
        $this->initVar('pagetitle', XOBJ_DTYPE_TXTBOX, null, false, 255);
        $this->initVar('pageadmin', XOBJ_DTYPE_INT, null, false);
        $this->initVar('module', XOBJ_DTYPE_TXTBOX, null, true, 25);
        $this->initVar('module_name', XOBJ_DTYPE_TXTBOX, null, true, 50);
        $this->initVar('item_name', XOBJ_DTYPE_TXTBOX, null, false, 10);
        $this->initVar('item_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('request_method', XOBJ_DTYPE_TXTBOX, null, false, 20);
        $this->initVar('zget', XOBJ_DTYPE_SOURCE);
        $this->initVar('post', XOBJ_DTYPE_SOURCE);
        $this->initVar('request', XOBJ_DTYPE_SOURCE);
        $this->initVar('files', XOBJ_DTYPE_SOURCE);
        $this->initVar('env', XOBJ_DTYPE_SOURCE);
        $this->initVar('session', XOBJ_DTYPE_SOURCE);
        $this->initVar('cookie', XOBJ_DTYPE_SOURCE);
        $this->initVar('header', XOBJ_DTYPE_SOURCE);
        $this->initVar('logger', XOBJ_DTYPE_SOURCE);
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
     * @return Userlog\Log
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
     * @return mixed
     */
    public function getLogTime()
    {
        return $this->helper->formatTime($this->getVar('log_time'));
    }

    /**
     * @return bool|string
     */
    public function last_login()
    {
        return $this->helper->formatTime($this->getVar('last_login'));
    }

    /**
     * @return array|mixed
     */
    public function post()
    {
        $post = $this->getVar('post');

        return is_array($post) ? $post : json_decode($post, true);
    }

    /**
     * @param int    $limit
     * @param int    $start
     * @param string $sort
     * @param string $order
     * @param array  $modules
     * @param int    $since
     * @param array  $users
     * @param array  $groups
     *
     * @return array
     */
    public function getViews(
        $limit = 10,
        $start = 0,
        $sort = 'count',
        $order = 'DESC',
        $modules = [],
        $since = 0,
        $users = [],
        $groups = []
    ) {
        if (!empty($modules)) {
            $criteriaModule = new \CriteriaCompo();
            foreach ($modules as $module_dir => $items) {
                $criteriaItem = new \CriteriaCompo();
                $criteriaItem->add(new \Criteria('module', $module_dir));
                $criteriaItemName = new \CriteriaCompo();
                if (!empty($items['item_name'])) {
                    foreach ($items['item_name'] as $item_name) {
                        // why we cannot use this $criteriaItemName->add(new \Criteria('item_name', $items, "IN"));
                        $criteriaItemName->add(new \Criteria('item_name', $item_name), 'OR');
                    }
                }
                $criteriaItem->add($criteriaItemName);
                $criteriaScript = new \CriteriaCompo();
                if (!empty($items['script'])) {
                    foreach ($items['script'] as $script_name) {
                        $criteriaScript->add(new \Criteria('script', $script_name), 'OR');
                    }
                }
                $criteriaItem->add($criteriaScript);
                $criteriaModule->add($criteriaItem, 'OR');
                unset($criteriaItem, $criteriaItemName, $criteriaScript);
            }
        }

        if (!empty($since)) {
            $starttime     = time() - $this->helper->getSinceTime($since);
            $criteriaSince = new \CriteriaCompo();
            $criteriaSince->add(new \Criteria('log_time', $starttime, '>'));
        }

        if (!empty($users)) {
            $criteriaUser = new \CriteriaCompo();
            $criteriaUser->add(new \Criteria('uid', '(' . implode(',', $users) . ')', 'IN'));
        }
        if (!empty($groups)) {
            $criteriaGroup = new \CriteriaCompo();
            foreach ($groups as $group) {
                $criteriaGroup->add(new \Criteria('groups', '%g' . $group . '%', 'LIKE'), 'OR');
            }
        }

        // add all criterias
        $criteria = new \CriteriaCompo();
        if (!empty($criteriaModule)) {
            $criteria->add($criteriaModule);
        }
        if (!empty($criteriaSince)) {
            $criteria->add($criteriaSince);
        }
        if (!empty($criteriaUser)) {
            $criteria->add($criteriaUser);
        }
        if (!empty($criteriaGroup)) {
            $criteria->add($criteriaGroup);
        }
        $criteria->setLimit($limit);
        $criteria->setStart($start);
        $sortItem = ('module_count' === $sort) ? 'module_name' : $sort;
        $criteria->setSort($sortItem);
        $criteria->setOrder($order);
        $fields = [
            'uid',
            'groups',
            'pagetitle',
            'pageadmin',
            'module',
            'module_name',
            'script',
            'item_name',
            'item_id'
        ];
        $criteria->setGroupBy('pageadmin, module, script, item_name, item_id');

        list($loglogsObj, $itemViews) = $this->helper->getHandler('log')->getLogsCounts($criteria, $fields);
        $criteria->setGroupBy('module');
        $criteria->setSort(('module_count' === $sort) ? 'count' : 'module');
        $moduleViews = $this->helper->getHandler('log')->getCounts($criteria);
        unset($criteria);
        // initializing
        $items = []; // very important!!!
        foreach ($loglogsObj as $key => $loglogObj) {
            $module_dirname = $loglogObj->module();
            $item_id        = $loglogObj->item_id();
            if (!empty($item_id)) {
                $link = 'modules/' . $module_dirname . '/' . $loglogObj->script() . '?' . $loglogObj->item_name() . '=' . $item_id;
            } elseif ('system-root' !== $module_dirname) {
                $link = 'modules/' . $module_dirname . (('system' !== $module_dirname
                                                         && $loglogObj->pageadmin()) ? '/admin/' : '/') . $loglogObj->script();
            } else {
                $link = $loglogObj->script();
            }
            $items[$link]                 = [];
            $items[$link]['count']        = $itemViews[$key];
            $items[$link]['pagetitle']    = $loglogObj->pagetitle();
            $items[$link]['module']       = $module_dirname;
            $items[$link]['module_name']  = $loglogObj->module_name();
            $items[$link]['module_count'] = $moduleViews[$module_dirname];
        }
        foreach ($items as $link => $item) {
            $col1[$link] = $item[$sort];
            $col2[$link] = $item['count'];//second sort by
        }
        if (!empty($items)) {
            array_multisort($col1, ('ASC' === $order) ? SORT_ASC : SORT_DESC, $col2, SORT_DESC, $items);
        }

        return $items;
    }

    /**
     * @param      $tolog
     * @param bool $force
     *
     * @return bool
     */
    public function store($tolog, $force = true)
    {
        if ($this->store > 1) {
            $this->storeFile($tolog);
        } // store file
        if (2 == $this->store) {
            return true;
        } // do not store db
        $this->storeDb($tolog, $force);

        return null;
    }

    /**
     * @param      $tolog
     * @param bool $force
     *
     * @return mixed
     */
    public function storeDb($tolog, $force = true)
    {
        // set vars
        foreach ($tolog as $option => $logvalue) {
            if (!empty($logvalue)) {
                // value array to string. use json_encode
                if (is_array($logvalue) && count($logvalue) > 0) {
                    $logvalue = json_encode($logvalue, (PHP_VERSION > '5.4.0') ? JSON_UNESCAPED_UNICODE : 0);
                }
                switch ($option) {
                    // update referral in stats table
                    case 'referer':
                        if (false === strpos($logvalue, XOOPS_URL)) {
                            $statsObj = Userlog\Stats::getInstance();
                            $statsObj->update('referral', 0, 1, true, parse_url($logvalue, PHP_URL_HOST)); // auto increment 1
                        }
                        break;
                    // update browser and OS in stats table
                    case 'user_agent':
                        $statsObj   = Userlog\Stats::getInstance();
                        $browserArr = $this->helper->getBrowsCap()->getBrowser($logvalue, true);
                        $statsObj->update('browser', 0, 1, true, !empty($browserArr['Parent']) ? (!empty($browserArr['Crawler']) ? 'crawler: ' : '') . $browserArr['Parent'] : 'unknown'); // auto increment 1
                        $statsObj->update('OS', 0, 1, true, $browserArr['Platform']); // auto increment 1
                        break;
                }
                $this->setVar($option, $logvalue);
            }
        }
        $ret = $this->helper->getHandler('log')->insert($this, $force);
        $this->unsetNew();

        return $ret;
    }

    /**
     * @param       $logs
     * @param array $skips
     *
     * @return mixed
     */
    public function arrayToDisplay($logs, $skips = [])
    {
        foreach ($logs as $log_id => $log) {
            $logs[$log_id]['log_time']   = $this->helper->formatTime($logs[$log_id]['log_time']);
            $logs[$log_id]['last_login'] = $this->helper->formatTime($logs[$log_id]['last_login']);
            if (!empty($logs[$log_id]['groups'])) {
                // change g1g2 to Webmasters, Registered Users
                $groups                  = explode('g', substr($logs[$log_id]['groups'], 1)); // remove the first "g" from string
                $userGroupNames          = $this->helper->getFromKeys($this->helper->getGroupList(), $groups);
                $logs[$log_id]['groups'] = implode(',', $userGroupNames);
            }
            foreach ($this->sourceJSON as $option) {
                // if value is not string it was decoded in file
                if (!is_string($logs[$log_id][$option])) {
                    continue;
                }
                $logArr = json_decode($logs[$log_id][$option], true);
                if ($logArr) {
                    $logs[$log_id][$option] = var_export($logArr, true);
                }
            }
            // merge all request_method to one column - possibility to log methods when user dont set to log request_method itself
            $logs[$log_id]['request_method'] = empty($logs[$log_id]['request_method']) ? '' : $logs[$log_id]['request_method'] . "\n";
            foreach ($this->sourceJSON as $option) {
                if (!empty($logs[$log_id][$option])) {
                    $logs[$log_id]['request_method'] .= '$_' . strtoupper($option) . ' ' . $logs[$log_id][$option] . "\n";
                }
                if ('env' === $option) {
                    break;
                } // only $sourceJSON = array("zget","post","request","files","env"
            }
            foreach ($skips as $option) {
                unset($logs[$log_id][$option]);
            }
        }

        return $logs;
    }

    /**
     * @param $tolog
     *
     * @return bool
     */
    public function storeFile($tolog)
    {
        $log_file = $this->helper->getWorkingFile();
        // file create/open/write
        $fileHandler = \XoopsFile::getHandler();
        $fileHandler->__construct($log_file, false);
        if ($fileHandler->size() > $this->helper->getConfig('maxlogfilesize')) {
            $log_file_name = $this->helper->getConfig('logfilepath') . '/' . USERLOG_DIRNAME . '/' . $this->helper->getConfig('logfilename');
            $old_file      = $log_file_name . '_' . date('Y-m-d_H-i-s') . '.' . $this->helper->logext;
            if (!$result = rename($log_file, $old_file)) {
                $this->setErrors("ERROR renaming ({$log_file})");

                return false;
            }
        }
        // force to create file if not exist
        if (!$fileHandler->exists()) {
            if (!$fileHandler->__construct($log_file, true)) { // create file and folder
                // Errors Warning: mkdir() [function.mkdir]: Permission denied in file /class/file/folder.php line 529
                $this->setErrors("Cannot create folder/file ({$log_file})");

                return false;
            }
            $this->setErrors("File was not exist create file ({$log_file})");
            // update the new file in database
            $statsObj = Userlog\Stats::getInstance();
            $statsObj->update('file', 0, 0, false, $log_file); // value = 0 to not auto increment
            // update old file if exist
            if (!empty($old_file)) {
                $statsObj->update('file', 0, 0, false, $old_file); // value = 0 to not auto increment
            }
            $statsObj->updateAll('file', 100); // prob = 100
            $data = '';
        } else {
            $data = "\n";
        }
        $data .= json_encode($tolog, (PHP_VERSION > '5.4.0') ? JSON_UNESCAPED_UNICODE : 0);
        if (false === $fileHandler->open('a')) {
            $this->setErrors("Cannot open file ({$log_file})");

            return false;
        }
        if (false === $fileHandler->write($data)) {
            $this->setErrors("Cannot write to file ({$log_file})");

            return false;
        }
        $fileHandler->close();

        return true;
    }

    /**
     * @param array  $log_files
     * @param        $headers
     * @param string $csvNamePrefix
     * @param string $delimiter
     *
     * @return bool|string
     */
    public function exportFilesToCsv($log_files = [], $headers, $csvNamePrefix = 'list_', $delimiter = ';')
    {
        $log_files = $this->parseFiles($log_files);
        if (0 == ($totalFiles = count($log_files))) {
            $this->setErrors(_AM_USERLOG_FILE_SELECT_ONE);

            return false;
        }
        list($logs, $totalLogs) = $this->getLogsFromFiles($log_files);
        $logs          = $this->arrayToDisplay($logs);
        $csvNamePrefix = basename($csvNamePrefix);
        if ($csvFile == $this->exportLogsToCsv($logs, $headers, $csvNamePrefix . 'from_file_total_' . $totalLogs, $delimiter)) {
            return $csvFile;
        }

        return false;
    }

    /**
     * @param        $logs
     * @param        $headers
     * @param string $csvNamePrefix
     * @param string $delimiter
     *
     * @return bool|string
     */
    public function exportLogsToCsv($logs, $headers, $csvNamePrefix = 'list_', $delimiter = ';')
    {
        $csvFile = $this->helper->getConfig('logfilepath') . '/' . USERLOG_DIRNAME . '/export/csv/' . $csvNamePrefix . '_' . date('Y-m-d_H-i-s') . '.csv';
        // file create/open/write
        /** @var \XoopsFileHandler $fileHandler */
        $fileHandler = \XoopsFile::getHandler();
        $fileHandler->__construct($csvFile, false);
        // force to create file if not exist
        if (!$fileHandler->exists()) {
            $fileHandler->__construct($csvFile, true); // create file and folder
            $this->setErrors("File was not exist create file ({$csvFile})");
        }
        if (false === $fileHandler->open('a')) {
            $this->setErrors("Cannot open file ({$csvFile})");

            return false;
        }
        if (!fputcsv($fileHandler->handler, $headers, $delimiter)) {
            return false;
        }
        foreach ($logs as $thisRow) {
            if (!fputcsv($fileHandler->handler, $thisRow, $delimiter)) {
                return false;
            }
        }
        $fileHandler->close();

        return $csvFile;
    }

    /**
     * @param array  $log_files
     * @param int    $limit
     * @param int    $start
     * @param null   $options
     * @param string $sort
     * @param string $order
     *
     * @return array
     */
    public function getLogsFromFiles(
        $log_files = [],
        $limit = 0,
        $start = 0,
        $options = null,
        $sort = 'log_time',
        $order = 'DESC'
    ) {
        $logs    = [];
        $logsStr = $this->readFiles($log_files);
        // if no logs return empty array and total = 0
        if (empty($logsStr)) {
            return [[], 0];
        }
        foreach ($logsStr as $id => $log) {
            $logArr = json_decode($log, true);
            // check if data is correct in file before do anything more
            if (!is_array($logArr) || !array_key_exists('log_id', $logArr)) {
                continue;
            }
            foreach ($logArr as $option => $logvalue) {
                // value array to string
                $logs[$id][$option] = is_array($logvalue) ? ((count($logvalue) > 0) ? var_export($logvalue, true) : '') : $logvalue;
            }
        }
        // START Criteria in array
        foreach ($options as $key => $val) {
            // if user input an empty variable unset it
            if (empty($val)) {
                continue;
            }
            // deal with greater than and lower than
            $tt = substr($key, -2);
            switch ($tt) {
                case 'GT':
                    $op = substr($key, 0, -2);
                    break;
                case 'LT':
                    $op = substr($key, 0, -2);
                    break;
                default:
                    $op = $key;
                    break;
            }
            $val_arr = explode(',', $val);
            // if type is text
            if (!empty($val_arr[0]) && 0 == (int)$val_arr[0]) {
                foreach ($logs as $id => $log) {
                    if (is_array($log[$op])) {
                        $log[$op] = json_encode($log[$op], (PHP_VERSION > '5.4.0') ? JSON_UNESCAPED_UNICODE : 0);
                    }
                    foreach ($val_arr as $qry) {
                        // if !QUERY eg: !logs.php,views.php
                        if (0 === strpos($qry, '!')) {
                            $flagStr = true;
                            if (false !== strpos($log[$op], substr($qry, 1))) {
                                $flagStr = false; // have that delete
                                break; // means AND
                            }
                        } else {
                            $flagStr = false;
                            if (false !== strpos($log[$op], $qry)) {
                                $flagStr = true; // have that dont delete
                                break; // means OR
                            }
                        }
                    }
                    if (!$flagStr) {
                        unset($logs[$id]);
                    }
                }
            } else {
                // if there is one value - deal with =, > ,<
                if (1 == count($val_arr)) {
                    $val_int = $val_arr[0];
                    if ('log_time' === $op || 'last_login' === $op) {
                        $val_int = time() - $this->helper->getSinceTime($val_int);
                    }
                    // query is one int $t (=, < , >)
                    foreach ($logs as $id => $log) {
                        switch ($tt) {
                            case 'GT':
                                if ($log[$op] <= $val_int) {
                                    unset($logs[$id]);
                                }
                                break;
                            case 'LT':
                                if ($log[$op] >= $val_int) {
                                    unset($logs[$id]);
                                }
                                break;
                            default:
                                if ($log[$op] != $val_int) {
                                    unset($logs[$id]);
                                }
                                break;
                        }
                    }
                } else {
                    // query is an array of int separate with comma. use OR ???
                    foreach ($logs as $id => $log) {
                        if (!in_array($log[$op], $val_arr)) {
                            unset($logs[$id]);
                        }
                    }
                }
            }
        }
        // END Criteria in array
        // if no logs return empty array and total = 0
        if (empty($logs)) {
            return [[], 0];
        }

        // sort order array. multisort is possible :D
        if (!empty($sort)) {
            // log_id is just the same as log_time
            if ('log_id' === $sort) {
                $sort = 'log_time';
            }
            // $typeFlag = is_numeric($logs[0][$sort]) ? SORT_NUMERIC : SORT_STRING;
            // Obtain a list of columns
            foreach ($logs as $key => $log) {
                $col[$key] = $log[$sort];
                //$col2[$key]  = $log[$sort2];
            }
            // Add $logs as the last parameter, to sort by the common key
            array_multisort($col, ('ASC' === $order) ? SORT_ASC : SORT_DESC, $logs);
        }
        // get count
        $total = count($logs);
        // now slice the array with desired start and limit
        if (!empty($limit)) {
            $logs = array_slice($logs, $start, $limit);
        }

        return [$logs, $total];
    }

    /**
     * @param array $log_files
     *
     * @return array
     */
    public function readFiles($log_files = [])
    {
        $log_files = $this->parseFiles($log_files);
        if (0 == ($totalFiles = count($log_files))) {
            return $this->readFile();
        }
        $logs = [];
        foreach ($log_files as $file) {
            $logs = array_merge($logs, $this->readFile($file));
        }

        return $logs;
    }

    /**
     * @param array $log_files
     * @param null  $mergeFileName
     *
     * @return bool|string
     */
    public function mergeFiles($log_files = [], $mergeFileName = null)
    {
        $log_files = $this->parseFiles($log_files);
        if (0 == ($totalFiles = count($log_files))) {
            $this->setErrors(_AM_USERLOG_FILE_SELECT_ONE);

            return false;
        }
        $logs          = [];
        $logsStr       = $this->readFiles($log_files);
        $data          = implode("\n", $logsStr);
        $mergeFile     = $this->helper->getConfig('logfilepath') . '/' . USERLOG_DIRNAME . '/';
        $mergeFileName = basename($mergeFileName, '.' . $this->helper->logext);
        if (empty($mergeFileName)) {
            $mergeFile .= $this->helper->getConfig('logfilename') . '_merge_' . count($log_files) . '_files_' . date('Y-m-d_H-i-s');
        } else {
            $mergeFile .= $mergeFileName;
        }
        $mergeFile .= '.' . $this->helper->logext;

        // file create/open/write
        $fileHandler = \XoopsFile::getHandler();
        $fileHandler->__construct($mergeFile, false); //to see if file exist
        if ($fileHandler->exists()) {
            $this->setErrors("file ({$mergeFile}) is exist");

            return false;
        }
        $fileHandler->__construct($mergeFile, true); // create file and folder
        if (false === $fileHandler->open('a')) {
            $this->setErrors("Cannot open file ({$mergeFile})");

            return false;
        }
        if (false === $fileHandler->write($data)) {
            $this->setErrors("Cannot write to file ({$mergeFile})");

            return false;
        }
        $fileHandler->close();

        return $mergeFile;
    }

    /**
     * @param null $log_file
     *
     * @return array
     */
    public function readFile($log_file = null)
    {
        if (!$log_file) {
            $log_file = $this->helper->getWorkingFile();
        }
        // file open/read
        $fileHandler = \XoopsFile::getHandler();
        // not create file if not exist
        $fileHandler->__construct($log_file, false);
        if (!$fileHandler->exists()) {
            $this->setErrors("Cannot open file ({$log_file})");

            return [];
        }

        if (false === ($data = $fileHandler->read())) {
            $this->setErrors("Cannot read file ({$log_file})");

            return [];
        }
        $fileHandler->close();
        $logs = explode("\n", $data);

        return $logs;
    }

    /**
     * @param array $log_files
     *
     * @return int
     */
    public function deleteFiles($log_files = [])
    {
        $log_files = $this->parseFiles($log_files);
        if (0 == ($totalFiles = count($log_files))) {
            $this->setErrors(_AM_USERLOG_FILE_SELECT_ONE);

            return false;
        }
        $deletedFiles = 0;
        // file open/read
        $fileHandler = \XoopsFile::getHandler();
        foreach ($log_files as $file) {
            $fileHandler->__construct($file, false);
            if (!$fileHandler->exists()) {
                $this->setErrors("({$file}) is a folder or is not exist");
                continue;
            }
            if (false === ($ret = $fileHandler->delete())) {
                $this->setErrors("Cannot delete ({$file})");
                continue;
            }
            ++$deletedFiles;
        }
        $fileHandler->close();

        return $deletedFiles;
    }

    /**
     * @param null $log_file
     * @param null $newFileName
     *
     * @return bool|string
     */
    public function renameFile($log_file = null, $newFileName = null)
    {
        if (!is_string($log_file)) {
            $this->setErrors(_AM_USERLOG_FILE_SELECT_ONE);

            return false;
        }
        // check if file exist
        $fileHandler = \XoopsFile::getHandler();
        $fileHandler->__construct($log_file, false);
        if (!$fileHandler->exists()) {
            $this->setErrors("({$log_file}) is a folder or is not exist");

            return false;
        }

        $newFileName = basename($newFileName, '.' . $this->helper->logext);
        if (empty($newFileName)) {
            $newFileName = $fileHandler->name() . '_rename_' . date('Y-m-d_H-i-s');
        }
        $newFile = dirname($log_file) . '/' . $newFileName . '.' . $this->helper->logext;
        // check if new file exist => return false
        $fileHandler->__construct($newFile, false);
        if ($fileHandler->exists()) {
            $this->setErrors("({$newFile}) is exist");

            return false;
        }
        if (!@rename($log_file, $newFile)) {
            $this->setErrors("Cannot rename ({$log_file})");

            return false;
        }
        $fileHandler->close();

        return $newFile;
    }

    /**
     * @param null $log_file
     * @param null $newFileName
     *
     * @return bool|string
     */
    public function copyFile($log_file = null, $newFileName = null)
    {
        if (!is_string($log_file)) {
            $this->setErrors(_AM_USERLOG_FILE_SELECT_ONE);

            return false;
        }
        // check if file exist
        $fileHandler = \XoopsFile::getHandler();
        $fileHandler->__construct($log_file, false);
        if (!$fileHandler->exists()) {
            $this->setErrors("({$log_file}) is a folder or is not exist");

            return false;
        }

        $newFileName = basename($newFileName, '.' . $this->helper->logext);
        if (empty($newFileName)) {
            $newFileName = $fileHandler->name() . '_copy_' . date('Y-m-d_H-i-s');
        }
        $newFile = dirname($log_file) . '/' . $newFileName . '.' . $this->helper->logext;
        // check if new file exist => return false
        $fileHandler->__construct($newFile, false);
        if ($fileHandler->exists()) {
            $this->setErrors("({$newFile}) is exist");

            return false;
        }
        if (!@copy($log_file, $newFile)) {
            $this->setErrors("Cannot copy ({$log_file})");

            return false;
        }
        $fileHandler->close();

        return $newFile;
    }

    /**
     * @param array $folders
     *
     * @return array
     */
    public function getFilesFromFolders($folders = [])
    {
        list($allFiles, $totalFiles) = $this->helper->getAllLogFiles();
        if (empty($totalFiles)) {
            return [];
        }
        $pathFiles = [];
        $getAll    = false;
        if (in_array('all', $folders)) {
            $getAll = true;
        }
        foreach ($allFiles as $path => $files) {
            if ($getAll || in_array($path, $folders)) {
                foreach ($files as $file) {
                    $pathFiles[] = $path . '/' . $file;
                }
            }
        }

        return $pathFiles;
    }

    /**
     * @param array $log_files
     *
     * @return array
     */
    public function parseFiles($log_files = [])
    {
        $pathFiles = $this->getFilesFromFolders($log_files);
        $log_files = array_unique(array_merge($log_files, $pathFiles));
        // file open/read
        $fileHandler = \XoopsFile::getHandler();
        foreach ($log_files as $key => $file) {
            $fileHandler->__construct($file, false);
            if (!$fileHandler->exists()) {
                $this->setErrors("({$file}) is a folder or is not exist");
                unset($log_files[$key]);
                continue;
            }
        }
        $fileHandler->close();

        return $log_files;
    }

    /**
     * @param array $log_files
     * @param null  $zipFileName
     *
     * @return string
     */
    public function zipFiles($log_files = [], $zipFileName = null)
    {
        $log_files = $this->parseFiles($log_files);
        if (0 == ($totalFiles = count($log_files))) {
            $this->setErrors('No file to zip');

            return false;
        }
        //this folder must be writeable by the server
        $zipFolder     = $this->helper->getConfig('logfilepath') . '/' . USERLOG_DIRNAME . '/zip';
        $folderHandler = \XoopsFile::getHandler('folder', $zipFolder, true);// create if not exist
        $zipFileName   = basename($zipFileName, '.zip');
        if (empty($zipFileName)) {
            $zipFileName = $this->helper->getConfig('logfilename') . '_zip_' . $totalFiles . '_files_' . date('Y-m-d_H-i-s') . '.zip';
        } else {
            $zipFileName .= '.zip';
        }
        $zipFile = $zipFolder . '/' . $zipFileName;

        $zip = new \ZipArchive();

        if (true !== $zip->open($zipFile, \ZipArchive::CREATE)) {
            $this->setErrors("Cannot open ({$zipFile})");

            return false;
        }
        foreach ($log_files as $file) {
            if (!$zip->addFile($file, basename($file))) {
                $this->setErrors("Cannot zip ({$file})");
            }
        }
        // if there are some files existed in zip file and/or some files overwritten
        if ($totalFiles != $zip->numFiles) {
            $this->setErrors("Number of files operated in zipped file: ({$zip->numFiles})");
        }
        //$this->setErrors("Zip file name: ({$zip->filename})");
        $zip->close();

        return $zipFile;
    }

    /**
     * @param array $currentFile
     * @param bool  $multi
     * @param int   $size
     *
     * @return \XoopsFormSelect
     */
    public function buildFileSelectEle($currentFile = [], $multi = false, $size = 3)
    {
        // $modversion['config'][$i]['options'] = array(_AM_USERLOG_FILE_WORKING=>'0',_AM_USERLOG_STATS_FILEALL=>'all');
        if (0 == count($currentFile) || '0' == $currentFile[0]) {
            $currentFile = $this->helper->getWorkingFile();
        }
        $fileEl = new \XoopsFormSelect(_AM_USERLOG_FILE, 'file', $currentFile, $size, $multi);
        list($allFiles, $totalFiles) = $this->helper->getAllLogFiles();
        if (empty($totalFiles)) {
            return $fileEl;
        }
        $log_file_name = $this->helper->getConfig('logfilename');
        $working_file  = $log_file_name . '.' . $this->helper->logext;
        $fileEl->addOption('all', _AM_USERLOG_STATS_FILEALL);
        foreach ($allFiles as $path => $files) {
            $fileEl->addOption($path, '>' . $path);
            foreach ($files as $file) {
                $fileEl->addOption($path . '/' . $file, '-----' . $file . (($file == $working_file) ? '(' . _AM_USERLOG_FILE_WORKING . ')' : ''));
            }
        }

        return $fileEl;
    }

    /**
     * @return bool
     */
    public function setItem()
    {
        // In very rare occasions like newbb the item_id is not in the URL $_REQUEST
        //        require_once __DIR__ . '/plugin/plugin.php';
        //        require_once __DIR__ . '/plugin/Abstract.php';
        if ($plugin = Userlog\Plugin\Plugin::getPlugin($this->helper->getLogModule()->getVar('dirname'), USERLOG_DIRNAME, true)) {
            /*
            // get all module scripts can accept an item_name to check if this script is exist
            $scripts = $plugin->item();
            $ii = 0;
            $len_script = count($scripts);
            foreach ($scripts as $item_name=>$script_arr) {
                ++$ii;
                $script_arr = is_array($script_arr) ? $script_arr : array($script_arr);
                if(in_array($this->script(), $script_arr)) break;
                if($ii == $len_script) return false;
            }
            */
            $item = $plugin->item($this->script());
            if (empty($item['item_id'])) {
                return false;
            }
            $this->setVar('item_name', $item['item_name']);
            $this->setVar('item_id', $item['item_id']);

            return true;
        }
        // if there is no plugin, use notifications
        $not_config = $this->helper->getLogModule()->getInfo('notification');
        if (!empty($not_config)) {
            foreach ($not_config['category'] as $category) {
                // if $item_id != 0 ---> return true
                if (!empty($category['item_name'])
                    && in_array($this->script(), is_array($category['subscribe_from']) ? $category['subscribe_from'] : [$category['subscribe_from']])
                    && $item_id = Request::getInt($category['item_name'], 0)) {
                    $this->setVar('item_name', $category['item_name']);
                    $this->setVar('item_id', $item_id);

                    return true;
                }
            }
        }

        return false;
    }
}
