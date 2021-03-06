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
 * Functions used by all storage backend
 * Copyright (C) 2010-2014 Tobias Leupold <tobias.leupold@web.de>
 *
 * @license LGPL 2.1
 * @access public
 * @package b8
 * @author Tobias Leupold
 */

namespace B8\Storage;

use B8\B8;
use B8\Degenerator\DegeneratorInterface;
use B8\Word;
use Exception;

abstract class Base implements StorageInterface
{

    /**
     * @var DegeneratorInterface
     */
    protected $degenerator = null;

    const INTERNALS_TEXTS     = 'b8*texts';
    const INTERNALS_DBVERSION = 'b8*dbversion';

    /**
     * Checks if a b8 database is used and if it's version is okay.
     *
     * @return void throws an exception if something's wrong with the database
     * @throws Exception
     */
    public function checkVersion()
    {
        $this->storageOpen();
        $internals = $this->storageRetrieve(self::INTERNALS_DBVERSION);
        $this->storageClose();

        if ($internals[B8::DBVERSION]->count_ham == B8::DBVERSION) {
            return;
        }

        throw new Exception(
            'b8_storage_base: The connected database is not a b8 v' . b8::DBVERSION . ' database.'
        );
    }

    /**
     * Get the database's internal variables.
     *
     * @access public
     * @return Word Returns an array of all internals.
     */
    public function getInternals()
    {
        $this->storageOpen();
        $internals = $this->storageRetrieve(self::INTERNALS_TEXTS);
        $this->storageClose();

        return $internals[self::INTERNALS_TEXTS];
    }

    /**
     * Get all data about a list of tags from the database.
     *
     * @access public
     * @param array $tokens
     * @return mixed Returns false on failure, otherwise returns array of returned data
     * in the format array('tokens' => array(token => count),
     * 'degenerates' => array(token => array(degenerate => count))).
     */
    public function getTokens($tokens)
    {
        $this->storageOpen();

        # First we see what we have in the database.
        $token_data = $this->storageRetrieve($tokens);

        # Check if we have to degenerate some tokens
        $missing_tokens = array();
        foreach ($tokens as $token) {
            if (! isset($token_data[$token])) {
                $missing_tokens[] = $token;
            }
        }

        if (count($missing_tokens) > 0) {
            # We have to degenerate some tokens
            $degenerates_list = array();

            # Generate a list of degenerated tokens for the missing tokens ...
            $degenerates = $this->degenerator->degenerate($missing_tokens);

            # ... and look them up
            foreach ($degenerates as $token => $token_degenerates) {
                $degenerates_list = array_merge($degenerates_list, $token_degenerates);
            }

            $token_data = array_merge($token_data, $this->storageRetrieve($degenerates_list));
        }

        $this->storageClose();

        # Here, we have all available data in $token_data.

        $return_data_tokens = array();
        $return_data_degenerates = array();

        foreach ($tokens as $token) {
            if (isset($token_data[$token]) === true) {
                # The token was found in the database
                $return_data_tokens[$token] = $token_data[$token];
            } else {
                # The token was not found, so we look if we
                # can return data for degenerated tokens
                foreach ($this->degenerator->getDegenerates($token) as $degenerate) {
                    if (isset($token_data[$degenerate]) === true) {
                        # A degeneration of the token way found in the database
                        $return_data_degenerates[$token][$degenerate] = $token_data[$degenerate];
                    }
                }
            }
        }

        # Now, all token data directly found in the database is in $return_data_tokens
        # and all data for degenerated versions is in $return_data_degenerates, so
        return array(
            'tokens'      => $return_data_tokens,
            'degenerates' => $return_data_degenerates
        );
    }

    /**
     * Stores or deletes a list of tokens from the given category.
     *
     * @access public
     * @param array $tokens
     * @param string $category Either b8::HAM or b8::SPAM
     * @param string $action Either b8::LEARN or b8::UNLEARN
     * @return void
     */
    public function processText($tokens, $category, $action)
    {
        # No matter what we do, we first have to check what data we have.

        # First get the internals, including the ham texts and spam texts counter
        $internals = $this->getInternals();

        $this->storageOpen();

        # Then, fetch all data for all tokens we have
        $token_data = $this->storageRetrieve(array_keys($tokens));

        # Process all tokens to learn/unlearn
        foreach ($tokens as $token => $count) {
            if (isset($token_data[$token])) {
                # We already have this token, so update it's data

                # Get the existing data
                $count_ham  = $token_data[$token]->count_ham;
                $count_spam = $token_data[$token]->count_spam;

                # Increase or decrease the right counter
                if ($action === b8::LEARN) {
                    if ($category === b8::HAM) {
                        $count_ham += $count;
                    } elseif ($category === b8::SPAM) {
                        $count_spam += $count;
                    }
                } elseif ($action == b8::UNLEARN) {
                    if ($category === b8::HAM) {
                        $count_ham -= $count;
                    } elseif ($category === b8::SPAM) {
                        $count_spam -= $count;
                    }
                }

                # We don't want to have negative values
                if ($count_ham < 0) {
                    $count_ham = 0;
                }
                if ($count_spam < 0) {
                    $count_spam = 0;
                }

                # Now let's see if we have to update or delete the token
                if ($count_ham != 0 or $count_spam != 0) {
                    $this->storageUpdate(new Word($token, $count_ham, $count_spam));
                } else {
                    $this->storageDel($token);
                }
            } else {
                # We don't have the token. If we unlearn a text, we can't delete it
                # as we don't have it anyway, so just do something if we learn a text
                if ($action === b8::LEARN) {
                    if ($category === b8::HAM) {
                        $this->storagePut(new Word($token, $count, 0));
                    } elseif ($category === b8::SPAM) {
                        $this->storagePut(new Word($token, 0, $count));
                    }
                }
            }
        }

        # Now, all token have been processed, so let's update the right text
        if ($action === b8::LEARN) {
            if ($category === b8::HAM) {
                $internals->count_ham++;
            } elseif ($category === b8::SPAM) {
                $internals->count_spam++;
            }
        } elseif ($action == b8::UNLEARN) {
            if ($category === b8::HAM) {
                if ($internals->count_ham > 0) {
                    $internals->count_ham--;
                }
            } elseif ($category === b8::SPAM) {
                if ($internals->count_spam > 0) {
                    $internals->count_spam--;
                }
            }
        }

        $this->storageUpdate($internals);

        $this->storageClose();
    }
}
