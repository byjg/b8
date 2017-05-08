<?php

#   Copyright (C) 2006-2012 Tobias Leupold <tobias.leupold@web.de>
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
 * The DBA (Berkeley DB) backend for communicating with the database.
 * Copyright (C) 2006-2012 Tobias Leupold <tobias.leupold@web.de>
 *
 * @license LGPL 2.1
 * @access public
 * @package b8
 * @author Tobias Leupold
 */

class b8_storage_dba extends b8_storage_base
{

	public $config = array(
		'database' => 'wordlist.db',
		'handler'  => 'db4'
	);
	
	private $_db = NULL;
	
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
		
			switch($name) {
		
				case 'database':
				case 'handler':
					$this->config[$name] = (string) $value;
					break;
				
				default:
					throw new Exception("b8_storage_dba: Unknown configuration key: \"$name\"");
					
			}
			
		}
		
		# Connect to the database
		
		$dbfile = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $this->config['database'];
		
		if(is_file($dbfile) !== TRUE)
			throw new Exception("b8_storage_dba: Database file \"{$this->config['database']}\" not found.");
			
		if(is_readable($dbfile) !== TRUE)
			throw new Exception("b8_storage_dba: Database file \"{$this->config['database']}\" is not readable.");
			
		if(is_writeable($dbfile) !== TRUE)
			throw new Exception("b8_storage_dba: Database file \"{$this->config['database']}\" is not writeable.");
		
		$this->_db = dba_open($dbfile, 'w', $this->config['handler']);
		
		if($this->_db === FALSE) {
			
			$this->connected = FALSE;
			$this->_db = NULL;
			
			throw new Exception("b8_storage_dba: Could not connect to database file \"{$this->config['database']}\".");
		}
		
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
		dba_close($this->_db);
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
	
		$data = array();
		
		foreach ($tokens as $token) {
		
			# Try to the raw data in the format "count_ham count_spam lastseen"
			$count = dba_fetch($token, $this->_db);
			
			if($count !== FALSE) {
				
				# Split the data by space characters
				$split_data = explode(' ', $count);
				
				# As the internal variables just have one single value,
				# we have to check for this
				
				$count_ham  = NULL;
				$count_spam = NULL;
				
				if(isset($split_data[0]))
					$count_ham  = (int) $split_data[0];
				
				if(isset($split_data[1]))
					$count_spam = (int) $split_data[1];
				
				# Append the parsed data
				$data[$token] = array(
					'count_ham'  => $count_ham,
					'count_spam' => $count_spam
				);
			
			}
			
		}
		
		return $data;
		
	}
	
	/**
	 * Translates a count array to a count data string
	 *
	 * @access private
	 * @param array ('count_ham' => int, 'count_spam' => int)
	 * @return string The translated array
	 */
	
	private function _translate_count($count) {
	
		# Assemble the count data string
		$count_data = "{$count['count_ham']} {$count['count_spam']}";
		
		# Remove whitespace from data of the internal variables
		return(rtrim($count_data));
		
	}
	
	/**
	 * Store a token to the database.
	 *
	 * @access protected
	 * @param string $token
	 * @param string $count
	 * @return bool TRUE on success or FALSE on failure
	 */
	
	protected function _put($token, $count) {
		return dba_insert($token, $this->_translate_count($count), $this->_db);
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
		return dba_replace($token, $this->_translate_count($count), $this->_db);
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
		return dba_delete($token, $this->_db);
	}
	
	/**
	 * Does nothing. We just need this function because the (My)SQL backend(s) need it.
	 *
	 * @access protected
	 * @return void
	 */
	
	protected function _commit()
	{
		return;
	}
	
}

?>