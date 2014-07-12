<?php

#   Copyright (C) 2012 Tobias Leupold <tobias.leupold@web.de>
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

#######################################################
## This script updates a b8 v2 MySQL database to v3. ##
#######################################################

# Please fill in the below configuration. The database file given in
# 'dbfile_v3' will be created by the script. Be sure to have sufficient
# permissions on all files and directories involved.

$config = array(
	'database_v2'   => 'test',
	'table_name_v2' => 'b8_wordlist',
	'host_v2'       => 'localhost',
	'user_v2'       => '',
	'pass_v2'       => '',
	'database_v3'   => 'test',
	'table_name_v3' => 'b8_wordlist_v3',
	'host_v3'       => 'localhost',
	'user_v3'       => '',
	'pass_v3'       => ''
);

##### Here starts the update script. #####

function update_failed($msg1, $msg2)
{
	echo "$msg1<br />\n";
	echo "<span style=\"color:red;\">$msg2</span>\n";
	echo "</p>\n\n</div>\n\n</body>\n\n</html>\n";
	exit(1);
}

echo <<<END
<?xml version="1.0" encoding="UTF-8"?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
   "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>

<title>b8 database updater: MySQL v2 to v3</title>

<meta http-equiv="content-type" content="text/html; charset=UTF-8" />

<meta name="dc.creator" content="Tobias Leupold" />
<meta name="dc.rights" content="Copyright (c) by Tobias Leupold" />

</head>

<body>

<div>

<h1>b8 database updater: MySQL v2 to v3</h1>


END;

echo "<h2>Preparing database update</h2>\n\n";

echo "<p>\n";

echo "Setting up MySQL connections &hellip;<br />\n";

echo "Connecting to the v2 MySQL server &hellip; ";

$db_v2 = mysql_connect($config['host_v2'], $config['user_v2'], $config['pass_v2']);

if(!is_resource($db_v2) or get_resource_type($db_v2) != 'mysql link')
	update_failed('failed', 'Could not connect to the v2 MySQL database.');
else
	echo "done<br />\n";

echo "Selecting the v2 database \"{$config['database_v2']}\" &hellip; ";

if(mysql_select_db($config['database_v2']) === FALSE)
	update_failed('failed', "Could not select v2 database \"{$config['database_v2']}\"");
else
	echo "done<br />\n";

echo "Connecting to the v3 MySQL server &hellip; ";

$db_v3 = mysql_connect($config['host_v3'], $config['user_v3'], $config['pass_v3']);

if(!is_resource($db_v3) or get_resource_type($db_v3) != 'mysql link')
	update_failed('failed', 'Could not connect to the v3 MySQL database.');
else
	echo "done<br />\n";

echo "Selecting the v3 database \"{$config['database_v3']}\" &hellip; ";

if(mysql_select_db($config['database_v3']) === FALSE)
	update_failed('failed', "Could not select v3 database \"{$config['database_v3']}\"");
else
	echo "done<br />\n";

echo 'Checking for correct version (2) of the source table &hellip; ';

$res = mysql_query("SELECT count FROM `{$config['table_name_v2']}` WHERE token='bayes*dbversion'", $db_v2);

if(get_resource_type($res) != 'mysql result')
	update_failed('failed', "Could not query table \"{$config['table_name_v2']}\" " . mysql_error());

else {
	
	$res = mysql_fetch_assoc($res);
	
	if($res === FALSE)
		update_failed('failed', "Could not fetch 'bayes*dbversion' from \"{$config['table_name_v2']}\"");
	
	$version = $res['count'];
	
	if($version != 2)
		update_failed('wrong version', "The source database isn't a b8 v2 database (it's v$version). Can't update.");
	else
		echo "done<br />\n";

}

echo 'Creating a new table for the v3 database &hellip; ';

$res = mysql_query("
	CREATE TABLE `{$config['table_name_v3']}` (
		`token` varchar(255) character set utf8 collate utf8_bin NOT NULL,
		`count_ham` int unsigned default NULL,
		`count_spam` int unsigned default NULL,
		PRIMARY KEY (`token`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
", $db_v3);

if($res !== TRUE)
	update_failed('failed', "Could not create new table \"{$config['table_name_v3']}\": " . mysql_error());
else
	echo "done<br />\n";

echo "</p>\n\n";

echo "<h2>Updating database to version 3</h2>\n\n";

echo "<p>\n";
echo 'Inserting internal variables &hellip; ';

$res = mysql_fetch_assoc(mysql_query("SELECT count FROM `{$config['table_name_v2']}` WHERE token='bayes*texts.ham'", $db_v2));
$internals_texts_ham = $res['count'];

$res = mysql_fetch_assoc(mysql_query("SELECT count FROM `{$config['table_name_v2']}` WHERE token='bayes*texts.spam'", $db_v2));
$internals_texts_spam = $res['count'];

$res = mysql_query("INSERT INTO `{$config['table_name_v3']}`(token, count_ham, count_spam) VALUES('b8*texts', '$internals_texts_ham', '$internals_texts_spam')", $db_v3);

if($res !== TRUE)
	update_failed('failed', "Could not insert datan ('b8*texts', '$internals_texts_ham', '$internals_texts_spam') into table \"{$config['table_name_v3']}\": " . mysql_error());

$res = mysql_query("INSERT INTO `{$config['table_name_v3']}`(token, count_ham) VALUES('b8*dbversion', '3')", $db_v3);

if($res !== TRUE)
	update_failed('failed', "Could not insert data ('b8*dbversion', '3') into table \"{$config['table_name_v3']}\": " . mysql_error());

echo "done<br />\n";

echo "Processing all tokens &hellip; ";

$processed = 0;

$res = mysql_query("SELECT token, count FROM `{$config['table_name_v2']}` WHERE token != 'bayes*dbversion' AND token != 'bayes*texts.ham' AND token != 'bayes*texts.spam'", $db_v2);

while($dat = mysql_fetch_assoc($res)) {
	
	$token = mysql_real_escape_string($dat['token'], $db_v3);
	$parts = explode(' ', $dat['count']);
	$ham = $parts[0];
	$spam = $parts[1];
	
	$res2 = mysql_query("INSERT INTO `{$config['table_name_v3']}`(token, count_ham, count_spam) VALUES('$token', '$ham', '$spam')", $db_v3);
	
	if($res2 !== TRUE)
		update_failed('failed', "Could not insert data ('$token', '$ham', '$spam') into table \"{$config['table_name_v3']}\": " . mysql_error());
	else
		$processed++;
	
}

echo "processed $processed tokens\n</p>\n\n";

echo "<p>Finished database update to version 3. The new database table is \"{$config['table_name_v3']}\".</p>\n";

?>

</div>

</body>

</html>
