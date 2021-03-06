<?php

#   Copyright (C) 2010-2014 Tobias Leupold <tobias.leupold@web.de>
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
 * Functions used by all storage backends
 * Copyright (C) 2010-2014 Tobias Leupold <tobias.leupold@web.de>
 *
 * @license LGPL 2.1
 * @access public
 * @package b8
 * @author Tobias Leupold
 */

namespace B8\Storage;

use B8\Degenerator\DegeneratorInterface;
use B8\Word;

class Dba extends Base
{
    protected $db;

    protected $path;

    /**
     * Dba constructor.
     * @param $path
     * @param DegeneratorInterface $degenerator
     */
    public function __construct($path, $degenerator)
    {
        $this->path = $path;
        $this->degenerator = $degenerator;
    }

    public function storageOpen()
    {
        $this->db = dba_open($this->path, 'w', 'db4');
    }

    public function storageClose()
    {
        dba_close($this->db);
        $this->db = null;
    }

    /**
     * @param array $tokens
     * @return Word[]
     */
    public function storageRetrieve($tokens)
    {
        $data = [];

        foreach ((array)$tokens as $token) {
            // Try to the raw data in the format "count_ham count_spam"
            $count = dba_fetch($token, $this->db);

            if ($count !== false) {
                // Split the data by space characters
                $split_data = explode(' ', $count);

                // As an internal variable may have just one single value, we have to check for this
                $count_ham  = isset($split_data[0]) ? (int) $split_data[0] : null;
                $count_spam = isset($split_data[1]) ? (int) $split_data[1] : null;

                // Append the parsed data
                $data[$token] = new Word($token, $count_ham, $count_spam);
            }
        }

        return $data;
    }

    /**
     * Store a token to the database.
     *
     * @access protected
     * @param Word $word
     * @return void
     */
    public function storagePut($word)
    {
        return dba_insert($word->token, $word->count_ham . " " . $word->count_spam, $this->db);
    }

    /**
     * Update an existing token.
     *
     * @access protected
     * @param Word $word
     * @return void
     */
    public function storageUpdate($word)
    {
        return dba_replace($word->token, $word->count_ham . " " . $word->count_spam, $this->db);
    }

    /**
     * Remove a token from the database.
     *
     * @access protected
     * @param string $token
     * @return void
     */
    public function storageDel($token)
    {
        return dba_delete($token, $this->db);
    }
}
