
--   Copyright (C) 2013 Tom Regner <tom@goochesa.de>
--
--   This file is part of the b8 package
--
--   This program is free software; you can redistribute it and/or modify it
--   under the terms of the GNU Lesser General Public License as published by
--   the Free Software Foundation in version 2.1 of the License.
--
--   This program is distributed in the hope that it will be useful, but
--   WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
--   or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public
--   License for more details.
--
--   You should have received a copy of the GNU Lesser General Public License
--   along with this program; if not, write to the Free Software Foundation,
--   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

CREATE SCHEMA b8;
SET search_path = b8, pg_catalog;

CREATE TABLE b8.b8_wordlist (
  token varchar(255) PRIMARY KEY NOT NULL,
  count_ham integer default NULL,
  count_spam integer default NULL
);

INSERT INTO b8.b8_wordlist (token, count_ham) VALUES ('b8*dbversion', 3);
INSERT INTO b8.b8_wordlist (token, count_ham, count_spam) VALUES ('b8*texts', 0, 0);

-- this rule let us handle updates in one insert statement, even if
-- the updated row does not exist, almost analog to mysqls "INSERT ...
-- ON DUPLICATE KEY UPDATE" mechanism

CREATE RULE b8_wordlist_update_on_insert AS ON INSERT TO b8.b8_wordlist
  WHERE EXISTS(
    SELECT 1
      FROM b8.b8_wordlist
      WHERE token = NEW.token
  )
  DO INSTEAD
    UPDATE b8.b8_wordlist
    SET count_ham = NEW.count_ham, count_spam = NEW.count_spam
    WHERE token = NEW.token
  ;
