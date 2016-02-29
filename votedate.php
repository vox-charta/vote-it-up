<?php
include_once("/var/www/html/voxcharta/wp-blog-header.php");
include_once(VoteItUp_Path()."/votingfunctions.php");
?>
<div>
<span class="votelink">
<form style="font-size: x-small;">
<select name="datesel" id="datesel" class="regular-text">
<?php
global $institution, $today;
if (!isset($schedaffil)) $schedaffil = $_COOKIE['schedule_affiliation'];
if (!isset($institution)) $institution = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name='{$schedaffil}'");
date_default_timezone_set($institution->timezone);
$today = time();
$ndiscussions = 5;
$nextdiscussions = array();
for ($n = 1; $n <= $ndiscussions; $n++) {
	echo AgendaOffset('next', 'co', $today);
	$today = $today + 86400*AgendaOffset('next', 'co', $today);
	echo $today;
	array_push($nextdiscussions, $today);
}

foreach ($nextdiscussions as $nd) {
	$ndformatted = date('m/d/Y', $nd);
	echo "<option value='{$ndformatted}'>{$ndformatted}</option>";
}
?>
</select>
</form>

<a href="javascript:closeDate('overlay<?php echo $_POST['postid']; ?>');">Close</a>
</span>
</div>
