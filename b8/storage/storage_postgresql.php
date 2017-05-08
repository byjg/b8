<?php

#   Copyright (C) 2006-2013 Tobias Leupold <tobias.leupold@web.de>
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
 * The PostgreSQL backend for communicating with the database.
 * Based on the MySQL backend
 *
 * Copyright (C) 2013 Tom Regner <tom@goochesa.de>
 * Copyright (C) 2013 Tobias Leupold <tobias.leupold@web.de>
 *
 * @license LGPL 2.1
 * @access public
 * @package b8
 * @author Tom Regner (original PostgreSQL backend)
 * @author Tobias Leupold
 */

class b8_storage_postgresql extends b8_storage_base
{

	public $config = array(
		'database'   => 'b8_wordlist',
		'schema'     => 'b8',
		'table_name' => 'b8_wordlist',
		'host'       => 'localhost',
		'port'       => '5432',
		'user'       => FALSE,
		'pass'       => FALSE,
		'connection' => NULL
	);
	
	private $_connection = NULL;
	
	private $_deletes = array();
	private $_puts    = array();
	
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
		
		foreach($config as $name => $value) {
		
			switch($name) {
				
				case 'table_name':
				case 'host':
				case 'user':
				case 'pass':
				case 'database':
				case 'schema':
				case 'port':
					$this->config[$name] = (string) $value;
					break;
					
				case 'connection':
					$this->config['connection'] = $value;
					break;
					
				default:
					throw new Exception("b8_storage_postgresql: Unknown configuration key: \"{$name}\"");
					
			}
			
		}
		
		if($this->config['connection'] !== NULL) {
			
			# A connection has been given, so check it
			
			if(!$this->config['connection'] instanceof PDO_PGSQL)
				throw new Exception('b8_storage_postgresql: The object passed via the "connection" paramter is no PDO_PGSQL instance.');
			
			# If we reach here, we can use the passed resource.
			$this->_connection = $this->config['connection'];
			
		}
		
		else {
		
			# Create a connection
			
			try {
				$this->_connection = new PDO(
					'pgsql:'.
					'host=' .     $this->config['host'] .     ';' .
					'port=' .     $this->config['port'] .     ';' .
					'dbname=' .   $this->config['database'] . ';' .
					'user=' .     $this->config['user'] .     ';' .
					'password=' . $this->config['pass'])
				;
				
			}
			catch(PDOException $e) {
				throw new Exception('b8_storage_postgresql: ' . $e->getMessage());
			}
			
		}
		
		# Check to see if the wordlist table exists
		$sth = $this->_connection->prepare('
			SELECT * FROM pg_catalog.pg_tables
			WHERE schemaname = ? AND tablename = ?
		');
		
		if(!$sth->execute(array($this->_config['schema'], $this->_config['table_name'])))
			throw new Exception('b8_storage_postgresql: ' . print_r($sth->errorInfo(), true));
		
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
	
		# Commit any changes before closing
		$this->_commit();
		
		# Just close the connection if no link-resource was passed and b8 created it's own connection
		if($this->config['connection'] === NULL)
			unset($this->_connection);
			
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
		
		$count = count($tokens);
		
		if($count > 1) {
			# We have more than 1 token
			$qList = $this->_makePlaceholders($count);
			$where = "token IN ({$qList})";
		}
		
		elseif(count($tokens) == 1) {
			# We have exactly one token
			$where = "token = ?";
			$tokens = $tokens[0];
		}
		
		elseif(count($tokens) == 0) {
			# We have no tokens
			# This can happen when we do a degenerates lookup and we don't have any degenerates.
			return array();
		}
		
		# ... and fetch the data
		
		$sql = '
			SELECT token, count_ham, count_spam
			FROM ' . $this->config['schema'] . '.' . $this->config['table_name'] . "
			WHERE $where"
		;
		
		$sth = $this->_connection->prepare($sql);
		
		if($sth->execute($tokens) === FALSE)
			return array();
		
		$data = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$data[$row['token']] = array(
				'count_ham'  => $row['count_ham'],
				'count_spam' => $row['count_spam']
			);
		}
		
		$sth->closeCursor();
		
		unset($sth);
		
		return $data;
		
	}
	
	/**
	* Store a token to the database.
	*
	* @access protected
	* @param string $token
	* @param array $count
	* @return void
	*/
	
	protected function _put($token, $count)
	{
		array_push($this->_puts, array($token, $count['count_ham'], $count['count_spam']));
	}
	
	/**
	* Update an existing token.
	*
	* @access protected
	* @param string $token
	* @param array $count
	* @return void
	*/
	
	protected function _update($token, $count)
	{
		# Puts and updates are treated the same
		# and are subject to the b8_wordlist_update_on_insert rule
		$this->_put($token, $count);
	}
	
	/**
	* Remove a token from the database.
	*
	* @access protected
	* @param string $token
	* @return void
	*/
	
	protected function _del($token)
	{
		array_push($this->_deletes, $token);
	}
	
	/**
	* Commits any modification queries.
	*
	* @access protected
	* @return void
	*/
	
	protected function _commit()
	{
	
		$deleteCount = count($this->_deletes);
		
		if($deleteCount > 0) {
		
			$sth = $this->_connection->prepare("
				DELETE FROM {$this->config['schema']}.{$this->config['table_name']}
				WHERE token IN (" . $this->_makePlaceholders($deleteCount) . ");
			");
			
			$sth->execute($this->_deletes);
			
			$this->_deletes = array();
			
		}
		
		$putsCount = count($this->_puts);
		
		if($putsCount > 0) {
		
			$sql = "
				INSERT INTO {$this->config['schema']}.{$this->config['table_name']}(
					token,
					count_ham,
					count_spam
				)
				VALUES 
			";
			
			$q = array();
			
			for($i = 0; $i < $putsCount; ++$i)
				$q[] = "(?,?,?)";
			
			$sql .= implode(", ", $q);
			
			$sth = $this->_connection->prepare($sql);
			
			for($i = 1, $l = 0; $l < $putsCount; ++$l, ++$i) {
				$sth->bindValue($i, $this->_puts[$l][0]);
				++$i; $sth->bindValue($i, $this->_puts[$l][1]);
				++$i; $sth->bindValue($i, $this->_puts[$l][2]);
			}
			
			$sth->execute();
			$this->_puts = array();
			
		}
		
	}
	
	/**
	* Generates a placeholder string
	*
	* @access private
	* @return string returns the placeholder string
	*/
	
	private function _makePlaceholders($count)
	{
		return str_repeat('?,', $count - 1) . '?';
	}
	
}

?>