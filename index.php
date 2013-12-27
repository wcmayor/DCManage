<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>DCManage</title>

<!--<link href="../styles/main.css" rel="stylesheet" type="text/css" />-->
        
</head>

<body>
<?php
//the needed includes
include "db.php";
include "functions.php";

//sets up the database connection used in the functions.php page
$conn = mysql_connect($dbhost, $dbuser, $dbpass);
mysql_select_db($dbname, $conn) or die(mysql_error());

$action = $_GET['action'];
if (!$action)
$action = "home";
showLinks();
if ($action == "showall")
{
	showAllRacks();
}

if ($action == "home")
{
	echo "DCManage Home<br />";
	//showLinks();
}

if ($action == "update")
{
	updateServer();
}

if ($action == "processUpdate")
{
	processUpdate();
}

if ($action == "insert")
{
	insertServer();
}

if ($action == "processInsert")
{
	processInsert();
}

if ($action == "showallservers")
{
	showAllServers();
}

if ($action == "showonerack")
{
	$rackname = $_POST['rackname'];
	showOneRack($rackname);
}
?>

</body>
</html>
