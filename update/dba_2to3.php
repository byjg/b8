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

########################################################################
## This script updates a b8 v2 DBA (Berkeley DB) database file to v3. ##
########################################################################

# Please fill in the below configuration. The database file given in
# 'dbfile_v3' will be created by the script. Be sure to have sufficient
# permissions on all files and directories involved.

$config = array(
	'dbfile_v2'  => 'wordlist.db',
	'handler_v2' => 'db4',
	'dbfile_v3'  => 'wordlist_v3.db',
	'handler_v3' => 'db4'
);

##### Here starts the update script #####

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

<title>b8 database updater: dba (Berkeley DB) v2 to v3</title>

<meta http-equiv="content-type" content="text/html; charset=UTF-8" />

<meta name="dc.creator" content="Tobias Leupold" />
<meta name="dc.rights" content="Copyright (c) by Tobias Leupold" />

</head>

<body>

<div>

<h1>b8 database updater: dba (Berkeley DB) v2 to v3</h1>


END;

$dbfile_v2_html = htmlentities($config['dbfile_v2']);
$dbfile_v3_html = htmlentities($config['dbfile_v3']);

echo "<h2>Preparing database update</h2>\n\n";

echo "<p>\n";

echo "Checking if \"$dbfile_v2_html\" is readable &hellip; ";

if(is_readable($config['dbfile_v2']) !== TRUE)
	update_failed('no', "Can't read the source database file. Please check the permissions and/or existance of \"$dbfile_v2_html\"");
else
	echo "done<br />\n";

echo "Touching/Creating the destination database file \"$dbfile_v3_html\" &hellip; ";

if(touch($config['dbfile_v3']) === FALSE)
	update_failed('failed', "Could not touch/create \"$dbfile_v3_html\". Please check the persmissions of the directory conataining it.");
else
	echo "done<br />\n";

echo "Setting file permissions of \"$dbfile_v3_html\" to 0666 &hellip; ";

if(chmod($config['dbfile_v3'], 0666) === FALSE)
	update_failed('failed', "Could chmod \"$dbfile_v3_html\". Please check the file persmissions.");
else
	echo "done<br />\n";

echo "Checking if the target database file is empty &hellip; ";

if(filesize($config['dbfile_v3']) > 0)
	update_failed('no', "\"$dbfile_v3_html\" is not empty. Won't overwrite it.");
else
	echo "done<br />\n";

echo "Connecting to the source database file \"$dbfile_v2_html\" &hellip; ";

$db_v2 = dba_open($config['dbfile_v2'], "c", $config['handler_v2']);

if($db_v2 === FALSE)
	update_failed('failed', "Could not connect to the source database file \"$dbfile_v2_html\"");
else
	echo "done<br />\n";

echo 'Checking for correct version (2) of the source database &hellip; ';

$version = (int) dba_fetch('bayes*dbversion', $db_v2);

if($version === FALSE)
	$version = 1;

if($version != 2)
	update_failed('wrong version', "The source database isn't a b8 v2 database (it's v$version). Can't update.");
else
	echo "done<br />\n";

echo "Connecting to the target database file \"$dbfile_v3_html\" &hellip; ";

$db_v3 = dba_open($config['dbfile_v3'], 'c', $config['handler_v3']);

if($db_v3 === FALSE)
	update_failed('failed', "Could not connect to the target database file \"$dbfile_v3_html\"");
else
	echo "done\n";

echo "</p>\n\n";

echo "<h2>Updating database to version 3</h2>\n\n";

echo "<p>\n";
echo "Processing all tokens &hellip; ";

$processed = 0;

# Get first key
$key = dba_firstkey($db_v2);

$internals_texts_ham = 0;
$internals_texts_spam = 0;

while($key !== FALSE) {
	
	# Get the current value for the key and process it
	$val = dba_fetch($key, $db_v2);

	# Check for internal variables
	
	if($key == 'bayes*texts.ham' or $key == 'bayes*texts.spam' or $key == 'bayes*dbversion') {
		
		if($key == 'bayes*texts.ham')
			$internals_texts_ham = $val;
		if($key == 'bayes*texts.spam')
			$internals_texts_spam = $val;
			
	}
	
	else {
	
		$parts = explode(' ', $val);
		$val_processed = "{$parts[0]} {$parts[1]}";
		
		# Store it to the new database
		if(dba_insert($key, $val_processed, $db_v3) !== TRUE)
			update_failed('failed', "Failed to insert token \"$key\"");
		
		$processed++;
			
	}
	
	$key = dba_nextkey($db_v2);
	
}

# Re-insert the internal variables
foreach(array('b8*dbversion' => '3', 'b8*texts' => "$internals_texts_ham $internals_texts_spam") as $key => $val) {
	if(dba_insert($key, $val, $db_v3) !== TRUE)
		update_failed('failed', "Failed to insert token \"$key\"");
}

echo "processed $processed tokens\n</p>\n\n";

echo "<p>Finished database update to version 3. The new database file is \"$dbfile_v3_html\".</p>\n";

?>

</div>

</body>

</html>
