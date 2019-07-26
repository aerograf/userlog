<?php

namespace XoopsModules\Userlog;

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
require_once dirname(__DIR__) . '/include/common.php';

/**
 * Class Userlog\LogHandler
 */
class LogHandler extends \XoopsPersistableObjectHandler
{
    public $helper = null;

    /**
     * @param null|\XoopsDatabase $db
     */
    public function __construct(\XoopsDatabase $db = null)
    {
        /** @var Userlog\Helper $this ->helper */
        $this->helper = Userlog\Helper::getInstance();
        parent::__construct($db, USERLOG_DIRNAME . '_log', Log::class, 'log_id', 'log_time');
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
    public function getLogs(
        $limit = 0,
        $start = 0,
        $otherCriteria = null,
        $sort = 'log_id',
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
        $ret = &$this->getAll($criteria, $fields, $asObject, $id_as_key);

        return $ret;
    }

    /**
     * @param null $criteria
     * @param null $fields
     * @param bool $asObject
     * @param bool $id_as_key
     *
     * @return array
     */
    public function getLogsCounts($criteria = null, $fields = null, $asObject = true, $id_as_key = true)
    {
        if ($fields && is_array($fields)) {
            if (!in_array($this->keyName, $fields)) {
                $fields[] = $this->keyName;
            }
            $select = implode(',', $fields);
        } else {
            $select = '*';
        }
        $limit = null;
        $start = null;
        $sql   = "SELECT {$select}, COUNT(*) AS count FROM {$this->table}";
        if (isset($criteria) && is_subclass_of($criteria, 'CriteriaElement')) {
            $sql .= ' ' . $criteria->renderWhere();
            if ($groupby = $criteria->getGroupby()) {
                $sql .= !mb_strpos($groupby, 'GROUP BY') ? " GROUP BY {$groupby}" : $groupby;
            }
            if ($sort = $criteria->getSort()) {
                $sql .= " ORDER BY {$sort} " . $criteria->getOrder();
            }
            $limit = $criteria->getLimit();
            $start = $criteria->getStart();
        }
        $result   = $this->db->query($sql, $limit, $start);
        $ret      = [];
        $retCount = [];
        if ($asObject) {
            while (false !== ($myrow = $this->db->fetchArray($result))) {
                if ($id_as_key) {
                    $retCount[$myrow[$this->keyName]] = array_pop($myrow);
                } else {
                    $retCount[] = array_pop($myrow);
                }
                $object = $this->create(false);
                $object->assignVars($myrow);
                if ($id_as_key) {
                    $ret[$myrow[$this->keyName]] = $object;
                } else {
                    $ret[] = $object;
                }
                unset($object);
            }
        } else {
            $object = $this->create(false);
            while (false !== ($myrow = $this->db->fetchArray($result))) {
                if ($id_as_key) {
                    $retCount[$myrow[$this->keyName]] = array_pop($myrow);
                } else {
                    $retCount[] = array_pop($myrow);
                }
                $object->assignVars($myrow);
                if ($id_as_key) {
                    $ret[$myrow[$this->keyName]] = $object->getValues(array_keys($myrow));
                } else {
                    $ret[] = $object->getValues(array_keys($myrow));
                }
            }
            unset($object);
        }

        return [$ret, $retCount];
    }

    /**
     * @param null   $otherCriteria
     * @param string $notNullFields
     *
     * @return int
     */
    public function getLogsCount($otherCriteria = null, $notNullFields = '')
    {
        $criteria = new \CriteriaCompo();
        if (!empty($otherCriteria)) {
            $criteria->add($otherCriteria);
        }

        return $this->getCount($criteria, $notNullFields);
    }

    /**
     * Change Field in a table
     *
     * @access public
     *
     * @param string $field     - name of the field eg: "my_field"
     * @param string $structure - structure of the field eg: "VARCHAR(50) NOT NULL default ''"
     * @param        bool
     *
     * @return bool
     */
    public function changeField($field = null, $structure = null)
    {
        $sql = "ALTER TABLE {$this->table} CHANGE {$field} {$field} {$structure}";
        if (!$result = $this->db->queryF($sql)) {
            xoops_error($this->db->error() . '<br>' . $sql);

            return false;
        }

        return true;
    }

    /**
     * Show Fields in a table - one field or all fields
     *
     * @access   public
     *
     * @param string $field - name of the field eg: "my_field" or null for all fields
     *
     * @internal param array $ret [my_field] = Field    Type    Null    Key        Default        Extra
     *
     * @return array|bool
     */
    public function showFields($field = null)
    {
        $sql = "SHOW FIELDS FROM {$this->table}";
        if (isset($field)) {
            $sql .= " LIKE '{$field}'";
        }
        if (!$result = $this->db->queryF($sql)) {
            xoops_error($this->db->error() . '<br>' . $sql);

            return false;
        }
        $ret = [];
        while (false !== ($myrow = $this->db->fetchArray($result))) {
            $ret[$myrow['Field']] = $myrow;
        }

        return $ret;
    }

    /**
     * Add Field in a table
     *
     * @access public
     *
     * @param string $field     - name of the field eg: "my_field"
     * @param string $structure - structure of the field eg: "VARCHAR(50) NOT NULL default '' AFTER item_id"
     * @param        bool
     *
     * @return bool
     */
    public function addField($field = null, $structure = null)
    {
        if (empty($field) || empty($structure)) {
            return false;
        }
        if ($this->showFields($field)) {
            return false;
        } // field is exist
        $sql = "ALTER TABLE {$this->table} ADD {$field} {$structure}";
        if (!$result = $this->db->queryF($sql)) {
            xoops_error($this->db->error() . '<br>' . $sql);

            return false;
        }

        return true;
    }

    /**
     * Drop Field in a table
     *
     * @access public
     *
     * @param string $field - name of the field
     * @param        bool
     *
     * @return bool
     */
    public function dropField($field = null)
    {
        if (empty($field)) {
            return false;
        }
        if (!$this->showFields($field)) {
            return false;
        } // field is not exist
        $sql = "ALTER TABLE {$this->table} DROP {$field}";
        if (!$result = $this->db->queryF($sql)) {
            xoops_error($this->db->error() . '<br>' . $sql);

            return false;
        }

        return true;
    }

    /**
     * Show index in a table
     *
     * @access   public
     *
     * @param string $index - name of the index (will be used in KEY_NAME)
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
        $index_type = mb_strtoupper($index_type);
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

    /**
     * Show if the object table or any other table is exist in database
     *
     * @access   public
     *
     * @param string $table or $db->prefix("{$table}") eg: $db->prefix("bb_forums") or "bb_forums" will return same result
     * @internal param bool $found
     *
     * @return bool
     */
    public function showTable($table = null)
    {
        if (empty($table)) {
            $table = $this->table;
        } // the table for this object
        // check if database prefix is not added yet and then add it!!!
        if (0 !== mb_strpos($table, $this->db->prefix() . '_')) {
            $table = $this->db->prefix((string)$table);
        }
        $result = $this->db->queryF("SHOW TABLES LIKE '{$table}'");
        $found  = $this->db->getRowsNum($result);

        return empty($found) ? false : true;
    }

    /**
     * Rename an old table to the current object table in database
     *
     * @access public
     *
     * @param string $oldTable or $db->prefix("{$oldTable}") eg: $db->prefix("bb_forums") or "bb_forums" will return same result
     * @param        bool
     *
     * @return bool
     */
    public function renameTable($oldTable)
    {
        if ($this->showTable() || !$this->showTable($oldTable)) {
            return false;
        } // table is current || oldTable is not exist
        // check if database prefix is not added yet and then add it!!!
        if (0 !== mb_strpos($oldTable, $this->db->prefix() . '_')) {
            $oldTable = $this->db->prefix((string)$oldTable);
        }
        if (!$result = $this->db->queryF("ALTER TABLE {$oldTable} RENAME {$this->table}")) {
            xoops_error($this->db->error() . '<br>' . $sql);

            return false;
        }

        return true;
    }
}
