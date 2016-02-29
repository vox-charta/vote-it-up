<?php
//Installs and update options
$voteiu_dbversion = 2000;

function VoteItUp_InstallOptions() {
global $voteiu_dbversion;

//Default options for install
$voteiu_votetext = 'Vote';
$voteiu_sinktext = '';
$voteiu_aftervotetext = '';
$voteiu_allowguests = true;
$voteiu_allowownvote = true;
$voteiu_limit = 100;
$voteiu_widgetcount = 10;
$voteiu_skin = '';
$voteiu_initialoffset = 0;
$voteiu_nocoffee_except = '';
$voteiu_noclub_except = '';
$voteiu_yescoffee_except = '';
$voteiu_yescc_except = '';
$voteiu_yesjc_except = '';
$voteiu_discussion_loc = '';
$voteiu_reset_time = '12:30';
$voteiu_coffee_time = '10:30';
$voteiu_cc_day = 'Mon';
$voteiu_jc_day = 'Thu';

//Begins adding options if not available
//3rd parameter is deprecated, but added for compatibility
add_option('voteiu_votetext', $voteiu_votetext);
add_option('voteiu_sinktext', $voteiu_sinktext);
add_option('voteiu_aftervotetext', $voteiu_aftervotetext);
add_option('voteiu_allowguests', $voteiu_allowguests);
add_option('voteiu_allowownvote', $voteiu_allowownvote);
add_option('voteiu_limit', $voteiu_limit);
add_option('voteiu_widgetcount', $voteiu_widgetcount);
add_option('voteiu_skin', $voteiu_skin);
add_option('voteiu_dbversion', $voteiu_dbversion);
add_option('voteiu_initialoffset', $voteiu_initialoffset);
add_option('voteiu_nocoffee_except', $voteiu_nocoffee_except);
add_option('voteiu_noclub_except', $voteiu_noclub_except);
add_option('voteiu_yescoffee_except', $voteiu_yescoffee_except);
add_option('voteiu_yescc_except', $voteiu_yescoffee_except);
add_option('voteiu_yesjc_except', $voteiu_yescoffee_except);
add_option('voteiu_discussion_loc', $voteiu_discussion_loc);
add_option('voteiu_reset_time', $voteiu_reset_time);
add_option('voteiu_coffee_time', $voteiu_coffee_time);
add_option('voteiu_cc_day', $voteiu_cc_day);
add_option('voteiu_jc_day', $voteiu_jc_day);

//Change setting to default values if user left these fields blank
if (get_option('voteiu_initialoffset') == '') {
update_option('voteiu_initialoffset', $voteiu_initialoffset);
}
if (get_option('voteiu_limit') == '') {
update_option('voteiu_limit', $voteiu_limit);
}
if (get_option('voteiu_widgetcount') == '10') {
update_option('voteiu_widgetcount', $voteiu_widgetcount);
}

}

//Updates options and remove unused ones
function VoteItUp_UpgradeOptions() {
global $voteiu_dbversion;

$currentdbversion = 0;
if (get_option('voteiu_dbversion')) {
$currentdbversion = get_option('voteiu_dbversion');
}

if ($voteiu_dbversion > $currentdbversion) {

//Update options here

}

}

//Deletes old unused options
function VoteItUp_DeleteOldOptions() {
delete_option('voteiu_allowsinks');
delete_option('voteiu_excluded');
delete_option('voteiu_usevotetext');
}


//Installs DB tables

function VoteItUp_dbinstall() {
	global $wpdb;
	$table_name = $wpdb->prefix.'votes_users';
	if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."votes_users'") != $table_name) {
		$query = "CREATE TABLE ".$wpdb->prefix."votes_users (
			ID int(11) NOT NULL auto_increment,
			user int(11) NOT NULL,
			votes text NOT NULL,
			sinks text NOT NULL,
			affiliation text NOT NULL,
			PRIMARY KEY  (ID));";
		$wpdb->query($query);
	}
	$table_name = $wpdb->prefix.'votes';
	if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."votes'") != $table_name) {
		$query2 = "CREATE TABLE ".$wpdb->prefix."votes (
			ID int(11) NOT NULL auto_increment,
			post int(11) NOT NULL,
			lastvotetime int(11) NOT NULL,
			votes text NOT NULL,
			guests text NOT NULL,
			usersinks text NOT NULL,
			guestsinks text NOT NULL,
			PRIMARY KEY  (ID));";
		$wpdb->query($query2);
	}
	$table_name = $wpdb->prefix.'votes_recommend';
	if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."votes_recommend'") != $table_name) {
		$query3 = "CREATE TABLE ".$wpdb->prefix."votes_recommend (
			ID int(11) NOT NULL auto_increment,
			user int(11) NOT NULL,
			showreplace bool NOT NULL,
			showcrosslist bool NOT NULL,
			sendemail bool NOT NULL,
			reminddays int(11) NOT NULL,
			lastemailtime int(11) NOT NULL,
			bannedtags text NOT NULL,
			PRIMARY KEY  (ID));";
		$wpdb->query($query3);
	}
	$table_name = $wpdb->prefix.'votes_institutions';
	if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."votes_institutions'") != $table_name) {
		$query4 = "CREATE TABLE ".$wpdb->prefix."votes_institutions (
			ID int(11) NOT NULL auto_increment,
			name text NOT NULL,
			events text NOT NULL,
			location text NOT NULL,
			PRIMARY KEY  (ID));";
		$wpdb->query($query4);
	}
	$table_name = $wpdb->prefix.'votes_events';
	if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."votes_events'") != $table_name) {
		$query5 = "CREATE TABLE ".$wpdb->prefix."votes_events (
			ID int(11) NOT NULL auto_increment,
			name text NOT NULL,
			dayofweek text NOT NULL,
			canceleddays text NOT NULL,
			extradays text NOT NULL,
			PRIMARY KEY  (ID));";
		$wpdb->query($query5);
	}
}

?>
