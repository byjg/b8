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
use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmBeforeInvalidException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Exception\OrmModelInvalidException;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Repository;
use ByJG\Util\Uri;

class Rdbms extends Base
{
    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * Rdbms constructor.
     * @param Uri $uri
     * @param DegeneratorInterface $degenerator
     * @throws OrmModelInvalidException
     */
    public function __construct($uri, $degenerator)
    {
        $this->mapper = new Mapper(
            Word::class,
            'b8_wordlist',
            'token'
        );

        $dataset = Factory::getDbRelationalInstance($uri);

        $this->repository = new Repository($dataset, $this->mapper);

        $this->degenerator = $degenerator;
    }

    public function storageOpen()
    {
        // Do nothing;
    }

    public function storageClose()
    {
        // Do nothing;
    }

    /**
     * @param array $tokens
     * @return Word[]
     * @throws InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function storageRetrieve($tokens)
    {
        $collection = $this->repository->filterIn($tokens);

        $data = array();
        foreach ($collection as $row) {
            $data[$row->token] = $row;
        }
        return $data;
    }

    /**
     * Store a token to the database.
     *
     * @access protected
     * @param Word $word
     * @return void
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function storagePut($word)
    {
        $this->repository->save($word);
    }

    /**
     * Update an existing token.
     *
     * @access protected
     * @param Word $word
     * @return void
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function storageUpdate($word)
    {
        $this->repository->save($word);
    }

    /**
     * Remove a token from the database.
     *
     * @access protected
     * @param array $token
     * @return void
     * @throws InvalidArgumentException
     */
    public function storageDel($token)
    {
        $this->repository->delete($token);
    }
}
