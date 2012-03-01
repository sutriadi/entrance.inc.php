<?php
/*
 *      entrance.inc.php
 *      
 *      Copyright 2012 Indra Sutriadi Pipii <indra@sutriadi.web.id>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 *      
 *      
 */

if (!defined('INDEX_AUTH'))
    die("can not access this file directly");
elseif (INDEX_AUTH != 1)
    die("can not access this file directly");

$allowed_counter_ip = array('127.0.0.1');
$remote_addr = $_SERVER['REMOTE_ADDR'];
$confirmation = 0;

$info = __('Use this page to count visitor');

// get from visitor.inc.php
foreach ($allowed_counter_ip as $ip) {
	$ip = preg_replace('@\*$@i', '.', $ip);
    if ($ip == $remote_addr || $_SERVER['HTTP_HOST'] == 'localhost' || preg_match("@$ip@i", $ip)) {
        $confirmation = 1;
    }
}

if ( ! $confirmation) {
    header ("location: index.php");
}

$message = '';
if ($_POST AND isset($_POST['memberid']))
{
	if (trim($_POST['memberid']) === '')
		die();
	
	sleep(2);
	
	$expire = true;
	$memberid = $dbs->escape_string($_POST['memberid']);
	$sql = sprintf("SELECT member_id, member_name, member_image, inst_name, member_address, expire_date, IF(TO_DAYS('%s')>TO_DAYS(expire_date), 1, 0) AS is_expire FROM member WHERE member_id='%s'",
		date('Y-m-d'),
		$memberid
	);
	$q = $dbs->query($sql);
	if ($q->num_rows > 0)
	{
		$d = $q->fetch_object();
		if ($d->is_expire != 1)
			$expire = false;
		
		$photo = trim($d->member_image) ? trim($d->member_image) : 'person.png';
		$checkin_date = date('Y-m-d H:i:s');
		
		$insql = sprintf("INSERT INTO visitor_count (member_id, member_name, institution, checkin_date) VALUES ('%s', '%s', '%s', '%s')",
			$d->member_id,
			$d->member_name,
			$d->inst_name,
			$checkin_date
		);
		$dbs->query($insql);
		
		$dsql = sprintf("SELECT * FROM visitor_count WHERE DATE(checkin_date) = DATE(NOW())");
		$count = $dbs->query($dsql);
		$count = $count->num_rows;
		
		$message = sprintf('<p align="center"><strong>%d %s on %s</strong></p>',
				$count,
				__('visitor(s)'),
				date('d-m-Y')
			)
			. '<table style="width: 100%;">'
			. '<tr>'
				. sprintf('<td width="120px">%s</td>', __('ID'))
				. sprintf('<td width="400px">%s</td>', $d->member_id)
				. sprintf('<td rowspan="6" valign="top"><img src="images/persons/%s" /></td>', urlencode($photo))
			. '</tr>'
			. '<tr>'
				. sprintf('<td>%s</td>', __('Name'))
				. sprintf('<td>%s</td>', $d->member_name)
			. '</tr>'
			. '<tr>'
				. sprintf('<td>%s</td>', __('Institution'))
				. sprintf('<td>%s</td>', $d->inst_name)
			. '</tr>'
			. '<tr>'
				. sprintf('<td>%s</td>', __('Address'))
				. sprintf('<td>%s</td>', $d->member_address)
			. '</tr>'
			. '<tr>'
				. sprintf('<td>%s</td>', __('Expire Date'))
				. sprintf('<td>%s</td>', date('d-m-Y', mktime(0, 0, 0, substr($d->expire_date, -5, 2), substr($d->expire_date, -2), substr($d->expire_date, 0, 4))))
			. '</tr>'
			. '<tr>'
				. sprintf('<td>%s</td>', __('State'))
				. sprintf('<td>%s</td>', $expire === true ? __('Expired') : __('Active'))
			. '</tr>'
			. '</table>';
	}
	else
	{
		$message = __('Member ID invalid');
	}
	$_SESSION['entrance_message'] = $message;
	header("Location: ?p=entrance");

}
else if (isset($_SESSION['entrance_message']))
{
	$p = parse_url($_SERVER['HTTP_REFERER']);
	$q = $p['query'];
	parse_str($q, $q);
	if ($q['p'] == 'entrance')
	{
		$message = $_SESSION['entrance_message'];
		unset($_SESSION['entrance_message']);
	}
}
?>

	<h3><?php echo __('Visitor Counter');?></h3>
	<form id="regclient" name="regclient" method="POST" action="?p=entrance">
		<p>
			<label for="memberid">Member ID :</label>
			<input type="text" name="memberid" id="memberid" maxlength="13" />
			<input type="submit" value="Add" />
		</p>
	</form>
	<?php echo $message;?>

	<script type="text/javascript">		
		$(document).ready(function() {
			jQuery('#memberid').focus();
		});
	</script>
