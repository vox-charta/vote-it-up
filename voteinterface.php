<?php
include_once("../../../wp-blog-header.php");
include_once('../../../wp-config.php');
include_once('../../../wp-includes/wp-db.php');
include_once('../../../wp-includes/pluggable.php');
include_once("votingfunctions.php");
header('HTTP/1.1 200 OK'); //Wordpress sends a 404 for some reason, override this (added by JFG).
global $show_everyone, $schedaffil, $institution, $wpdb;
//$url_parts = explode('.', $_SERVER['HTTP_HOST']);
//$sub_is_inst = false;
//if (count($url_parts) == 3) {
//	$institutions = $wpdb->get_col("SELECT name FROM {$wpdb->prefix}votes_institutions WHERE name NOT LIKE 'Unaffiliated'");
//	foreach ($institutions as $ins) {	
//		if (strtolower($url_parts[0]) == strtolower($ins)) {
//			$sub_is_inst = true;
//			$inst_str = $ins;
//		}
//	}
//}
//if ($sub_is_inst) {
//	setcookie('schedule_affiliation',$inst_str,time()+365*24*3600,'/','.voxcharta.org');
//	setcookie('show_everyone','0',time()+365*24*3600,'/','.voxcharta.org');
//	$show_everyone = false;
//	$schedaffil = $inst_str;
//} else {
//	if (!isset($_COOKIE['show_everyone'])) {
//		setcookie('show_everyone','0',time()+365*24*3600,'/','.voxcharta.org');
//		$show_everyone = false;
//	} else {
//		$show_everyone = ($_COOKIE['show_everyone'] == '1') ? true : false;
//	}
//	if (!isset($_COOKIE['schedule_affiliation'])) {
//		setcookie('schedule_affiliation','UCSC',time()+365*24*3600,'/','.voxcharta.org');
//		$schedaffil = 'UCSC';
//	} else {
//		$schedaffil = $_COOKIE['schedule_affiliation'];
//	}
//}
//$institution = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name='{$schedaffil}'");
if (!isset($schedaffil)) $schedaffil = $_COOKIE['schedule_affiliation'];
if (!isset($show_everyone)) $show_everyone = ($_COOKIE['show_everyone'] == '1') ? true : false;
if (!isset($institution)) $institution = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name='{$schedaffil}'");
date_default_timezone_set($institution->timezone);

if (!is_user_logged_in()) {
	header("Location: ".get_option("siteurl")."/wp-login.php");
} else {
	//if ($_GET['uid'] != '') {
		//if ($_GET['uid'] == 0) {
		//	if (get_option('voteiu_allowguests') == 1) {
		//		//Guest voting
		//		if ($_GET['type'] != 'sink') {
		//			GuestVote($_GET['pid'],'vote');
		//		} else {
		//			GuestVote($_GET['pid'],'sink');
		//		}
		//	}
		//} else {
			//Add vote
	$myuid = wp_get_current_user()->ID;
	//if ($_GET['uid'] == '') {
	//	$myuid = wp_get_current_user()->ID;
	//} else {
	//	$myuid = $_GET['uid'];
	//}
	if ($_GET['arxivid'] != '') {
		$post = $wpdb->get_row("SELECT p.ID AS id, p.guid AS url from {$wpdb->prefix}posts AS p
			 				    INNER JOIN {$wpdb->prefix}postmeta AS pm ON (p.ID = pm.post_id)
			 				    WHERE pm.meta_key = 'wpo_arxivid' AND pm.meta_value = '".$_GET['arxivid']."';");
		$pid = $post->id;
		$url = $post->url;
		header("Location: {$post->url}");
	} else {
		$pid = $_GET['pid'];
	}

	if ($pid != '' && $pid != null) {
		if ($_GET['type'] == 'date') {
			Vote($_GET['pid'],$myuid,'date',$_GET['date']);
		} elseif ($_GET['type'] == 'suggest') {
			if ($_GET['sname'] != "suggest") {
				Suggest($_GET['pid'],$_GET['uid'],$_GET['sname']);
			}
		} elseif ($_GET['type'] == 'unvote') {
			Unvote($_GET['pid'],$myuid);
		} elseif ($_GET['type'] == 'discuss') {
			Discuss($_GET['pid'],$_GET['today']);
		} elseif ($_GET['type'] == 'present') {
			Present($_GET['pid'],$myuid);
		} elseif ($_GET['type'] != '') {
			Vote($pid,$myuid,$_GET['type']);
		}
		if ($_GET['tid'] != '') {
			if ($_GET['tid'] == 'total') {
				echo GetVotes($_GET['pid'], false);
			} else if ($_GET['tid'] == 'percent') {
				//run the math as a percentage not total
				echo GetVotes($_GET['pid'], true);
			} else {
				$barvotes = GetBarVotes($_GET['pid']);
				echo $barvotes[0];
			}
		} elseif ($_GET['type'] == 'suggest') {
			if ($_GET['sname'] != "suggest") {
				$nice_name = $wpdb->get_var("SELECT display_name FROM ".$wpdb->prefix."users WHERE user_nicename='".$_GET['sname']."'");
				echo '<em>Paper suggested to '.$nice_name.'!</em>';
			} else {
				echo '<em>Please select someone from the list!</em>';
			}
		}
	}
}
//} else {
//	echo '0';
//}
?>
