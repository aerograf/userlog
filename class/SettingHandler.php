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

defined('XOOPS_ROOT_PATH') || exit('Restricted access.');
require_once __DIR__ . '/../include/common.php';

xoops_loadLanguage('admin', USERLOG_DIRNAME);
xoops_load('XoopsFormLoader');

/**
 * Class SettingHandler
 */
class SettingHandler extends \XoopsPersistableObjectHandler
{
    public $helper = null;

    /**
     * @param null|\XoopsDatabase $db
     */
    public function __construct(\XoopsDatabase $db)
    {
        $this->helper = Userlog\Helper::getInstance();
        parent::__construct($db, USERLOG_DIRNAME . '_set', Setting::class, 'set_id', 'logby');
    }

    /**
     * @param int    $limit
     * @param int    $start
     * @param null   $otherCriteria
     * @param string $sort
     * @param string $order
     * @param null   $fields
     * @param bool   $asObject
     * @param bool   $id_as_key
     *
     * @return mixed
     */
    public function getSets(
        $limit = 0,
        $start = 0,
        $otherCriteria = null,
        $sort = 'set_id',
        $order = 'DESC',
        $fields = null,
        $asObject = true,
        $id_as_key = true)
    {
        $criteria = new \CriteriaCompo();
        if (!empty($otherCriteria)) {
            $criteria->add($otherCriteria);
        }
        $criteria->setLimit($limit);
        $criteria->setStart($start);
        $criteria->setSort($sort);
        $criteria->setOrder($order);
        $ret = $this->getAll($criteria, $fields, $asObject, $id_as_key);

        return $ret;
    }
}
