<?php
	require_once(dirname(__FILE__) . '/../../../wp-config.php');
	require_once(dirname(__FILE__) . '/../vote-it-up/votingfunctions.php');

	if(isset($_GET['code']) && $_GET['code'] == get_option('wpo_croncode')) 
	{
		$names = $wpdb->get_col("SELECT name FROM {$wpdb->prefix}votes_institutions");
		if (in_array($_GET['portalname'], $names)) {
			echo "<font color='red'><h3>Error: This portal was already created!</h3></font>";
			return;
		}
		$catvis = '1,1,1,1,1,1,1,0,0,0,0,0,1,1,0,0';
		$ins_q = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."votes_institutions (
			name, subdomain, primaryevent, timezone, events, location, normaldays, extradays, canceleddays, resettime,
			discussiontime, closedelay, closevoting, announceaddress, announcefrom, announcemaintemp, announcenotemp,
			announceeventtemp, announcedelay, announcecopy, announceabstracts, announcemeta, announceplain,
			noemptyagenda, agendalimit, pushnotdiscussed, showhistoricalvotes, gcaladdress, url, active, catvis
			) VALUES(
			'%s', '%s', 'ArXiv Discussion', '%s', '', 'Discussion Location', 'Mon,Tue,Wed,Thu,Fri', '', '', '12:00',
			'10:30', '0', '0', '', '', '', '', '', '15', '0', '1', '1', '0', '1', '0', '0', '0', '', '%s', '1', '%s')",
			$_GET['portalname'], $_GET['subdomain'], $_GET['timezone'], $_GET['url'], $catvis);
		$wpdb->query($ins_q);
		echo $ins_q . "<br>";
		$namelist = explode(',', $_GET['usernames']);
		$user_q = $wpdb->prepare("SELECT ID, user_login FROM {$wpdb->prefix}users WHERE user_login IN ('%s')", $namelist);
		$users = $wpdb->get_results($user_q);
		foreach ($users as $user) {
			$user_update_q = $wpdb->prepare("UPDATE {$wpdb->prefix}votes_users SET affiliation = '%s' WHERE user = '{$user->ID}'", $_GET['portalname']);
			wp_update_user( array( 'ID' => $user->ID, 'role' => 'liaison' ) );
			$wpdb->query($user_update_q);
			$user_info = get_userdata($user->ID);
			echo $user_info->user_login;
		}
	}
?>
