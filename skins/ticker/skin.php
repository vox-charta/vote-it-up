<?php
/* VoteItUp skin file 
Name: Ticker
Version: 2

*/

global $skinname, $support_images, $support_sinks;
$skinname = 'Ticker'; //No effect yet, but its good to state

function SkinInfo($item) {
switch ($item) {
case 'name':
return 'Ticker';
break;
case 'supporttwoway':
return true;
break;
default:
return false;
break;
}
}


function LoadVoteWidget($a = '') {
	global $wpdb, $show_everyone, $today, $ishome, $user_ID, $institution, $ismostvoted;

	$isaffil = ($wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user='{$user_ID}'") == $institution->name);
	if ($a == '') $a = SortVotes();
	//Before
?>
<?php
$rows = 0;
$visible_rows = 0;

//Now does not include deleted posts
$i = 0;
$nodischead = true;
$futurehead = true;
$prevvt = 0;
date_default_timezone_set($institution->timezone);
while ($rows < get_option('voteiu_widgetcount')) {
	if ($a['pid'][$i][0] != '') {
		$postdat = get_post($a['pid'][$i][0]);
		if (!empty($postdat)) {
			$rows++;
			if ($a['future'][$i] == 0 && $a['discussed'][$i] == 1) {
				$visible_rows++;
			}
			if ($visible_rows == $institution->agendalimit + 1 && $institution->agendalimit != 0) {
	   			echo "</div></div><div id='hiddenagendalink' class='darkpostbody'>";
				echo "<div style='padding: 5px 30px 5px 30px;'><div style='font-size: 10pt;'>Only showing top {$institution->agendalimit} agenda items, <a href='javascript:showHiddenAgendaItems();'>show all</a>.</div></div>";
				echo "</div><div class='lightpostbody'><div id='hiddenagendaspace' style='padding: 10px 30px 11px;'>";
			}

			if ($a['discussed'][$i] == 0 && $nodischead == true) {
	   			echo '</div></div><div class="darkpostbody">';
				if ($a['archived']) {
					echo '<div style="padding: 5px 30px 5px 30px;"><div style="font-size: 10pt;">The following papers were not discussed:</div></div>';
					echo '</div><div class="lightpostbody"><div style="padding: 10px 30px 11px;">';
				} elseif ($institution->pushnotdiscussed) { //Not quite right, doesn't handle both cases simultaneously.
					echo '<div style="padding: 5px 30px 5px 30px;"><div style="font-size: 10pt;">The following papers were voted on previously but not discussed:</div></div>';
					echo '</div><div class="lightpostbody"><div style="padding: 10px 30px 11px;">';
				} elseif ($a['future'][$i] == 0) {
					echo '<div style="padding: 5px 30px 5px 30px;"><div style="font-size: 10pt;">The following papers were only voted on by other institutions and are not scheduled to be discussed:</div></div>';
					echo '</div><div class="lightpostbody"><div style="padding: 10px 30px 11px;">';
				} else {
					echo '</div><div><div>';
				}
				$nodischead = false;
			}
			if ($a['future'][$i] == 1 && $futurehead == true) {
	   			echo '</div></div><div class="darkpostbody"><div style="padding: 5px 30px 5px 30px;">';
				if ($a['future']) {
					if ($nodischead == false) {
						echo '<div style="font-size: 10pt;">The following papers are scheduled to be discussed at other institutions at a later date:</div>';
					} else {
						echo '<div style="font-size: 10pt;">The following papers are scheduled to be discussed at a later date:</div>';
					}
				}
				echo '</div></div><div class="lightpostbody"><div style="padding: 10px 30px 11px;">';
				$futurehead = false;
			}
			$voters_string = '<div><span class="votedon">';
			if ($a['votes'][$i][0] + $a['guestvotes'][$i][0] > 0) {
				$voters_string .= "<img src='http://voxcharta.org/wp-content/plugins/vote-it-up/thumbup_sm.png' height='12' width='12'> ";
			}
			if ($a['votes'][$i][0] > 0) {
				for ($j = 0; $j < count($a['votenames'][$i]); $j++) {
					$vote_time = "Vote cast " . date("g:ia, m/d/Y", $a['votetimes'][$i][$j]);
					//$style_str = ($a['votefutures'][$i][$j] == 1) ? " style='opacity:0.5;' " : "";
					$voters_string .= "<span title='{$vote_time}'>";
					if ($a['votepresents'][$i][$j] == 1) {
						$voters_string .= "<img src='http://voxcharta.org/wp-content/plugins/vote-it-up/present_sm.png' height='12' width='12'> ";
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
				$voters_string .= $a['guestvotes'][$i][0].$guest_string;
			}
			if ($a['votes'][$i][0] + $a['guestvotes'][$i][0] > 0) $voters_string .= "&nbsp;&nbsp;";
			if ($a['sinks'][$i][0] + $a['guestsinks'][$i][0] > 0) {
				$voters_string .= ' <img src="http://voxcharta.org/wp-content/plugins/vote-it-up/thumbdown_sm.png" height="12" width="12"> ';
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
				$voters_string .= $a['guestsinks'][$i][0].$guest_string;
			}
			if ($a['sinks'][$i][0] + $a['guestsinks'][$i][0] > 0) $voters_string .= "&nbsp;&nbsp;";
			$nc = $a['ncomments'][$i][0];
			if ($nc > 0) {
				$voters_string .= " <img src='" . get_option("siteurl") . "/comment_icon.png' style='height: 12px; width: 12px; margin: 0 0 -1px 0'>" . $nc . " comment" . (($nc > 1) ? 's' : '');
				$voters_string .= "&nbsp;&nbsp;";
			}
			$voters_string .= '</span>';
			if ($a['archived'] == true || ($institution->pushnotdiscussed && !$nodischead)) {
				$post_custom = get_post_custom($a['pid'][$i][0]);
				if (array_key_exists('wpo_arxivid', $post_custom)) {
					$arxivid = $post_custom['wpo_arxivid'][0];
					//$chstr = (is_chrome()) ? 'aps.' : '';
					$pdf_link = 'http://arxiv.org/pdf/'.$arxivid;
					$voters_string .= "<span class=\"discusslink\"><a href=\"".$pdf_link."\">View PDF</a></span>&nbsp;&nbsp;";	
				} else {
					$voters_string .= "<span class=\"discusslink\">No PDF Available</span>&nbsp;&nbsp;";
				}
				if ($user_ID != '' && $isaffil) {
					if ($ismostvoted) {
						$voters_string .= "<span class=\"discusslink\"><a href=\"javascript:discuss('".$a['pid'][$i][0]."','".VoteItUp_ExtPath()."','".$today."','".$ishome."','0');\">";	
					} else {
						$voters_string .= "<span class=\"discusslink\"><a href=\"javascript:discuss('".$a['pid'][$i][0]."','".VoteItUp_ExtPath()."','".$today."','".$ishome."','1');\">";	
					}
					if ($nodischead == true) {
						$voters_string .= 'Mark as not discussed</a></span>';
					} else {
						$voters_string .= 'Mark as discussed</a></span>';
					}
				}
			}
			$voters_string .= '</div>';

			if ($a['future'][$i] == 1) {
				$vt = $a['minvotetime'][$i][0];
				$corrvt = $vt + 86400*AgendaOffset('next', 'co', $vt);
				$vtdate = date('n/j/Y', $corrvt);
				if ($vtdate != date('n/j/Y', $prevvt)) {
					echo "<div class='agendafuture'>";
					echo "<b>{$vtdate}</b>:</div>";
				}
				$prevvt = $corrvt;
			}
			$postdat = get_post($a['pid'][$i][0]);
			if ($a['future'][$i] == 0 && $a['discussed'][$i] == 1 &&
				$visible_rows > $institution->agendalimit && $institution->agendalimit != 0) {
				echo "<div class='hiddenitem'>";
			}
			$pguid = str_replace('voxcharta.org', $institution->subdomain . '.voxcharta.org', $postdat->guid);
			echo "<div><span class='votemicro'>".$a['sum'][$i][0].
				'</span><ul id="voteli"><li><span class="votemicrotext"><a href="'.$pguid.'" target="_blank" title="'.
				$postdat->post_title.'">'.$postdat->post_title.'</a><br>'.$voters_string.'</span></div></ul>';
			if ($a['future'][$i] == 0 && $a['discussed'][$i] == 1 &&
				$visible_rows > $institution->agendalimit && $institution->agendalimit != 0) {
				echo "</div>";
			}
		}
	}
	if ($i < count($a['pid']) - 1) {
	$i++;
	} else {
	break; //exit the loop
	}
}
?>

<?php

}

?>
