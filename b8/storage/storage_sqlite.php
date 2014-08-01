<?php

#   Copyright (C) 2006-2014 Tobias Leupold <tobias.leupold@web.de>
#
#   This file is part of the b8 package
#
#   This program is free software; you can redistribute it and/or modify it
#   under the terms of the GNU Lesser General Public License as published by
#   the Free Software Foundation in version 2.1 of the License.
#
#   This program is distributed in the hope that it will be useful, but
#   WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
#   or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public
#   License for more details.
#
#   You should have received a copy of the GNU Lesser General Public License
#   along with this program; if not, write to the Free Software Foundation,
#   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.

/**
 * The SQLite 3 backend for communicating with the database.
 * Copyright (C) 2013 BohwaZ <bohwaz@bohwaz.net>
 * Copyright (C) 2013-2014 Tobias Leupold <tobias.leupold@web.de>
 *
 * @license LGPL 2.1
 * @access public
 * @package b8
 * @author BohwaZ
 * @author Tobias Leupold
 */

class b8_storage_sqlite extends b8_storage_base
{
    public $config = array(
        'database'   => 'wordlist.sqlite',
        'table_name' => 'b8_wordlist',
    );

    private $_db = NULL;
    private $_transactionStarted = FALSE;

    /**
     * Constructs the backend.
     *
     * @access public
     * @param string $config
     */
    function __construct($config, &$degenerator)
    {
        # Pass the degenerator instance to this class
        $this->degenerator = $degenerator;

        # Validate the config items
        foreach ($config as $name => $value) {
            switch ($name) {
            case 'database':
            case 'table_name':
                $this->config[$name] = (string) $value;
                break;
            default:
                throw new Exception("b8_storage_sqlite: Unknown configuration key: \"$name\"");
            }
        }

        # Connect to the database

        # First, we do some checks.
        if( file_exists($this->config['database']) !== TRUE)
            throw new Exception("b8_storage_sqlite: Database file \"{$this->config['database']}\" does not exist.");
        if (is_file($this->config['database']) !== TRUE)
            throw new Exception("b8_storage_sqlite: Database file \"{$this->config['database']}\" is not a file.");
        if (is_readable($this->config['database']) !== TRUE)
            throw new Exception("b8_storage_sqlite: Database file \"{$this->config['database']}\" is not readable.");
        if (is_writable($this->config['database']) !== TRUE)
            throw new Exception("b8_storage_sqlite: Database file \"{$this->config['database']}\" is not writeable.");

        # At least, the database file exists and we can read and write it. So connect to it.
        $this->_db = new SQLite3($this->config['database']);
        if ($this->_db === FALSE) {
            $this->connected = FALSE;
            $this->_db = NULL;
            throw new Exception("b8_storage_sqlite: Could not connect to database file \"{$this->config['database']}\".");
        }

        # Let's see if we actually have an SQLite database file
        # and if it does actually contain the given b8 wordlist table
        $escapedTable = $this->_db->escapeString($this->config['table_name']);
        $result = $this->_db->query("
            SELECT tbl_name FROM sqlite_master
            WHERE type='table'
            AND tbl_name='$escapedTable'
        ;");

        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row === FALSE)
            throw new Exception("b8_storage_sqlite: Database file \"{$this->config['database']}\" does not contain table \"{$this->config['table_name']}\".");

        # Let's see if the wordlist table actually has the needed structure

        $result = $this->_db->query("
            SELECT
                token,
                count_ham,
                count_spam
            FROM '$escapedTable'
            LIMIT 1
        ;");

        if ($result === FALSE)
            throw new Exception("b8_storage_sqlite: Table \"{$this->config['table_name']}\" in database file \"{$this->config['database']}\" does not have the needed structure.");

        # Let's see if this is a b8 database and the version is okay
        $this->check_database();
    }

    /**
     * Closes the database connection.
     *
     * @access public
     * @return void
     */
    function __destruct()
    {
        # Commit any queries
        $this->_commit();
        # Close the connection
        $this->_db->close();
    }

    /**
     * Does the actual interaction with the database when fetching data.
     *
     * @access protected
     * @param array $tokens
     * @return mixed Returns an array of the returned data in the format array(token => data) or an empty array if there was no data.
     */
    protected function _get_query($tokens)
    {
        # Construct the query ...
        if (count($tokens) > 1) {
            # We have more than 1 token
            $where = array();
            foreach ($tokens as $token) {
                $token = $this->_db->escapeString($token);
                array_push($where, $token);
            }
            $where = "token IN ('" . implode("', '", $where) . "')";
        } elseif (count($tokens) == 1) {
            # We have exactly one token
            $token = $this->_db->escapeString($tokens[0]);
            $where = "token = '" . $token . "'";
        } elseif (count($tokens) == 0) {
            # We have no tokens
            # This can happen when we do a degenerates lookup and we don't have any degenerates.
            return array();
        }

        # ... and fetch the data
        $result = $this->_db->query('
            SELECT token, count_ham, count_spam
            FROM ' . $this->config['table_name'] . '
            WHERE ' . $where . '
        ;');

        $data = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[$row['token']] = array(
                'count_ham'  => $row['count_ham'],
                'count_spam' => $row['count_spam']
            );
        }

        $result->finalize();
        return $data;
    }

    /**
     * Store a token to the database.
     *
     * @access protected
     * @param string $token
     * @param string $count
     * @return bool TRUE on success or FALSE on failure
     */
    protected function _put($token, $count)
    {
        # Check if we are in transaction mode, start it if necessary
        $this->_checkTransactionStarted();

        $statement = $this->_db->prepare('
            INSERT INTO ' . $this->config['table_name'] . ' (
                token, count_ham, count_spam
            )
            VALUES (
                ?, ?, ?
            )
        ;');

        $statement->bindValue(1, $token);
        $statement->bindValue(2, $count['count_ham']);
        $statement->bindValue(3, $count['count_spam']);

        return $statement->execute() ? TRUE : FALSE;
    }

    /**
     * Update an existing token.
     *
     * @access protected
     * @param string $token
     * @param string $count
     * @return bool TRUE on success or FALSE on failure
     */

    protected function _update($token, $count)
    {
        # Check if we are in transaction mode, start it if necessary
        $this->_checkTransactionStarted();

        $statement = $this->_db->prepare('
            INSERT OR REPLACE INTO ' . $this->config['table_name'] . '(
                token, count_ham, count_spam
            )
            VALUES (
                ?, ?, ?
            )
        ;');

        $statement->bindValue(1, $token);
        $statement->bindValue(2, $count['count_ham']);
        $statement->bindValue(3, $count['count_spam']);

        return $statement->execute() ? TRUE : FALSE;
    }

    /**
     * Remove a token from the database.
     *
     * @access protected
     * @param string $token
     * @return bool TRUE on success or FALSE on failure
     */
    protected function _del($token)
    {
        # Check if we are in transaction mode, start it if necessary
        $this->_checkTransactionStarted();

        $statement = $this->_db->prepare('
            DELETE FROM ' . $this->config['table_name'] . '
            WHERE token = ?
        ;');
        $statement->bindValue(1, $token);

        return $statement->execute() ? TRUE : FALSE;
    }

    /**
     * Puts SQLite in transaction mode if necessary
     *
     * @access protected
     * @return void
     */
    protected function _checkTransactionStarted()
    {
        if ($this->_transactionStarted === FALSE) {
            $this->_db->exec('BEGIN;');
            $this->_transactionStarted = TRUE;
        }
    }

    /**
     * Commits any query
     *
     * @access protected
     * @return void
     */
    protected function _commit()
    {
        if ($this->_transactionStarted === TRUE) {
            $this->_db->exec('END;');
            $this->_transactionStarted = FALSE;
        }
    }

}

?>