<?php
/*
Plugin Name: Vote It Up
Plugin URI: http://www.tevine.com/projects/voteitup/
Description: Vote It Up enables bloggers to add voting functionality to their posts.
Version: 1.1.1
Author: Nicholas Kwan (multippt)
Author URI: http://www.tevine.com/
*/

/*  Copyright 2007  Nicholas Kwan  (email : ready725@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Installs plugin options and database
include_once('voteinstall.php');
VoteItUp_InstallOptions();
VoteItUp_dbinstall();
//register_activation_hook( basename(__FILE__), 'VoteItUp_dbinstall'); //doesn't seem to work at times..., but I'll put it here

//External configuration file
@include_once('compat.php');

//Declare paths used by plugin
function VoteItUp_Path() {
global $voteitupint_path;
if ($voteitupint_path == '') {
	$dir = dirname(__FILE__);
	$dir = str_replace("\\", "/", $dir); //For Linux
	return $dir;
} else {
	return $voteitup_path;
}
}

function VoteItUp_ExtPath() {
global $voteitup_path;
if ($voteitup_path == '') {
	$dir = VoteItUp_Path();
	$base = ABSPATH;
	$base = str_replace("\\", "/", $base);
	$edir = str_replace($base, "", $dir);
	$edir = "https://".$_SERVER['HTTP_HOST']."/".$edir;
	$edir = str_replace("\\", "/", $edir);
	return $edir;
} else {
	return $voteitup_path;
}
}

//Includes other functions of the plugin
include_once(VoteItUp_Path()."/votingfunctions.php");
include_once(VoteItUp_Path()."/skin.php");

//Installs configuration page
include("voteconfig.php");

function VoteItUp_header() {
$voteiu_skin = get_option('voteiu_skin');
//If no skin is selected, only include default theme/script to prevent conflicts.
if ($voteiu_skin == '') {
?>
<link rel="stylesheet" href="<?php echo VoteItUp_ExtPath(); ?>/votestyles.css" type="text/css" />
<script type="text/javascript" src="<?php echo VoteItUp_ExtPath(); ?>/voterajax.js"></script>
<?php
} else {
	LoadSkinHeader($voteiu_skin);
}
/* These are things always used by voteitup */
?>
<link rel="stylesheet" href="<?php echo VoteItUp_ExtPath(); ?>/voteitup.css" type="text/css" />
<script type="text/javascript" src="<?php echo VoteItUp_ExtPath(); ?>/userregister.js"></script>
<?php
}

function VoteItUp_footer() {
?><div class="regcontainer" id="regbox">
<div class="regcontainerbackground">&nbsp;</div>
<div class="regpopup">
<a href="javascript:regclose();" title="Close"><img class="regclosebutton" width="16" height="16" src="<?php echo VoteItUp_ExtPath(); ?>/closebutton.png" /></a>
<h3>You need to log in to vote</h3>

<p>The blog owner requires users to be <a href="<?php echo get_option('siteurl').'/wp-login.php'; ?>" title="Log in">logged in</a> to be able to vote for this post.</p>
<p>Alternatively, if you do not have an account yet you can <a href="<?php echo get_option('siteurl').'/wp-login.php?action=register'; ?>" title="Register account">create one here</a>.</p>
<p class="regsmalltext">Powered by <a href="http://www.tevine.com/projects/voteitup/" title="Vote It Up plugin">Vote It Up</a></p></div></div>
<?php
}

//Displays the widget, theme supported
function MostVotedAllTime($a = '', $skinname = '', $mode = '') {
$voteiu_skin = get_option('voteiu_skin');
$tempvar = $voteiu_skin;
if ($skinname != '') {
$tempvar = $skinname;
}
if ($tempvar == '' | $tempvar == 'default') {
if ($mode == 'sidebar') {
MostVotedAllTime_SidebarWidget();
} else {
MostVotedAllTime_Widget($a); //Use default bar
}
} else {
if (!LoadSkinWidget($a, $tempvar, $mode)) {
if ($mode == 'sidebar') {
MostVotedAllTime_SidebarWidget();
} else {
MostVotedAllTime_Widget($a); //Use default bar
}
}
}

}

function UserRecord() {
	global $wpdb, $current_user;

	get_currentuserinfo();
	$a = UserPosts($current_user->ID);
?>
	<div class="votewidget_skin">
	<?php
	$nvotes = count($a[0]);
	?>
	Complete list of papers you have voted on while using this website.
	<?php

	for ($i = 0; $i < $nvotes; $i++) {
		$postdat = get_post($a[0][$i][0]);
		if (!empty($postdat)) {
			$textcolor = ($a[2][$i][0]) ? "#009900" : "#990000";
			DisplayPost($postdat, null, array(), '', false, '', true, true, true, false, false);
		//	echo '<tr><td>'.date('m/d/Y',$a[1][$i][0]).'</td><td><a style="color:'.$textcolor.'" href="'.$postdat->guid.'" rel="bookmark" title="'.htmlentities($postdat->post_title).'">'.$postdat->post_title.'</a></td></tr>';
		}
	}

	?>

	</div>
	<?php 
}

function TopPosts() {
	global $wpdb, $current_user;

	get_currentuserinfo();
	$a = UserPosts($current_user->ID);
	$datelimit = time() - 30*86400;
	$posts = $wpdb->get_col("
		SELECT post FROM (
			SELECT p.ID AS post,
			GROUP_CONCAT(if (v.votes ='', null, v.votes) SEPARATOR ',') AS votes
			FROM {$wpdb->prefix}posts AS p
			INNER JOIN {$wpdb->prefix}votes AS v ON (p.ID = v.post)
			WHERE (UNIX_TIMESTAMP(p.post_date) > '$datelimit'
			AND (v.votes <> ''))
			GROUP BY v.post
		) AS raw
		ORDER BY (LENGTH(votes) - LENGTH(REPLACE(votes,',','')) + 1) DESC LIMIT 100");
?>
	<div class="votewidget_skin">
	<h1 style="font-size: 200%"><?php  printf(__('Top 100 Papers of the Last 30 Days', 'arclite'), get_the_time(__('F jS, Y','arclite')));  ?></h1>
	<?php

	foreach ($posts as $post) {
		$postdat = get_post($post);
		if (!empty($postdat)) {
			$textcolor = ($a[2][$i][0]) ? "#009900" : "#990000";
			DisplayPost($postdat, null, array(), '', false, '', true, true, true, false, false);
		//	echo '<tr><td>'.date('m/d/Y',$a[1][$i][0]).'</td><td><a style="color:'.$textcolor.'" href="'.$postdat->guid.'" rel="bookmark" title="'.htmlentities($postdat->post_title).'">'.$postdat->post_title.'</a></td></tr>';
		}
	}

	?>

	</div>
	<?php 
}

function VoterStats($a = '') {
	global $wpdb, $current_user;

	if ($a == '') $a = VoterSort();
	$page_address = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	$address_parts = explode('?', $page_address);
	$page_address = $address_parts[0];	
?>
	<div class="votewidget_skin">
	<table class="votestats">
	<tr>
	<td><b>Display Name</b><span>&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=name';?>">▾</a></span></td>
	<td><b>Votes</b><span>&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address;?>">▾</a></span></td>
	<td><img src="<?php get_option("siteurl") ?>/wp-content/plugins/vote-it-up/thumbup.png"><span>&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=votecount';?>">▾</a></span></td>
	<td><img src="<?php get_option("siteurl") ?>/wp-content/plugins/vote-it-up/thumbdown.png"><span>&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=sinkcount';?>">▾</a></span></td>
	<td><img src="<?php get_option("siteurl") ?>/comment_icon_big.png"><span>&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=comments';?>">▾</a></span></td>
	<td><b>Posts</b><span>&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=posts';?>">▾</a></span></td>
	<td><b>User Since</b><span>&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=regtime';?>">▾</a></span></td>
	<td><b>Affiliation</b><span>&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=affil';?>">▾</a></span></td>
	</tr>
	<?php

	$i = 0;
	while (1) {
		$name = $a['name'][$i];
		if ($name != '') {
			if ($a['url'][$i] != '') $name = '<a href="'.$a['url'][$i].'">'.$name.'</a>';
			if ($current_user->ID == $a['uid'][$i]) $name = '<b>'.$name.'</b>';
			echo '<tr><td>'.$name.'</td><td>'.$a['count'][$i].'</td><td>'.$a['votecount'][$i].'</td><td>'.$a['sinkcount'][$i].
				'</td><td>'.$a['comments'][$i].'</td><td>'.$a['posts'][$i].'</td><td>'.date('m/d/Y', $a['regtime'][$i]).'</td><td>'.$a['affil'][$i].'</td></tr>';
		}
		$i++;
		if ($i >= count($a['uid'])) {
			break; //exit the loop
		}
	}

	?>
	<th colspan='8'></th>
	<tr>
	<td><b><em>Totals</em></b></td>
	<td><b><?php echo array_sum($a['count']); ?></b></td>
	<td><b><?php echo array_sum($a['votecount']); ?></b></td>
	<td><b><?php echo array_sum($a['sinkcount']); ?></b></td>
	<td><b><?php echo array_sum($a['comments']); ?></b></td>
	<td><b><?php echo array_sum($a['posts']); ?></b></td>
	<td><b>-</b></td>
	<td><b>-</b></td>
	</tr>
	</table>

	</div>
	<?php
}

function AuthorStats($a = '') {
	global $wpdb, $current_user;

	if ($a == '') $a = AuthorSort();
	$page_address = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	$address_parts = explode('?', $page_address);
	$page_address = $address_parts[0];	
?>
	<div class="votewidget_skin">
	<table class="votestats">
	<tr>
	<td><b>Display Name</b><span>&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=name';?>">▾</a></span></td>
	<td><b>Votes</b><span>&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address;?>">▾</a></span></td>
	<td><img src="<?php get_option("siteurl") ?>/wp-content/plugins/vote-it-up/thumbup.png"><span>&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=votecount';?>">▾</a></span></td>
	<td><img src="<?php get_option("siteurl") ?>/wp-content/plugins/vote-it-up/thumbdown.png"><span>&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=sinkcount';?>">▾</a></span></td>
	</tr>
	<?php

	$i = 0;
	while (1) {
		$name = $a['name'][$i];
		if ($name != '') {
			if ($a['slug'][$i] != '') $name = '<a href="https://voxcharta.org/post_author/'.$a['slug'][$i].'">'.$name.'</a>';
			echo '<tr><td>'.$name.'</td><td>'.$a['count'][$i].'</td><td>'.$a['votecount'][$i].'</td><td>'.$a['sinkcount'][$i].
				'</td></tr>';
		}
		$i++;
		if ($i >= 100) {
			break; //exit the loop
		}
	}

	?>
	<th colspan='8'></th>
	<tr>
	<td><b><em>Totals</em></b></td>
	<td><b><?php echo array_sum($a['count']); ?></b></td>
	<td><b><?php echo array_sum($a['votecount']); ?></b></td>
	<td><b><?php echo array_sum($a['sinkcount']); ?></b></td>
	</tr>
	</table>

	</div>
	<?php
}

function InstitutionStats() {
	global $wpdb, $institution;

	$institutions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}votes_institutions");
	$page_address = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	$address_parts = explode('?', $page_address);
	$page_address = $address_parts[0];	
?>
	<div class="votewidget_skin" style="overflow-x: auto;">
	<table style="width: 100%">
	<tr>
	<td style="white-space: nowrap;"><b>Name</b><span style="float: right;">&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=name';?>">▾</a></span></td>
	<td style="white-space: nowrap;"><b>Users</b><span style="float: right;">&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=numusers';?>">▾</a></span></td>
	<td style="white-space: nowrap;"><b>Votes</b><span style="float: right;">&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address;?>">▾</a></span></td>
	<td style="white-space: nowrap;"><img src="<?php get_option("siteurl") ?>/wp-content/plugins/vote-it-up/thumbup.png"><span style="float: right;">&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=votecount';?>">▾</a></span></td>
	<td style="white-space: nowrap;"><img src="<?php get_option("siteurl") ?>/wp-content/plugins/vote-it-up/thumbdown.png"><span style="float: right;">&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=sinkcount';?>">▾</a></span></td>
	<td style="white-space: nowrap;"><img src="<?php get_option("siteurl") ?>/comment_icon_big.png"><span style="float: right;">&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=comments';?>">▾</a></span></td>
	<td style="white-space: nowrap;"><b>Posts</b><span style="float: right;">&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=posts';?>">▾</a></span></td>
	<td style="white-space: nowrap;"><b>First Discussion</b><span style="float: right;">&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=firstdisc';?>">▾</a></span></td>
	<td style="white-space: nowrap;"><b>Last Discussion</b><span style="float: right;">&nbsp;<a style = "text-decoration: none;" href="<?php echo $page_address . '?sortby=lastdisc';?>">▾</a></span></td>
	</tr>
	<?php

	$names
		= $urls
		= $tot_count
		= $num_users
		= $tot_count
		= $vote_count
		= $sink_count
		= $comments
		= $posts
		= $first_disc
		= $last_disc
		= array();

	$alluids = $wpdb->get_col("SELECT u.ID FROM {$wpdb->users} AS u INNER JOIN {$wpdb->prefix}votes_users AS vu ON u.ID = vu.user;");
	$post_counts = count_many_users_posts($alluids, 'post');
	$comment_counts = count_many_users_comments($alluids);

	foreach ($institutions as $i => $inst) {
		//$comment_counts = (array) $wpdb->get_results("SELECT user_id, COUNT(*) AS total FROM {$wpdb->comments} AS c INNER JOIN {$wpdb->prefix}votes_users AS vu ON c.user_id = vu.user {$where} AND vu.affiliation = '{$inst->name}' GROUP BY user_id", object);
		//$post_counts = (array) $wpdb->get_results("SELECT post_author, COUNT(*) AS total FROM {$wpdb->posts} AS p INNER JOIN {$wpdb->prefix}votes_users AS vu ON p.post_author = vu.user
		//	WHERE post_author <> 0 AND vu.affiliation = '{$inst->name}' GROUP BY post_author", object);
		$uid = $wpdb->get_col("SELECT u.ID FROM {$wpdb->users} AS u INNER JOIN {$wpdb->prefix}votes_users AS vu ON u.ID = vu.user WHERE vu.affiliation='{$inst->name}'");

		if (count($uid) > 0) {
			$countarray = array_fill(0,count($uid),0);
			$votecountarray = array_fill(0,count($uid),0);
			$sinkcountarray = array_fill(0,count($uid),0);
			$commentsarray = array_fill(0,count($uid),0);
			$postsarray = array_fill(0,count($uid),0);

			foreach ($uid as $i => $voter) {
				$vu = $wpdb->get_row("SELECT votes, sinks FROM {$wpdb->prefix}votes_users WHERE user='{$voter}' AND affiliation='{$inst->name}'");
				if ($vu->votes !== null && $vu->votes !== '') $votecountarray[$i] = count(explode(",",$vu->votes));
				if ($vu->sinks !== null && $vu->sinks !== '') $sinkcountarray[$i] = count(explode(",",$vu->sinks));
				$countarray[$i] = $votecountarray[$i] + $sinkcountarray[$i];
				$commentsarray[$i] = $comment_counts[$voter];
				$postsarray[$i] = $post_counts[$voter];
			}
		} else {
			$countarray = array_fill(0, 1, 0);
			$votecountarray = array_fill(0, 1, 0);
			$sinkcountarray = array_fill(0, 1, 0);
			$commentsarray = array_fill(0, 1, 0);
			$postsarray = array_fill(0, 1, 0);
		}

		$mintime = PHP_INT_MAX;
		$maxtime = 0;
		if ($inst->name != 'Unaffiliated') {
			$timestrs = $wpdb->get_results("SELECT votetimes, sinktimes FROM {$wpdb->prefix}votes WHERE institution='{$inst->ID}'");
			foreach($timestrs as $timestr) {
				$minvotetime = $minsinktime = PHP_INT_MAX;
				if ($timestr->votetimes !== '') $minvotetime = min(explode(",",$timestr->votetimes));
				if ($timestr->sinktimes !== '') $minsinktime = min(explode(",",$timestr->sinktimes));
				$mintime = min($mintime, $minvotetime, $minsinktime);

				$maxvotetime = $maxsinktime = 0;
				if ($timestr->votetimes !== '') $maxvotetime = max(explode(",",$timestr->votetimes));
				if ($timestr->sinktimes !== '') $maxsinktime = max(explode(",",$timestr->sinktimes));
				$maxtime = max($maxtime, $maxvotetime, $maxsinktime);
			}
		}

		$names[] = $inst->name;
		$urls[] = $inst->url;
		$num_users[] = count($uid);
		$tot_count[] = array_sum($countarray);
		$vote_count[] = array_sum($votecountarray);
		$sink_count[] = array_sum($sinkcountarray);
		$comments[] = array_sum($commentsarray);
		$posts[] = array_sum($postsarray);
		$first_disc[] = ($mintime == PHP_INT_MAX) ? 0 : $mintime + AgendaOffset('next', 'an', $mintime)*86400;
		$last_disc[] = $maxtime;
	}

	$sorttype = SORT_NUMERIC;
	$order = ($_GET['order'] != '') ? SORT_ASC : SORT_DESC;
	if ($_GET['sortby'] != '') {
		switch ($_GET['sortby']) {
			case 'name':
				$sorttype = SORT_STRING;
				$sortarray = $names;
				$order = ($_GET['order'] != '') ? SORT_DESC : SORT_ASC;
				break;
			case 'numusers':
				$sortarray = $num_users;
				break;
			case 'votecount':
				$sortarray = $vote_count;
				break;
			case 'sinkcount':
				$sortarray = $sink_count;
				break;
			case 'comments':
				$sortarray = $comments;
				break;
			case 'posts':
				$sortarray = $posts;
				break;
			case 'firstdisc':
				$sortarray = $first_disc;
				break;
			case 'lastdisc':
				$sortarray = $last_disc;
				break;
			default:
				$sortarray = $countarray;
		}
	} else {
		$sortarray = $tot_count;
	}
	array_multisort($sortarray, $order, $sorttype, $names, $urls, $num_users, $tot_count, $vote_count, $sink_count, $comments, $posts, $first_disc, $last_disc);

	foreach ($institutions as $i => $inst) {
		$name = $names[$i];
		if ($urls[$i] != '') $name = '<a href="'.$urls[$i].'">'.$name.'</a>';
		if ($institution->name == $names[$i]) $name = '<b>'.$name.'</b>';
			echo '<tr><td>'.$name.'</td><td>'.$num_users[$i].'</td><td>'.$tot_count[$i].'</td><td>'.$vote_count[$i].'</td><td>'.$sink_count[$i].
				'</td><td>'.$comments[$i].'</td><td>'.$posts[$i].'</td><td>'.(($first_disc[$i] == 0) ? '-' : date('m/d/Y', $first_disc[$i])).'</td><td>'.
				(($last_disc[$i] == 0) ? '-' : date('m/d/Y', $last_disc[$i])).'</td></tr>';
	}

	?>
	<th colspan='8'></th>
	<tr>
	<td style="white-space: nowrap;"><b><em>Totals</em></b></td>
	<td style="white-space: nowrap;"><b><?php echo array_sum($num_users); ?></b></td>
	<td style="white-space: nowrap;"><b><?php echo array_sum($tot_count); ?></b></td>
	<td style="white-space: nowrap;"><b><?php echo array_sum($vote_count); ?></b></td>
	<td style="white-space: nowrap;"><b><?php echo array_sum($sink_count); ?></b></td>
	<td style="white-space: nowrap;"><b><?php echo array_sum($comments); ?></b></td>
	<td style="white-space: nowrap;"><b><?php echo array_sum($posts); ?></b></td>
	<td style="white-space: nowrap;"><b>-</b></td>
	<td style="white-space: nowrap;"><b>-</b></td>
	</tr>
	</table>

	</div>
	<?php
}

function DisplaySuggest($postID, $user_list = '') {
	global $user_ID, $wpdb, $current_user, $institution, $page_uri;

	if ($user_ID != '') { 
?>
		<form style="font-size: x-small;"><select id="suggestid<?php echo $postID; ?>">
		<option value="suggest" selected="selected">Suggest this paper!</option>
		<option disabled>---</option>
		<?php
		if ($user_list == '') {
			$users = $wpdb->get_results("SELECT wu.ID as ID, wu.display_name AS name, wu.user_nicename AS nicename FROM {$wpdb->prefix}votes_users AS vu
				INNER JOIN {$wpdb->prefix}users AS wu ON (wu.ID = vu.user) WHERE vu.affiliation = '{$institution->name}' ORDER BY wu.display_name");

			$i = 0;
			foreach ($users as $user) {
				if ($user->name != '') {
					$user_list .= '<option value="'.$user->nicename.'">'.$user->name.'</option>';
				}
			}
		}

		echo $user_list;

?>
		</select>
		<input type="button" value="Suggest" onClick="javascript:suggest('suggestid<?php echo $postID; ?>','suggestresp<?php echo $postID; ?>',<?php echo $postID;?>,<?php echo $user_ID;?>,'<?php echo VoteItUp_ExtPath();?>');">
		</form>
		<div class='suggeststatus' id='suggestresp<?php echo $postID;?>'></div>
<?php
	} else {
		echo " | <em>You must be <a href='".get_option("siteurl")."/wp-login.php?redirect_to={$page_uri}'>logged in</a> to suggest a paper!</em>";
	}
}

function DisplayPost($post, $pd = null, $bpids = array(), $catsh = '', $sep = false, $user_list = '', $show_poster = false, $show_votebox = true, $show_meta = true, $show_number = true, $show_voters = false) {
	include_once("/var/www/html/voxcharta/wp-blog-header.php");
	global $user_ID, $user_login, $today, $institution, $wpdb, $ishome, $page_uri, $postloop, $showabstracts, $arxiv_cats, $arxiv_cnums;
	$postID = $post->ID;
	$pguid = str_replace('voxcharta.org', $institution->subdomain . '.voxcharta.org', $post->guid);
	if (!isset($today)) {
		$today = time();
	}
	if (function_exists('VoteItUp_options')) {
		if (isset($pd)) {?>
			<div class="container" id="container-<?php echo $post->ID; ?>" categories="<?php echo $catsh;?>">
			<div class="lightpostbody"><div>
		<?php } ?>
		<div id="post-<?php echo $postID; ?>" class="post">
		<h3><a href="<?php echo $pguid; ?>" rel="bookmark"><?php
		if ($pd->primary_cat != '') echo '['.$pd->primary_cat;
		if ($show_number) {
			echo ' #'.$pd->cat_nums[$pd->primary_cat];
		}
		if ($pd->primary_cat != '') echo '] ';
		echo $post->post_title; ?></a></h3>
			<span class="additional-info" id="info-<?php echo $post->ID; ?>" <?php echo (isset($postloop) && !$showabstracts) ? "style='display: none'" : "";?>>
		<div class="postinfo">
		<div>
		<?php if ($show_votebox) { ?>
		<div>
		<div>
		<span id="votecount<?php echo $postID; ?>"><?php echo GetVotes($postID);?></span>
		</div><span class="votelink">
		<?php if ($user_ID != '') { ?>
			<div>
			<?php if (array_search($postID, $bpids) !== false) { echo '<div class="hirecommend">This paper is highly recommended for you!</div>'; } ?>
			<span id="votelinks<?php echo $postID; ?>"><?php display_vote_info($postID, $user_ID); ?></span>

			<div><span class="small_comm_num"><a href="<?php echo $pguid;?>#respond"><?php $comm_cnt = get_comment_count($postID); echo $comm_cnt['approved']; ?></a></span><span class="small_comm_num_bg"><a href="<?php echo $pguid;?>#respond"><img src="https://voxcharta.org/comment_icon_big.png" class="voteicon" alt='Comment on this paper'></a></span><a href="<?php echo $pguid;?>#respond">Comment</a>
		<?php if ($post->post_author == $user_ID || current_user_can( 'edit_others_posts', $user_ID)) {
			echo "&nbsp;<a href='https://{$institution->subdomain}.voxcharta.org/wp-admin/post.php?action=edit&post={$post->ID}'><img src='https://{$institution->subdomain}.voxcharta.org/wp-content/plugins/vote-it-up/pencil.png' class='voteicon'>Edit this post</a>";
		} ?>
		</div>
		</div>
			<?php DisplaySuggest($postID, $user_list); ?>
		<?php } else { ?>
		Please <a href="https://<?php echo $institution->subdomain;?>.voxcharta.org/wp-login.php?redirect_to=<?php echo $page_uri;?>">log in</a> or <a href="https://<?php echo $institution->subdomain;?>.voxcharta.org/wp-login.php?action=register&affiliation=<?php echo $institution->name; ?>&redirect_to=<?php echo $page_uri;?>">create an account</a>  to vote!
		
		<?php } ?>
		<?php if (get_option('voteiu_allowguests') == 'true') { ?>
			<?php if(!GuestVoted($postID,md5($_SERVER['REMOTE_ADDR']))) { ?>
				<!--<br><img src="https://voxcharta.org/wp-content/plugins/vote-it-up/bite.png" class="voteicon"> Bite it!-->
			<?php } else { ?>
				<!--<br><img src="https://voxcharta.org/wp-content/plugins/vote-it-up/bite.png" class="voteicon"> Bitten.-->
		<?php }} ?>
		</div>
		<?php } ?>
		</span><span class="postinfometa">
		<?php
		if ($show_meta) {
			if ($pd->pmd == '') {
				echo get_post_meta_data(array($post))[0];
			} else echo $pd->pmd;
		}
		$post_time = strtotime($post->post_date);
		echo '<p class="postinfodate">Originally posted';
		if ($show_poster) {
			$poster = get_userdata($post->post_author);
			$poster_url = get_author_posts_url($poster->ID);
			$poster_str = "<a href='{$poster_url}'>{$poster->display_name}</a>";
			$inst_str = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user='{$poster->ID}'");
			echo ' by ' . $poster_str . ' from ' . $inst_str . ' on';
		}
		echo ' <a href="https://'.$institution->subdomain.'.voxcharta.org/'.date('Y/m/d', $post_time).'">' . date('m/d/Y', $post_time) . '</a></p>';
		?>
		</span>
		<span class="postinfocats">
		<?php echo get_category_graphics($postID); ?>
		</span>
		</div>
		</div>
		<?php if ($show_voters) GetVoteList($postID);?>
			  <div class="post-content clearfix">
			  <?php echo apply_filters('the_content', $post->post_content); ?>
			  </div></span>
			</div>
		<?php if (isset($pd)) {?>
			</div></div>
			<?php if ($sep == true) echo '<div class="darksep"><div></div></div>'; ?>
			</div>
		<?php } ?>
	<?php }
	ob_flush();
	flush();
}

//Display the votes as a bar
function DisplayVotes($postID, $count = '', $evcount = '', $type = '') {
global $user_ID, $guest_votes, $vote_text, $use_votetext, $allow_sinks, $voteiu_skin;

$voteiu_skin = get_option('voteiu_skin');
$votes = GetVotes($postID, $count, $evcount);
switch ($type) {
case '':
if ($voteiu_skin == '') {
DisplayVotes($postID, $count, $evcount, 'bar'); //Use default bar
} else {
if (!LoadSkin($voteiu_skin, $postID, $count, $evcount)) {
DisplayVotes($postID, $count, $evcount, 'bar'); //Use default bar
}
}
break;
case 'bar':
$barvotes = GetBarVotes($postID);
?>
<span class="barcontainer"><span class="barfill" id="votecount<?php echo $postID ?>" style="width:<?php echo round($barvotes[0] * 2.5); ?>%;">&nbsp;</span></span>
<?php if ($user_ID != '') { 
 if (!($user_login == get_the_author_login() && !get_option('voteiu_allowownvote'))) { ?>
	<span>
	<?php if(!UserVoted($postID,$user_ID)) { ?><span class="bartext" id="voteid<?php echo $postID; ?>">
			<a href="javascript:vote('votecount<?php echo $postID; ?>','voteid<?php echo $postID; ?>','<?php echo get_option('voteiu_aftervotetext'); ?>',<?php echo $postID; ?>,<?php echo $user_ID; ?>,'<?php echo VoteItUp_ExtPath(); ?>');"><?php echo get_option('voteiu_votetext'); ?></a><?php if (get_option('voteiu_sinktext') != '') { ?><a href="javascript:sink('votecount<?php echo $postID; ?>','voteid<?php echo $postID; ?>','<?php echo get_option('voteiu_aftervotetext'); ?>',<?php echo $postID; ?>,<?php echo $user_ID; ?>,'<?php echo VoteItUp_ExtPath(); ?>');"><?php echo get_option('voteiu_sinktext'); ?></a>
			<?php } ?>

		</span>
	<?php } else { ?>
	<?php if (get_option('voteiu_aftervotetext') != '') { ?><span class="bartext" id="voteid<?php echo $postID; ?>"><?php echo get_option('voteiu_aftervotetext'); ?></span><?php } ?>
	<?php } ?>
	</span>
<?php } } else {
if (get_option('voteiu_allowguests') == 'true') { ?>
	<span>
	<?php if(!GuestVoted($postID,md5($_SERVER['REMOTE_ADDR']))) { ?><span class="bartext" id="voteid<?php echo $postID; ?>">
			<a href="javascript:vote('votecount<?php echo $postID; ?>','voteid<?php echo $postID; ?>','<?php echo get_option('voteiu_aftervotetext'); ?>',<?php echo $postID; ?>,0,'<?php echo VoteItUp_ExtPath(); ?>');"><?php echo get_option('voteiu_votetext'); ?></a><?php if (get_option('voteiu_sinktext') != '') { ?><a href="javascript:sink('votecount<?php echo $postID; ?>','voteid<?php echo $postID; ?>','<?php echo get_option('voteiu_aftervotetext'); ?>',<?php echo $postID; ?>,0,'<?php echo VoteItUp_ExtPath(); ?>');"><?php echo get_option('voteiu_sinktext'); ?></a>
			<?php } ?>

		</span>
	<?php } ?>
	</span>
	<?php } }
break;
case 'ticker':
?>
<span class="tickercontainer" id="votes<?php echo $postID; ?>"><?php echo $votes; ?></span>
<?php if ($user_ID != '') { ?>
<span id="voteid<?php echo $postID; ?>">
	<?php if(!UserVoted($postID,$user_ID)) { ?><span class="tickertext">
		<?php if ($use_votetext == 'true') { ?>
		<a class="votelink" href="javascript:vote_ticker(<?php echo $postID ?>,<?php echo $postID ?>,<?php echo $user_ID; ?>,'<?php echo VoteItUp_ExtPath(); ?>');"><?php echo $vote_text; ?></a>
		<?php } else { ?>
			<span class="imagecontainer">
			<?php if ($allow_sinks == 'true') { ?>
			<a href="javascript:sink_ticker(<?php echo $postID ?>,<?php echo $postID ?>,<?php echo $user_ID; ?>,'<?php echo VoteItUp_ExtPath(); ?>');">
			<img class="votedown" src="<?php echo VoteItUp_ExtPath(); ?>/votedown.png" alt="Vote down" border="0" />
			</a>
			<?php } ?>
			<a href="javascript:vote_ticker(<?php echo $postID ?>,<?php echo $postID ?>,<?php echo $user_ID; ?>,'<?php echo VoteItUp_ExtPath(); ?>');">
			<img class="voteup" src="<?php echo VoteItUp_ExtPath(); ?>/voteup.png" alt="Vote up" border="0" />
			</a>
			</span>
		<?php } ?>
		</span>
	<?php } ?>
</span>
<?php } else {
if ($guest_votes == 'true') { ?>
	<span id="voteid<?php echo $postID; ?>">
	<?php if(!GuestVoted($postID,md5($_SERVER['REMOTE_ADDR']))) { ?>
		<span class="tickertext">
		<?php if ($use_votetext == 'true') { ?>
			<a class="votelink" href="javascript:vote_ticker(<?php echo $postID ?>,<?php echo $postID ?>,0,'<?php echo VoteItUp_ExtPath(); ?>');"><?php echo $vote_text; ?></a></span>
		<?php } else { ?>
			<span class="imagecontainer">
			<?php if ($allow_sinks == 'true') { ?>
			<a href="javascript:sink_ticker(<?php echo $postID ?>,<?php echo $postID ?>,0,'<?php echo VoteItUp_ExtPath(); ?>');">
			<img class="votedown" src="<?php echo VoteItUp_ExtPath(); ?>/votedown.png" alt="Vote down" border="0" />
			</a>
			<?php } ?>
			<a href="javascript:vote_ticker(<?php echo $postID ?>,<?php echo $postID ?>,0,'<?php echo VoteItUp_ExtPath(); ?>');">
			<img class="voteup" src="<?php echo VoteItUp_ExtPath(); ?>/voteup.png" alt="Vote up" border="0" />
			</a>
			</span>
		<?php } ?>
		</span>
	<?php } ?>
</span>

<?php
}
}
break;
}
}

/* Widget examples can be found in widget.php of wp-includes.*/
function widget_MostVotedAllTime_init() {

if (function_exists('wp_register_sidebar_widget')) {
function widget_MostVotedAllTime($args) {
$options = get_option("widget_MostVotedAllTime");
if ($options['title'] != '') {
$title = $options['title'];
} else {
$title = 'Most Voted Posts';
}
    extract($args);
?>
        <?php echo $before_widget; ?>
            <?php echo $before_title
                . $title
                . $after_title; ?>
            <?php MostVotedAllTime('', '', 'sidebar'); ?>
        <?php echo $after_widget; ?>
<?php
}
wp_register_sidebar_widget('most_voted_posts', 'Most Voted Posts', 'widget_MostVotedAllTime');
//$widget_ops = array('classname' => 'widget_MostVotedAllTime', 'description' => __( "Displays the most voted up posts") );
//@wp_register_sidebar_widget('widget_MostVotedAllTime', __('Most Voted Posts'), 'widget_MostVotedAllTime', $widget_ops);

function widget_MostVotedAllTime_Control() {
$options = $newoptions = get_option("widget_MostVotedAllTime");

if ($_POST) 
{
$newoptions['title'] = strip_tags(stripslashes($_POST['widget_MostVotedAllTime_title']));
}
if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_MostVotedAllTime', $options);
}
$title = esc_attr($options['title']);
?>
<p>
    <label for="widget_MostVotedAllTime_title">Title: </label>
    <input type="text" class="widefat" id="widget_MostVotedAllTime_title" name="widget_MostVotedAllTime_title" value="<?php echo $title; ?>" />
	<input type="hidden" id="voteitup-submit" name="voteitup-submit" value="1" />
  </p>
<?php
}

wp_register_widget_control('most_voted_posts_control', 'Most Voted Posts', 'widget_MostVotedAllTime_Control' );

}
}

function autoVote($post) {
	global $user_ID, $wpdb;
	$pid = $post->ID;
	$post_votes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}votes WHERE post='{$pid}'");
	if (!empty($post_votes) && $post_votes >= 1) return;
	$cats = wp_get_post_categories($pid);
	if (in_array(18011, $cats)) Vote($pid, $user_ID, 'vote');
}

function show_user_affiliation($user) 
{
	global $wpdb;
	SetUser($user->ID);
	$result = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user=" . (int)$user->ID); 
	?> 
	<h3><?php _e("Additional user information", 'affiliation') ?></h3> 
	<table class="form-table"> 
	<tr> 
	<th><label for="affiliation"><?php _e("Affiliation", 'affiliation'); ?></label></th>
	<td>

	<select name="affiliation" id="affiliation" class="regular-text">
	<?php
	$institutions = $wpdb->get_col("SELECT name FROM {$wpdb->prefix}votes_institutions WHERE name <> 'Unaffiliated' AND active = 1 ORDER BY name");
	$institutions = array_merge($institutions, array('---','Unaffiliated'));
	foreach ($institutions as $institution) {
		if ($institution === $result) {
			echo "<option selected value='{$institution}'>{$institution}</option>";
		} elseif ($institution === '---') {
			echo "<option disabled='disabled'>---</option>";
		} else {
			echo "<option value='{$institution}'>{$institution}</option>";
		}
	}
	?>
	</select>
	<span class="description"><?php _e("User's primary institution.", 'affiliation'); ?></span></td> 
	</tr>
	</table> 
	<?php 
} 

function save_user_affiliation( $user_id ) {
	global $wpdb;
	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;

	$wpdb->query("UPDATE {$wpdb->prefix}votes_users SET affiliation='{$_POST['affiliation']}' WHERE user='{$user_id}'");
}

function trimmed_category_checklist( $post_id = 0, $descendants_and_self = 0, $selected_cats = false, $popular_cats = false, $walker = null, $checked_ontop = true ) {
	global $current_user, $wpdb;
	$affiliation = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user='{$current_user->ID}'");
	$institution = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name='{$affiliation}'");
	$cat_slugs = $wpdb->get_col("SELECT categoryslug FROM {$wpdb->prefix}votes_events WHERE affiliation='{$institution->ID}'"); 
	$categories = array(get_term_by('slug', 'special-topics', 'category'));
	foreach ($cat_slugs as $slug) {
		if ($slug != '') {
			$cat = get_term_by('slug', $slug, 'category');
			$categories[] = $cat;
		}
	}
	if ( empty($walker) || !is_a($walker, 'Walker') )
		$walker = new Walker_Category_Checklist;

	$descendants_and_self = (int) $descendants_and_self;

	$args = array();

	if ( is_array( $selected_cats ) )
		$args['selected_cats'] = $selected_cats;
	elseif ( $post_id )
		$args['selected_cats'] = wp_get_post_categories($post_id);
	else
		$args['selected_cats'] = array();

	$args['popular_cats'] = array();

	if ( $checked_ontop ) {
		// Post process $categories rather than adding an exclude to the get_terms() query to keep the query the same across all posts (for any query cache)
		$checked_categories = array();
		$keys = array_keys( $categories );

		foreach( $keys as $k ) {
			if ( in_array( $categories[$k]->term_id, $args['selected_cats'] ) ) {
				$checked_categories[] = $categories[$k];
				unset( $categories[$k] );
			}
		}

		// Put checked cats on top
		echo call_user_func_array(array(&$walker, 'walk'), array($checked_categories, 0, $args));
	}
	// Then the rest of them
	echo call_user_func_array(array(&$walker, 'walk'), array($categories, 0, $args));
}

function trimmed_categories_meta_box($post) {
?>
<ul id="category-tabs">
	<li class="tabs"><a href="#categories-all" tabindex="3"><?php _e( 'All Categories' ); ?></a></li>
</ul>

<div id="categories-all" class="tabs-panel">
	<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
	<?php
		if ( current_user_can('manage_categories') ) {
	    	wp_category_checklist($post->ID, false, false, array());
		} else {
	    	trimmed_category_checklist($post->ID, false, false, array());
		}
	?>
	</ul>
</div>

<?php if ( current_user_can('manage_categories') ) : ?>
<div id="category-adder" class="wp-hidden-children">
	<h4><a id="category-add-toggle" href="#category-add" class="hide-if-no-js" tabindex="3"><?php _e( '+ Add New Category' ); ?></a></h4>
	<p id="category-add" class="wp-hidden-child">
	<label class="screen-reader-text" for="newcat"><?php _e( 'Add New Category' ); ?></label><input type="text" name="newcat" id="newcat" class="form-required form-input-tip" value="<?php esc_attr_e( 'New category name' ); ?>" tabindex="3" aria-required="true"/>
	<label class="screen-reader-text" for="newcat_parent"><?php _e('Parent category'); ?>:</label><?php wp_dropdown_categories( array( 'hide_empty' => 0, 'name' => 'newcat_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => __('Parent category') ) ); ?>
	<input type="button" id="category-add-sumbit" class="add:categorychecklist:category-add button" value="<?php esc_attr_e( 'Add' ); ?>" tabindex="3" />
<?php	wp_nonce_field( 'add-category', '_ajax_nonce', false ); ?>
	<span id="category-ajax-response"></span></p>
</div>
<?php
endif;

}

function parse_request_fix($request) {
	if (isset($request['year']) && isset($request['monthnum']) && isset($request['day'])) {
		$req_time = strtotime($request['monthnum'].'/'.$request['day'].'/'.$request['year']);
		if (date('w', $req_time) == '5') {
			$req_time = $req_time - 86400;
			$request['day'] = date('d', $req_time);
			$request['monthnum'] = date('m', $req_time);
			$request['year'] = date('Y', $req_time);
		}
	}

	return $request;
}

require_once(ABSPATH.'wp-admin/includes/template.php');
//Runs the plugin
add_action('wp_head', 'VoteItUp_header');
add_action('get_footer', 'VoteItUp_footer');
add_action('admin_menu', 'VoteItUp_options');
add_action('init', 'widget_MostVotedAllTime_init');
add_action('new_to_publish', 'autoVote');
add_action('show_user_profile', 'show_user_affiliation'); 
add_action('edit_user_profile', 'show_user_affiliation');
add_action('personal_options_update', 'save_user_affiliation');
add_action('edit_user_profile_update', 'save_user_affiliation');
//add_filter('request', 'parse_request_fix');
function modify_meta_boxes() {
	remove_meta_box('post_categories_meta_box', 'post', 'advanced');
	add_meta_box('categorydiv', __('Categories'), 'trimmed_categories_meta_box', 'post', 'side', 'low');
}
add_action('admin_menu', 'modify_meta_boxes');

?>
