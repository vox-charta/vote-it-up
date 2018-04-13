<?php
/*
This script is designed to be run from wordpress. It will work if placed in the root directory of a wordpress install.
Revision: 4

-Added Widget
-Fixed Guest Voting
-Modified to use get_options
-Fixed Widget, now excludes deleted posts

*/

//Run this to create an entry for a post in the voting system. Will check if the post exists. If it doesn't, it will create an entry.
function SetPost($post_ID, $institution_ID) {
	global $wpdb;
	
	//prevents SQL injection
	$p_ID = $wpdb->escape($post_ID);

	//Check if entry exists
	$ids_post = $wpdb->get_col("SELECT institution FROM ".$wpdb->prefix."votes WHERE post='".$p_ID."'");

	if (!in_array($institution_ID, $ids_post)) {
		//entry does not exist
		$wpdb->query("INSERT INTO ".$wpdb->prefix."votes (post, votes, guests, usersinks, guestsinks, lastvotetime, votetimes, sinktimes, guestvotetimes, guestsinktimes, institution, discussed) VALUES(".$p_ID.", '', '', '', '', 0, '', '', '', '', '{$institution_ID}', 0) ") or die(mysql_error());
	}
}

//Run this to create an entry for a user in the voting system. Will check if the user exists. If it doesn't, it will create an entry.
function SetUser($user_ID) {
	global $wpdb;
	
	//prevents SQL injection
	$u_ID = $wpdb->escape($user_ID);

	//Check if entry exists
	$id_raw = $wpdb->get_var("SELECT ID FROM ".$wpdb->prefix."votes_users WHERE user='".$u_ID."'");
	if ($id_raw != '') {
		//entry exists, do nothing
	} else {
		//entry does not exist
		$wpdb->query("INSERT INTO ".$wpdb->prefix."votes_users (user, votes, sinks, presents, affiliation) VALUES(".$u_ID.", '', '', '', 'UCSC') ") or die(mysql_error());
	}
}

function SetEvent() {
	global $wpdb, $institution;
	
	$wpdb->query("INSERT INTO ".$wpdb->prefix."votes_events (nicename, dayofweek, categoryslug, canceleddays, extradays, affiliation, theme, noagenda) VALUES('', '', '', '', '', '{$institution->ID}', '', '0') ") or die(mysql_error());
}

function DeleteEvent($event_ID) {
	global $wpdb;
	
	//prevents SQL injection
	$e_ID = $wpdb->escape($event_ID);

	$wpdb->query("DELETE FROM {$wpdb->prefix}votes_events WHERE ID = '{$e_ID}'");
}

function SetRecommend($user_ID) {
	global $wpdb;
	
	//prevents SQL injection
	$u_ID = $wpdb->escape($user_ID);

	//Check if entry exists
	$id_raw = $wpdb->get_var("SELECT ID FROM ".$wpdb->prefix."votes_recommend WHERE user='".$u_ID."'");
	if ($id_raw != '') {
		//entry exists, do nothing
	} else {
		//entry does not exist
		$wpdb->query("INSERT INTO ".$wpdb->prefix."votes_recommend (user, showreplace, showcrosslist, sendemail, dontemail, reminddays, lastemailtime, bannedtags) VALUES(".$u_ID.", 0, 1, 1, 1, 21, 0, '') ") or die(mysql_error());
	}
}

function GetVoteCounts($post_ID, &$count, &$evcount, &$evinsts = NULL) {
	global $wpdb, $institution;
	
	//prevents SQL injection
	$p_ID = $wpdb->escape($post_ID);

	//Create entries if not existant
	//SetPost($p_ID);

	//Returns 0 if no one has voted.
	//if (GetLastVoteTime($p_ID) == 0) {
	//	$count = 0;
	//	$evcount = 0;
	//	return;
	//}

	//Gets the votes
	$votes_raw = $wpdb->get_results("SELECT lastvotetime, institution, votes, usersinks, guests, guestsinks FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND lastvotetime != 0");

	//$inst_users = $wpdb->get_col("SELECT user FROM {$wpdb->prefix}votes_users WHERE affiliation='{$institution->name}'");
	//$votecount = ($votes_raw[0] == "") ? 0 : count(array_intersect(explode(",", $votes_raw[0]), $inst_users));
	//$sinkcount = ($votes_raw[1] == "") ? 0 : count(array_intersect(explode(",", $votes_raw[1]), $inst_users));
	$evvotecount = 0;
	$evsinkcount = 0;
	$evguestvotecount = 0;
	$evguestsinkcount = 0;
	$votecount = 0;
	$sinkcount = 0;
	$guestvotecount = 0;
	$guestsinkcount = 0;
	$inst_list = array();
	foreach ($votes_raw as &$vote_raw) {
		if ($evinsts !== NULL) $inst_list[] = $vote_raw->institution;
		$evvotecount += ($vote_raw->votes == "") ? 0 : count(explode(",", $vote_raw->votes));
		$evsinkcount += ($vote_raw->usersinks == "") ? 0 : count(explode(",", $vote_raw->usersinks));
		//if (get_option('voteiu_allowguests')) {
			$evguestvotecount += ($vote_raw->guests == "") ? 0 : count(explode(",", $vote_raw->guests));
			$evguestsinkcount += ($vote_raw->guestsinks == "") ? 0 : count(explode(",", $vote_raw->guestsinks));
		//}
		if ($vote_raw->institution == $institution->ID) {
			$votecount += ($vote_raw->votes == "") ? 0 : count(explode(",", $vote_raw->votes));
			$sinkcount += ($vote_raw->usersinks == "") ? 0 : count(explode(",", $vote_raw->usersinks));
			//if (get_option('voteiu_allowguests')) {
				$guestvotecount += ($vote_raw->guests == "") ? 0 : count(explode(",", $vote_raw->guests));
				$guestsinkcount += ($vote_raw->guestsinks == "") ? 0 : count(explode(",", $vote_raw->guestsinks));
			//}
		}
	}

	$count = $votecount - $sinkcount + $guestvotecount - $guestsinkcount;
	$evcount = $evvotecount - $evsinkcount + $evguestvotecount - $evguestsinkcount;
	$evinsts = count(array_unique($inst_list));
}

//Returns the vote count
function GetVotes($post_ID, $count = '', $evcount = '', $evinsts = '') {
	global $wpdb, $institution;

	if (!isset($count) || $count == '') {
		GetVoteCounts($post_ID, $count, $evcount, $evinsts);
	}

	$votestr = (abs($count) == 1) ? 'vote' : 'votes';
	$evotestr = (abs($evcount) == 1) ? 'vote' : 'votes';
	$ret_string = '<span style="font-size: 15pt; letter-spacing: -1px;">'.$count.' '.$votestr.' @'.$institution->name.'</span><br><span style="font-size: 8pt;">('.$evcount.' '.$evotestr;
	if ($evinsts != '') {
		$ret_string .= ' from '.$evinsts.' institution';
		if ($evinsts > 1) $ret_string .= 's';
	} else {
		$ret_string .= ' over all institutions';
	}
	$ret_string .= ')</span>';
	return $ret_string;
}

//Returns a series of information
function GetBarVotes($post_ID) {

	//Some minor configuration
	$max_displayed_votes = 40;
	$vote_threshold = 30;

	$votes = GetVotes($post_ID);
	$votemax = $max_displayed_votes;
	$votebreak =  30; //votes at which bar changes color
	$bar[0] = 0; //The length of the bar
	$bar[1] = 0; //The state of the bar
	if ($votes > $votemax && $votes > -1) {
		$bar[0] = $votemax;
	} else {
		if ($votes > -1) {
			$bar[0] = $votes;
		} else {
			$bar[0] = 0;
		}
	}
	if ($votes > $votebreak) {
		$bar[1] = 1;
	}
	return $bar;
}

//Returns the last vote time
function GetLastVoteTime($post_ID, $i_ID = '', $u_ID = '') {
	global $wpdb, $institution;
	
	//prevents SQL injection
	$p_ID = $wpdb->escape($post_ID);

	//Create entry if not existant
	//SetPost($p_ID);

	if (empty($i_ID)) {
		return $wpdb->get_var("SELECT MAX(lastvotetime) FROM  ".$wpdb->prefix."votes WHERE post='".$p_ID."'");
	} else {
		if (empty($u_ID)) {
			return $wpdb->get_var("SELECT lastvotetime FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$i_ID}'");
		} else {
			$post_votes = $wpdb->get_row("SELECT votes, usersinks AS sinks, votetimes, sinktimes FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$i_ID}'");
			$votes = array();
			$sinks = array();
			$votetimes = array();
			$sinktimes = array();
			if ($post_votes->votes != '') {
				$votes = explode(",", $post_votes->votes);
				$votetimes = explode(",", $post_votes->votetimes);
			}
			if ($post_votes->sinks != '') {
				$sinks = explode(",", $post_votes->sinks);
				$sinktimes = explode(",", $post_votes->sinktimes);
			}
			$votecomb = array_combine(array_merge($votes, $sinks), array_merge($votetimes, $sinktimes));
			if (count($votecomb) > 0) return $votecomb[$u_ID];
			return $wpdb->get_var("SELECT lastvotetime FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$i_ID}'");
		}
	}
}

function UserVoteTime($post_ID, $user_ID) {
	global $wpdb, $institution;

	$p_ID = $wpdb->escape($post_ID);
	$u_ID = $wpdb->escape($user_ID);

	$user_inst = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user='{$u_ID}'");
	$inst_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}votes_institutions WHERE name='{$user_inst}'");
	$thevote = $wpdb->get_row("SELECT votes, usersinks, votetimes, sinktimes FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$inst_id}'");
	
	$votes = ($thevote->votes == '') ? array() : explode(",", $thevote->votes);
	$sinks = ($thevote->usersinks == '') ? array() : explode(",", $thevote->usersinks);
	$votetimes = ($thevote->votetimes == '') ? array() : explode(",", $thevote->votetimes);
	$sinktimes = ($thevote->sinktimes == '') ? array() : explode(",", $thevote->sinktimes);
	$allvotes = array_combine(array_merge($votes, $sinks), array_merge($votetimes, $sinktimes));
	return $allvotes[$u_ID];
}
//Checks if the user voted
function UserVoted($post_ID, $user_ID) {
	global $wpdb, $institution;
	
	//prevents SQL injection
	$p_ID = $wpdb->escape($post_ID);
	$u_ID = $wpdb->escape($user_ID);

	//Create entry if not existant
	//SetPost($p_ID);

	//Gets the votes
	$user_inst = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user='{$u_ID}'");
	$inst_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}votes_institutions WHERE name='{$user_inst}'");
	$votes_raw = $wpdb->get_row("SELECT votes, usersinks FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$inst_id}'", ARRAY_N);
	$votes = explode(",", $votes_raw[0]);
	$sinks = explode(",", $votes_raw[1]);
	/*$votes_raw = $wpdb->get_var("SELECT votes FROM ".$wpdb->prefix."votes WHERE post='".$p_ID."'");
	$sinks_raw = $wpdb->get_var("SELECT usersinks FROM ".$wpdb->prefix."votes WHERE post='".$p_ID."'");

	//Put it in array form
	$votes = explode(",", $votes_raw);
	$sinks = explode(",", $sinks_raw);*/
	
	$voted = FALSE;
	$votekey = array_search($u_ID, $votes);
	$sinkkey = array_search($u_ID, $sinks);
	if ($votekey !== FALSE) {
		$voted = 1;
	}
    if ($sinkkey !== FALSE) {
		$voted = 2;
	}
	return $voted;
}

function UserCommitted($post_ID, $user_ID) {
	global $wpdb, $institution;
	
	//prevents SQL injection
	$p_ID = $wpdb->escape($post_ID);
	$u_ID = $wpdb->escape($user_ID);

	//Create entry if not existant
	//SetPost($p_ID);

	//Gets the votes
	$user_inst = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user='{$u_ID}'");
	$inst_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}votes_institutions WHERE name='{$user_inst}'");
	$presents_raw = $wpdb->get_var("SELECT presents FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$inst_id}'");
	$presents = explode(",", $presents_raw);
	
	$committed = FALSE;
	$presentkey = array_search($u_ID, $presents);
	if ($presentkey !== FALSE) {
		$committed = TRUE;
	}
	return $committed;
}

//Checks if the visitor voted
function GuestVoted($post_ID, $user_IPhash) {
	global $wpdb;
	
	//prevents SQL injection
	$p_ID = $wpdb->escape($post_ID);
	$u_ID = $wpdb->escape($user_IPhash);

	//Create entry if not existant
	//SetPost($p_ID);

	//Gets the votes
	$votes_raw = $wpdb->get_var("SELECT guests FROM ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
	$sinks_raw = $wpdb->get_var("SELECT guestsinks FROM ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");

	//Put it in array form
	$votes = explode(",", $votes_raw);
	$sinks = explode(",", $sinks_raw);
	
	$voted = FALSE;
	$votekey = array_search($u_ID, $votes);
	$sinkkey = array_search($u_ID, $sinks);
	if ($votekey != '' | $sinkkey != '') {
		$voted = TRUE;
	}
	return $voted;
}

//Checks the key to see if it is valid
function CheckKey($key, $id) {
	global $wpdb;
	$userdata = $wpdb->get_results("SELECT display_name, user_email, user_url, user_registered FROM $wpdb->users WHERE ID = '".$id."'", ARRAY_N);
	$chhash = md5($userdata[0][0].$userdata[0][3]);
	if ($chhash == $key) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function Suggest($post_ID, $user_ID, $nice_name) {
	global $wpdb;

	$p_ID = $wpdb->escape($post_ID);
	$u_ID = $wpdb->escape($user_ID);
	$nn = $wpdb->escape($nice_name);

	$postdat = get_post($p_ID);
	$sugg_email = $wpdb->get_var("SELECT user_email FROM  ".$wpdb->prefix."users WHERE user_nicename='".$nn."'");
	$u_name = $wpdb->get_var("SELECT display_name FROM ".$wpdb->prefix."users WHERE ID='".$u_ID."'");
	$u_email = $wpdb->get_var("SELECT user_email FROM ".$wpdb->prefix."users WHERE ID='".$u_ID."'");
	$email_body .= "\n<head><style type=\"text/css\">.par p {margin-top: 0.4em; margin-bottom:0.4em;}</style>\n<div class=\"par\"></head>\n";
	$email_body .= "<p>{$u_name} has suggested that you read the following paper:</p><br>\n";
	$email_body .= "<p><a href=\"{$postdat->guid}\"><strong>{$postdat->post_title}</strong></a></p>\n";
	$email_body .= get_post_meta_data(array($postdat))[0] . "\n";
	$email_body .= "<p>".$postdat->post_content."</p>\n";
	$email_body .= "<br><p>If you find the paper interesting, please vote for it so it can be discussed!</p></div>\n";
	$post_custom = get_post_custom($p_ID);
	$mail_content = "--voxcharta\nContent-Type: text/html; charset=\"UTF-8\"\nContent-Transfer-Encoding: quoted-printable\n\n";
	$mail_content .= wordwrap(imap_8bit($email_body), 120, "\n", true);
	$mail_content .= "\n\n--voxcharta--\n";
	$arxivid = $post_custom['wpo_arxivid'][0];
	add_filter('wp_mail_from', create_function('', "return \"{$u_email}\"; "));
	add_filter('wp_mail_from_name', create_function('', "return \"Vox Charta on behalf of {$u_name}\"; "));
	add_filter('wp_mail_content_type', create_function('', 'return "multipart/alternative; boundary=voxcharta"; '));
	wp_mail($sugg_email, $u_name . ' has suggested that you read '.$arxivid, $mail_content);
	remove_all_filters('wp_mail_from');
	remove_all_filters('wp_mail_from_name');
	remove_all_filters('wp_mail_content_type');
	//wp_mail('jfg@ucolick.org', $u_name . ' has suggested a paper for '.$nn.' to read!', $email_body, $headers);
}

function Discuss($post_ID, $today) {
	global $wpdb, $institution;

	//Prevents SQL injection
	$p_ID = $wpdb->escape($post_ID);
	//$u_ID = $wpdb->escape($user_ID);

	//Create entries if not existant
	SetPost($p_ID, $institution->ID);

	//$user_inst = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user='{$u_ID}'");
	//if ($institution->name != $user_inst) return;

	$discussed = $wpdb->get_var("SELECT discussed FROM {$wpdb->prefix}votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
	$discussed = ($discussed == 1) ? 0 : 1;
	$votes_raw = $wpdb->get_row("SELECT votes, usersinks, lastvotetime FROM {$wpdb->prefix}votes
		WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
	$no_inst_votes = ($votes_raw->votes == '' && $votes_raw->usersinks == '') ? true : false;
	if ($discussed == 1) {
		if ($no_inst_votes) {
			$lastvotetime = $today;
		} else {
			$lastvotetime = $votes_raw->lastvotetime;
		}
	} else {
		if ($no_inst_votes) {
			$wpdb->query("DELETE FROM {$wpdb->prefix}votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
			return;
		} else {
			$times = $wpdb->get_row("SELECT votetimes, sinktimes FROM {$wpdb->prefix}votes
				WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
			$lastvotetime = max(array_merge(explode(",", $times->votetimes), explode(",", $times->sinktimes)));
		}
	}
	if (!$no_inst_votes) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes SET discussed = '{$discussed}' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
	} else {
		$wpdb->query("UPDATE {$wpdb->prefix}votes SET discussed = '{$discussed}', lastvotetime = '{$lastvotetime}' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
	}
}

//Saves the vote of a user to the database
function Vote($post_ID, $user_ID, $type, $date = null) {
	global $wpdb, $institution;

	//Prevents SQL injection
	$p_ID = $wpdb->escape($post_ID);
	$u_ID = $wpdb->escape($user_ID);

	//Check if user ID is valid
	$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = '%d'", $u_ID));
	if ($count == 0) return;

	SetUser($u_ID);

	$user_inst = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user='{$u_ID}'");
	$institution = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name='{$user_inst}'");

	SetPost($p_ID, $institution->ID);

	//Gets the votes
	$votes_raw = $wpdb->get_var("SELECT votes FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
	$sinks_raw = $wpdb->get_var("SELECT usersinks FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
	$uservotes_raw = $wpdb->get_var("SELECT votes FROM ".$wpdb->prefix."votes_users WHERE user='".$u_ID."'");
	$usersinks_raw = $wpdb->get_var("SELECT sinks FROM ".$wpdb->prefix."votes_users WHERE user='".$u_ID."'");
	
	//Gets the votes in array form
	$votes = explode(",", $votes_raw);
	$sinks = explode(",", $sinks_raw);
	$uservotes = explode(",", $uservotes_raw);
	$usersinks = explode(",", $usersinks_raw);

	//Check if user voted
	if (!UserVoted($post_ID,$user_ID)) {
	//user hasn't vote, so the script allows the user to vote
		$votetime = (isset($date)) ? $date : time();
		if ($type == 'vote') {
			//Add vote to array
			$user_var[0] = $u_ID;
			$post_var[0] = $p_ID;
			$votes_result = ($votes[0] == false) ? $user_var : array_merge($votes,$user_var);
			$votes_result_raw = implode(",",$votes_result);
			$uservotes_result = ($uservotes[0] == false) ? $post_var : array_merge($uservotes,$post_var);
			$uservotes_result_raw = implode(",",$uservotes_result);
			$votes_result_sql = $wpdb->escape($votes_result_raw);
			$uservotes_result_sql = $wpdb->escape($uservotes_result_raw);
			$wpdb->query("UPDATE ".$wpdb->prefix."votes SET votes='".$votes_result_sql."' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
			$wpdb->query("UPDATE ".$wpdb->prefix."votes_users SET votes='".$uservotes_result_sql."' WHERE user='".$u_ID."'");
			$votetimes_raw = $wpdb->get_var("SELECT votetimes FROM ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
			$votetimes = ($votetimes_raw == '') ? strval($votetime): $votetimes_raw . ',' . strval($votetime);
			$wpdb->query("UPDATE ".$wpdb->prefix."votes SET votetimes = '{$votetimes}' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		} elseif ($type == 'sink') {
			//Add sink to array
			$user_var[0] = $u_ID;
			$post_var[0] = $p_ID;
			$sinks_result = ($sinks[0] == false) ? $user_var : array_merge($sinks,$user_var);
			$sinks_result_raw = implode(",",$sinks_result);
			$usersinks_result = ($usersinks[0] == false) ? $post_var : array_merge($usersinks,$post_var);
			$usersinks_result_raw = implode(",",$usersinks_result);
			$sinks_result_sql = $wpdb->escape($sinks_result_raw);
			$usersinks_result_sql = $wpdb->escape($usersinks_result_raw);
			$wpdb->query("UPDATE ".$wpdb->prefix."votes SET usersinks='".$sinks_result_sql."' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
			$wpdb->query("UPDATE ".$wpdb->prefix."votes_users SET sinks='".$usersinks_result_sql."' WHERE user='".$u_ID."'");
			$sinktimes_raw = $wpdb->get_var("SELECT sinktimes FROM ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
			$sinktimes = ($sinktimes_raw == '') ? strval($votetime) : $sinktimes_raw . ',' . strval($votetime);
			$wpdb->query("UPDATE ".$wpdb->prefix."votes SET sinktimes = '{$sinktimes}' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		}

		$lastvotetime_raw = $wpdb->get_row("SELECT votetimes, sinktimes, guestvotetimes, guestsinktimes FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		$votetimemax = ($lastvotetime_raw->votetimes == '') ? 0 : max(explode(",",$lastvotetime_raw->votetimes));
		$sinktimemax = ($lastvotetime_raw->sinktimes == '') ? 0 : max(explode(",",$lastvotetime_raw->sinktimes));
		$guestvotetimemax = ($lastvotetime_raw->guestvotetimes == '') ? 0 : max(explode(",",$lastvotetime_raw->guestvotetimes));
		$guestsinktimemax = ($lastvotetime_raw->guestsinktimes == '') ? 0 : max(explode(",",$lastvotetime_raw->guestsinktimes));
		$lastvotetime = max($votetimemax, $sinktimemax, $guestvotetimemax, $guestsinktimemax);
		$wpdb->query("UPDATE ".$wpdb->prefix."votes SET lastvotetime = {$lastvotetime} WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		$wpdb->query("UPDATE ".$wpdb->prefix."votes SET discussed = '1' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		
		$result = 'true';
	} elseif ($type == 'bump' || $type == 'date') {
		Unvote($p_ID, $user_ID);
		Vote($p_ID, $user_ID, 'vote', $date);
		//$last_vote_time = GetLastVoteTime($p_ID, $institution->ID);
		//if (time() > $last_vote_time) $last_vote_time = time();
		//$wpdb->query("UPDATE ".$wpdb->prefix."votes SET lastvotetime='".$last_vote_time."' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		$wpdb->query("UPDATE ".$wpdb->prefix."votes SET discussed = '1' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		//The user voted, thus the script will not update the votes in the article
		$result = 'false';
	} else { //If a paper was bumped by someone else, NOT IMPLEMENTED YET.
		//$votetimes_raw = $wpdb->get_var("SELECT votetimes FROM ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		//$sinktimes_raw = $wpdb->get_var("SELECT sinktimes FROM ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		//$votetimes = explode(",", $votetimes_raw);
		//$sinktimes = explode(",", $sinktimes_raw);

		$result = 'false';
	}

	return $result; //returns '' on failure, returns 'true' if votes were casted, returns 'false' if user already casted a vote
}

function Unvote($post_ID, $user_ID) {
	global $wpdb, $institution;

	//Prevents SQL injection
	$p_ID = $wpdb->escape($post_ID);
	$u_ID = $wpdb->escape($user_ID);

	//Check if user ID is valid
	$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = '%d'", $u_ID));
	if ($count == 0) return;

	SetUser($u_ID);

	//Create entries if not existant
	$user_inst = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user='{$u_ID}'");
	$institution = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name='{$user_inst}'");

	SetPost($p_ID, $institution->ID);

	//Gets the votes
	$votes_raw = $wpdb->get_row("SELECT votes, votetimes, usersinks AS sinks, presents, sinktimes FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
	$uservotes_raw = $wpdb->get_row("SELECT votes, sinks, presents FROM ".$wpdb->prefix."votes_users WHERE user='".$u_ID."'");

	$votetimes = explode(",", $votes_raw->votetimes);
	$sinktimes = explode(",", $votes_raw->sinktimes);
	//Gets the votes in array form
	$votes = explode(",", $votes_raw->votes);
	$uservotes = explode(",", $uservotes_raw->votes);
	$newvotes = $votes_raw->votes;
	if (in_array($u_ID, $votes) && in_array($p_ID, $uservotes)) {
		$arr_pos = array_search($u_ID, $votes);
		$arr_pos2 = array_search($p_ID, $uservotes);
		array_splice($votes, $arr_pos, 1);
		array_splice($votetimes, $arr_pos, 1);
		array_splice($uservotes, $arr_pos2, 1);
		$last_vote_time = (count(array_merge($votetimes,$sinktimes)) > 0) ? max(array_merge($votetimes,$sinktimes)) : 0;
		$newvotes = (count($votes) > 0) ? implode(",", $votes) : '';
		$newvotetimes = (count($votetimes) > 0) ? implode(",", $votetimes) : '';
		$newuservotes = (count($uservotes) > 0) ? implode(",", $uservotes) : '';
		$wpdb->query("UPDATE {$wpdb->prefix}votes SET votes='{$newvotes}', votetimes='{$newvotetimes}', lastvotetime='{$last_vote_time}' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		$wpdb->query("UPDATE {$wpdb->prefix}votes_users SET votes='{$newuservotes}' WHERE user='{$u_ID}'");
	}
	$sinks = explode(",", $votes_raw->sinks);
	$usersinks = explode(",", $uservotes_raw->sinks);
	$newsinks = $votes_raw->sinks;
	if (in_array($u_ID, $sinks) && in_array($p_ID, $usersinks)) {
		$arr_pos = array_search($u_ID, $sinks);
		$arr_pos2 = array_search($p_ID, $usersinks);
		array_splice($sinks, $arr_pos, 1);
		array_splice($sinktimes, $arr_pos, 1);
		array_splice($usersinks, $arr_pos2, 1);
		$last_vote_time = (count(array_merge($votetimes,$sinktimes)) > 0) ? max(array_merge($votetimes,$sinktimes)) : 0;
		$newsinks = (count($sinks) > 0) ? implode(",", $sinks) : '';
		$newsinktimes = (count($sinktimes) > 0) ? implode(",", $sinktimes) : '';
		$newusersinks = (count($usersinks) > 0) ? implode(",", $usersinks) : '';
		$wpdb->query("UPDATE {$wpdb->prefix}votes SET usersinks='{$newsinks}', sinktimes='{$newsinktimes}', lastvotetime='{$last_vote_time}' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		$wpdb->query("UPDATE {$wpdb->prefix}votes_users SET sinks='{$newusersinks}' WHERE user='{$u_ID}'");
	}
	$presents = explode(",", $votes_raw->presents);
	$userpresents = explode(",", $uservotes_raw->presents);
	$newpresents = $votes_raw->presents;
	if (in_array($u_ID, $presents) && in_array($p_ID, $userpresents)) {
		$arr_pos = array_search($u_ID, $presents);
		$arr_pos2 = array_search($p_ID, $userpresents);
		array_splice($presents, $arr_pos, 1);
		array_splice($userpresents, $arr_pos2, 1);
		$newpresents = (count($presents) > 0) ? implode(",", $presents) : '';
		$newuserpresents = (count($userpresents) > 0) ? implode(",", $userpresents) : '';
		$wpdb->query("UPDATE {$wpdb->prefix}votes SET presents='{$newpresents}' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		$wpdb->query("UPDATE {$wpdb->prefix}votes_users SET presents='{$newuserpresents}' WHERE user='{$u_ID}'");
	}
	if ($newvotes === '' && $newsinks === '') {
		$wpdb->query("DELETE FROM {$wpdb->prefix}votes WHERE post='{$p_ID}' AND institution='{$institution->ID}' LIMIT 1");
	}
}

function Present($post_ID, $user_ID) {
	global $wpdb, $institution;

	//Prevents SQL injection
	$p_ID = $wpdb->escape($post_ID);
	$u_ID = $wpdb->escape($user_ID);

	//Check if user ID is valid
	$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = '%d'", $u_ID));
	if ($count == 0) return;

	SetUser($u_ID);

	$user_inst = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user='{$u_ID}'");
	$institution = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name='{$user_inst}'");

	SetPost($p_ID, $institution->ID);

	//Gets the presents
	$presents_raw = $wpdb->get_var("SELECT presents FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
	$userpresents_raw = $wpdb->get_var("SELECT presents FROM ".$wpdb->prefix."votes_users WHERE user='".$u_ID."'");
	
	//Gets the votes in array form
	$presents = explode(",", $presents_raw);
	$userpresents = explode(",", $userpresents_raw);

	//Check if user voted
	if (UserVoted($post_ID,$user_ID)) {
		$user_var[0] = $u_ID;
		$post_var[0] = $p_ID;
		$presents_result = ($presents[0] == false) ? $user_var : array_merge($presents,$user_var);
		$presents_result_raw = implode(",",$presents_result);
		$userpresents_result = ($userpresents[0] == false) ? $post_var : array_merge($userpresents,$post_var);
		$userpresents_result_raw = implode(",",$userpresents_result);
		$presents_result_sql = $wpdb->escape($presents_result_raw);
		$userpresents_result_sql = $wpdb->escape($userpresents_result_raw);
		$wpdb->query("UPDATE ".$wpdb->prefix."votes SET presents='".$presents_result_sql."' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		$wpdb->query("UPDATE ".$wpdb->prefix."votes_users SET presents='".$userpresents_result_sql."' WHERE user='".$u_ID."'");

		$result = 'true';
	} else {
		$result = 'false';
	}

	return $result; //returns '' on failure, returns 'true' if votes were casted, returns 'false' if user already casted a vote
}

//Saves the vote of a guest to the database
function GuestVote($post_ID, $type) {
	global $wpdb, $institution;
	
	//Guest's vote is stored permanently. May implement votes that will expire.
	//Use user IP
	$iphash = md5($_SERVER['REMOTE_ADDR']);

	//Set a cookie if there isn't any. This is to reduce the problem of users using proxies and voting multiple times on the same stories.
/*
	if(isset($_COOKIE['tevinevotes'])) {
		$iphash = $_COOKIE['tevinevotes']; 
	} else {
		$cookielife = 12 * 60 * 24 * 60 + time(); //Set to expire in a year from now
		setcookie('tevinevotes', $iphash, $cookielife);
	}
*/	

	//Prevents SQL injection
	$u_ID = $wpdb->escape($iphash);
	$p_ID = $wpdb->escape($post_ID);

	//Create entries if not existant
	SetPost($p_ID, $institution->ID);

	//Gets the info
	$votes_raw = $wpdb->get_var("SELECT guests FROM ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
	$sinks_raw = $wpdb->get_var("SELECT guestsinks FROM ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
	
	//Gets the votes in array form
	$votes = explode(",", $votes_raw);
	$sinks = explode(",", $sinks_raw);

	//Check if user voted
	if (!GuestVoted($p_ID,$u_ID)) {
	//user hasn't vote, so the script allows the user to vote

		if ($type != 'sink') {
			//Add vote to array
			$user_var[0] = $u_ID;
			$post_var[0] = $p_ID;
			$votes_result = array_merge($votes,$user_var);
			$votes_result_raw = implode(",",$votes_result);
			$sinks_result_raw = $sinks_raw;
		} else {
			//Add sink to array
			$user_var[0] = $u_ID;
			$post_var[0] = $p_ID;
			$sinks_result = array_merge($sinks,$user_var);
			$sinks_result_raw = implode(",",$sinks_result);
			$votes_result_raw = $votes_raw;
		}
		$last_vote_time = time();
		
		//Prevents SQL injection
		$votes_result_sql = $wpdb->escape($votes_result_raw);
		$sinks_result_sql = $wpdb->escape($sinks_result_raw);
		$last_vote_time_result_sql = $wpdb->escape($last_vote_time);
		
		//Update votes
		$wpdb->query("UPDATE ".$wpdb->prefix."votes SET guests='".$votes_result_sql."' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		$wpdb->query("UPDATE ".$wpdb->prefix."votes SET guestsinks='".$sinks_result_sql."' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		$wpdb->query("UPDATE ".$wpdb->prefix."votes SET lastvotetime='".$last_vote_time_result_sql."' WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		
		$result = 'true';
	} else {
		//The user voted, thus the script will not update the votes in the article
		$result = 'false';
	}

	return $result; //returns '' on failure, returns 'true' if votes were casted, returns 'false' if user already casted a vote
}

//Gets an array of posts with vote count
//Identical to SortVotes() except that it is sorted by ID in descending order, and has more information on votes
function GetVoteArray() {
	global $wpdb;

	$limit = get_option('voteiu_limit');

	//Get posts logged, and get the latest posts voted [new posts have larger IDs
	$posts = $wpdb->get_results("SELECT post FROM ".$wpdb->prefix."votes WHERE ID != '' AND lastvotetime > 0 ORDER BY ID DESC");

	$postarray = array();
	$votesarray = array();
	$uservotes_a = array();
	$usersinks_a = array();
	$guestvotes_a = array();
	$guestsinks_a = array();

	/*
	Limit, so as to reduce time taken for this script to run. If you want to raise the limit to maximum, use
	$limit = count($posts);
	*/
	/*if ($limit == '') {
		$limit = 100;
	}
	$limit = 999;*/

	//Ignore limit if post count is below limit
	if ($limit > count($posts)) {
		$limit = count($posts);
	}


	//foreach ($posts as &$post) { 
	//Support PHP4 by not using foreach
	for ($counter = 0; $counter < $limit; $counter += 1) {
		$post = $posts[$counter];
		$p_ID = $post->post;

		//Gets the votes
		$votes_raw = $wpdb->get_var("SELECT votes FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		$sinks_raw = $wpdb->get_var("SELECT usersinks FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		$guestvotes_raw = $wpdb->get_var("SELECT guests FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");
		$guestsinks_raw = $wpdb->get_var("SELECT guestsinks FROM  ".$wpdb->prefix."votes WHERE post='{$p_ID}' AND institution='{$institution->ID}'");

		//Put it in array form
		$votes = ($votes_raw == '') ? 0 : count(explode(",", $votes_raw));
		$sinks = ($sinks_raw == '') ? 0 : count(explode(",", $sinks_raw));
		$guestvotes = ($guestvotes_raw == '') ? 0 : count(explode(",", $guestvotes_raw));
		$guestsinks = ($guestsinks_raw == '') ? 0 : count(explode(",", $guestsinks_raw));

		$initial = get_option('voteiu_initialoffset');

		//The mathematics
		$votecount = $votes - $sinks + $guestvotes - $guestsinks + $initial;
		array_push($postarray, array($p_ID));
		array_push($votesarray, array($votecount));
		array_push($uservotes_a, array($votes)); //Apparently there is one extra item in the array
		array_push($usersinks_a, array($sinks));
		array_push($guestvotes_a, array($guestvotes));
		array_push($guestsinks_a, array($guestsinks));
	}
	$output = array($postarray, $votesarray, $uservotes_a, $usersinks_a, $guestvotes_a, $guestsinks_a);
	return $output;

}

function NullifyVotes($last_vote_time, $offset, $agenda_time) {
	//$today variable must be set on the calling page. This is done for speed.
	$deadline = $agenda_time - $offset*86400;
	$prev_deadline = $deadline - 86400;
	return ((time() > $deadline && $last_vote_time < $deadline) ||
		(time() < $deadline && $last_vote_time < $prev_deadline));
}

//function clubCheck($agenda_time = 0, $next_offset = null)
//{
//	global $today;
//	if ($agenda_time == 0) $agenda_time = $today;
//	if (!isset($next_offset)) $next_offset = AgendaOffset('next', $agenda_time, false, true);
//
//	$club_time = $agenda_time + $next_offset*86400;
//	$club_day = date('D', $club_time);
//	$club_date = date('m/d/Y', $club_time);
//	$club = 'no';
//	if (get_option('voteiu_yescc_except') != '') {
//		$yescc = explode(',',get_option('voteiu_yescc_except'));
//		foreach($yescc as $except) {
//			if (date('m/d/Y', strtotime($except)) == $club_date) return 'cc';
//		}
//	}
//	if (get_option('voteiu_yesjc_except') != '') {
//		$yesjc = explode(',',get_option('voteiu_yesjc_except'));
//		foreach($yesjc as $except) {
//			if (date('m/d/Y', strtotime($except)) == $club_date) return 'jc';
//		}
//	}
//	if ($club_day == get_option('voteiu_cc_day') && isQuarter($club_time)) $club = 'cc';
//	if ($club_day == get_option('voteiu_jc_day')) $club = 'jc';
//	if (checkHoliday($club_time)) return 'no'.$club;
//	if ($club != 'no' && get_option('voteiu_noclub_except') != '') {
//		$noclub = explode(',',get_option('voteiu_noclub_except'));
//		foreach($noclub as $except) {
//			if (date('m/d/Y', strtotime($except)) == $club_date) return 'no'.$club;
//		}
//	}
//
//	return $club;
//}

function AgendaOffset($type = 'prev', $stop_for = 'an', $agenda_time = null) {
	//Account for Journal Club and weekends.
	global $today, $agenda_info, $institution, $wpdb, $yesdays, $nodays, $ishome,
		$yeseventdays, $noeventdays, $yeseventdaysflat, $events, $event_canceled;

	$offset = 0;
	$event_canceled = false;
	if (!isset($agenda_time)) $agenda_time = $today;
	$reset_time = (!isset($institution)) ? get_option('voteiu_reset_time') : GetResetTime($agenda_time);
	if ($agenda_time > $reset_time && $type == 'next') $offset = 1;
	if ($agenda_time <= $reset_time && $type == 'prev') $offset = 1;
	//echo 'ao: ', date('H:i:s m/d/Y', $reset_time) . ' ' . date('H:i:s m/d/Y', $agenda_time) . '<br>';
	$dayssign = ($type == 'next') ? -1 : 1;

	$events = array();
	$yeseventdaysflat = array();
	$noeventdays = array();
	if (isset($institution)) {
		$events = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}votes_events WHERE affiliation = '{$institution->ID}'");
		if (count($events) != 0) {
			$yeseventdays = array_fill(0,count($events),array());
			$noeventdays = array_fill(0,count($events),array());
			foreach ($events as $e => $event) {
				if ($event->extradays != '') {
					$yesevent = explode(',',$event->extradays);
					$yeseventdays[$e] = array();
					foreach($yesevent as $range) {
						$stridearr = explode(':',$range);
						$stride = (count($stridearr) == 2) ? $stridearr[1] : 1;
						$rangearr = explode('-',$stridearr[0]);
						if (count($rangearr) == 2) {
							$yeseventdays[$e] = array_merge($yeseventdays[$e],
								range($dayssign*round(($agenda_time - GetResetTime(strtotime($rangearr[0])))/86400),
									  $dayssign*round(($agenda_time - GetResetTime(strtotime($rangearr[1])))/86400), $stride));
						} else {
							array_push($yeseventdays[$e], $dayssign*round(($agenda_time - GetResetTime(strtotime($rangearr[0])))/86400));
						}
					}
				}
				if ($event->canceleddays != '') {
					$noevent = explode(',',$event->canceleddays);
					$noeventdays[$e] = array();
					foreach ($noevent as $range) {
						$stridearr = explode(':',$range);
						$stride = (count($stridearr) == 2) ? $stridearr[1] : 1;
						$rangearr = explode('-',$stridearr[0]);
						if (count($rangearr) == 2) {
							$noeventdays[$e] = array_merge($noeventdays[$e],
								range($dayssign*round(($agenda_time - GetResetTime(strtotime($rangearr[0])))/86400),
									  $dayssign*round(($agenda_time - GetResetTime(strtotime($rangearr[1])))/86400), $stride));
						} else array_push($noeventdays[$e], $dayssign*round(($agenda_time - GetResetTime(strtotime($rangearr[0])))/86400));
					}
					sort($noeventdays[$e]);
				}
			}

			if (count($yeseventdays) != 0) {
				foreach ($yeseventdays as $yesevent) {
					$yeseventdaysflat = array_merge($yeseventdaysflat, $yesevent);
				}
			}

			//When an event overlaps a canceled second event
			if ($stop_for === 'co') {
				foreach ($events as $e => $event) {
					$noeventdays[$e] = leo_array_diff($noeventdays[$e], $yeseventdaysflat);
				}
			}
		}
	}

	//Handle exceptions to rules.
	$nodays = array();
	if (isset($institution) && $institution->canceleddays != '') {
		$nocoffee = explode(',',$institution->canceleddays);
		$nodays = array();
		foreach ($nocoffee as $range) {
			$stridearr = explode(':',$range);
			$stride = (count($stridearr) == 2) ? $stridearr[1] : 1;
			$rangearr = explode('-',$stridearr[0]);
			if (count($rangearr) == 2) {
				$nodays = array_merge($nodays,
					range($dayssign*round(($agenda_time - GetResetTime(strtotime($rangearr[0])))/86400),
						  $dayssign*round(($agenda_time - GetResetTime(strtotime($rangearr[1])))/86400), $stride));
			} else {
				array_push($nodays, $dayssign*round(($agenda_time - GetResetTime(strtotime($rangearr[0])))/86400));
			}
		}
		sort($nodays);
	}
	//print_r($nodays);
	$yesdays = array();
	if (isset($institution) && $institution->extradays != '') {
		$yescoffee = explode(',',$institution->extradays);
		$yesdays = array();
		foreach ($yescoffee as $range) {
			$stridearr = explode(':',$range);
			$stride = (count($stridearr) == 2) ? $stridearr[1] : 1;
			$rangearr = explode('-',$stridearr[0]);
			if (count($rangearr) == 2) {
				$yesdays = array_merge($yesdays,
					range($dayssign*round(($agenda_time - GetResetTime(strtotime($rangearr[0])))/86400),
						  $dayssign*round(($agenda_time - GetResetTime(strtotime($rangearr[1])))/86400), $stride));
			} else array_push($yesdays, $dayssign*round(($agenda_time - GetResetTime(strtotime($rangearr[0])))/86400));
		}
		sort($yesdays);
	}

	$normaldays_arr = explode(",", $institution->normaldays);

	while (1) {
		$old_offset = $offset;
		$today_offset = ($type == 'prev') ? $agenda_time-$offset*86400 : $agenda_time+$offset*86400;
		$offset_date = date('D', $today_offset);
		foreach($events as $e => $event) {
			if (in_array($stop_for, array('an', $event->ID)) && in_array($offset, $yeseventdays[$e])) {
				$agenda_info = $event->ID;
				goto retoff;
			}
		}
		foreach($events as $e => $event) {
			$eventdays_arr = explode(",", $event->dayofweek);
			foreach ($eventdays_arr as $ed => $dow) {
				if ($dow != '' && $offset_date == $dow) { 
					if (in_array($offset, $noeventdays[$e])) {
						if ($stop_for === 'an') {
							$agenda_info = $event->ID;
							$event_canceled = true;
							goto retoff;
						} elseif ($stop_for === 'co') {
							if(in_array($offset, $nodays)) {
								$offset++; break;
							} else {
								$agenda_info = 'co';
								goto retoff;
							}
						} else {
							$offset++; break;
						}
					}
					if ($stop_for === 'co') {
						$offset++; break;
					}
					if (in_array($stop_for, array('an', $event->ID))) {
						$agenda_info = $event->ID;
						goto retoff;
					}
				}
			}
		}
		if ($old_offset != $offset) continue;
		if ($stop_for === 'co' || $stop_for === 'an') {
			if (in_array($offset, $nodays) || in_array($offset, $yeseventdaysflat)) {
				$offset++; //continue;
				if ($offset > 3650) {
					echo 'Error: Offset too large';
					return;
				} else {
					continue;
				}
			}
		}
		if ($offset > 30) goto retoff;
		if (!in_array($offset_date, $normaldays_arr) && !in_array($offset, $yesdays)) {
			$offset++; continue;
		}

		//if (checkHoliday($today_offset)) {
		//	$offset++; continue;
		//}

		if (!in_array($stop_for, array('an', 'co'))) {
			$offset++; continue;
		}
		$agenda_info = 'co';
		goto retoff;
	} 

	retoff:

	return $offset;
}

function isQuarter($today)
{
	$qbegin = array("09/28/2009", "01/08/2010", "03/29/2010");
	$qend   = array("12/04/2009", "03/12/2010", "06/04/2010");

	for ($i = 0; $i < 3; $i++) {
		if ($today >= strtotime($qbegin[$i]) && $today <= strtotime($qend[$i])) return true; 
	}

	return false;
}

function checkHoliday($time) 
{ 
	$month = date("m", $time);
	$year = date("Y", $time);
	$tgiving = strtotime("3 weeks thursday", mktime(0,0,0,11,1,$year));
	if ($time > $tgiving && $time < $tgiving + 2*86400) return true;
	if (date("m d", $time) == "07 04") return true;
	return false;
} 

//Used in options page
function DisplayPostList() {
	$a = GetVoteArray();
	$i = 0;

//Begin table
?>
<table class="widefat post fixed" id="formtable" style="clear: both;" cellspacing="0">
	<thead>
	<tr>

	<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" name="multiselect[]" onclick="javascript:CheckUncheck()" /></th>
	<th scope="col" id="title" class="manage-column column-title" style="">Post</th>
<?php /*?>	<th scope="col" id="author" class="manage-column column-author" style="">Author</th><?php */ ?>
	<th scope="col" id="votes" class="manage-column column-categories" style="width: 40%">Votes</th>


	</tr>
	</thead>

	<tfoot>
	<tr>
	<th scope="col"  class="manage-column column-cb check-column" style=""><input type="checkbox" name="multiselect[]" onclick="javascript:CheckUncheck()" /></th>
	<th scope="col"  class="manage-column column-title" style="">Post</th>
<?php /* ?>	<th scope="col"  class="manage-column column-author" style="">Author</th><?php */ ?>

	<th scope="col"  class="manage-column column-categories" style="">Votes</th>

	</tr>
	</tfoot>
	<tbody>
<?php


	while ($i < count($a[0])) {
	$postdat = get_post($a[0][$i][0]);
	if (!empty($postdat)) {
?>
	<tr id='post-<?php echo $a[0][$i][0]; ?>' class='alternate author-other status-publish iedit' valign="top">
		<th scope="row" class="check-column"><input type="checkbox" name="post[]" value="<?php echo $a[0][$i][0]; ?>" /></th>
<td class="post-title column-title"><strong><a class="row-title" href="<?php echo $postdat->guid; ?>" title="<?php echo $postdat->post_title; ?>"><?php echo $postdat->post_title; ?></a></strong></td>
<?php /* ?><td class="author column-author"><?php echo $postdat->post_author; ?></td><?php */ ?>
<td class="categories column-categories"><?php echo $a[1][$i][0]; ?> (Users: <span style="color:#00CC00">+<?php echo $a[2][$i][0]; ?></span>/<span style="color:#CC0000">-<?php echo $a[3][$i][0]; ?></span>, Guests: <span style="color:#00CC00">+<?php echo $a[4][$i][0]; ?></span>/<span style="color:#CC0000">-<?php echo $a[5][$i][0]; ?></span><?php
if(get_option('voteiu_initialoffset') != '0') {
echo ', Offset: ';
if (get_option('voteiu_initialoffset') > 0) {
echo '<span style="color:#00CC00">+'.get_option('voteiu_initialoffset').'</span>';
} else {
echo '<span style="color:#CC0000">'.get_option('voteiu_initialoffset').'</span>';
} } ?>)</td>
<?php

/*
	echo $postdat->post_title;
	echo ' - ';
	echo $a[1][$i][0];
	echo '<br />';
*/

?>
</tr>
<?php
	}
	$i++;
	}


//End table
?> 
	</tbody>
	</table>
<?php
}

//Handles the deleting of votes, used to read the POST when the page is submitted
function VoteBulkEdit() {
//error_reporting(E_ALL);
/*print_r($_POST);*/
$buttonnumber = 0; //Determines which apply button was clicked on. 0 if no button was clicked.
$action = 'none'; //Determines what should be done
if (array_key_exists('doaction1', $_POST)) {
$buttonnumber = 1;
}
if (array_key_exists('doaction2', $_POST)) {
$buttonnumber = 2;
}
if ($buttonnumber != 0 && array_key_exists('action'.$buttonnumber, $_POST)) {
	if ($_POST['action'.$buttonnumber] != -1) {
		//Assigns action to be done
		$action = $_POST['action'.$buttonnumber];
	}
}

if (!array_key_exists('post', $_POST)) {
$action = 'none'; //set action to none if there are no posts to modify
}

//Begin modifying votes
if ($action != 'none' && $action != '') {
ResetVote($_POST['post'], $action);
}

}

function InstitutionEdit($institution_ID) {
	global $wpdb;
	$i_ID = $wpdb->escape($institution_ID);
	//if (array_key_exists('events', $_POST)) {
	//	if ($_POST['events'] == '') {
	//		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET events = ''  WHERE ID = '{$i_ID}'");
	//	} else {
	//		$failed = false;
	//		$events_raw = explode(',',$_POST['events']);
	//		foreach ($events_raw as $i => $tag) {
	//			$events_raw[$i] = trim($tag);
	//			if (!is_numeric($events_raw[$i]) || $events_raw[$i] <= 0) {
	//				echo "<font color='red'><h3>Error: {$events_raw[$i]} is an invalid event ID, each event must be a positive number!</h3></font>";
	//				$failed = true;
	//				break;
	//			}
	//		}
	//		if (!$failed) {
	//			$events = implode(',',$events_raw);
	//			$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET events = '{$events}'  WHERE ID = '{$i_ID}'");
	//		}
	//	}
	//}
	if (array_key_exists('instname', $_POST)) {
		$failed = false;
		$names = $wpdb->get_col("SELECT name FROM {$wpdb->prefix}votes_institutions");
		$old_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}votes_institutions WHERE ID = '{$i_ID}'");
		if (in_array($_POST['instname'], $names) && $_POST['instname'] != $old_name) {
			echo "<font color='red'><h3>Error: {$_POST['instname']} is already claimed by another institution!</h3></font>";
			$failed = true;
		}
		if (!$failed) {
			$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET name = '{$_POST['instname']}'  WHERE ID = '{$i_ID}'");
			$wpdb->query("UPDATE {$wpdb->prefix}votes_users SET affiliation = '{$_POST['instname']}'  WHERE affiliation = '{$old_name}'");
		}
	}
	if (array_key_exists('subdomain', $_POST)) {
		$failed = false;
		$subdomains = $wpdb->get_col("SELECT subdomain FROM {$wpdb->prefix}votes_institutions");
		$old_subdomain = $wpdb->get_var("SELECT subdomain FROM {$wpdb->prefix}votes_institutions WHERE ID = '{$i_ID}'");
        if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|
			↪([A-Za-z0-9]+))$", $_POST['subdomain'])) {
			echo "<font color='red'><h3>Error: {$_POST['subdomain']} is not a valid subdomain!</h3></font>";
			$failed = true;
		}
		if (in_array(strtolower($_POST['subdomain']), array_map('strtolower',$subdomains)) &&
			strtolower($_POST['subdomain']) != $old_subdomain) {
			echo "<font color='red'><h3>Error: {$_POST['subdomain']} is already claimed by another institution!</h3></font>";
			$failed = true;
		}
		if (!$failed) {
			$subdomain = strtolower($_POST['subdomain']);
			$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET subdomain = '{$subdomain}'  WHERE ID = '{$i_ID}'");
		}
	}
	if (array_key_exists('url', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET url = '{$_POST['url']}'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('primaryevent', $_POST)) {
		$str = ucwords($_POST['primaryevent']);
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET primaryevent = '{$str}'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('timezone', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET timezone = '{$_POST['timezone']}'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('location', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET location = '{$_POST['location']}'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('closedelay', $_POST)) {
		$failed = false;
		if (!is_numeric($_POST['closedelay'])) {
			echo "<font color='red'><h3>Error: {$_POST['closedelay']} is an invalid delay (must be numeric)!</h3></font>";
			$failed = true;
		}
		if (!$failed) $wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET closedelay = '{$_POST['closedelay']}'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('agendalimit', $_POST)) {
		$failed = false;
		if (!is_numeric($_POST['agendalimit'])) {
			echo "<font color='red'><h3>Error: {$_POST['agendalimit']} is an invalid limit (must be numeric)!</h3></font>";
			$failed = true;
		}
		if ($_POST['agendalimit'] < 0) {
			echo "<font color='red'><h3>Error: {$_POST['agendalimit']} is an invalid limit (must be &gt;= 0)!</h3></font>";
			$failed = true;
		}
		if (!$failed) $wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET agendalimit = '{$_POST['agendalimit']}'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('closevoting', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET closevoting = '1'  WHERE ID = '{$i_ID}'");
	} elseif (!empty($_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET closevoting = '0'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('normaldays', $_POST)) {
		$failed = false;
		if ($_POST['normaldays'] != '') {
			$normaldays = explode(',',$_POST['normaldays']);
			$dayarray = array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
			foreach ($normaldays as $i => $normalday) {
				$normaldays[$i] = trim($normalday);
				if (!in_array($normaldays[$i], $dayarray)) {
					echo "<font color='red'><h3>Error: {$normaldays[$i]} is an invalid day, must be one of ".implode(", ", $dayarray).".</h3></font>";
					$failed = true;
					break;
				}
			}
		}
		if (!$failed) {
			$normaldays = ($_POST['normaldays'] == '') ? '' : implode(',',$normaldays);
			$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET normaldays = '{$normaldays}'  WHERE ID = '{$i_ID}'");
		}
	}
	$discussiondays = explode(",",$wpdb->get_var("SELECT normaldays FROM {$wpdb->prefix}votes_institutions WHERE ID = '{$i_ID}'"));
	if (array_key_exists('discussiontime', $_POST)) {
		$failed = false;
		if ($_POST['discussiontime'] != '') {
			$discussiontimes = explode(',',$_POST['discussiontime']);
			if (!(count($discussiontimes) == 1 || count($discussiontimes) == count($discussiondays))) {
				echo "<font color='red'><h3>Error: Number of discussion times must either be one or equal to the number of discussion days!</h3></font>";
				$failed = true;
			}
			foreach ($discussiontimes as $i => $discussiontime) {
				$discussiontimes[$i] = trim($discussiontime);
				if (strtotime($discussiontime) === false) {
					echo "<font color='red'><h3>Error: {$_POST['discussiontime']} is an invalid time!</h3></font>";
					$failed = true;
					break;
				}
			}
		}
		if (!$failed) {
			$discussiontimes = ($_POST['discussiontime'] == '') ? '' : implode(',',$discussiontimes);
			$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET discussiontime = '{$discussiontimes}'  WHERE ID = '{$i_ID}'");
		}
	}
	$discussiontimes = explode(",",$wpdb->get_var("SELECT discussiontime FROM {$wpdb->prefix}votes_institutions WHERE ID = '{$i_ID}'"));
	if (array_key_exists('discussionduration', $_POST)) {
		$discussiondurations = explode(",",$_POST['discussionduration']);
		$duration_failed = false;
		if (!(count($discussiondurations) == 1 || count($discussiondurations) == count($discussiontimes))) {
			echo "<font color='red'><h3>Error: Number of discussion times must either be one or equal to the number of discussion days!</h3></font>";
			$duration_failed = true;
		}
		if (count($discussiondurations) < count($discussiontimes)) {
			$discussiondurations = array_fill(0, count($discussiontimes), $discussiondurations[0]);
		}
		foreach ($discussiondurations as $discussionduration) {
			if (!is_numeric($discussionduration)) {
				echo "<font color='red'><h3>Error: {$discussionduration} is an invalid duration!</h3></font>";
				$duration_failed = true;
			}
		}
	}
	if (!array_key_exists('discussionduration', $_POST) || $duration_failed) {
		$resettimes = explode(",",$institution->resettime);
		$discussiondurations = array_fill(0, count($discussiontimes), 0);
		foreach ($discussiontimes as $i => $discussiontime) {
			$discussiondurations[$i] = (strtotime($resettimes[$i]) - strtotime($discussiontime)) / 60;
		}
		$discussiondurations = implode(",",$discussiondurations);
	}
	if (array_key_exists('discussionduration', $_POST) || array_key_exists('discussiontimes', $_POST)) {
		$resettimes = array_fill(0, count($discussiontimes), 0);
		foreach ($discussiontimes as $i => $discussiontime) {
			$resettimes[$i] = date('G:i', strtotime($discussiontime) + 60*$discussiondurations[$i]);
		}
		$resettimes = implode(",",$resettimes);
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET resettime = '{$resettimes}'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('extradays', $_POST)) {
		if ($_POST['extradays'] == '') {
			$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET extradays = '{$extradays}'  WHERE ID = '{$i_ID}'");
		} else {
			$extradays_raw = explode(',',$_POST['extradays']);
			$failed = false;
			foreach ($extradays_raw as $i => $tag) {
				// The custom time is currently not written into the code elsewhere. Should be added eventually.
				$rawtime = explode(';', trim($tag));
				if (count($rawtime) == 2) {
					if (!isTime($rawtime[1])) {
						echo "<font color='red'><h3>Error: {$rawtime[1]} is an invalid time!</h3></font>";
						$failed = true;
						break;
					}
				}
				$rawstride = explode(':', $rawtime[0]);
				if (count($rawstride) == 2) {
					if (!is_numeric($rawstride[1])) {
						echo "<font color='red'><h3>Error: {$rawstride[1]} is an invalid stride!</h3></font>";
						$failed = true;
						break;
					}
				}
				$rawrange = $rawstride[0];
				$extrarangedays = explode("-",$rawrange);
				foreach ($extrarangedays as $j => $extrarangeday) {
					$extrarangedays[$j] = trim($extrarangeday);
					$event_time = strtotime($extrarangedays[$j]);
					$month = date('n', $event_time);
					$day = date('j', $event_time);
					$year = date('Y', $event_time);
					if (!checkdate($month, $day, $year) || $event_time === false) {
						echo "<font color='red'><h3>Error: {$extrarangedays[$j]} is an invalid date!</h3></font>";
						$failed = true;
						break;
					}
				}
				if (!$failed) $extradays_raw[$i] = implode("-",$extrarangedays);
				if (count($rawstride) == 2) $extradays_raw[$i] .= ':' . $rawstride[1];
				if (count($rawtime) == 2) $extradays_raw[$i] .= ':' . $rawtime[1];
			}
			if (!$failed) {
				$extradays = implode(',',$extradays_raw);
				$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET extradays = '{$extradays}'  WHERE ID = '{$i_ID}'");
			}
		}
	}
	if (array_key_exists('canceleddays', $_POST)) {
		if ($_POST['canceleddays'] == '') {
			$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET canceleddays = ''  WHERE ID = '{$i_ID}'");
		} else {
			$canceleddays_raw = explode(',',$_POST['canceleddays']);
			$failed = false;
			foreach ($canceleddays_raw as $i => $tag) {
				$rawstride = explode(':', trim($tag));
				if (count($rawstride) == 2) {
					if (!is_numeric($rawstride[1])) {
						echo "<font color='red'><h3>Error: {$rawstride[1]} is an invalid stride!</h3></font>";
						$failed = true;
						break;
					}
				}
				$rawrange = $rawstride[0];
				$canceledrangedays = explode("-",$rawrange);
				$old_event_time = 0;
				foreach ($canceledrangedays as $j => $canceledrangeday) {
					$canceledrangedays[$j] = trim($canceledrangeday);
					$event_time = strtotime($canceledrangedays[$j]);
					if ($event_time < $old_event_time) {
						echo "<font color='red'><h3>Error: Date range should be in chronological order!</h3></font>";
						$failed = true;
						break;
					}
					$old_event_time = $event_time;
					$month = date('n', $event_time);
					$day = date('j', $event_time);
					$year = date('Y', $event_time);
					if (!checkdate($month, $day, $year) || $event_time === false) {
						echo "<font color='red'><h3>Error: {$canceledrangedays[$j]} is an invalid date!</h3></font>";
						$failed = true;
						break;
					}
				}
				if (!$failed) $canceleddays_raw[$i] = implode("-",$canceledrangedays);
				if (count($rawstride) == 2) $canceleddays_raw[$i] .= ':' . $rawstride[1];
			}
			if (!$failed) {
				$canceleddays = implode(',',$canceleddays_raw);
				$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET canceleddays = '{$canceleddays}'  WHERE ID = '{$i_ID}'");
			}
		}
	}
	if (array_key_exists('announceaddress', $_POST)) {
		if ($_POST['announceaddress'] == '') {
			$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announceaddress = ''  WHERE ID = '{$i_ID}'");
		} else {
			$failed = false;
			$announceaddress_raw = explode(",",$_POST['announceaddress']);
			foreach ($announceaddress_raw as $i => $tag) {
				$add = trim($tag);
				if (!check_email_address($add)) {
					echo "<font color='red'><h3>Error: {$add} is not a valid e-mail address!</h3></font>";
					$failed = true;
					break;
				}
				$announceaddress_raw[$i] = $add;
			}
			if (!$failed) {
				$announceaddress = implode(",", $announceaddress_raw);
				$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announceaddress = '{$announceaddress}'  WHERE ID = '{$i_ID}'");
			}
		}
	}
	if (array_key_exists('announcefrom', $_POST)) {
		if ($_POST['announcefrom'] == '') {
			$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announcefrom = ''  WHERE ID = '{$i_ID}'");
		} else {
			$failed = false;
			$announcefrom = trim($_POST['announcefrom']);
			if (!check_email_address($announcefrom)) {
				echo "<font color='red'><h3>Error: {$announcefrom} is not a valid e-mail address!</h3></font>";
				$failed = true;
			}
			if (!$failed) {
				$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announcefrom = '{$announcefrom}'  WHERE ID = '{$i_ID}'");
			}
		}
	}
	if (array_key_exists('announcedelay', $_POST)) {
		$failed = false;
		$delays = explode(",", $_POST['announcedelay']);
		foreach ($delays as $i => $delay) {
			$delays[$i] = trim($delay);
			if (!is_numeric($delays[$i])) {
				echo "<font color='red'><h3>Error: '{$_POST['announcedelay']}' is an invalid delay time!</h3></font>";
				$failed = true;
			}
		}
		if (!$failed) {
			sort($delays,SORT_NUMERIC);
			$delay_str = implode(",", $delays);
			$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announcedelay = '{$delay_str}'  WHERE ID = '{$i_ID}'");
		}
	}
	if (array_key_exists('announcecopy', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announcecopy = '1'  WHERE ID = '{$i_ID}'");
	} elseif (!empty($_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announcecopy = '0'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('announceabstracts', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announceabstracts = '1'  WHERE ID = '{$i_ID}'");
	} elseif (!empty($_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announceabstracts = '0'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('announcemeta', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announcemeta = '1'  WHERE ID = '{$i_ID}'");
	} elseif (!empty($_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announcemeta = '0'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('announceplain', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announceplain = '1'  WHERE ID = '{$i_ID}'");
	} elseif (!empty($_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET announceplain = '0'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('noemptyagenda', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET noemptyagenda = '1'  WHERE ID = '{$i_ID}'");
	} elseif (!empty($_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET noemptyagenda = '0'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('pushnotdiscussed', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET pushnotdiscussed = '1'  WHERE ID = '{$i_ID}'");
	} elseif (!empty($_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET pushnotdiscussed = '0'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('showhistoricalvotes', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET showhistoricalvotes = '1'  WHERE ID = '{$i_ID}'");
	} elseif (!empty($_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET showhistoricalvotes = '0'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('gcaladdress', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_institutions SET gcaladdress = '{$_POST['gcaladdress']}'  WHERE ID = '{$i_ID}'");
	}
	if (array_key_exists('liaison', $_POST)) {
		if ($_POST['liaison'] >= 0) {
			$liaisons = get_users( array( 'role' => 'liaison', 'fields' => 'ID' ) );
			$inst_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}votes_institutions WHERE ID = '{$i_ID}'");
			$inst_users = $wpdb->get_col("SELECT user FROM {$wpdb->prefix}votes_users WHERE affiliation='{$inst_name}'");
			$liaisons = array_intersect($liaisons, $inst_users);
			if (count($liaisons) <= 1) {
				echo "<font color='red'><h3>Error: There must be at least one liaison for each portal, please add a replacement if you are removing yourself! If you would like to disable your institution's portal entirely, please e-mail the Vox Charta admin (jguillochon@cfa.harvard.edu).</h3></font>";
			} else wp_update_user( array( 'ID' => $_POST['liaison'], 'role' => 'author' ) );
		}
	}
	if (array_key_exists('normal', $_POST)) {
		if ($_POST['normal'] >= 0) {
			if ($_POST['submit'] == 'promote') {
				wp_update_user( array( 'ID' => $_POST['normal'], 'role' => 'liaison' ) );
			} elseif ($_POST['submit'] == 'unaffil') {
				$inst_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}votes_institutions WHERE ID = '{$i_ID}'");
				$user_inst = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user = '{$_POST['normal']}'");
				if ($user_inst == $inst_name)
					$wpdb->query("UPDATE {$wpdb->prefix}votes_users SET affiliation = 'Unaffiliated' WHERE user = '{$_POST['normal']}'");
			}
		}
	}


	if (!empty($_POST)) {
		$cronstr =
			"36,46,56 17 * * 0-4 /usr/bin/curl -k https://voxcharta.org/wp-content/plugins/wp-o-matic-jfg/cron.php?code=7175e58b\n" .
			"6,16,26,36,46,56 18 * * 0-4 /usr/bin/curl -k https://voxcharta.org/wp-content/plugins/wp-o-matic-jfg/cron.php?code=7175e58b\n" .
			"6,16,26,36,46,56 19-20 * * 0-4 /usr/bin/curl -k https://voxcharta.org/wp-content/plugins/wp-o-matic-jfg/cron.php?code=7175e58b\n" .
			//"1,11,21,31,41,51 0-10 * * 0-4 /usr/bin/curl -k https://voxcharta.org/wp-content/plugins/wp-o-matic-jfg/cron.php?code=7175e58b\n" .
			"00 18,19,20 * * 0-4 /usr/bin/curl -k https://voxcharta.org/wp-content/plugins/search-unleashed/reindex-posts.php\n"; //Only necessary because indexing post doesn't work with wp-o-matic for some reason...
			//"1,11,21,31,41,51 19-23 * * 0-4 /usr/bin/curl -k https://voxcharta.org/wp-content/plugins/wp-o-matic-jfg/cron.php?code=7175e58b\n". //Emergency cron line
			//"1,11,21,31,41,51 0-16 * * 0-4 /usr/bin/curl -k https://voxcharta.org/wp-content/plugins/wp-o-matic-jfg/cron.php?code=7175e58b\n". //Emergency cron line
		$institutions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name <> 'Unaffiliated' AND active = 1");
		
		date_default_timezone_set('US/Pacific');
		$rectime = strtotime('6:30 pm');

		$ic = 0;
		foreach ($institutions as $inst) {
			date_default_timezone_set('US/Pacific');
			$reccrontime = date("i G", $rectime);
			date_default_timezone_set($inst->timezone);
			$disctimes = explode(',',$inst->discussiontime);
			$normaldays = explode(",", $inst->normaldays);
			if (count($disctimes) < count($normaldays)) {
				$disctimes = array_fill(0, count($normaldays), $disctimes[0]);
			}
			if ($inst->announceaddress != '' || $inst->announcecopy == 1) {
				$delays = explode(",", $inst->announcedelay);
				foreach ($delays as $delay) {
					if (trim($inst->normaldays) != '' && $inst->extradays == '') {
						foreach ($normaldays as $i => $normalday) {
							date_default_timezone_set($inst->timezone);
							$normdaynum = date("w", strtotime($normalday));
							$disctime = strtotime($disctimes[$i]);
							$tzshift = date("w", $disctime);
							date_default_timezone_set('US/Pacific');
							$tzshift = $tzshift - date("w", $disctime);
							$normdayshift = round((strtotime(date("m/d/Y", $disctime)) -
												   strtotime(date("m/d/Y", $disctime - 60*$delay)))/86400);
							$normdaynum = ($normdaynum - $normdayshift - $tzshift) % 7;
							if ($normdaynum < 0) $normdaynum = $normdaynum + 7;
							$cronstr .= date("i G", $disctime - 60*$delay) . " * * ".$normdaynum." /usr/bin/curl -k https://voxcharta.org/wp-content/plugins/vote-it-up/cron.php?code=7175e58b\&institution=".urlencode($inst->name)."\&dayindex=".$i."\n";
						}
					} else {
						date_default_timezone_set($inst->timezone);
						$disctime = strtotime($disctimes[0]);
						date_default_timezone_set('US/Pacific');
						$normdaynum = "0-6";
						$cronstr .= date("i G", $disctime - 60*$delay) . " * * ".$normdaynum." /usr/bin/curl -k https://voxcharta.org/wp-content/plugins/vote-it-up/cron.php?code=7175e58b\&institution=".urlencode($inst->name)."\&dayindex=0\n";
					}

				}
			}
			$cronstr .= $reccrontime." * * 0-4 /usr/bin/curl -k https://voxcharta.org/wp-content/plugins/vote-it-up/recommend-cron.php?code=7175e58b\&institution=".urlencode($inst->name)."\n";
			$ic++;
			if ($ic % 5 == 0) $rectime += 60;
		}

		$cronhand = fopen("/var/spool/cron/apache", "w+");
		if ($cronhand) {
			fwrite($cronhand, $cronstr);
			fclose($cronhand);
			exec("crontab /var/spool/cron/apache");
		} else {
			echo "<font color='red'><h3>Error: Failed to edit crontab!</h3></font>";
		}
	}
}

function check_email_address($email) {
  // First, we check that there's one @ symbol, 
  // and that the lengths are right.
  if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
    // Email invalid because wrong number of characters 
    // in one section or wrong number of @ symbols.
    return false;
  }
  // Split it into sections to make life easier
  $email_array = explode("@", $email);
  $local_array = explode(".", $email_array[0]);
  for ($i = 0; $i < sizeof($local_array); $i++) {
    if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&
      ↪'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$",
      $local_array[$i])) {
      return false;
    }
  }
  // Check if domain is IP. If not, 
  // it should be valid domain name
  if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
    $domain_array = explode(".", $email_array[1]);
    if (sizeof($domain_array) < 2) {
        return false; // Not enough parts to domain
    }
    for ($i = 0; $i < sizeof($domain_array); $i++) {
      if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|
        ↪([A-Za-z0-9]+))$",
        $domain_array[$i])) {
        return false;
      }
    }
	// Disabled by JFG, causing problems with some domains
	//$pruned_domain = implode(".", array_slice($domain_array, sizeof($domain_array) - 2, 2));
	//if(!checkdnsrr($pruned_domain,'MX')) {
	//  return false;
    //}
  }
  return true;
}

function EventsEdit($event) {
	global $wpdb;
	$e_ID = $event->ID;
	if (array_key_exists('nicename', $_POST)) {
		$name = urldecode($_POST['nicename']);
		$old_name = $wpdb->get_var("SELECT nicename FROM {$wpdb->prefix}votes_events WHERE ID = '{$e_ID}'");
		if ($old_name != $name) {
			$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET nicename = '{$name}'  WHERE ID = '{$e_ID}'");
			$slug = str_replace(array("'", "\\"), "", strtolower($name));
			$slug = str_replace(" ", "-", $slug);
			$baseslug = $slug;
			$i = 2;
			while (get_term_by('slug', $slug, 'category')) {
				$slug = $baseslug . $i;
				$i++;
			}
			$cat = wp_insert_term($name, 'category', array('slug' => $slug));
			$cat_id = $cat['term_id'];
			//$cat_id = wp_create_category($name);
			$category = get_term_by('id', $cat_id, 'category');
			if ($event->categoryslug != '') {
				$old_category = get_term_by('slug', $event->categoryslug, 'category');
				$posts = get_posts('category='.$old_category->term_id);
				foreach ($posts as $post) {
					wp_set_object_terms($post->ID, $cat_id, 'category', true);
				}
				wp_delete_category($old_category->term_id);
			}
			$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET categoryslug = '{$slug}'  WHERE ID = '{$e_ID}'");
		}
	}
	if (array_key_exists('dayofweek', $_POST)) {
		if ($_POST['dayofweek'] == '') {
			$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET dayofweek = ''  WHERE ID = '{$e_ID}'");
		} else {
			$failed = false;
			$daysofweek = explode(',',$_POST['dayofweek']);
			$dayarray = array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
			foreach ($daysofweek as $i => $dayofweek) {
				$daysofweek[$i] = substr(trim($dayofweek), 0, 3);
				if (!in_array($daysofweek[$i], $dayarray)) {
					echo "<font color='red'><h3>Error: {$daysofweek[$i]} is an invalid day, must be one of ".implode(", ", $dayarray).".</h3></font>";
					$failed = true;
					break;
				}
			}
			if (!$failed) {
				$daysofweek = implode(',',$daysofweek);
				$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET dayofweek = '{$daysofweek}'  WHERE ID = '{$e_ID}'");
			}
		}
	}
	if (array_key_exists('extradays', $_POST)) {
		if ($_POST['extradays'] == '') {
			$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET extradays = '{$extradays}'  WHERE ID = '{$e_ID}'");
		} else {
			$extradays_raw = explode(',',$_POST['extradays']);
			$failed = false;
			foreach ($extradays_raw as $i => $tag) {
				// The custom time is currently not written into the code elsewhere. Should be added eventually.
				$rawtime = explode(';', trim($tag));
				if (count($rawtime) == 2) {
					if (!isTime($rawtime[1])) {
						echo "<font color='red'><h3>Error: {$rawtime[1]} is an invalid time!</h3></font>";
						$failed = true;
						break;
					}
				}
				$rawstride = explode(':', $rawtime[0]);
				if (count($rawstride) == 2) {
					if (!is_numeric($rawstride[1])) {
						echo "<font color='red'><h3>Error: {$rawstride[1]} is an invalid stride!</h3></font>";
						$failed = true;
						break;
					}
				}
				$rawrange = $rawstride[0];
				$extrarangedays = explode("-",$rawrange);
				foreach ($extrarangedays as $j => $extrarangeday) {
					$extrarangedays[$j] = trim($extrarangeday);
					$event_time = strtotime($extrarangedays[$j]);
					$month = date('n', $event_time);
					$day = date('j', $event_time);
					$year = date('Y', $event_time);
					if (!checkdate($month, $day, $year) || $event_time === false) {
						echo "<font color='red'><h3>Error: {$extrarangedays[$j]} is an invalid date!</h3></font>";
						$failed = true;
						break;
					}
				}
				if (!$failed) $extradays_raw[$i] = implode("-",$extrarangedays);
				if (count($rawstride) == 2) $extradays_raw[$i] .= ':' . $rawstride[1];
				if (count($rawtime) == 2) $extradays_raw[$i] .= ':' . $rawtime[1];
			}
			if (!$failed) {
				$extradays = implode(',',$extradays_raw);
				$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET extradays = '{$extradays}'  WHERE ID = '{$e_ID}'");
			}
		}
	}
	if (array_key_exists('canceleddays', $_POST)) {
		if ($_POST['canceleddays'] == '') {
			$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET canceleddays = ''  WHERE ID = '{$e_ID}'");
		} else {
			$canceleddays_raw = explode(',',$_POST['canceleddays']);
			$failed = false;
			foreach ($canceleddays_raw as $i => $tag) {
				$rawstride = explode(':', trim($tag));
				if (count($rawstride) == 2) {
					if (!is_numeric($rawstride[1])) {
						echo "<font color='red'><h3>Error: {$rawstride[1]} is an invalid stride!</h3></font>";
						$failed = true;
						break;
					}
				}
				$rawrange = $rawstride[0];
				$canceledrangedays = explode("-",$rawrange);
				$old_event_time = 0;
				foreach ($canceledrangedays as $j => $canceledrangeday) {
					$canceledrangedays[$j] = trim($canceledrangeday);
					$event_time = strtotime($canceledrangedays[$j]);
					if ($event_time < $old_event_time) {
						echo "<font color='red'><h3>Error: Date range should be in chronological order!</h3></font>";
						$failed = true;
						break;
					}
					$old_event_time = $event_time;
					$month = date('n', $event_time);
					$day = date('j', $event_time);
					$year = date('Y', $event_time);
					if (!checkdate($month, $day, $year) || $event_time === false) {
						echo "<font color='red'><h3>Error: {$canceledrangedays[$j]} is an invalid date!</h3></font>";
						$failed = true;
						break;
					}
				}
				if (!$failed) $canceleddays_raw[$i] = implode("-",$canceledrangedays);
				if (count($rawstride) == 2) $canceleddays_raw[$i] .= ':' . $rawstride[1];
			}
			if (!$failed) {
				$canceleddays = implode(',',$canceleddays_raw);
				$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET canceleddays = '{$canceleddays}'  WHERE ID = '{$e_ID}'");
			}
		}
	}
	//if (array_key_exists('extradays', $_POST) && $_POST['extradays'] !== '') {
	//	$extradays_raw = explode(',',$_POST['extradays']);
	//	$failed = false;
	//	$old_event_time = 0;
	//	foreach ($extradays_raw as $i => $tag) {
	//		$extradays_raw[$i] = trim($tag);
	//		$event_time = strtotime($extradays_raw[$i]);
	//		if ($event_time < $old_event_time) {
	//			echo "<font color='red'><h3>Error: Date range should be in chronological order!</h3></font>";
	//			$failed = true;
	//			break;
	//		}
	//		$old_event_time = $event_time;
	//		$month = date('n', $event_time);
	//		$day = date('j', $event_time);
	//		$year = date('Y', $event_time);
	//		if (!checkdate($month, $day, $year) || $event_time === false) {
	//			echo "<font color='red'><h3>Error: {$extradays_raw[$i]} is an invalid date!</h3></font>";
	//			$failed = true;
	//			break;
	//		}
	//	}
	//	if (!$failed) {
	//		$extradays = implode(',',$extradays_raw);
	//		$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET extradays = '{$extradays}'  WHERE ID = '{$e_ID}'");
	//	}
	//}
	//if (array_key_exists('canceleddays', $_POST) && $_POST['canceleddays'] !== '') {
	//	$canceleddays_raw = explode(',',$_POST['canceleddays']);
	//	$failed = false;
	//	foreach ($canceleddays_raw as $i => $tag) {
	//		$rawrange = trim($tag);
	//		$canceledrangedays = explode("-",$rawrange);
	//		foreach ($canceledrangedays as $j => $canceledrangeday) {
	//			$canceledrangedays[$j] = trim($canceledrangeday);
	//			$event_time = strtotime($canceledrangedays[$j]);
	//			$month = date('n', $event_time);
	//			$day = date('j', $event_time);
	//			$year = date('Y', $event_time);
	//			if (!checkdate($month, $day, $year) || $event_time === false) {
	//				echo "<font color='red'><h3>Error: {$canceledrangedays[$j]} is an invalid date!</h3></font>";
	//				$failed = true;
	//				break;
	//			}
	//		}
	//		if (!$failed) $canceleddays_raw[$i] = implode("-",$canceledrangedays);
	//	}
	//	if (!$failed) {
	//		$canceleddays = implode(',',$canceleddays_raw);
	//		$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET canceleddays = '{$canceleddays}'  WHERE ID = '{$e_ID}'");
	//	}
	//	//$canceleddays_raw = explode(',',$_POST['canceleddays']);
	//	//$failed = false;
	//	//foreach ($canceleddays_raw as $i => $tag) {
	//	//	$canceleddays_raw[$i] = trim($tag);
	//	//	$event_time = strtotime($canceleddays_raw[$i]);
	//	//	$month = date('n', $event_time);
	//	//	$day = date('j', $event_time);
	//	//	$year = date('Y', $event_time);
	//	//	if (!checkdate($month, $day, $year) || $event_time === false) {
	//	//		echo "<font color='red'><h3>Error: {$canceleddays_raw[$i]} is an invalid date!</h3></font>";
	//	//		$failed = true;
	//	//		break;
	//	//	}
	//	//}
	//	//if (!$failed) {
	//	//	$canceleddays = implode(',',$canceleddays_raw);
	//	//	$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET canceleddays = '{$canceleddays}'  WHERE ID = '{$e_ID}'");
	//	//}
	//}
	if (array_key_exists('theme', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET theme = '{$_POST['theme']}'  WHERE ID = '{$e_ID}'");
	}
	if (array_key_exists('noagenda', $_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET noagenda = '1'  WHERE ID = '{$e_ID}'");
	} elseif (!empty($_POST)) {
		$wpdb->query("UPDATE {$wpdb->prefix}votes_events SET noagenda = '0'  WHERE ID = '{$e_ID}'");
	}
}

function CreatePortal() {
	global $wpdb, $reCAPTCHA;
	$failed = false;
	if (array_key_exists('g-recaptcha-response', $_POST)) {
		$resp = $_POST['g-recaptcha-response'];
		if (!$reCAPTCHA->isValid($resp)) {
			echo "<font color='red'><h3>Error: Incorrectly entered captcha!</h3></font>";
			$failed = true;
		}
	} else $failed = true;
	if (array_key_exists('portalname', $_POST)) {
		$names = $wpdb->get_col("SELECT name FROM {$wpdb->prefix}votes_institutions");
		if ($_POST['portalname'] === '') {
			echo "<font color='red'><h3>Error: You must specify a portal name!</h3></font>";
			$failed = true;
		}
		if (in_array($_POST['portalname'], $names)) {
			echo "<font color='red'><h3>Error: \"{$_POST['portalname']}\" is already claimed by another institution!</h3></font>";
			$failed = true;
		}
	} else $failed = true;
	if (array_key_exists('subdomain', $_POST)) {
		$subdomains = $wpdb->get_col("SELECT subdomain FROM {$wpdb->prefix}votes_institutions");
		if ($_POST['subdomain'] === '') {
			echo "<font color='red'><h3>Error: You must specify a subdomain!</h3></font>";
			$failed = true;
		} elseif (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|
			↪([A-Za-z0-9]+))$", $_POST['subdomain'])) {
			echo "<font color='red'><h3>Error: '{$_POST['subdomain']}' is not a valid subdomain!</h3></font>";
			$failed = true;
		} elseif (in_array(strtolower($_POST['subdomain']), array_map('strtolower',$subdomains))) {
			echo "<font color='red'><h3>Error: \"{$_POST['subdomain']}\" is already claimed by another institution!</h3></font>";
			$failed = true;
		} else $subdomain = strtolower($_POST['subdomain']);
	} else $failed = true;
	if (array_key_exists('url', $_POST)) {
		if ($_POST['url'] === '') {
			echo "<font color='red'><h3>Error: You must specify an associated URL for your portal!</h3></font>";
			$failed = true;
		} elseif(filter_var($_POST['url'], FILTER_VALIDATE_URL) === FALSE) {
			echo "<font color='red'><h3>Error: You must specify a valid associated URL for your portal!</h3></font>";
			$failed = true;
		}
	} else $failed = true;
	if (array_key_exists('usernames', $_POST)) {
		if ($_POST['usernames'] === '') {
			echo "<font color='red'><h3>Error: You must specify at least one account name!</h3></font>";
			$failed = true;
		} else {
			$namelist = explode(',', $_POST['usernames']);
			$user_q = $wpdb->prepare("SELECT u.ID, u.user_login FROM {$wpdb->prefix}users AS u
				INNER JOIN {$wpdb->prefix}votes_users AS vu ON u.ID = vu.user
				WHERE u.user_login IN ('%s') AND vu.affiliation = 'Unaffiliated'", $namelist);
			$users = $wpdb->get_results($user_q);
			$newuser_q = $wpdb->prepare("SELECT user FROM {$wpdb->prefix}sabre_table WHERE user IN ('%s') AND status = 'to confirm'", $namelist);
			$newusers = $wpdb->get_col($newuser_q);
			if (count($users) == 0 || $users == '') {
				echo "<font color='red'><h3>Error: No valid unaffiliated account names specified, ";
				echo "change your affiliation to 'Unaffiliated' before using this form, or create ";
				echo "an account using the link below if you do not have one!</h3></font>";
				$failed = true;
			}
		}
	} else $failed = true;
	if (!$failed) {
		$arg_arr = array('portalname' => $_POST['portalname'],
						 'subdomain' => $subdomain,
						 'timezone' => $_POST['timezone'],
						 'url' => $_POST['url'],
						 'usernames' => $_POST['usernames'],
					 	 'code' => get_option('wpo_croncode'));
		$query = http_build_query($arg_arr);
		$link = 'https://voxcharta.org/wp-content/plugins/vote-it-up/confirm-new-portal.php?'.$query;
		$email_from = "jguillochon@cfa.harvard.edu";
		add_filter('wp_mail_from', create_function('', "return \"{$email_from}\"; "));
		add_filter('wp_mail_from_name', create_function('', "return \"Vox Charta\"; "));
		add_filter('wp_mail_content_type', create_function('', 'return "multipart/alternative; boundary=voxcharta"; '));
		$content = "\n--voxcharta\nContent-Type: text/html; charset=\"UTF-8\"\nContent-Transfer-Encoding: quoted-printable\n\n";
		$body = "Click <a href={$link}>here</a> to approve.";
		$body .= "\n\nUsernames to approve in Sabre: " . implode(', ', $newusers);
		$content .= wordwrap(imap_8bit($body), 120, "\n", true);
		$content .= "\n\n--voxcharta--\n";
		wp_mail("guillochon@gmail.com", "New Portal Request for {$_POST['portalname']}", $content);
	}
	return !$failed;
}

function RecommendEdit($user_ID) {
	global $wpdb;
	$u_ID = $wpdb->escape($user_ID);
	if (array_key_exists('showreplace', $_POST)) {
		$wpdb->query('UPDATE '.$wpdb->prefix.'votes_recommend SET showreplace = '.$_POST['showreplace'].'  WHERE user = "'.$u_ID.'"');
	}
	if (array_key_exists('showcrosslist', $_POST)) {
		$wpdb->query('UPDATE '.$wpdb->prefix.'votes_recommend SET showcrosslist = '.$_POST['showcrosslist'].'  WHERE user = "'.$u_ID.'"');
	}
	if (array_key_exists('sendemail', $_POST)) {
		$wpdb->query('UPDATE '.$wpdb->prefix.'votes_recommend SET sendemail = '.$_POST['sendemail'].'  WHERE user = "'.$u_ID.'"');
	}
	if (array_key_exists('dontemail', $_POST)) {
		$wpdb->query('UPDATE '.$wpdb->prefix.'votes_recommend SET dontemail = '.$_POST['dontemail'].'  WHERE user = "'.$u_ID.'"');
	}
	if (array_key_exists('reminddays', $_POST)) {
		if (!is_numeric($_POST['reminddays']) || $_POST['reminddays'] <= 0) {
			echo '<font color="red"><h3>Error: Recommendation day limit must be a positive number!</h3></font>';
		} else $wpdb->query('UPDATE '.$wpdb->prefix.'votes_recommend SET reminddays = '.$_POST['reminddays'].'  WHERE user = "'.$u_ID.'"');
	}
	if (array_key_exists('bannedtags', $_POST)) {
		$bannedtags_raw = explode(',',$_POST['bannedtags']);
		foreach ($bannedtags_raw as $i => $tag) {
			$bannedtags_raw[$i] = trim($tag);
		}
		$bannedtags = implode(',',$bannedtags_raw);
		$wpdb->query('UPDATE '.$wpdb->prefix.'votes_recommend SET bannedtags = "'.$bannedtags.'"  WHERE user = "'.$u_ID.'"');
	}

}
function ResetVote($postids, $action) {
/*
Testing stuff...
echo 'Posts: '.implode(', ',$_POST['post']);
echo '<br />';
echo "Action: ".$action;
*/
global $wpdb;
//$wpdb->show_errors();

switch ($action) {

case 'none':
//do nothing
break;
case 'delete':
//reset all votes for the post
$i = 0;
$wpdb->show_errors();
while ($i < count($postids)) {
$postvotesarr = $wpdb->get_results("SELECT votes, usersinks, institution FROM {$wpdb->prefix}votes WHERE post='{$postids[$i]}'");
foreach ($postvotesarr as $postvotes) {
$votes = explode(",", $postvotes->votes);
foreach ($votes as $vote) {
	$uservotes = $wpdb->get_var("SELECT votes FROM {$wpdb->prefix}votes_users WHERE user='{$vote}'");
	$newuservotes = $wpdb->escape(implode(",",array_unique(array_diff(explode(",",$uservotes), array($postids[$i])))));
	$wpdb->query("UPDATE {$wpdb->prefix}votes_users SET votes='{$newuservotes}' WHERE user='{$vote}'");
}
$sinks = explode(",", $postvotes->usersinks);
foreach ($sinks as $sink) {
	$usersinks = $wpdb->get_var("SELECT sinks FROM {$wpdb->prefix}votes_users WHERE user='{$sink}'");
	$newusersinks = $wpdb->escape(implode(",",array_unique(array_diff(explode(",",$usersinks), array($postids[$i])))));
	$wpdb->query("UPDATE {$wpdb->prefix}votes_users SET sinks='{$newusersinks}' WHERE user='{$sink}'");
}
$wpdb->query("DELETE FROM ".$wpdb->prefix."votes WHERE `post`=".$postids[$i]." AND institution='{$postvotes->institution}' LIMIT 1 ;");
}
$i++;
}
EditVoteSuccess();
break;
case 'deleteuser':
//reset all votes for users
$i = 0;
while ($i < count($postids)) {
$postvotes = $wpdb->get_row("SELECT votes, usersinks FROM {$wpdb->prefix}votes WHERE post='{$postids[$i]}'");
$votes = explode(",", $postvotes->votes);
foreach ($votes as $vote) {
	$uservotes = $wpdb->get_var("SELECT votes FROM {$wpdb->prefix}votes_users WHERE user='{$vote}'");
	$newuservotes = $wpdb->escape(implode(",",array_unique(array_diff(explode(",",$uservotes), array($postids[$i])))));
	$wpdb->query("UPDATE {$wpdb->prefix}votes_users SET votes='{$newuservotes}' WHERE user='{$vote}'");
}
$sinks = explode(",", $postvotes->usersinks);
foreach ($sinks as $sink) {
	$usersinks = $wpdb->get_var("SELECT sinks FROM {$wpdb->prefix}votes_users WHERE user='{$sink}'");
	$newusersinks = $wpdb->escape(implode(",",array_unique(array_diff(explode(",",$usersinks), array($postids[$i])))));
	$wpdb->query("UPDATE {$wpdb->prefix}votes_users SET sinks='{$newusersinks}' WHERE user='{$sink}'");
}
$wpdb->query("UPDATE ".$wpdb->prefix."votes SET votes = '', usersinks = '', WHERE post=".$postids[$i]." LIMIT 1 ;");
$i++;
}
EditVoteSuccess();
break;
case 'deleteguest':
//reset all votes for guests
$i = 0;
while ($i < count($postids)) {
$wpdb->query("UPDATE ".$wpdb->prefix."votes SET guests = '', guestsinks = '', WHERE post=".$postids[$i]." LIMIT 1 ;");
$i++;
}
EditVoteSuccess();
break;
case 'resetvotetime':
//reset vote times
$i = 0;
while ($i < count($postids)) {
$wpdb->query("UPDATE ".$wpdb->prefix."votes SET lastvotetime = 0 WHERE post=".$postids[$i]." LIMIT 1 ;");
$i++;
}
EditVoteSuccess();
break;
}





}

function EditVoteSuccess() {
?><div id="message" class="updated fade"><p><strong>Votes edited</strong></p></div><?php
}

//Retrieves user voting record
function UserPosts($u_ID) {
	global $wpdb;

	$postarray = array();
	$timearray = array();
	$votedirarray = array();

	//Get posts logged, and get the latest posts voted [new posts have larger IDs
	$posts = $wpdb->get_results("SELECT post, votes, usersinks, lastvotetime FROM ".$wpdb->prefix."votes WHERE ID != '' AND lastvotetime > 0
								 AND (FIND_IN_SET({$u_ID}, votes) > 0 OR FIND_IN_SET({$u_ID}, usersinks) > 0) ORDER BY lastvotetime DESC");
	foreach ($posts as $post) { 
		$votes = explode(",", $post->votes);
		$sinks = explode(",", $post->usersinks);
		$upped = in_array($u_ID, $votes);
		$downed = in_array($u_ID, $sinks);
		if ($upped || $downed)
		{
			array_push($postarray, array($post->post));
			array_push($timearray, array($post->lastvotetime));
			array_push($votedirarray, array($upped));
		}
	}

	//array_multisort($timearray, SORT_DESC, $postarray, $votedirarray);
	$output = array($postarray, $timearray, $votedirarray);
	return $output;

}

//Used to sort users by votes
function VoterSort() {
	global $wpdb;

	$limit = get_option('voteiu_limit');

	$where = 'WHERE comment_approved = 1 AND user_id <> 0';
	$comment_counts = (array) $wpdb->get_results("SELECT user_id, COUNT( * ) AS total FROM {$wpdb->comments} {$where} GROUP BY user_id", object);
	$post_counts = (array) $wpdb->get_results("SELECT post_author, COUNT( * ) AS total FROM {$wpdb->posts}
		WHERE post_status = 'publish' AND post_author <> 0 GROUP BY post_author", object);
	$uid = $wpdb->get_col("SELECT $wpdb->users.ID FROM $wpdb->users");

	$countarray = array_fill(0,count($uid),0);
	$votecountarray = array_fill(0,count($uid),0);
	$sinkcountarray = array_fill(0,count($uid),0);
	$commentsarray = array_fill(0,count($uid),0);
	$postsarray = array_fill(0,count($uid),0);
	$namesarray = array_fill(0,count($uid),'');
	$regtimesarray = array_fill(0,count($uid),'');
	$urlsarray = array_fill(0,count($uid),'');
	$affilsarray = array_fill(0,count($uid),'');

	foreach ($uid as $i => $voter) {
		$votestr = $wpdb->get_var("SELECT votes FROM {$wpdb->prefix}votes_users WHERE user='{$voter}'");
		if ($votestr !== null) $votecountarray[$i] = count(explode(",",$votestr));
		$sinkstr = $wpdb->get_var("SELECT sinks FROM {$wpdb->prefix}votes_users WHERE user='{$voter}'");
		if ($sinkstr !== null) $sinkcountarray[$i] = count(explode(",",$sinkstr));
		$countarray[$i] = $votecountarray[$i] + $sinkcountarray[$i];
		$info = $wpdb->get_row("SELECT display_name as name, user_registered as reg, user_url as url FROM {$wpdb->prefix}users WHERE ID='{$voter}'");
		$namesarray[$i] = $info->name;
		$regtimesarray[$i] = strtotime($info->reg);
		$urlsarray[$i] = $info->url;
		$affilsarray[$i] = $wpdb->get_var("SELECT affiliation FROM ".$wpdb->prefix."votes_users WHERE user='{$voter}'");
		foreach ($comment_counts as $user_comment) {
			if ($user_comment->user_id == $voter) $commentsarray[$i] = $user_comment->total;
		}
		foreach ($post_counts as $user_post) {
			if ($user_post->post_author == $voter) $postsarray[$i] = $user_post->total;
		}
	}

	$sorttype = SORT_NUMERIC;
	$order = ($_GET['order'] != '') ? SORT_ASC : SORT_DESC;
	if ($_GET['sortby'] != '') {
		switch ($_GET['sortby']) {
			case 'name':
				$sorttype = SORT_STRING;
				$sortarray = $namesarray;
				$order = ($_GET['order'] != '') ? SORT_DESC : SORT_ASC;
				break;
			case 'votecount':
				$sortarray = $votecountarray;
				break;
			case 'sinkcount':
				$sortarray = $sinkcountarray;
				break;
			case 'comments':
				$sortarray = $commentsarray;
				break;
			case 'posts':
				$sortarray = $postsarray;
				break;
			case 'regtime':
				$sortarray = $regtimesarray;
				break;
			case 'affil':
				$sorttype = SORT_STRING;
				$sortarray = $affilsarray;
				$order = ($_GET['order'] != '') ? SORT_DESC : SORT_ASC;
				break;
			default:
				$sortarray = $countarray;
		}
	} else {
		$sortarray = $countarray;
	}
	array_multisort($sortarray, $order, $sorttype, $countarray, $votecountarray, $sinkcountarray, $uid,
		$commentsarray, $postsarray, $namesarray, $regtimesarray, $urlsarray, $affilsarray);
	$output = array('uid' => $uid, 'votecount' => $votecountarray, 'sinkcount' => $sinkcountarray,
		'count' => $countarray, 'comments' => $commentsarray, 'posts' => $postsarray,
		'name' => $namesarray, 'regtime' => $regtimesarray, 'url' => $urlsarray, 'affil' => $affilsarray);
	return $output;

}

function AuthorSort() {
	global $wpdb;

	$wpdb->get_results("SET SESSION group_concat_max_len = 100000;");
	$authors = $wpdb->get_results("
		SELECT name, (LENGTH(votes) - LENGTH(REPLACE(votes,',','')) + 1) AS votecount,
		(LENGTH(sinks) - LENGTH(REPLACE(sinks,',','')) + 1) AS sinkcount FROM (
			SELECT t.name AS name,
			GROUP_CONCAT(if (vo.votes ='', null, vo.votes) SEPARATOR ',') AS votes,
			GROUP_CONCAT(if (vo.usersinks ='', null, vo.usersinks) SEPARATOR ',') AS sinks
			FROM {$wpdb->posts} AS p 
			INNER JOIN {$wpdb->prefix}votes AS vo ON (p.ID = vo.post)
			INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
			INNER JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id)
			WHERE tt.taxonomy = 'post_author'
			AND (vo.votes <> '' || vo.usersinks <> '')
			GROUP BY t.term_id
		) AS raw ORDER BY votecount DESC LIMIT 500;
	");

	$namearray = array();
	$votecountarray = array();
	$sinkcountarray = array();
	$countarray = array();

	$i = 0;
	foreach ($authors as $author) {
		$num_votes = $author->votecount;
		$num_sinks = $author->sinkcount;

		$namearray[] = $author->name;
		$votecountarray[] = $num_votes;
		$sinkcountarray[] = $num_sinks;
		$countarray[] = $num_votes + $num_sinks;
	
		$i++;
	}

	$sorttype = SORT_NUMERIC;
	$order = ($_GET['order'] != '') ? SORT_ASC : SORT_DESC;
	if ($_GET['sortby'] != '') {
		switch ($_GET['sortby']) {
			case 'name':
				$sorttype = SORT_STRING;
				$sortarray = $namearray;
				$order = ($_GET['order'] != '') ? SORT_DESC : SORT_ASC;
				break;
			case 'votecount':
				$sortarray = $votecountarray;
				break;
			case 'sinkcount':
				$sortarray = $sinkcountarray;
				break;
			default:
				$sortarray = $countarray;
		}
	} else {
		$sortarray = $countarray;
	}

	array_multisort($sortarray, $order, $sorttype, $countarray, SORT_DESC,
					$votecountarray, SORT_DESC, $sinkcountarray, $namearray);

	$output = array('count' => $countarray, 'name' => $namearray,
					'votecount' => $votecountarray, 'sinkcount' => $sinkcountarray);

	return $output;

	//$i = 1;
	//foreach ($authors as $author) {
	//	echo $i . ". " . $author['counter'] . " " . $author['name'] . '<br>';
	//	$i++;
	//}

	//$authors = $wpdb->get_results("SELECT t.name AS name, COUNT(t.term_id) as counter FROM {$wpdb->posts} AS p
	//	INNER JOIN {$wpdb->prefix}votes AS vo ON (p.ID = vo.post)
	//	INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
	//	INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
	//	INNER JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id)
	//	WHERE tt.taxonomy = 'post_author'
	//	AND vo.usersinks != ''
	//	GROUP BY t.term_id
	//	ORDER BY counter DESC
	//	LIMIT 500;", ARRAY_A);

	//$i = 1;
	//foreach ($authors as $author) {
	//	echo $i . ". " . $author['counter'] . " " . $author['name'] . '<br>';
	//	$i++;
	//}

	/*
	$where = 'WHERE comment_approved = 1 AND user_id <> 0';
	$comment_counts = (array) $wpdb->get_results("SELECT user_id, COUNT( * ) AS total FROM {$wpdb->comments} {$where} GROUP BY user_id", object);
	$post_counts = (array) $wpdb->get_results("SELECT post_author, COUNT( * ) AS total FROM {$wpdb->posts}
		WHERE post_status = 'publish' AND post_author <> 0 GROUP BY post_author", object);
	$uid = $wpdb->get_col("SELECT $wpdb->users.ID FROM $wpdb->users");

	$countarray = array_fill(0,count($uid),0);
	$votecountarray = array_fill(0,count($uid),0);
	$sinkcountarray = array_fill(0,count($uid),0);
	$commentsarray = array_fill(0,count($uid),0);
	$postsarray = array_fill(0,count($uid),0);
	$namesarray = array_fill('',count($uid),0);
	$regtimesarray = array_fill('',count($uid),0);
	$urlsarray = array_fill('',count($uid),0);
	$affilsarray = array_fill('',count($uid),0);

	foreach ($uid as $i => $voter) {
		$votestr = $wpdb->get_var("SELECT votes FROM {$wpdb->prefix}votes_users WHERE user='{$voter}'");
		if ($votestr !== null) $votecountarray[$i] = count(explode(",",$votestr));
		$sinkstr = $wpdb->get_var("SELECT sinks FROM {$wpdb->prefix}votes_users WHERE user='{$voter}'");
		if ($sinkstr !== null) $sinkcountarray[$i] = count(explode(",",$sinkstr));
		$countarray[$i] = $votecountarray[$i] + $sinkcountarray[$i];
		$info = $wpdb->get_row("SELECT display_name as name, user_registered as reg, user_url as url FROM {$wpdb->prefix}users WHERE ID='{$voter}'");
		$namesarray[$i] = $info->name;
		$regtimesarray[$i] = strtotime($info->reg);
		$urlsarray[$i] = $info->url;
		$affilsarray[$i] = $wpdb->get_var("SELECT affiliation FROM ".$wpdb->prefix."votes_users WHERE user='{$voter}'");
		foreach ($comment_counts as $user_comment) {
			if ($user_comment->user_id == $voter) $commentsarray[$i] = $user_comment->total;
		}
		foreach ($post_counts as $user_post) {
			if ($user_post->post_author == $voter) $postsarray[$i] = $user_post->total;
		}
	}

	$sorttype = SORT_NUMERIC;
	$order = ($_GET['order'] != '') ? SORT_ASC : SORT_DESC;
	if ($_GET['sortby'] != '') {
		switch ($_GET['sortby']) {
			case 'name':
				$sorttype = SORT_STRING;
				$sortarray = $namesarray;
				$order = ($_GET['order'] != '') ? SORT_DESC : SORT_ASC;
				break;
			case 'votecount':
				$sortarray = $votecountarray;
				break;
			case 'sinkcount':
				$sortarray = $sinkcountarray;
				break;
			case 'comments':
				$sortarray = $commentsarray;
				break;
			case 'posts':
				$sortarray = $postsarray;
				break;
			case 'regtime':
				$sortarray = $regtimesarray;
				break;
			case 'affil':
				$sorttype = SORT_STRING;
				$sortarray = $affilsarray;
				$order = ($_GET['order'] != '') ? SORT_DESC : SORT_ASC;
				break;
			default:
				$sortarray = $countarray;
		}
	} else {
		$sortarray = $countarray;
	}
	array_multisort($sortarray, $order, $sorttype, $countarray, $votecountarray, $sinkcountarray, $uid,
		$commentsarray, $postsarray, $namesarray, $regtimesarray, $urlsarray, $affilsarray);
	$output = array('uid' => $uid, 'votecount' => $votecountarray, 'sinkcount' => $sinkcountarray,
		'count' => $countarray, 'comments' => $commentsarray, 'posts' => $postsarray,
		'name' => $namesarray, 'regtime' => $regtimesarray, 'url' => $urlsarray, 'affil' => $affilsarray);
	return $output;
	*/

}

//Used to sort votes for widgets
function SortVotes($agenda_time = 0, $type = '', $force_everyone = false, $post_ID = null) {
	global $wpdb, $today, $institution, $show_everyone, $ishome;

	//$limit = get_option('voteiu_limit');
	if ($agenda_time == 0) $agenda_time = $today;

	if ($type == '') {
		if (!isset($show_everyone)) {
			if (!isset($_COOKIE['show_everyone'])) {
				$show_everyone = false;
			} else $show_everyone = $_COOKIE['show_everyone'];
		}
	}
	if ($force_everyone) $show_everyone = true;
	$affil_id = $institution->ID;

	if ($post_ID) {
		$time_min = 0;
		$time_max = PHP_INT_MAX;
		$archived = false;
	} else {
		$reset_time = GetResetTime($agenda_time);
		$prev_offset = AgendaOffset('prev', 'co', $agenda_time);
		$next_offset = AgendaOffset('next', 'co', $agenda_time);
		//if ($ishome && $agenda_time < $reset_time) {
		//	$prev_offset++;
		//	$next_offset--;
		//}
		$disc_time = GetResetTime($agenda_time, 'start');
		$time_min = $reset_time - $prev_offset*86400;
		$time_max = $reset_time + $next_offset*86400;
		if ($institution->closevoting == 1) {
			$time_min -= 60*$institution->closedelay; 
			$time_max -= 60*$institution->closedelay; 
		}
		//echo date('H:i:s m/d/Y', $reset_time) . ' ' . date('H:i:s m/d/Y', $agenda_time) . ' ' . date('H:i:s m/d/Y', $time_min) . ' ' . date('H:i:s m/d/Y', $time_max);
		$archived = ($disc_time + $next_offset*86400 < time()) ? true : false;
	}

	//$archived = (date(" m/d/Y", $time_max) === date(" m/d/Y", time() + AgendaOffset('next', 'co', time())*86400)) ? false : true;
	$inst_query = ($show_everyone) ? '' :
		"AND (institution='{$affil_id}' OR (discussed = '1' AND institution='{$institution->ID}' AND votes = '' AND usersinks = ''))";
	if ($post_ID) $inst_query .= " AND post='{$post_ID}'";
	#$limit_str = ($institution->agendalimit != 0) ? "LIMIT ".$institution->agendalimit : "";
	#$posts = $wpdb->get_results("SELECT post, votes, usersinks, votetimes, sinktimes, lastvotetime, guests, guestsinks, guestvotetimes, guestsinktimes, discussed, institution FROM {$wpdb->prefix}votes
	#	WHERE ID <> ''{$inst_query} ORDER BY lastvotetime DESC {$limit_str}");
	#echo "SELECT v.post, v.votes, v.usersinks, v.votetimes, v.sinktimes, v.lastvotetime, v.guests, v.guestsinks, v.guestvotetimes, v.guestsinktimes, v.discussed, v.institution FROM {$wpdb->prefix}votes AS v
	#	JOIN {$wpdb->posts} AS p ON (p.ID = v.post)
	#	WHERE p.ID <> ''{$inst_query} AND (UNIX_TIMESTAMP(p.post_date) < {$time_max} OR v.lastvotetime > {$time_min}) ORDER BY v.lastvotetime DESC {$limit_str}";
	$posts_query = "SELECT v.post, v.votes, v.usersinks, v.votetimes, v.sinktimes, v.lastvotetime,
					v.guests, v.guestsinks, v.guestvotetimes, v.guestsinktimes, v.discussed,
					v.institution, v.presents FROM {$wpdb->prefix}votes AS v
		            JOIN {$wpdb->posts} AS p ON (p.ID = v.post)
		            WHERE p.ID <> '' AND UNIX_TIMESTAMP(p.post_date) < {$time_max}";
	if (!$institution->pushnotdiscussed || $show_everyone) $posts_query .= " AND v.lastvotetime > {$time_min}";
	$posts_query .= " {$inst_query} ORDER BY v.lastvotetime DESC";
	$posts = $wpdb->get_results($posts_query);

	$postarray = array();
	$sumarray = array();
	$votesarray = array();
	$sinksarray = array();
	$presentsumarray = array();
	$guestvotesarray = array();
	$guestsinksarray = array();
	$maxvotetimearray = array();
	$minvotetimearray = array();
	$votetimesarray = array();
	$sinktimesarray = array();
	$votefuturesarray = array();
	$sinkfuturesarray = array();
	$votepresentsarray = array();
	$sinkpresentsarray = array();
	$numcommentarray = array();
	$votenamesarray = array();
	$sinknamesarray = array();
	$voteinstsarray = array();
	$sinkinstsarray = array();
	$discussedarray = array();
	$futurearray = array();

	/*
	Limit, so as to reduce time taken for this script to run. If you want to raise the limit to maximum, use
	$limit = count($posts);
	*/
	//if ($limit == '') {
	//$limit = 100;
	//}

	//Ignore limit if post count is below limit
	//if ($limit > count($posts)) {
	//	$limit = count($posts);
	//}

	$initial = get_option('voteiu_initialoffset');

	foreach ($posts as &$post) { 
		//$post = $posts[$counter];
		$p_ID = $post->post;

		//Gets the votes
		$votes_new = array();
		$votetimes_new = array();
		$votefutures = array();
		$votepresents = array();
		$sinks_new = array();
		$sinktimes_new = array();
		$sinkfutures = array();
		$sinkpresents = array();
		$guests_new = array();
		$guesttimes_new = array();
		$guestsinks_new = array();
		$guestsinktimes_new = array();

		$presents_raw = ($post->presents != '') ? explode(',', $post->presents) : array();

		if ($post->votes != '') {
			$votes_raw = explode(',', $post->votes);
			$votetimes_raw = explode(',', $post->votetimes);
			foreach ($votetimes_raw as $i => $votetime) {
				if (($ishome && (($institution->pushnotdiscussed && !$post->discussed) ||
					($post->lastvotetime >= $time_min && $institution->showhistoricalvotes))
					&& $post->institution == $institution->ID) ||
					($votetime >= $time_min && ($ishome || $votetime <= $time_max))) {
					array_push($votes_new, $votes_raw[$i]);
					array_push($votetimes_new, $votetime);
					array_push($votefutures, ($votetime > $time_max) ? 1 : 0);
					if (array_search($votes_raw[$i], $presents_raw) !== false &&
						$post->institution == $institution->ID && $votetime > $time_min) {
						array_push($votepresents, 1);
					} else {
						array_push($votepresents, 0);
					}
				}
			}
			array_multisort($votes_new, $votefutures, $votetimes_new, $votepresents);
			$votecount = count($votes_new);
			$votepresentcount = array_sum($votepresents);
			$votes_str = implode(",", $votes_new);
		} else {
			$votecount = 0;
			$votepresentcount = 0;
			$votes_str = '';
		}
		if ($post->usersinks != '') {
			$sinks_raw = explode(',', $post->usersinks);
			$sinktimes_raw = explode(',', $post->sinktimes);
			foreach ($sinktimes_raw as $i => $sinktime) {
				if (($ishome && (($institution->pushnotdiscussed && !$post->discussed) ||
					($post->lastvotetime >= $time_min && $institution->showhistoricalvotes))
					&& $post->institution == $institution->ID) ||
					($sinktime >= $time_min && ($ishome || $sinktime <= $time_max))) {
					array_push($sinks_new, $sinks_raw[$i]);
					array_push($sinktimes_new, $sinktime);
					array_push($sinkfutures, ($sinktime > $time_max) ? 1 : 0);
					if (array_search($sinks_raw[$i], $presents_raw) !== false && $post->institution == $institution->ID) {
						array_push($sinkpresents, 1);
					} else {
						array_push($sinkpresents, 0);
					}
				}
			}
			array_multisort($sinks_new, $sinktimes_new, $sinkfutures, $sinkpresents);
			$sinkcount = count($sinks_new);
			$sinkpresentcount = array_sum($sinkpresents);
			$sinks_str = implode(",", $sinks_new);
		} else {
			$sinkcount = 0;
			$sinkpresentcount = 0;
			$sinks_str = '';
		}
		if ($post->guests != '') {
			$guests_raw = explode(',', $post->guests);
			$guesttimes_raw = explode(',', $post->guestvotetimes);
			foreach ($guesttimes_raw as $i => $guesttime) {
				if (($ishome && (($institution->pushnotdiscussed && !$post->discussed) || $institution->showhistoricalvotes) && $post->institution == $institution->ID) ||
					($guesttime >= $time_min && ($ishome || $guesttime <= $time_max))) {
					array_push($guests_new, $guests_raw[$i]);
					array_push($guesttimes_new, $guesttime);
				}
			}
			array_multisort($guests_new, $guesttimes_new);
			$guestvotecount = count($guests_new);
		} else {
			$guestvotecount = 0;
		}
		if ($post->guestsinks != '') {
			$guestsinks_raw = explode(',', $post->guestsinks);
			$guestsinktimes_raw = explode(',', $post->guestsinktimes);
			foreach ($guestsinktimes_raw as $i => $guestsinktime) {
				if (($ishome && (($institution->pushnotdiscussed && !$post->discussed) || $institution->showhistoricalvotes) && $post->institution == $institution->ID) ||
					($guestsinktime >= $time_min && ($ishome || $guestsinktime <= $time_max))) {
					array_push($guestsinks_new, $guestsinks_raw[$i]);
					array_push($guestsinktimes_new, $guestsinktime);
				}
			}
			array_multisort($guestsinks_new, $guestsinktimes_new);
			$guestsinkcount = count($guestsinks_new);
		} else {
			$guestsinkcount = 0;
		}
		if ($votecount + $sinkcount + $guestvotecount + $guestsinkcount == 0) {
		    if ($post->institution != $institution->ID) continue;
			if ($post->discussed == 0) continue;
			if ($post->lastvotetime < $time_min || (!$ishome && $post->lastvotetime > $time_max)) continue;
		}
		$voteinfos = ($votes_str == '') ? array() : $wpdb->get_results("SELECT ID, display_name FROM {$wpdb->prefix}users WHERE ID IN ({$votes_str}) ORDER BY ID");
		$sinkinfos = ($sinks_str == '') ? array() : $wpdb->get_results("SELECT ID, display_name FROM {$wpdb->prefix}users WHERE ID IN ({$sinks_str}) ORDER BY ID");
		$inst_name = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}votes_institutions WHERE ID = '{$post->institution}'");
		$voteinsts = (count($voteinfos) > 0) ? array_fill(0, count($voteinfos), $inst_name) : array();
		$sinkinsts = (count($sinkinfos) > 0) ? array_fill(0, count($sinkinfos), $inst_name) : array();

		$votenames = array();
		foreach ($voteinfos as $voteinfo) {
			$voter_url = get_author_posts_url($voteinfo->ID);
			$votenames[] = "<a href='{$voter_url}'>{$voteinfo->display_name}</a>";
		}
		$sinknames = array();
		foreach ($sinkinfos as $sinkinfo) {
			$voter_url = get_author_posts_url($sinkinfo->ID);
			$sinknames[] = "<a href='{$voter_url}'>{$sinkinfo->display_name}</a>";
		}
		//foreach ($voteinsts as $v => $voteinst) {
		//	$voteinsts[$v] = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}votes_institutions WHERE ID = '{$voteinst}'");
		//}
		//foreach ($sinkinsts as $v => $sinkinst) {
		//	$sinkinsts[$v] = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}votes_institutions WHERE ID = '{$sinkinst}'");
		//}

		//The mathematics
		$votesum = $votecount - $sinkcount + $guestvotecount - $guestsinkcount + $initial;
		$presentsum = $votepresentcount + $sinkpresentcount;

		$first_vote_times = array();
		if (in_array(1, $votepresents)) {
			foreach ($votetimes_new as $i => $vt) {
				if ($votepresents[$i] == 1) $first_vote_times[] = $vt;
			}
		}
		else $first_vote_times = array_merge($first_vote_times, $votetimes_new);
		if (in_array(1, $sinkpresents)) {
			foreach ($sinktimes_new as $i => $vt) {
				if ($sinkpresents[$i] == 1) $first_vote_times[] = $vt;
			}
		}
		else $first_vote_times = array_merge($first_vote_times, $sinktimes_new);
		$first_vote_time = (count($first_vote_times) > 0) ? min($first_vote_times) : 0;

		$last_vote_time = $post->lastvotetime;
		//$last_vote_time = max(array_merge($votetimes_new, $sinktimes_new));
		$pos = -1;
		foreach ($postarray as $i => $p) {
			if ($p[0] == $p_ID) {
				$pos = $i;
				break;
			}
		}
		if ($pos == -1) {
			array_push($postarray, array($p_ID));
			array_push($sumarray, array($votesum));
			array_push($votesarray, array($votecount));
			array_push($sinksarray, array($sinkcount));
			array_push($guestvotesarray, array($guestvotecount));
			array_push($guestsinksarray, array($guestsinkcount));
			array_push($maxvotetimearray, array($last_vote_time));
			array_push($minvotetimearray, array($first_vote_time));
			array_push($presentsumarray, array($presentsum));
			array_push($votetimesarray, $votetimes_new);
			array_push($sinktimesarray, $sinktimes_new);
			array_push($votefuturesarray, $votefutures);
			array_push($sinkfuturesarray, $sinkfutures);
			array_push($votepresentsarray, $votepresents);
			array_push($sinkpresentsarray, $sinkpresents);
			array_push($votenamesarray, $votenames);
			array_push($sinknamesarray, $sinknames);
			array_push($voteinstsarray, $voteinsts);
			array_push($sinkinstsarray, $sinkinsts);
			$comments = get_comment_count($p_ID);
			array_push($numcommentarray, array($comments['approved']));
			if ($post->institution == $institution->ID) {
				array_push($discussedarray, $post->discussed);
			} else {
				array_push($discussedarray, 0);
			}
			array_push($futurearray, ($ishome && $first_vote_time > $time_max) ? 1 : 0);
		} else {
			$sumarray[$pos][0] += $votesum;
			$votesarray[$pos][0] += $votecount;
			$sinksarray[$pos][0] += $sinkcount;
			$guestvotesarray[$pos][0] += $guestvotecount;
			$guestsinksarray[$pos][0] += $guestsinkcount;
			$presentsumarray[$pos][0] += $presentsum;
			$maxvotetimearray[$pos][0] = max($maxvotetimearray[$pos][0], $last_vote_time);
			$minvotetimearray[$pos][0] = max($minvotetimearray[$pos][0], $first_vote_time);
			$votetimesarray[$pos] = array_merge($votetimesarray[$pos], $votetimes_new);
			$sinktimesarray[$pos] = array_merge($sinktimesarray[$pos], $sinktimes_new);
			$votefuturesarray[$pos] = array_merge($votefuturesarray[$pos], $votefutures);
			$sinkfuturesarray[$pos] = array_merge($sinkfuturesarray[$pos], $sinkfutures);
			$votepresentsarray[$pos] = array_merge($votepresentsarray[$pos], $votepresents);
			$sinkpresentsarray[$pos] = array_merge($sinkpresentsarray[$pos], $sinkpresents);
			$votenamesarray[$pos] = array_merge($votenamesarray[$pos], $votenames);
			$sinknamesarray[$pos] = array_merge($sinknamesarray[$pos], $sinknames);
			$voteinstsarray[$pos] = array_merge($voteinstsarray[$pos], $voteinsts);
			$sinkinstsarray[$pos] = array_merge($sinkinstsarray[$pos], $sinkinsts);
			if ($post->institution == $institution->ID) $discussedarray[$pos] = $post->discussed;
			$futurearray[$pos] = ($ishome && $first_vote_time > $time_max) ? 1 : 0;
		}
	}
	//if (!$archived) {
	//	array_multisort($sumarray, SORT_DESC, $numcommentarray, SORT_DESC, $maxvotetimearray, SORT_ASC,
	//		$postarray, $votesarray, $sinksarray, $votenamesarray, $sinknamesarray, $voteinstsarray, $sinkinstsarray, $guestvotesarray, $guestsinksarray, $votetimesarray, $sinktimesarray, $discussedarray);
	//} else {
	array_multisort($discussedarray, SORT_DESC, $futurearray, SORT_ASC, $presentsumarray, SORT_DESC, 
			$sumarray, SORT_DESC,
			$numcommentarray, SORT_DESC, $maxvotetimearray, SORT_ASC, $minvotetimearray,
			$postarray, $votesarray, $sinksarray, $votenamesarray, $sinknamesarray, $voteinstsarray,
			$sinkinstsarray, $guestvotesarray, $guestsinksarray, $votetimesarray, $sinktimesarray,
			$votefuturesarray, $sinkfuturesarray, $votepresentsarray, $sinkpresentsarray);
	//}
	$output = array('pid' => $postarray, 'sum' => $sumarray, 'votes' => $votesarray, 'sinks' => $sinksarray, 'ncomments' => $numcommentarray,
		'votenames' => $votenamesarray, 'sinknames' => $sinknamesarray, 'voteinsts' => $voteinstsarray, 'sinkinsts' => $sinkinstsarray,
		'guestvotes' => $guestvotesarray, 'guestsinks' => $guestsinksarray, 'minvotetime' => $minvotetimearray,
		'votetimes' => $votetimesarray, 'sinktimes' => $sinktimesarray, 'votefutures' => $votefuturesarray, 'sinkfutures' => $sinkfuturesarray,
		'votepresents' => $votepresentsarray, 'sinkpresents' => $sinkpresentsarray,
		'future' => $futurearray, 'discussed' => $discussedarray, 'archived' => $archived);
	foreach ($output['votetimes'] as $i => $votetimes) {
		if ($output['votes'][$i][0] == 0) continue;
		array_multisort($output['votepresents'][$i], SORT_DESC, 
			$output['votetimes'][$i], SORT_ASC,
			$output['votenames'][$i], $output['voteinsts'][$i], $output['votefutures'][$i]);
		if (count($output['votetimes'][$i]) != count($output['votenames'][$i])) {
			print_r( $output['voteinsts'][$i] );
			print_r( $output['votetimes'][$i] );
			print_r( $output['votenames'][$i] );
		}
	}
	foreach ($output['sinktimes'] as $i => $sinktimes) {
		if ($output['sinks'][$i][0] == 0) continue;
		array_multisort($output['sinkpresents'][$i], SORT_DESC,
			$output['sinktimes'][$i], SORT_ASC,
			$output['sinknames'][$i], $output['sinkinsts'][$i], $output['sinkfutures'][$i]);
	}
	return $output;
}

function CountMostVoted($a, $include_others = true) {
	if ($include_others) {
		return (isset($a['pid'])) ? count($a['pid']) : 0;
	} else {
		$count = 0;
		foreach ($a['discussed'] as $d => $discussed) {
		   if ($discussed != 0 && $a['future'][$d] == 0) $count++;
		}
		return $count;
	}
}

function MostVotedAllTime_Cron($a = '', $incl_abstracts = false, $incl_meta = false, $showing_everyone = false) {
	global $institution;
	if ($a == '') $a = SortVotes();

	$email_str = '';
	if (!$institution->announceplain) {
		$email_str = "<head><style type=\"text/css\">.par p {margin-top: 0.4em; margin-bottom:0.4em;}</style>\n<div class=\"par\"></head>\n";
	}
	if (!$showing_everyone) {
		$email_str .= "<strong>Today's Discussion Agenda:</strong>\n<br><br><div class=\"par\">";
	} else {
		$email_str .= "<strong>No one from ".$institution->name." voted today!</strong>\n<br><br><div class=\"par\">";
		$email_str .= "Here's the top 5 papers voted on by other institutions:\n<br><br><div class=\"par\">";
	}
	$rows = 0;
    $i = 0;
	if ($institution->announceplain) $email_str = str_replace('<br><br>', "\n", $email_str);
	if ($institution->announceplain) $email_str = strip_tags($email_str);

    while ($rows < get_option('voteiu_widgetcount') && (!$showing_everyone || $rows < 5) && ($rows <= $institution->agendalimit || $institution->agendalimit == 0)) {
    	if ($a['pid'][$i][0] != '') {
    		$postdat = get_post($a['pid'][$i][0]);
    		if (!empty($postdat)) {
    		   	$rows++;
    
				if ($institution->announceplain) {
					$email_str .= "\n".$a['sum'][$i][0]." ".PluralizeWord($a['sum'][$i][0], 'votes, ', 'vote, ').
						"{$postdat->post_title}\n";
					$email_str .= "({$postdat->guid})\n";
				} else {
					$email_str .= "\n<p>".$a['sum'][$i][0]." ".PluralizeWord($a['sum'][$i][0], 'votes, ', 'vote, ').
						"<a href=\"{$postdat->guid}\"><strong>{$postdat->post_title}</strong></a></p>\n";
				}
				if ($incl_meta) {
					//$email_str .= "<p>".get_post_meta($a['pid'][$i][0], 'wpo_authors', true)."</p>\n";
					$email_str .= get_post_meta_data(array($postdat))[0] . "\n";
				}
				if ($incl_abstracts) {
					$email_str .= "<p>".html_entity_decode($postdat->post_content)."</p>\n";
				}
				$email_str .= '<br>';
    		}
    	}
    	if ($i < count($a['pid'])) {
    		$i++;
    	} else {
			break; //exit the loop
		}
	}
	
	$email_str .= "<p>If you plan to attend and have not yet read the papers in the agenda, please quickly skim them and cast your votes now!</p></div>";
	if ($institution->announceplain) $email_str = str_replace('<br>', "\n", $email_str);
	if ($institution->announceplain) $email_str = str_replace(array('<p>', '</p>', '</div>'), '', $email_str);
	return $email_str;
}

function GetVoteList($post_ID) {
	global $wpdb, $institution;
	
	$p_ID = $wpdb->escape($post_ID);

	$a = SortVotes(0, '', true, $post_ID);

	$show_everyone = true;

	$i = 0;
	if (count($a['votes']) > 0) { 
		echo "<div class='postinfo' style='padding: 5px; margin: 0;'>";
		$voters_string = '<div><span class="votedon">';
		if ($a['votes'][$i][0] + $a['guestvotes'][$i][0] > 0) {
			$voters_string .= '<b>' . array_sum($a['votes'][$i]) . ' ';
			$voters_string .= 
				"<img src='https://voxcharta.org/wp-content/plugins/vote-it-up/thumbup_sm.png' style='height: 12px; width: 12px;'>'s:</b> ";
		}
		if ($a['votes'][$i][0] > 0) {
			for ($j = 0; $j < count($a['votenames'][$i]); $j++) {
				$vote_time = "Vote cast " . date("g:ia, m/d/Y", $a['votetimes'][$i][$j]);
				$voters_string .= "<span title='{$vote_time}'>";
				if ($a['votepresents'][$i][$j] == 1) {
					$voters_string .= "<img src='https://voxcharta.org/wp-content/plugins/vote-it-up/present_sm.png' style='height: 12px; width: 12px;'> ";
				}
				$voters_string .= $a['votenames'][$i][$j];
				if ($show_everyone) $voters_string .= ' (' . $a['voteinsts'][$i][$j] . ')';
				$voters_string .= "</span>";
				if ($j != count($a['votenames'][$i]) - 1) $voters_string .= ', ';
			}
		}
		if ($a['guestvotes'][$i][0] > 0) {
			if ($a['votes'][$i][0] > 1) $voters_string .= ',';
			if ($a['votes'][$i][0] > 0) $voters_string .= ' and ';
			$guest_string = ($a['guestvotes'][$i][0] > 1) ? ' guests' : ' guest';
			$voters_string = $voters_string.$a['guestvotes'][$i][0].$guest_string;
		}
		if ($a['votes'][$i][0] + $a['guestvotes'][$i][0] > 0) $voters_string .= "<br>";
		if ($a['sinks'][$i][0] + $a['guestsinks'][$i][0] > 0) {
			$voters_string .= '<b>' . array_sum($a['sinks'][$i]) . ' ';
			$voters_string .= 
				"<img src='https://voxcharta.org/wp-content/plugins/vote-it-up/thumbdown_sm.png' style='height: 12px; width: 12px;'>'s:</b> ";
		}
		if ($a['sinks'][$i][0] > 0) {
			for ($j = 0; $j < count($a['sinknames'][$i]); $j++) {
				$sink_time = "Vote cast " . date("g:ia, m/d/Y", $a['sinktimes'][$i][$j]);
				$voters_string .= "<span title='{$sink_time}'>";
				$voters_string .= $a['sinknames'][$i][$j];
				if ($show_everyone) $voters_string .= ' (' . $a['sinkinsts'][$i][$j] . ')';
				$voters_string .= "</span>";
				if ($j != count($a['sinknames'][$i]) - 1) $voters_string .= ', ';
			}
		}
		if ($a['guestsinks'][$i][0] > 0) {
			if ($a['sinks'][$i][0] > 1) $voters_string .= ',';
			if ($a['sinks'][$i][0] > 0) $voters_string .= ' and ';
			$guest_string = ($a['guestsinks'][$i][0] > 1) ? ' guests' : ' guest';
			$voters_string = $voters_string.$a['guestsinks'][$i][0].$guest_string;
		}
		if ($a['sinks'][$i][0] + $a['guestsinks'][$i][0] > 0) $voters_string .= "&nbsp;&nbsp;";
		$voters_string .= '</div>';
		echo "{$voters_string}</div>";
	}
}

//Displays the widget
function MostVotedAllTime_Widget($a = '') {
	if ($a == '') $a = SortVotes();
	//Before

?>
<div class="votewidget">
<div class="title">Most Voted</div>
<?php
	$rows = 0;

//Now does not include deleted posts
$i = 0;
while ($rows < get_option('voteiu_widgetcount')) {
	if ($a['pid'][$i][0] != '') {
		$postdat = get_post($a['pid'][$i][0]);
		if (!empty($postdat)) {
		   	$rows++;

			if (round($rows / 2) == ($rows / 2)) {
				echo '<div class="fore">';
			} else {
				echo '<div class="back">';
			}
			echo '<div class="votecount">'.$a['sum'][$i][0].' '.PluralizeWord($a['sum'][$i][0], 'votes', 'vote').' </div><div><a href="'.$postdat->guid.'" title="'.$postdat->post_title.'">'.$postdat->post_title.'</a></div>';
			echo '</div>';
		}
	}
	if ($i < count($a['pid'])) {
	$i++;
	} else {
	break; //exit the loop
	}
}

//End
?>

</div>
<?php

}

//Displays the widget optimised for sidebar
function MostVotedAllTime_SidebarWidget() {
	$a = SortVotes();
	//Before

?>
<div class="votewidget">
<?php
	$rows = 0;

//Now does not include deleted posts
$i = 0;
while ($rows < get_option('voteiu_widgetcount')) {
	if ($a[0][$i][0] != '') {
			$postdat = get_post($a[0][$i][0]);
		if (!empty($postdat)) {
			$rows++;
				if (round($rows / 2) == ($rows / 2)) {
					echo '<div class="fore">';
				} else {
					echo '<div class="back">';
				}
				echo '<div class="votecount" style="width: 1em; color: #555555; font-weight: bold;">'.$a[1][$i][0].' </div><div><a href="'.$postdat->guid.'" title="'.$postdat->post_title.'">'.$postdat->post_title.'</a></div>';
				echo '</div>';
		}
	}
	if ($i < count($a[0])) {
	$i++;
	} else {
	break; //exit the loop
	}
}

//End
?>

</div>
<?php

}

//For those particular with English
function PluralizeWord($number, $plural, $singular) {
	if ($number == 1) {
		return $singular;
	} else {
		return $plural;
	}
}

//Not used yet
function IsExcluded($id) {
	global $excludedid;
	$clean = str_replace("\r", "", $excludedid);
	$excluded = explode("\n", $clean);
}

function GetResetTime($time, $when = 'stop') {
	global $institution, $grt_discussiondays, $grt_resettimes, $grt_disctimes;

	if (!isset($grt_discussiondays)) {
		$grt_discussiondays = explode(",",$institution->normaldays);
		$grt_resettimes = explode(",",$institution->resettime);
		if (count($grt_resettimes) < count($grt_discussiondays)) {
			$grt_resettimes = array_fill(0, count($grt_discussiondays), $grt_resettimes[0]);
		}
		$grt_disctimes = explode(",",$institution->discussiontime);
		if (count($grt_disctimes) < count($grt_discussiondays)) {
			$grt_disctimes = array_fill(0, count($grt_discussiondays), $grt_disctimes[0]);
		}
	}
	$myday = date("D", $time);
	$mdy = date(" m/d/Y", $time);
	//$search_result = array_search($myday, $grt_discussiondays);
	//$year = idate("Y", $time);
	//$month = idate("m", $time);
	//$day = idate("d", $time);
	//if ($search_result !== false) return strtotime($grt_resettimes[$search_result].$mdy);
	$j = 0;
	foreach ($grt_discussiondays as $i => $discussionday) {
		if ($discussionday === $myday) {
			break;
			//return strtotime($grt_resettimes[$i].$mdy);
		}
		$j++;
	}
	if ($j >= count($grt_resettimes)) $j = 0;
	//$itime = explode(':',$grt_resettimes[$i]);
	//return mktime($itime[0], $itime[1], 0, $month, $day, $year);
	if ($when == 'stop') {
		return strtotime($grt_resettimes[$j].$mdy);
	} else return strtotime($grt_disctimes[$j].$mdy);
}

/*function GetDiscussionTime($time, $reset_time) {
	global $institution;

	$discussiondays = explode(",",$institution->normaldays);
	$myday = date("D", $time);
	$discussiontimes = explode(",",$institution->discussiontime);
	if (count($discussiontimes) < count($discussiondays)) {
		$discussiontimes = array_fill(0, count($discussiondays), $discussiontimes[0]);
	}
	foreach ($discussiondays as $i => $discussionday) {
		if ($discussionday == $myday) {
			return strtotime($discussiontimes[$i].date(" m/d/Y", $time));
		}
	}

	return strtotime($reset_time.date(" m/d/Y", $time));
}*/

?>
