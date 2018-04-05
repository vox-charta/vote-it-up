<?php
/* VoteItUp configuration page */

function VoteItUp_options() {
	if (function_exists('add_options_page')) {
		add_options_page("Vote It Up", "Vote It Up", "administrator", "voteitupconfig", "VoteItUp_optionspage");
		add_options_page("Edit Votes", "Edit Votes", "administrator", "voteitupeditvotes", "VoteItUp_editvotespage");
		add_options_page("Recommendations", "Recommendations", 0, "voteituprecommend", "VoteItUp_recommendpage");
		add_options_page("Institution", "Institution", "edit_institution", "voteitupinstitution", "VoteItUp_institutionpage");
		add_options_page("Events", "Events", "edit_institution", "voteitupevents", "VoteItUp_eventspage");
	}
}

/* Wordpress MU fix, options whitelist */
if(function_exists('wpmu_create_blog')) {
add_filter('whitelist_options','voteitup_alter_whitelist_options');
function voteitup_alter_whitelist_options($whitelist) {
if(is_array($whitelist)) {
$option_array = array('voteitup' => array('voteiu_initialoffset','voteiu_votetext','voteiu_sinktext','voteiu_aftervotetext',
	'voteiu_allowguests','voteiu_allowownvote','voteiu_limit','voteiu_widgetcount','voteiu_skin','voteiu_nocoffee_except','voteiu_noclub_except',
	'voteiu_yescoffee_except','voteiu_yescc_except','voteiu_yesjc_except','voteiu_reset_time','voteiu_discussion_loc','voteiu_coffee_time'));
$whitelist = array_merge($whitelist,$option_array);
}
return $whitelist;
}

}

//Page meant for administrators
function VoteItUp_optionspage() {

?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
<h2><?php _e('Voting options'); ?></h2>
<form method="post" action="options.php">
<?php
/* bugfix for wordpress mu */
if(function_exists('wpmu_create_blog')) {
wp_nonce_field('voteitup-options');
echo '<input type="hidden" name="option_page" value="voteitup" />';
} else {
wp_nonce_field('update-options');
}
?>

<h3>General</h3>
<table class="form-table" border="0">
<tr valign="top">
<th scope="row" style="text-align: left;">Initial vote count</th>
<td>
<input type="text" name="voteiu_initialoffset" id="voteiu_initialoffset" value="<?php if (get_option('voteiu_initialoffset')=='') { echo '0'; } else { echo get_option('voteiu_initialoffset'); } ?>" />
</td></tr>
<tr valign="top">
<th scope="row" style="text-align: left;">Name of positive votes</th>
<td>
<input type="text" name="voteiu_votetext" id="voteiu_votetext" value="<?php echo htmlentities(get_option('voteiu_votetext')); ?>" /><br />
You can use <code>&lt;img&gt;</code> to use images instead of text. Example: <code>&lt;img src=&quot;<?php echo VoteItUp_ExtPath(); ?>/uparrow.png&quot; /&gt;</code><br />
Default: <code>Vote</code>
</td>
</tr>
<tr valign="top">
<th scope="row" style="text-align: left;">Name of negative votes</th>
<td>
<input type="text" name="voteiu_sinktext" id="voteiu_sinktext" value="<?php echo htmlentities(get_option('voteiu_sinktext')); ?>" <?php if (!GetCurrentSkinInfo('supporttwoway')) { echo 'disabled="disabled" '; }?>/><br />
<?php if (GetCurrentSkinInfo('supporttwoway')) { ?>You can use <code>&lt;img&gt;</code> to use images instead of text. Example: <code>&lt;img src=&quot;<?php echo VoteItUp_ExtPath(); ?>/downarrow.png&quot; /&gt;</code><br />
If this is left blank two-way voting is disabled.<?php } else {
?>Current widget template does not support two-way voting<?php } ?>
</td>
</tr>
<tr valign="top">
<th scope="row" style="text-align: left;">Text displayed after vote is cast</th>
<td>
<input type="text" name="voteiu_aftervotetext" id="voteiu_aftervotetext" value="<?php echo htmlentities(get_option('voteiu_aftervotetext')); ?>" /><br />
You can use <code>&lt;img&gt;</code> to use images instead of text. Text is displayed after user casts a vote. If this is left blank the vote button disappears.
</td>
</tr>
</table>

<h3>Permissions</h3>
<table class="form-table" border="0">
<tr valign="top">
<th scope="row" style="text-align: left;">Allow guests to vote</th>
<td>
<input type="checkbox" name="voteiu_allowguests" id="voteiu_allowguests" value="true" <?php if (get_option('voteiu_allowguests') == 'true') { echo ' checked="checked"'; } ?> />
</td></tr>
<tr valign="top">
<th scope="row" style="text-align: left;">Post author can vote own post</th>
<td>
<input type="checkbox" name="voteiu_allowownvote" id="voteiu_allowownvote" value="true" <?php if (get_option('voteiu_allowownvote') == 'true') { echo ' checked="checked"'; } ?> />
</td></tr>
</table>

<h3>Theming</h3>
<p>External templates for the voting widgets can be installed via the &quot;skin&quot; directory. Voting widgets using <code>&lt;?php DisplayVotes(get_the_ID()); ?&gt;</code> will use the new themes. Setting this to &quot;none&quot; will result in the default bar theme being used.</p>
<?php SkinsConfig(); ?>

<h3>Widget</h3>
<p>The widget shows posts which have the most votes. Only new posts are considered to keep the list fresh.</p>
<p>The widget can be displayed to where you want by using the following code: <code>&lt;?php MostVotedAllTime(); ?&gt;</code>, or if your template supports widgets it can be added via the <a href="widgets.php" title="Widgets">widgets panel</a>.</p>
<table class="form-table" border="0">
<tr valign="top">
<th scope="row" style="text-align: left;">No. of most recent posts to be considered</th>
<td><input type="text" name="voteiu_limit" id="voteiu_limit" value="<?php echo get_option('voteiu_limit'); ?>" /><br />
Default: <code>100</code>
</td>
</tr>
<tr valign="top">
<th scope="row" style="text-align: left;">No. of posts shown in widget</th>
<td><input type="text" name="voteiu_widgetcount" id="voteiu_widgetcount" value="<?php if (get_option('voteiu_widgetcount')=='') { echo '10'; } else {echo get_option('voteiu_widgetcount');} ?>" /><br />
Default: <code>10</code>
</td>
</tr>
</table>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="voteiu_initialoffset,voteiu_votetext,voteiu_sinktext,voteiu_aftervotetext,
	voteiu_allowguests,voteiu_allowownvote,voteiu_limit,voteiu_widgetcount,voteiu_skin,voteiu_nocoffee_except,voteiu_noclub_except,
	voteiu_yescoffee_except,voteiu_yescc_except,voteiu_yesjc_except,
	voteiu_reset_time,voteiu_discussion_loc,voteiu_coffee_time" />

<h3>Voting code</h3>
<p>The following code should be added in your index.php and single.php. This displays the vote buttons.</p>
<p><strong>Themable Version</strong><br />
<code>&lt;?php DisplayVotes(get_the_ID()); ?&gt;</code></p>

<p class="submit">
<input type="submit" name="Submit" value="<?php _e('Update Options &raquo;') ?>" />
</p>
</form>


</div>
<?php

}

/* VoteItUp edit votes page */

function VoteItUp_editvotespage() {
VoteBulkEdit();
?>
<div class="wrap">
<div id="icon-edit" class="icon32"><br /></div>
<h2><?php _e('Edit Votes'); ?></h2>
<form method="post" action="">
<?php /* wp_nonce_field('update-options'); */ ?>
<div class="tablenav">

<div class="alignleft actions">

<select name="action1">
<option value="-1" selected="selected">Bulk Actions</option>
<option value="delete">Reset Votes</option>
<option value="deleteuser">Reset User Votes</option>
<option value="deleteguest">Reset Guest Votes</option>
<option value="resetvotetime">Reset Last Vote Time</option>
</select>
<input type="submit" value="Apply" name="doaction1" id="doaction1" class="button-secondary action" />
</div></div>

<?php DisplayPostList(); ?>

<div class="tablenav">


<div class="alignleft actions">
<select name="action2">
<option value="-1" selected="selected">Bulk Actions</option>
<option value="delete">Reset Votes</option>
<option value="deleteuser">Reset User Votes</option>
<option value="deleteguest">Reset Guest Votes</option>
<option value="resetvotetime">Reset Last Vote Time</option>
</select>

<input type="submit" value="Apply" name="doaction2" id="doaction2" class="button-secondary action" />
<br class="clear" />
</div>
<br class="clear" />
</div>

</form>

</div>
<?php
}

function VoteItUp_createportal () {
	global $institution, $reCAPTCHA;
	echo "<h2>Apply For a New Vox Charta Portal</h2>";
	$result = CreatePortal();
	if (isset($_POST) && $result) {
		echo "<h3>Portal application successfully submitted! New portals should be approved within 24 hours of submission, you will receive an e-mail with further instructions upon approval.</h3>";
		return;
	}
	echo "<h3>Instructions:</h3>";
	echo "<ol>";
	echo "<li>If you do not already have a Vox Charta user account, create an account <a href='http://{$institution->subdomain}.voxcharta.org/wp-login.php";
	echo "?action=register&affiliation=CreatePortal&redirect_to=/tools/apply-for-portal/' target='_blank'>using this link</a> (note the regular registration page will not work).";
	echo "If you already have an account, change your affiliation to \"Unaffiliated\" in your ";
	echo "<a href='http://{$institution->subdomain}.voxcharta.org/wp-admin/profile.php' target='_blank'>user profile</a>. This account will become the \"liaison\" of the new portal. ";
	echo "Multiple accounts can be designated as account liaisons, but <em>all</em> listed accounts must change their affiliations to \"Unaffiliated\" before the application ";
	echo "will process successfully.";
	echo "<li>Fill out all fields in the form below and press the \"Submit\" button.";
	echo "<li>Await the approval e-mail from Vox Charta's administrator. New portals should be approved within 24 hours of submission.";
	echo "</ol>";
	echo "<form method='post' action=''>";
	echo "<table class='form-table' border='0' width='100%'>";
	echo "<tr valign='top'>";
	echo "<th scope='row' style='text-align: left;'>Portal Name</th>";
	echo "<td><input type='text' name='portalname' id='portalname' value='{$_POST['portalname']}' /><br />";
	echo "Examples: <code>UCSC Outflows</code>, <code>Lehigh University</code>, <code>Penn State</code>";
	echo "</td></tr>";
	echo "<tr valign='top'>";
	echo "<th scope='row' style='text-align: left;'>Portal Subdomain</th>";
	echo "<td><input type='text' name='subdomain' id='subdomain' value='{$_POST['subdomain']}' /><br />";
	echo "Examples: <code>ucsc-outflows</code>, <code>lehigh</code>, <code>psu</code>";
	echo "</td></tr>";
	echo "<tr valign='top'>";
	echo "<th scope='row' style='text-align: left;'>Time Zone</th>";
	echo "<td><select name='timezone' id='timezone' />";
	$timezones = timezone_identifiers_list();
	foreach ($timezones as $timezone) {
		if (isset($_POST['timezone']) && $_POST['timezone'] == $timezone) {
			echo "<option selected value='{$timezone}'>{$timezone}</option>";
		} elseif ($institution->timezone == $timezone) {
			echo "<option selected value='{$timezone}'>{$timezone}</option>";
		} else {
			echo "<option value='{$timezone}'>{$timezone}</option>";
		}
	}
	echo "</select></td></tr>";
	echo "<tr valign='top'>";
	echo "<th scope='row' style='text-align: left;'>Portal's Affiliated URL</th>";
	echo "<td><input type='text' name='url' id='url' value='{$_POST['url']}' /><br />";
	echo "Examples: <code>http://astro.ucsc.edu</code>, <code>physics.cas2.lehigh.edu</code>, <code>www.astro.psu.edu</code>";
	echo "</td></tr>";
	echo "<tr valign='top'>";
	echo "<th scope='row' style='text-align: left;'>Liaison Account Name(s)</th>";
	echo "<td><input type='text' name='usernames' id='usernames' value='{$_POST['usernames']}' /><br />";
	echo "Note: This should be a comma-delimited list of account names with affiliations set to <b>unaffiliated</b> (the default for new accounts). The application will fail if any of the ";
	echo "listed account names are non-existent, or if any of the listed accounts do not have their affiliations set to unaffiliated.<br />";
	echo "Examples: <code>astrophguy</code>, <code>chandrasekhar</code>, <code>youngeinstein</code>";
	echo "</td></tr>";
	echo "</table>";
	echo $reCAPTCHA->getHtml();
	echo "<input style='font-size: 30px; font-weight:bold;' type='submit' value='Submit Portal Application' name='submit' id='submit' class='button-secondary action' /><br />";
	echo "<em>All fields are required.</em>";
	echo "</form>";
	echo "<b><font color=red>If you encounter any issues with this form please e-mail <a href='mailto:jguillochon@cfa.harvard.edu'>Vox Charta's administrator</a>.</font></b>";
}

function VoteItUp_recommendpage() {
	global $wpdb, $current_user;
	$user_ID = $current_user->ID;
	$u_ID = $wpdb->escape($user_ID);
	SetRecommend($u_ID);
	RecommendEdit($u_ID);
	$vr = $wpdb->get_row("SELECT sendemail, dontemail, reminddays, showreplace, showcrosslist, bannedtags FROM wp_votes_recommend WHERE user = '{$user_ID}'", ARRAY_A);
	?>
	<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2><?php _e('Recommendation Options'); ?></h2>
	<form method="post" action="">
	<table class="form-table" border="0">
	<tr valign="top">
	<th scope="row" style="text-align: left;">Send me recommendation e-mails</th>
	<td>
	<input type="hidden" name="sendemail" value=0 /><input type="checkbox" name="sendemail" id="sendemail" value=1 <?php if ($vr['sendemail']) { echo ' checked="checked"'; } ?> />
	</td></tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Don't e-mail me if I have voted recently</th>
	<td>
	<input type="hidden" name="dontemail" value=0 /><input type="checkbox" name="dontemail" id="dontemail" value=1 <?php if ($vr['dontemail']) { echo ' checked="checked"'; } ?> /><br />
	Note: Reminder e-mails are only sent if you haven't voted in the number of days defined by recommendation day limit below.
	</td></tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Maximum number of days to include in recommendations</th>
	<td><input type="text" name="reminddays" id="reminddays" value="<?php echo $vr['reminddays'] ?>" /><br />
	Default value: <code>28</code><br>
	Note: This is also the number of days between reminder e-mails.
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Include replacements in recommendations</th>
	<td>
	<input type="hidden" name="showreplace" value=0 /><input type="checkbox" name="showreplace" id="showreplace" value=1 <?php if ($vr['showreplace']) { echo ' checked="checked"'; } ?> />
	</td></tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Include cross-listings in recommendations</th>
	<td>
	<input type="hidden" name="showcrosslist" value=0 /><input type="checkbox" name="showcrosslist" id="showcrosslist" value=1 <?php if ($vr['showcrosslist']) { echo ' checked="checked"'; } ?> />
	</td></tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Your top tags</th>
	<td>
<?php
	$user_raw = $wpdb->get_row("SELECT votes,sinks FROM {$wpdb->prefix}votes_users WHERE user='{$user_ID}' LIMIT 1;");
	$bannedtags = '"'.implode('", "', explode(",", $vr['bannedtags'])).'"';
	$tags = $wpdb->get_results("SELECT t.name as name, tt.term_id as ID, COUNT(tt.term_id) AS counter FROM {$wpdb->posts} AS p
		INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
		INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
		INNER JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id)
		WHERE (tt.taxonomy = 'post_tag' AND p.ID IN ({$user_raw->votes}))
		AND name NOT IN ({$bannedtags})
		AND p.post_status = 'publish'
		AND p.post_type = 'post'
		GROUP BY tt.term_id
		ORDER BY counter DESC
		LIMIT 20;");
?>
<?php
	foreach($tags as $tag) {
		echo "{$tag->name} ({$tag->counter} votes)<br>";
	}
?>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Ban these tags from recommendations (comma-delimited)</th>
	<td><input type="text" name="bannedtags" id="bannedtags" value="<?php echo $vr['bannedtags'] ?>" /><br />
	Example: <code>planet,neutron star,AGN</code>
	</td>
	</tr>
	<tr>
	<th scope="row" style="text-align: left;"> </th>
	<td style="text-align: left;">
	<input type="submit" value="Apply" name="submit" id="submit" class="button-secondary action" /> <?php if (count($_POST) > 0) echo '&nbsp;&nbsp;<b>Changes applied!</b>'; ?>
	</td>
	</tr>
	</table>

	</form>

	</div>
	<?php
}

function VoteItUp_institutionpage() {
	global $wpdb, $current_user;
	$user_ID = $current_user->ID;
	$u_ID = $wpdb->escape($user_ID);
	$user_instit = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user = '{$u_ID}'");
	$institution = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name = '{$user_instit}'");
	InstitutionEdit($institution->ID);
	$user_instit = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user = '{$u_ID}'");
	$institution = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name = '{$user_instit}'");
	?>
	<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2><?php _e('Institution Options'); ?></h2>
	<form method="post" action="">
	<h3>General</h3>
	<table class="form-table" border="0">
	<tr valign="top">
	<th scope="row" style="text-align: left;">Name of institution</th>
	<td><input type="text" name="instname" id="instname" value="<?php echo $institution->name ?>" size=40 /><br />
	Example: <code>UCSC</code>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Subdomain</th>
	<td><input type="text" name="subdomain" id="subdomain" value="<?php echo $institution->subdomain ?>" size=40 /><br />
	Example: <code>ucsc</code>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Timezone</th>
	<td><select name="timezone" id="timezone" />
	<?php $timezones = timezone_identifiers_list(); ?>
	<?php foreach ($timezones as $timezone) {
		if ($institution->timezone == $timezone) {
			echo "<option selected value='{$timezone}'>{$timezone}</option>";
		} else {
			echo "<option value='{$timezone}'>{$timezone}</option>";
		}
	} ?>
	</select><br />
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Institution's homepage</th>
	<td><input type="text" name="url" id="url" value="<?php echo $institution->url ?>" size=80 /><br />
	Example: <code>http://ucsc.astro.edu</code>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Institution's Google Calendar XML address</th>
	<td><textarea class="input" type="text" name="gcaladdress" id="gcaladdress" rows=2 cols=60 /><?php echo $institution->gcaladdress ?></textarea><br />
	Note: This should be copied from the "XML" link on a Google Calendar's settings page. Ensure that the final word in the URL is 'full' instead of 'basic'.<br />
	Example: <code>http://www.google.com/calendar/feeds/u9o0q52sl451s0f3fdqpa3ksg4%40group.calendar.google.com/public/full</code>
	</td>
	</tr>
	<tr>
	<th scope="row" style="text-align: left;"> </th>
	<td style="text-align: left;">
	<input type="submit" value="Apply New Settings" name="submit" id="submit" class="button-secondary action" /> <?php if (count($_POST) > 0) echo '&nbsp;&nbsp;<b>Changes applied!</b>'; ?>
	</td>
	</tr>
	</table>
	<h3>Primary Discussion</h3>
	<em>The primary discussion uses the voting system to construct each day's agenda.</em>
	<table class="form-table" border="0">
	<tr valign="top">
	<th scope="row" style="text-align: left;">Name of discussion</th>
	<td><input type="text" name="primaryevent" id="primaryevent" value="<?php echo $institution->primaryevent ?>" size=40 /><br />
	Examples: <code>Coffee</code>, <code>Astro-ph Discussion</code>, <code>Journal club</code>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Discussion location</th>
	<td><input type="text" name="location" id="location" value="<?php echo $institution->location ?>" size=40 /><br />
	Example: <code>in CfAO Atrium</code>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Days of Discussion</th>
	<td><input type="text" name="normaldays" id="normaldays" value="<?php echo $institution->normaldays ?>" size=40 /><br />
	Example: <code>Mon,Wed,Fri</code>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Times of discussions<br>(24 hour format)</th>
	<td><input type="text" name="discussiontime" id="discussiontime" value="<?php echo $institution->discussiontime ?>" size=40 /><br />
	Example: <code>14:00</code>, <code>10:30,10:30,10:45</code><br>
	Note: This is the time each discussion begins. If multiple discussion durations are entered in comma-delimited form, the durations correspond to the matching days of discussion specified above.
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Durations of discussions<br>(in minutes)</th>
	<?php
	$discussiontimes = explode(",",$institution->discussiontime);
	$resettimes = explode(",",$institution->resettime);
	$discussiondurations = array_fill(0, count($discussiontimes), 0);
	foreach ($discussiontimes as $i => $discussiontime) {
		$discussiondurations[$i] = (strtotime($resettimes[$i]) - strtotime($discussiontime)) / 60;
	}
	$discussiondurations = implode(",",$discussiondurations);
	?>
	<td><input type="text" name="discussionduration" id="discussionduration" value="<?php echo $discussiondurations; ?>" size=40 /><br />
	Example: <code>120</code>, <code>60,45,60</code><br>
	Note: This is the duration of each discussion. If multiple discussion durations are entered in comma-delimited form, the durations correspond to the matching days of discussion specified above. All votes that occur before the end of the discussion will appear in the current agenda, whereas votes that occur after will appear in the next agenda.
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Close voting this many minutes before end of discussion</th>
	<td><input type="text" name="closedelay" id="closedelay" value="<?php echo $institution->closedelay; ?>" /><br />
	Default: <code>0</code> (Voting is open until the discussion ends)<br>
	Note: This is useful to prevent last-minute votes.
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Limit the agenda to only show this many postings by default. </th>
	<td><input type="text" name="agendalimit" id="agendalimit" value="<?php echo $institution->agendalimit; ?>" /><br />
	Default: <code>0</code> (All postings with votes will be shown)<br>
	Note: Postings below this limit can be made visible by the user by clicking "Show all votes" at the bottom of the agenda. A setting of 0 imposes no limit.
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Allow voting for next discussion after voting for current discussion has closed</th>
	<td><input type="checkbox" name="closevoting" id="closevoting" <?php if ($institution->closevoting == 1) echo 'checked'; ?> size=40 /><br />
	Note: This is only applicable if voting closes before the end of the discussion (i.e. the above option is greater than zero).
	</td>
	</tr>
	<tr valign="top">
	<tr valign="top">
	<th scope="row" style="text-align: left;">Extra days</th>
	<td><textarea class="input" type="text" name="extradays" id="extradays" rows=3 cols=60><?php echo $institution->extradays ?></textarea><br />
	Example: <code>1/20/2010,2/2/2010,3/13/2010,4/1/2010-4/14/2010,6/12/2010-8/12/2010:7</code><br>
	Notes: This supports ranges with the following syntax: start-end:stride. This allows for more sophisticated schedules (e.g. bi-weekly events, temporary changes in regular discussion days, etc). All dates should be entered in mm/dd/yyyy format.
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Canceled days</th>
	<td><textarea class="input" type="text" name="canceleddays" id="canceleddays" rows=3 cols=60><?php echo $institution->canceleddays ?></textarea><br />
	Example: <code>1/20/2010,2/2/2010,3/13/2010,4/1/2010-4/14/2010,6/12/2010-8/12/2010:7</code><br>
	Notes: Supports same syntax as the "extra days" field. Canceled days will always override "day of week" and "extra days" settings.
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Include papers marked as "not discussed" in all future agendas</th>
	<td><input type="checkbox" name="pushnotdiscussed" id="pushnotdiscussed" <?php if ($institution->pushnotdiscussed == 1) echo 'checked'; ?> size=40 /><br />
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Show historical votes received by a paper on the homepage</th>
	<td><input type="checkbox" name="showhistoricalvotes" id="showhistoricalvotes" <?php if ($institution->showhistoricalvotes == 1) echo 'checked'; ?> size=40 /><br />
	</td>
	</tr>
	<tr>
	<th scope="row" style="text-align: left;"> </th>
	<td style="text-align: left;">
	<input type="submit" value="Apply New Settings" name="submit" id="submit" class="button-secondary action" /> <?php if (count($_POST) > 0) echo '&nbsp;&nbsp;<b>Changes applied!</b>'; ?>
	</td>
	</tr>
	</table>
	<h3>E-mail Announcements</h3>
	<table class="form-table" border="0">
	<tr valign="top">
	<th scope="row" style="text-align: left;">E-mail addresses to send announcements to</th>
	<td><input type="text" name="announceaddress" id="announceaddress" value="<?php echo $institution->announceaddress ?>" size=40 /><br />
	Example: <code>list@university.edu,user1@domain.com,user2@domain.com</code>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">E-mail address to send announcements from</th>
	<td><input type="text" name="announcefrom" id="announcefrom" value="<?php echo $institution->announcefrom ?>" size=40 /><br />
	Example: <code>user@university.edu</code><br>
	Note: The website will change the e-mail header to make the announcement e-mail appear as if it is coming from the above address. Some mailing lists do not allow messages to be sent from outside e-mail addresses. Leaving this field blank will send from the default Vox Charta address.
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">E-mail a copy of announcement to all users</th>
	<td><input type="checkbox" name="announcecopy" id="announcecopy" <?php if ($institution->announcecopy == 1) echo 'checked'; ?> size=40 /><br />
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Number of minutes announcement e-mail is sent prior to discussion</th>
	<td><input type="text" name="announcedelay" id="announcedelay" value="<?php echo $institution->announcedelay ?>" size=40 /><br />
	Note: A comma-delimited list will send out multiple reminder e-mails for each discussion.<br>
	Examples: <code>10</code> or <code>30,60</code>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Include abstracts in e-mail announcement</th>
	<td><input type="checkbox" name="announceabstracts" id="announceabstracts" <?php if ($institution->announceabstracts == 1) echo 'checked'; ?> size=40 /><br />
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Include metadata (i.e. list of authors, links to PDF, etc.) in e-mail announcement</th>
	<td><input type="checkbox" name="announcemeta" id="announcemeta" <?php if ($institution->announcemeta == 1) echo 'checked'; ?> size=40 /><br />
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Send announcement in plain text format</th>
	<td><input type="checkbox" name="announceplain" id="announceplain" <?php if ($institution->announceplain == 1) echo 'checked'; ?> size=40 /><br />
	Note: Enabling this option will ignore the "include metadata" option (metadata will not be included).
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Include top five voted-on papers from other institutions if agenda is empty</th>
	<td><input type="checkbox" name="noemptyagenda" id="noemptyagenda" <?php if ($institution->noemptyagenda == 1) echo 'checked'; ?> size=40 /><br />
	</td>
	</tr>
	<tr>
	<th scope="row" style="text-align: left;"> </th>
	<td style="text-align: left;">
	<input type="submit" value="Apply New Settings" name="submit" id="submit" class="button-secondary action" /> <?php if (count($_POST) > 0) echo '&nbsp;&nbsp;<b>Changes applied!</b>'; ?>
	</td>
	</tr>
	</table>
	<h3>User Settings</h3>
	<table class="form-table" border="0">
	<tr valign="top">
	<th scope="row" style="text-align: left;">List of <?php echo $institution->name;?>'s liaisons</th>
	<td>
	<select name='liaison' id='liaison'>
	<option value="-1" selected>Select a user</option>
	<?php
	$liaisons = get_users('role=liaison');
	$users = $wpdb->get_col("SELECT wu.user_email FROM wp_users as wu INNER JOIN wp_votes_users as vu ON (wu.ID = vu.user) WHERE vu.affiliation LIKE '{$institution->name}'");
	foreach ($liaisons as $liaison) {
		if (array_search($liaison->user_email, $users)) {
			echo "<option value='{$liaison->ID}'>".$liaison->display_name . " (" . $liaison->user_email . ")</option>";
		}
	}
	?></select>
	<input type="submit" value="Remove liaison status from selected user" class="button-secondary action"></td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">List of regular <?php echo $institution->name; ?> users</th>
	<td>
	<select name='normal' id='normal'>
	<option value="-1" selected>Select a user</option>
	<?php
	$nonliaisons = array_merge(get_users('role=subscriber'),get_users('role=author'),get_users('role=editor'));
	foreach ($nonliaisons as $non) {
		if (array_search($non->user_email, $users)) {
			echo "<option value='{$non->ID}'>".$non->display_name . " (" . $non->user_email . ")</option>";
		}
	}
	?></select>
	<button id="promote-submit" type="submit" name="submit" value="promote" class="button-secondary action">Promote selected user to liaison</button>
	<button id="unaffil-submit" type="submit" name="submit" value="unaffil" class="button-secondary action">Remove selected user's affiliation</button><br />
	Note: Removing a user's affiliation will make user "Unaffiliated," and only the user will be able to set a new affiliation.
	</td>
	</tr>
	<tr>
	<th scope="row" style="text-align: left;">List of all e-mail addresses (for reference purposes)</th>
	<td>
	<textarea rows="6" cols="30"><?php
	foreach ($users as $user) {
		echo $user . "\n";
	}
	?></textarea>
	</td>
	</tr>
	<tr>
	<th scope="row" style="text-align: left;"> </th>
	<td style="text-align: left;">
	<input type="submit" value="Apply New Settings" name="submit" id="submit" class="button-secondary action" /> <?php if (count($_POST) > 0) echo '&nbsp;&nbsp;<b>Changes applied!</b>'; ?>
	</td>
	</tr>
	</table>

	</form>

	</div>
	<?php
}

function VoteItUp_eventspage() {
	global $wpdb, $current_user, $institution;
	$u_ID = $current_user->ID;
	$user_instit = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user = '{$u_ID}'");
	$institution = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name = '{$user_instit}'");
	$events = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}votes_events WHERE affiliation = '{$institution->ID}'");
	if (array_key_exists('addnew', $_POST)) {
		SetEvent();
		$e = $wpdb->get_var("SELECT max(ID) FROM {$wpdb->prefix}votes_events WHERE affiliation = '{$institution->ID}'");
		$events = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}votes_events WHERE affiliation = '{$institution->ID}'");
	} elseif (array_key_exists('delete', $_POST)) {
		if (array_key_exists('eventid', $_POST)) {
			DeleteEvent($_POST['eventid']);
		}
		$events = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}votes_events WHERE affiliation = '{$institution->ID}'");
		$e = $events[0]->ID;
	} elseif (array_key_exists('event', $_POST)) {
		$e = $_POST['event'];
	} else {
		$e = $events[0]->ID;
	}
	$event = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_events WHERE ID = '{$e}'");
	if (array_key_exists('eventid', $_POST) && $_POST['eventid'] == $e) {
		EventsEdit($event);
		$events = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}votes_events WHERE affiliation = '{$institution->ID}'");
	}
	$isfirst = (count($events) == 0) ? true : false;
	$event = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_events WHERE ID = '{$e}'");
	?>
	<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2><?php _e('Event Options'); ?></h2>
	<form name="eventform" id="eventform" method="post" action="">
	<?php if (!$isfirst) { ?>
	<table class="form-table" border="0">
	<tr valign="top">
	<th scope="row" style="text-align: left;">Show event</th>
	<td><select name="event" id="event" onchange="document.eventform.submit();">
	<?php
	if (count($events) == 0) echo "<option selected>Unnamed Event</option>";
	foreach ($events as $eventi) {
		$ename = ($eventi->nicename === '') ? 'Unnamed Event' : $eventi->nicename;
		if ($eventi->ID === $e) {
			echo "<option selected value={$eventi->ID}>{$ename}</option>";
		} else {
			echo "<option value={$eventi->ID}>{$ename}</option>";
		}
	}
	?>	
	</select>
	<input type="hidden" name="eventid" value=<?php echo $e; ?> />
	<?php } else { ?>
	<?php echo $institution->name; ?> does not currently have any special events!
	<?php } ?>
	<?php if ($isfirst || $event->nicename != '') {?>
	<input type="submit" value="Add new event" name="addnew" id="addnew" class="button-secondary action" />
	<?php } ?>
	<?php if (!$isfirst) { ?>
	<input type="button" value="Delete current event" class="button-secondary action"
		onClick="if (confirm('Are you sure you wish to delete this event?')) {
				     var delete_input = document.createElement('input');
				     delete_input.type = 'hidden';
				     delete_input.name = 'delete';
				     delete_input.value = 1;
				     document.eventform.appendChild(delete_input);
				     document.eventform.normalize();
				     document.eventform.submit();
				 }
    "/>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Full name</th>
	<td><input type="text" name="nicename" id="nicename" value="<?php echo $event->nicename ?>" size=40 /><br />
	Example: <code>Journal Club</code><br />
	Note: This name will be visible in the category listing when users add new posts.
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Theme</th>
	<td>
		<input type="radio" name="theme" value="cc" <?php if ($event->theme == 'cc') echo 'checked'; ?> size=40 />
		<img src="../wp-content/themes/arclite/colloquiumclub-icon.png" style="vertical-align: middle;">&nbsp;&nbsp;
		<input type="radio" name="theme" value="jc" <?php if ($event->theme == 'jc') echo 'checked'; ?> size=40 />
		<img src="../wp-content/themes/arclite/journalclub-icon.png" style="vertical-align: middle;">
		<input type="radio" name="theme" value="cf" <?php if ($event->theme == 'cf') echo 'checked'; ?> size=40 />
		<img src="../wp-content/themes/arclite/coffeeclub-icon.png" style="vertical-align: middle;">
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">No discussion agenda</th>
	<td><input type="checkbox" name="noagenda" id="noagenda" <?php if ($event->noagenda == 1) echo 'checked'; ?> size=40 />
	&nbsp;&nbsp;(This is useful to set up events for which there is no pre-determined agenda.)
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Day of week event occurs</th>
	<td><input type="text" name="dayofweek" id="dayofweek" value="<?php echo $event->dayofweek ?>" size=40 /><br />
	Example: <code>Mon</code>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Extra event days</th>
	<td><textarea class="input" type="text" name="extradays" id="extradays" rows=3 cols=60><?php echo $event->extradays ?></textarea><br />
	Example: <code>1/20/2010,2/2/2010,3/13/2010,4/1/2010-4/14/2010,6/12/2010-8/12/2010:7</code><br>
	Notes: This supports ranges with the following syntax: start-end:stride. This allows for more sophisticated schedules (e.g. bi-weekly events, temporary changes in regular discussion days, etc). All dates should be entered in mm/dd/yyyy format.
	</td>
	</tr>
	<tr valign="top">
	<th scope="row" style="text-align: left;">Canceled event days</th>
	<td><textarea class="input" type="text" name="canceleddays" id="canceleddays" rows=3 cols=60><?php echo $event->canceleddays ?></textarea><br />
	Example: <code>1/20/2010,2/2/2010,3/13/2010,4/1/2010-4/14/2010,6/12/2010-8/12/2010:7</code><br>
	Notes: Supports same syntax as the "extra days" field. Canceled days will always override "day of week" and "extra days" settings.
	</td>
	</tr>
	<tr>
	<th scope="row" style="text-align: left;"> </th>
	<td style="text-align: left;">
	<?php if ($event->nicename == '') {?>
	<input type="submit" value="Create this event" name="create" id="create" class="button-secondary action" />
	<?php } else { ?>
	<input type="submit" value="Apply changes" name="apply" id="apply" class="button-secondary action" />  <?php if (isset($_POST['create'])) echo '&nbsp;&nbsp;Event created!'; if (isset($_POST['apply'])) echo '&nbsp;&nbsp;<b>Changes applied!</b>'; ?>
	<?php } ?>
	</td>
	</tr>
	</table>

	<?php } ?>
	</form>

	</div>
	<?php
}
?>
