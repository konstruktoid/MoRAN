<?php

/* 
konstruktoid.net 
Mo(ngoDB)RAN(CID) web interface
I am not a web developer

Username and password should actually be an enviroment variable
User should only have minimal permissions on the specific database
php.ini : https://raw.githubusercontent.com/konstruktoid/ubuntu-conf/master/web/php.ini
*/

$dbusername = "rancidrw";
$dbpassword = "rancidrwpassword";
$database = "rancid";

$m = new MongoClient("mongodb://$dbusername:$dbpassword@localhost/$database?ssl=true");
$db = $m->selectDB("$database");
$coll = $db->net;

function cleanString($string){
	$string_clean = array();
	
	$string = preg_replace('/[^a-zA-Z0-9---. ]/', '', $string);
	$string = (string)$string;
	$string_clean['message'] = $string;

	return $string_clean['message'];
}

function cleanOutput($string){
    	$output_clean = array();
    
    	$output_string = htmlentities($string, ENT_QUOTES, 'UTF-8');
    
	$output_clean['message'] = $output_string;
    	return $output_clean['message'];
}

$query_up = array('status' => 'up');
$cursorup = $coll->find($query_up);

$query_down = array('status' => 'down');
$cursordown = $coll->find($query_down);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>MoRAN</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="./moran.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
</head>
<body>
<div id="main">

<div id="menu">
<form method="post" action="<?php echo ($_SERVER["PHP_SELF"]);?>">
<input name="search" size="30" class="input"></textarea>
<input type="submit" name="send" value="Search" class="button">
<br />
</form>
</div>

<?php
if (!empty($_POST['search'])){
	$post_search = cleanString($_POST['search']);
	$query_search = array('name' => $post_search);
	$unit = $coll->findOne($query_search);
	$count = $coll->count(array('name'=> $post_search));
	
        $name = $unit['name'];
        $status = $unit['status'];
        $vendor = $unit['vendor'];
 	
	echo "<div id=\"search\">";
	if (!empty($name)) {
	echo "<form method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "\">";
	echo "name: <input name=\"uname\" value=\"$name\" class=\"minput\"><br />\n";
	echo "vendor: <input name=\"uvendor\" value=\"$vendor\" class=\"minput\"><br />\n";
	echo "status: <input name=\"ustatus\" value=\"$status\" class=\"minput\"><br />\n";
		if (!empty($unit['comment'])){
                	$comment = $unit['comment'];
		} else {
			$comment = "";
		}
	echo "comment: <input name=\"ucomment\" value=\"$comment\" class=\"minput\">";
	echo "<br /><input type=\"submit\" name=\"update\" value=\"Upsert\" class=\"button\">";
	} else {
		echo "No result for <i>" . cleanOutput($post_search) . "</i><br />\n";
	}
	echo "<button type=\"reset\" class=\"button\" onClick=\"document.getElementById('search').style.display = 'none';\">Close</button>";
	echo "</form>";
	echo "</div>";
}

if (isset($_POST['uname']) && !empty(cleanString($_POST['uname']))){
	echo "<div id=\"upsert\">";
	$uname = cleanString($_POST['uname']);
	$uvendor = cleanString($_POST['uvendor']);
	$ustatus = cleanString($_POST['ustatus']);
	if (!empty(cleanString($_POST['ucomment']))){
                $ucomment = cleanString($_POST['ucomment']);
        } else {
                $ucomment = "";
	}
		
		$save = "1";
        if (empty($uvendor) || $uvendor != "cisco"){
                echo "Incorrect vendor: $uvendor<br />";
		$save = "0";
	} elseif (empty($ustatus) || $ustatus != "up" && $ustatus != "down"){ 
		echo "Incorrect status: $ustatus<br />";
		$save = "0";
	}
	
	if($save != "0"){
	echo "<form method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "\">";
	echo "<input type=\"hidden\" value=\"$uname\" name=\"upsertname\">";
	echo "<input type=\"hidden\" value=\"$uvendor\" name=\"upsertvendor\">";
	echo "<input type=\"hidden\" value=\"$ustatus\" name=\"upsertstatus\">";
	echo "<input type=\"hidden\" value=\"$ucomment\" name=\"upsertcomment\">";
	echo "Upsert $uname?<br />";
	echo "Vendor: $uvendor <br />\nStatus: $ustatus<br />\nComment: $ucomment<br />";
	echo "<input type=\"submit\" value=\"Commit\" name=\"upsertcommit\" class=\"button\">";
	echo "<button type=\"reset\" class=\"button\" onClick=\"document.getElementById('upsert').style.display = 'none';\">Close</button>";
	echo "</form>";
	} else {
	echo "<button type=\"reset\" class=\"button\" onClick=\"document.getElementById('upsert').style.display = 'none';\">Close</button>";
	}
	echo "</div>";
}

if (isset($_POST['upsertcommit'])){
	$upsertname = cleanString($_POST['upsertname']);
	$upsertvendor = cleanString($_POST['upsertvendor']);
	$upsertstatus = cleanString($_POST['upsertstatus']);
	$upsertcomment = cleanString($_POST['upsertcomment']);
	
	echo "<div id=\"upsert\">";
	$upsert_unit = $coll->update(
        array("name" => $upsertname),
        array('$set' => array("name" => $upsertname, "vendor" => $upsertvendor, "status" => $upsertstatus, "comment" => $upsertcomment)),
        array("upsert" => true));
	$upsert_unit;
	$upsert_result = array('name' => $upsertname);
	echo "$upsertname upserted.";
	echo "<br />\n<button type=\"reset\" class=\"button\" onClick=\"document.getElementById('upsert').style.display = 'none';\">Close</button>";
        echo "</div>";
}

?>

<div id="left">
<font class="title">Up</font><br />

<?php
foreach ($cursorup as $unit){
	$name = $unit['name'];
	$status = $unit['status'];
	$vendor = $unit['vendor'];

	if (!empty($unit['comment'])){
		$comment = $unit['comment'];
		echo "<span title=\"$name - $vendor - $status - $comment\">";
	} else {
		echo "<span title=\"$name - $vendor - $status\">";
	}
	echo "$name</span><br />\n";
}
?>

</div>

<div id="right">
<font class="title">Down</font><br />

<?php
foreach ($cursordown as $unit){
        $name = $unit['name'];
        $status = $unit['status'];
        $vendor = $unit['vendor'];

        if (!empty($unit['comment'])){
                $comment = $unit['comment'];
                echo "<span title=\"$name - $vendor - $status - $comment\">";
        } else {
                echo "<span title=\"$name - $vendor - $status\">";
        }
        echo "$name</span><br />\n";
}
?>

</div>
</div>
</body>
</html>

