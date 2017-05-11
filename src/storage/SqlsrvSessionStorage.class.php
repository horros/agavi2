<?php
namespace Agavi\Storage;

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2011 the Agavi Project.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+
use Agavi\Core\Context;
use Agavi\Exception\InitializationException;
use Agavi\Exception\StorageException;

/**
 * Provides support for session storage using an ext/sqlsrv connection.
 *
 * <b>Required parameters:</b>
 *
 * # <b>db_table</b> - [none] - The database table in which session data will be
 *                              stored.
 *
 * <b>Optional parameters:</b>
 *
 * # <b>database</b>     - [default]   - The database connection to use
 *                                       (see databases.xml).
 * # <b>db_id_col</b>    - [sess_id]   - The database column in which the
 *                                       session id will be stored.
 * # <b>db_data_col</b>  - [sess_data] - The database column in which the
 *                                       session data will be stored.
 *                                       This must be a varbinary column.
 * # <b>db_time_col</b>  - [sess_time] - The database column in which the
 *                                       session timestamp will be stored.
 * # <b>date_format</b>  - [U]         - The format string passed to date() to
 *                                       format timestamps. Defaults to "U",
 *                                       which means a Unix Timestamp again.
 *
 * @package    agavi
 * @subpackage storage
 *
 * @author     David Zülke <david.zuelke@bitextender.com>
 * @copyright  Authors
 * @copyright  The Agavi Project
 *
 * @since      1.0.6
 *
 * @version    $Id$
 */
class SqlsrvSessionStorage extends SessionStorage
{
    /**
     * @var        resource An ext/sqlsrv database resource.
     */
    protected $connection = null;
    
    /**
     * Initialize this Storage.
     *
     * @param      Context $context An Context instance.
     * @param      array   $parameters An associative array of initialization parameters.
     *
     * @throws     InitializationException If an error occurs while
     *                                                 initializing this Storage.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.0.6
     */
    public function initialize(Context $context, array $parameters = array())
    {
        // initialize the parent
        parent::initialize($context, $parameters);
        
        if (!$this->hasParameter('db_table')) {
            // missing required 'db_table' parameter
            $error = 'No "db_table" configuration parameter given for SqlsrvSessionStorage.';
            throw new InitializationException($error);
        }
        
        // use this object as the session handler
        session_set_save_handler(
            array($this, 'sessionOpen'),
            array($this, 'sessionClose'),
            array($this, 'sessionRead'),
            array($this, 'sessionWrite'),
            array($this, 'sessionDestroy'),
            array($this, 'sessionGC')
        );
    }
    
    /**
     * Close a session.
     *
     * @return     bool true, if the session was closed, otherwise false.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.0.6
     */
    public function sessionClose()
    {
        if ($this->connection) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Destroy a session.
     *
     * @param      string $id A session ID.
     *
     * @return     bool true, if the session was destroyed successfully.
     *
     * @throws     StorageException If the session cannot be destroyed.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.0.6
     */
    public function sessionDestroy($id)
    {
        if (!$this->connection) {
            return false;
        }
        
        // get table/column
        $db_table  = $this->getParameter('db_table');
        $db_id_col = $this->getParameter('db_id_col', 'sess_id');
        
        // delete the record associated with this id
        $sql = sprintf("DELETE FROM %s WHERE %s = ?", $db_table, $db_id_col);
        
        if (sqlsrv_query($this->connection, $sql, array($id))) {
            return true;
        }
        
        // failed to destroy session
        $error = "SqlsrvSessionStorage cannot destroy session, error reported by server:\n\n%s";
        $error = sprintf($error, implode("\n", $this->getContext()->getDatabaseManager()->getDatabase($this->getParameter('database'))->getErrors()));
        throw new StorageException($error);
    }
    
    /**
     * Cleanup old sessions.
     *
     * @param      int $lifetime The lifetime of a session.
     *
     * @return     bool true, if old sessions have been cleaned up successfully.
     *
     * @throws     StorageException If old sessions cannot be cleaned up.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.0.6
     */
    public function sessionGC($lifetime)
    {
        if (!$this->connection) {
            return false;
        }
        
        // determine deletable session time
        $time = time() - $lifetime;
        $time = date($this->getParameter('date_format', 'U'), $time);
        
        // get table/column
        $db_table    = $this->getParameter('db_table');
        $db_time_col = $this->getParameter('db_time_col', 'sess_time');
        
        if (is_numeric($time)) {
            $time = (int)$time;
        }
        
        // delete the records that are expired
        $sql = sprintf('DELETE FROM %s WHERE %s < ?', $db_table, $db_time_col);
        
        if (sqlsrv_query($this->connection, $sql, array($time))) {
            return true;
        }
        
        // failed to cleanup old sessions
        $error = "SqlsrvSessionStorage cannot garbage collect old sessions, error reported by server:\n\n%s";
        $error = sprintf($error, implode("\n", $this->getContext()->getDatabaseManager()->getDatabase($this->getParameter('database'))->getErrors()));
        throw new StorageException($error);
    }
    
    /**
     * Open a session.
     *
     * @param      string $path The path (ignored).
     * @param      string $name The name (ignored).
     *
     * @return     bool true, if the session was opened successfully.
     *
     * @throws     StorageException If the database connection failed.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.0.6
     */
    public function sessionOpen($path, $name)
    {
        // what database are we using?
        $database = $this->getContext()->getDatabaseManager()->getDatabase($this->getParameter('database'));
        if ($database === null || !($database instanceof SqlsrvDatabase)) {
            $error = 'Database connection "' . $database . '" could not be found or is not an AgaviSqlsrvDatabase connection.';
            throw new StorageException($error);
        }
        
        $this->connection = $database->getConnection();
        
        return true;
    }
    
    /**
     * Read a session.
     *
     * @param      string $id A session ID.
     *
     * @return     bool true, if the session was read successfully.
     *
     * @throws     StorageException If the session cannot be read.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.0.6
     */
    public function sessionRead($id)
    {
        if (!$this->connection) {
            return false;
        }
        
        // get table/column
        $db_table    = $this->getParameter('db_table');
        $db_data_col = $this->getParameter('db_data_col', 'sess_data');
        $db_id_col   = $this->getParameter('db_id_col', 'sess_id');
        
        // delete the record associated with this id
        $sql = sprintf("SELECT %s FROM %s WHERE %s = ?", $db_data_col, $db_table, $db_id_col);
        
        $result = sqlsrv_query($this->connection, $sql, array($id));
        
        if ($result != false && sqlsrv_fetch($result)) {
            // found the session
            return sqlsrv_get_field($result, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY));
        } elseif ($result !== false) {
            return '';
        }
        
        // failed to read session data
        $error = "SqlsrvSessionStorage cannot read session, error reported by server:\n\n%s";
        $error = sprintf($error, implode("\n", $this->getContext()->getDatabaseManager()->getDatabase($this->getParameter('database'))->getErrors()));
        throw new StorageException($error);
    }
    
    /**
     * Write session data.
     *
     * @param      string $id A session ID.
     * @param      string $data A serialized chunk of session data.
     *
     * @return     bool true, if the session was written^successfully.
     *
     * @throws     StorageException If the session data cannot be written.
     *
     * @author     David Zülke <david.zuelke@bitextender.com>
     * @since      1.0.6
     */
    public function sessionWrite($id, &$data)
    {
        if (!$this->connection) {
            return false;
        }
        
        // get table/column
        $db_table    = $this->getParameter('db_table');
        $db_data_col = $this->getParameter('db_data_col', 'sess_data');
        $db_id_col   = $this->getParameter('db_id_col', 'sess_id');
        $db_time_col = $this->getParameter('db_time_col', 'sess_time');
        
        $ts = date($this->getParameter('date_format', 'U'));
        if (is_numeric($ts)) {
            $ts = (int)$ts;
        }
        
        // attempt an update first
        $sql = sprintf(
            "UPDATE %s SET %s = ?, %s = ? WHERE %s = ?",
            $db_table,
            $db_data_col,
            $db_time_col,
            $db_id_col
        );
        
        $result = sqlsrv_query($this->connection, $sql, array(array($data, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)), $ts, $id));
        if ($result !== false && sqlsrv_rows_affected($result)) {
            return true;
        } elseif ($result !== false) {
            // no rows affected, so it's time for an insert
            $sql = sprintf(
                "INSERT INTO %s (%s, %s, %s) VALUES(?, ?, ?)",
                $db_table,
                $db_data_col,
                $db_time_col,
                $db_id_col
            );
            
            $result = sqlsrv_query($this->connection, $sql, array(array($data, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)), $ts, $id));
            if ($result !== false && sqlsrv_rows_affected($result)) {
                return true;
            }
        } else {
            // something went wrong
            $error = "SqlsrvSessionStorage cannot insert or update session, error reported by server:\n\n%s";
            $error = sprintf($error, implode("\n", $this->getContext()->getDatabaseManager()->getDatabase($this->getParameter('database'))->getErrors()));
            throw new StorageException($error);
        }
    }
}
