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

/**
 * Class StatsHandler
 */
class StatsHandler extends \XoopsPersistableObjectHandler
{
    public $helper = null;

    /**
     * @param null|\XoopsDatabase $db
     */
    public function __construct(\XoopsDatabase $db)
    {
        $this->helper = Userlog\Helper::getInstance();
        parent::__construct($db, USERLOG_DIRNAME . '_stats', Stats::class, 'stats_id', 'stats_type');
    }

    /**
     * @param       $object
     * @param array $duplicate
     * @param bool  $force
     *
     * @return bool
     */
    public function insertUpdate($object, $duplicate = [], $force = true)
    {
        $handler = $this->loadHandler('write');

        if (!$object->isDirty()) {
            trigger_error("Data entry is not inserted - the object '" . get_class($object) . "' is not dirty," . "' with errors: " . implode(', ', $object->getErrors()), E_USER_NOTICE);

            return $object->getVar($this->keyName);
        }
        if (!$handler->cleanVars($object)) {
            trigger_error("Insert failed in method 'cleanVars' of object '" . get_class($object) . "' with errors: " . implode(', ', $object->getErrors()), E_USER_WARNING);

            return $object->getVar($this->keyName);
        }
        $queryFunc = empty($force) ? 'query' : 'queryF';

        if ($object->isNew()) {
            $sql = "INSERT INTO {$this->table}";
            if (!empty($object->cleanVars)) {
                $keys = array_keys($object->cleanVars);
                $vals = array_values($object->cleanVars);
                $sql  .= ' (' . implode(', ', $keys) . ') VALUES (' . implode(',', $vals) . ')';
            } else {
                trigger_error("Data entry is not inserted - no variable is changed in object of '" . get_class($object) . "' with errors: " . implode(', ', $object->getErrors()), E_USER_NOTICE);

                return $object->getVar($this->keyName);
            }
            // START ON DUPLICATE KEY UPDATE
            if (!empty($duplicate)) {
                $sql  .= ' ON DUPLICATE KEY UPDATE';
                $keys = [];
                foreach ($duplicate as $keyD => $valD) {
                    $keys[] = " {$keyD} = {$valD} ";
                }
                $sql .= implode(', ', $keys);
            }
            // END ON DUPLICATE KEY UPDATE
            if (!$result = $this->db->{$queryFunc}($sql)) {
                return false;
            }
            if (!$object->getVar($this->keyName) && $object_id = $this->db->getInsertId()) {
                $object->assignVar($this->keyName, $object_id);
            }
        } elseif (!empty($object->cleanVars)) {
            $keys = [];
            foreach ($object->cleanVars as $k => $v) {
                $keys[] = " `{$k}` = {$v}";
            }
            $sql = 'UPDATE `' . $this->table . '` SET ' . implode(',', $keys) . ' WHERE `' . $this->keyName . '` = ' . $this->db->quote($object->getVar($this->keyName));
            if (!$result = $this->db->{$queryFunc}($sql)) {
                return false;
            }
        }

        return $object->getVar($this->keyName);
    }

    /**
     * Show index in a table
     *
     * @access   public
     *
     * @param string $index - name of the index (will be used in KEY_NAME)
     *
     * @internal param array $ret = Table    Non_unique    Key_name    Seq_in_index    Column_name        Collation    Cardinality        Sub_part    Packed    Null    Index_type    Comment    Index_comment
     *
     * @return array|bool
     */
    public function showIndex($index = null)
    {
        $sql = "SHOW INDEX FROM {$this->table}";
        if (isset($index)) {
            $sql .= " WHERE KEY_NAME = '{$index}'";
        }
        if (!$result = $this->db->queryF($sql)) {
            xoops_error($this->db->error() . '<br>' . $sql);

            return false;
        }
        $ret = [];
        while (false !== ($myrow = $this->db->fetchArray($result))) {
            $ret[] = $myrow;
        }

        return $ret;
    }

    /**
     * Add Index to a table
     *
     * @access public
     *
     * @param string $index      - name of the index
     * @param array  $fields     - array of table fields should be in the index
     * @param string $index_type - type of the index array("INDEX", "UNIQUE", "SPATIAL", "FULLTEXT")
     * @param        bool
     *
     * @return bool
     */
    public function addIndex($index = null, $fields = [], $index_type = 'INDEX')
    {
        if (empty($index) || empty($fields)) {
            return false;
        }
        if ($this->showIndex($index)) {
            return false;
        } // index is exist
        $index_type = strtoupper($index_type);
        if (!in_array($index_type, ['INDEX', 'UNIQUE', 'SPATIAL', 'FULLTEXT'])) {
            return false;
        }
        $fields = is_array($fields) ? implode(',', $fields) : $fields;
        $sql    = "ALTER TABLE {$this->table} ADD {$index_type} {$index} ( {$fields} )";
        if (!$result = $this->db->queryF($sql)) {
            xoops_error($this->db->error() . '<br>' . $sql);

            return false;
        }

        return true;
    }

    /**
     * Drop index in a table
     *
     * @access public
     *
     * @param string $index - name of the index
     * @param        bool
     *
     * @return bool
     */
    public function dropIndex($index = null)
    {
        if (empty($index)) {
            return false;
        }
        if (!$this->showIndex($index)) {
            return false;
        } // index is not exist
        $sql = "ALTER TABLE {$this->table} DROP INDEX {$index}";
        if (!$result = $this->db->queryF($sql)) {
            xoops_error($this->db->error() . '<br>' . $sql);

            return false;
        }

        return true;
    }

    /**
     * Change Index = Drop index + Add Index
     *
     * @access public
     *
     * @param string $index      - name of the index
     * @param array  $fields     - array of table fields should be in the index
     * @param string $index_type - type of the index array("INDEX", "UNIQUE", "SPATIAL", "FULLTEXT")
     * @param        bool
     *
     * @return bool
     */
    public function changeIndex($index = null, $fields = [], $index_type = 'INDEX')
    {
        if ($this->showIndex($index) && !$this->dropIndex($index)) {
            return false;
        } // if index is exist but cannot drop it

        return $this->addIndex($index, $fields, $index_type);
    }
}
