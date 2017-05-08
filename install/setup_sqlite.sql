
--   Copyright (C) 2013 Tobias Leupold <tobias.leupold@web.de>
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

CREATE TABLE 'b8_wordlist' (
	token TEXT NOT NULL,
	count_ham INTEGER DEFAULT NULL,
	count_spam INTEGER DEFAULT NULL,
	PRIMARY KEY (token)
);

INSERT INTO b8_wordlist (token, count_ham) VALUES ('b8*dbversion', '3');
INSERT INTO b8_wordlist (token, count_ham, count_spam) VALUES ('b8*texts', '0', '0');
