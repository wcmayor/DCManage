<?php
function showAllRacks()
{
	$q = "Select * from dc_racks order by 1 asc";
	$r = mysql_query($q);
	while ($racks = mysql_fetch_array($r))
	{
		showRack($racks);
	}
}

function showRack($racks)
{
	$rackid = $racks['id'];
	$rackname = $racks['rackname'];
	$capacity = $racks['capacity'];
	$filled = false;
	$nextslot = -1;
	
	$q = "Select * from dc_servers where rack = $rackid order by u desc";
	$r2 = mysql_query($q);
	
	$q = "select sum(s.height) / r.capacity * 100 as pct from dc_servers s, dc_racks r where s.rack = $rackid and r.id = $rackid";
	$r3 = mysql_query($q);
	$filledpct = mysql_fetch_array($r3);
	$pct = round($filledpct['pct'], 0);
	if ($pct < 80)
		$statuscolor = '#00ff00';
	elseif ($pct < 90)
		$statuscolor = 'yellow';
	else
		$statuscolor = 'red';
		

	echo"
	<table style='float: left; margin-right: 20px;'>
		<tr>
			<td colspan=3 align=center bgcolor=$statuscolor>$rackname - $pct% full</td>
		</tr>";
	for ($i = $capacity; $i > 0; $i--)
	{
		$filled = false;
		echo "<tr>";
		echo "<td>$i</td>";
		
		while ($servers = mysql_fetch_array($r2))
		{
			if($servers['u'] == $i)
			{
				$height = $servers['height'];
				$name = $servers['servername'];
				echo "<td rowspan = $height width = 300 bgcolor=#b7ffa3 align='center'><b><a href='index.php?action=update&rackid=$rackid&u=$i'>$name</a></b></td>";
				$filled = true;
				$nextslot = $i - $height;
			}
		}
		if ((!$filled) && (($nextslot >= $i) || $nextslot == -1))
			echo "<td width = 300 bgcolor=#bababa align='center'><a href='index.php?action=insert&rackid=$rackid&u=$i'>Empty</a></td>";
		mysql_data_seek($r2, 0);
		echo "<td>$i</td>";
		echo "</tr>";
	}
	
	echo "</table>";
}

function showOneRack($rackname)
{
	$q = "select rackname from dc_racks order by rackname asc";
	$r = mysql_query($q);
	
	echo "<h4>Select a rack!</h4>";
	echo "<form name='rackselector' action='index.php?action=showonerack' method='post'>";
	echo "<select name='rackname'>";
	while ($racks = mysql_fetch_array($r))
	{
		$tmpname = $racks['rackname'];
		echo "<option value='$tmpname'>$tmpname</option>";
	}
	echo "</select><input type='submit' value='Go!' /></form>";
	if ($rackname == null)
		return;
	
	$q = "select * from dc_racks where rackname = '$rackname'";
	$r = mysql_query($q);
	while ($racks = mysql_fetch_array($r))
		showRack($racks);
}

function updateServer()
{
	$rackid = $_GET['rackid'];
	$u = $_GET['u'];
	
	$q = "select * from dc_servers where rack = '$rackid' and u = '$u'";
	$r = mysql_query($q);
	$a = mysql_fetch_array($r);
	
	$id = $a['id'];
	$servername = $a['servername'];
	$servicetag = $a['servicetag'];
	$u = $a['u'];
	$rack = $a['rack'];
	$height = $a['height'];
	
	echo "<h4>Edit server <i>$servername</i></h4>";
	echo "<form name='updateServer' action='index.php?action=processUpdate' method='post'>";
	echo "<table>";
	echo "<tr><th>Server Name:</th><th><input type='text' name='servername' value='$servername' /></th></tr>";
	echo "<tr><th>Service Tag:</th><th><input type='text' name='servicetag' value='$servicetag' /></th></tr>";
	echo "<tr><th>Highest Rack Unit (U):</th><th><input type='text' name='u' value='$u' /></th></tr>";
	echo "<tr><th>Rack:</th><th><input type='text' name='rack' value='$rack' /></th></tr>";
	echo "<tr><th>Height (Number of U):</th><th><input type='text' name='height' value='$height' /></th></tr>";
	echo "<tr><td /><td><input type='submit' value='Submit' /></td></tr>";
	echo "<input type='hidden' name='id' value='$id' />";
	echo "</table></form>";
}

function processUpdate()
{
	$id = $_POST['id'];
	$servername = $_POST['servername'];
	$servicetag = $_POST['servicetag'];
	$u = $_POST['u'];
	$rack = $_POST['rack'];
	$height = $_POST['height'];
	
	if (isFilled($rack, $u, $height, $id) == true)
	{
		echo "Server already exists in the spot selected!";
		return;
	}
	$q = "delete from dc_filledslots where serverid = '$id'";
	$r = mysql_query($q);
	
	$q = "update dc_servers set servername=upper('$servername'), servicetag='$servicetag', u='$u', rack='$rack', height='$height' where id='$id'";
	$run = mysql_query($q);
	
	updateFilledSlots($servicetag, $u, $height, $rack);
	
	showAllRacks();
}

function insertServer()
{
	$rackid = $_GET['rackid'];
	$u = $_GET['u'];
	
	echo "<h4>New server</h4>";
	echo "<form name='insertServer' action='index.php?action=processInsert' method='post'>";
	echo "<table>";
	echo "<tr><th>Server Name:</th><th><input type='text' name='servername' /></th></tr>";
	echo "<tr><th>Service Tag:</th><th><input type='text' name='servicetag' /></th></tr>";
	echo "<tr><th>Highest Rack Unit (U):</th><th><input type='text' name='u' value='$u' /></th></tr>";
	echo "<tr><th>Rack:</th><th><input type='text' name='rack' value='$rackid' /></th></tr>";
	echo "<tr><th>Height (Number of U):</th><th><input type='text' name='height' value='1' /></th></tr>";
	echo "<tr><td /><td><input type='submit' value='Submit' /></td></tr>";
	echo "<input type='hidden' name='id' value='$id' />";
	echo "</table></form>";
}

function processInsert()
{
	$id = $_POST['id'];
	$servername = $_POST['servername'];
	$servicetag = $_POST['servicetag'];
	$u = $_POST['u'];
	$rack = $_POST['rack'];
	$height = $_POST['height'];
	$serverid = -1;
	
	if (isFilled($rack, $u, $height, $serverid) == true)
	{
		echo "Server already exists in the spot selected!";
		return;
	}
	
	$q = "insert into dc_servers (servername, servicetag, u, rack, height) values (upper('$servername'), '$servicetag', '$u', '$rack', '$height')";
	$run = mysql_query($q);
	
	updateFilledSlots($servicetag, $u, $height, $rack);

	showAllRacks();
}

function updateFilledSlots($servicetag, $u, $height, $rack)
{
	$q = "select * from dc_servers where servicetag = '$servicetag'";
	$r = mysql_query($q);
	$a = mysql_fetch_array($r);
	
	$newid = $a['id'];
	
	for ($i = $u; $i > ($u - $height); $i--)
	{
		$q = "insert into dc_filledslots (rackid, u, serverid) values ('$rack', '$i', '$newid')";
		$run = mysql_query($q);
	}
}

function showLinks()
{
	echo "<a href='index.php?action=insert'>Add New Server</a><br />";
	echo "<a href='index.php?action=showall'>Show All Racks</a><br />";
	echo "<a href='index.php?action=showonerack'>Show One Rack</a><br />";
	echo "<a href='index.php?action=showallservers'>Show All Servers</a><br />";
}

function showAllServers()
{
	$sortorder = $_POST['sortorder'];
	if ($sortorder == null)
		$sortorder = "servername";
	
	//sort alphabetically by server, by rack, by height
	if ($sortorder == "servername")
		$q = "select r.id as rackid, s.servername, s.u, s.height, r.rackname from dc_servers s, dc_racks r where s.rack = r.id order by s.servername asc";
	if ($sortorder == "rackname")
		$q = "select r.id as rackid, s.servername, s.u, s.height, r.rackname from dc_servers s, dc_racks r where s.rack = r.id order by r.rackname, s.servername asc";
	if ($sortorder == "height")
		$q = "select r.id as rackid, s.servername, s.u, s.height, r.rackname from dc_servers s, dc_racks r where s.rack = r.id order by s.height, s.servername asc";
	
	$run = mysql_query($q);
	echo "<h4>Show All Servers</h4>";
	echo "<h4>Choose how to sort the servers:</h4>";
	echo "<form action='index.php?action=showallservers' method='post'>";
	echo "<select name='sortorder'>";
	echo "<option value=servername>Server name</option>";
	echo "<option value=rackname>Rack name</option>";
	echo "<option value=height>Server height</option>";
	echo "<input type='submit' value='Sort!' /><h4 />";
	echo "<table border=1>";
	echo "<tr><th>Server Name</th><th>U</th><th>Height</th><th>Rack</th></tr>";
	
	while ($server = mysql_fetch_array($run))
	{
		//getting all the information for each server
		$servername = $server['servername'];
		$u = $server['u'];
		$height = $server['height'];
		$rackname = $server['rackname'];
		$rackid = $server['rackid'];
		
		//open a new row
		echo "<tr>";
		
		//echo all the information for each server
		echo "<td><a href='index.php?action=update&rackid=$rackid&u=$u'>$servername</a></td>";
		echo "<td>$u</td>";
		echo "<td>$height</td>";
		echo "<td>$rackname</td>";
		
		//close the table!
		echo "</td>";
	}
	echo "</table>";
}

function isFilled($rack, $u, $height, $serverid)
{	
	if ($height > 1)
	{
		$numarray = "(";
		
		for ($i = $u; $i > ($u - $height); $i--)
		{
			$numarray .= "$i, ";
		}
		
		//needed to drop the comma and space after the last item in the range of numbers
		$numarray = substr($numarray, 0, -2);
		
		//closes out the section
		$numarray .= ")";
	}
	
	else
	{
		$numarray = "($u)";
	}
	//echo $numarray;
	
	$q = "select * from dc_filledslots where rackid = $rack and u in $numarray and serverid != $serverid";
	$r = mysql_query($q) or die(mysql_error());

	if((mysql_num_rows($r)) > 0)
		return true;
	else
		return false;
}
?>
