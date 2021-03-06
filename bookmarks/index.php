<?php
/**
 * bookmark management
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the COMMON DEVELOPMENT AND DISTRIBUTION LICENSE
 *
 * You should have received a copy of the
 * COMMON DEVELOPMENT AND DISTRIBUTION LICENSE (CDDL) Version 1.0
 * along with this program.  If not, see http://www.sun.com/cddl/cddl.html
 */

/**
 * display any error we have
 */
ini_set('error_reporting',-1);
ini_set('display_errors',true);

# load the config file
require('./config.php');
# db connection
$db_con = mysql_connect(DB_HOST,DB_USER,DB_PASS) OR die('Can not connect to SQL server');
$db_sel = mysql_select_db(DB_NAME,$db_con) OR die('Can not select database');

# process the add
if(isset($_POST['sub']['submitNew'])) {
	$link = trim($_POST['new']['link']);
	$title = trim($_POST['new']['title']);
	$cat = trim($_POST['new']['category']);
	if(!empty($link) && !empty($title) && !empty($cat)) {
		$mir = '0';
		if(isset($_POST['new']['mirror']) && $_POST['new']['mirror'] == "yes") {
			$mir = '1';
		}
			$query = mysql_query("INSERT INTO `bookmarks` 
									SET `category` = '".mysql_real_escape_string($cat)."',
										`title` = '".mysql_real_escape_string($title)."',
										`link` = '".mysql_real_escape_string($link)."',
										`date_added` = '".time()."',
										`mirror` = '".mysql_real_escape_string($mir)."'");
		if($query !== false) {
			if($mir === "1") {
				// we want a mirror
				$insertID = mysql_insert_id();
				if(!empty($insertID)) {
					$postfields['link'] = $link;
					$postfields['saveTo'] = $insertID;
					// do the curl call to trigger the mirror creation
					# this way "avaoid" any wayting time
					$ch = curl_init("http://www.tld.de/createMirror.php");
					curl_setopt($ch, CURLOPT_POST, 1); // we use POST
					curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
					curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					# use this if you protect the script with htaccess
					curl_setopt($ch, CURLOPT_USERPWD, 'username:password');
					$cResult = curl_exec($ch);
					curl_close($ch);
				}
			}
			
			header("Location: index.php");
		}
		else {
			echo "not saved";
		}
	}
}
elseif(isset($_POST['sub']['submitEdit']) && isset($_GET['edit']) && !empty($_GET['edit'])) {
# process edit
	$link = trim($_POST['new']['link']);
	$title = trim($_POST['new']['title']);
	$cat = trim($_POST['new']['category']);
	if(!empty($link) && !empty($title) && !empty($cat)) {
		$query = mysql_query("UPDATE `bookmarks` 
								SET `category` = '".mysql_escape_string($cat)."',
									`title` = '".mysql_escape_string($title)."',
									`link` = '".mysql_escape_string($link)."'
								WHERE `id` = '".mysql_escape_string($_GET['edit'])."'");
		if($query !== false) {
			header("Location: index.php");
		}
		else {
			echo "not saved";
		} 
	}
}

# get the bookmarks
$bookmarks = array();
$query = mysql_query('SELECT * FROM `bookmarks` ORDER BY `category`,`title`');
if(mysql_num_rows($query) > 0) {
	while($result = mysql_fetch_assoc($query)) {
		$bookmarks[$result['id']] = $result;
	}
}
?>
<html>
	<head>
		<title>Bookmarks for myself</title>
		<style type="text/css">
			img {
				border: 0;
				padding: 0;
				margin: 0;
			}
		</style>
	</head>
	<body>
	<?php if(isset($_GET['edit']) && !empty($_GET['edit'])) { ?>
	<form method="post" action="">
		Link:<br />
		<input type="text" name="new[link]" size="60" value="<?php echo $bookmarks[$_GET['edit']]['link']; ?>" /><br />
		Titel:<br />
		<input type="text" name="new[title]" value="<?php echo $bookmarks[$_GET['edit']]['title']; ?>" size="60" /><br />
		Kategorie:<br />
		<input type="text" name="new[category]" value="<?php echo $bookmarks[$_GET['edit']]['category']; ?>" size="60" /><br />
		<button type="submit" title="Speichern" name="sub[submitEdit]">Speichern</button>
	</form>
	<?php } else { ?>
	<form method="post" action="">
		Link:<br />
		<input type="text" name="new[link]" size="60" value="" /><br />
		Titel:<br />
		<input type="text" name="new[title]" value="" size="60" /><br />
		Kategorie:<br />
		<input type="text" name="new[category]" value="" size="60" /><br />
		<br />
		<input type="checkbox" name="new[mirror]" value="yes" /> Httrack Mirror anlegen.<br />
		<button type="submit" title="Speichern" name="sub[submitNew]">Speichern</button>
	</form>
	<?php
	}
	# display the bookmark
	if(!empty($bookmarks)) {
		$cat = false;
		foreach($bookmarks as $entry) {
			if($cat != $entry['category']) {
				if(!empty($cat)) echo '</ul>';
				echo '<h2>'.$entry['category'].'</h2>';
				echo '<ul>';
				$cat = $entry['category'];
			}
			echo '<li><a href="'.$entry['link'].'">'.$entry['title'].'</a>';
			if($entry['mirror'] == "1") {
				echo ' | <a href="http://www.bananas-development-server.de/bookmarkMirror/'.$entry['id'].'/">mirror</a>';
			}
			echo ' | <a href="index.php?edit='.$entry['id'].'" title="edit"><img src="edit.png" width="16" alt="edit" /></a></li>';
		}
		echo '</ul>';
	}
?>
	</body>
</html>
