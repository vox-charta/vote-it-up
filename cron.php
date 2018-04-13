<?php
	
	require_once(dirname(__FILE__) . '/../../../wp-config.php');
	require_once(dirname(__FILE__) . '/../vote-it-up/votingfunctions.php');
	                                     
	nocache_headers();
	
	$force_send = false;

	// check password
	if(isset($_GET['code']) && $_GET['code'] == get_option('wpo_croncode')) 
	{
	if(isset($_GET['institution'])) 
	{
		$dayindex = $_GET['dayindex'];
		global $today, $agenda_info, $institution, $event_canceled;
		$institution = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name='{$_GET['institution']}'");
		date_default_timezone_set($institution->timezone);
		$email_tos = ($institution->announceaddress !== '') ? explode(",", $institution->announceaddress) : array();
		if ($institution->announcecopy) {
			$inst_users = $wpdb->get_col("SELECT user FROM {$wpdb->prefix}votes_users WHERE affiliation='{$_GET['institution']}'");
			$users = $wpdb->get_col("SELECT ID FROM {$wpdb->prefix}users");
			foreach ($users as $user) {
				$user_info = get_userdata($user);
				if (in_array($user, $inst_users)) {
					array_push($email_tos, $user_info->user_email);
				}
			}
		}
		//$email_tos = array('guillochon@gmail.com'); //For debugging
		$cur_time = time();
		$disc_date = date('m/d/Y', $cur_time + 86400*AgendaOffset('next', 'an', $cur_time));
		$delays = explode(",", $institution->announcedelay);
		$nomatch = true;
		foreach ($delays as $delay) {
			if (date('m/d/Y', $cur_time + $delay*60) == $disc_date) $nomatch = false;
		}
		if (!$force_send) {
			if ($nomatch) return;
			if ($agenda_info != 'co' && $event_canceled && AgendaOffset('next', 'co', $cur_time) != 0) return;
		}

		AgendaOffset('next', 'an', $cur_time); //Reset the AgendaOffset globals.

		$resettimes = explode(",",$institution->resettime);
		$today = strtotime($resettimes[$dayindex].date(" m/d/Y")) - 60;
		$club_where_string = " AND post_date > '" . date('Y-m-d H:i:s', $today - 6*86400) . "'";
		function filter_club($where = '') {
			global $club_where_string;
			return $where.$club_where_string;
		}
		$email_from = (empty($institution->announcefrom)) ? 'jguillochon@cfa.harvard.edu' : $institution->announcefrom;
		add_filter('wp_mail_from', create_function('', "return \"{$email_from}\"; "));
		add_filter('wp_mail_from_name', create_function('', "return \"Vox Charta for {$institution->name}\"; "));
		if ($institution->announceplain) {
			add_filter('wp_mail_content_type', create_function('', 'return "text/plain"; '));
		} else {
			add_filter('wp_mail_content_type', create_function('', 'return "multipart/alternative; boundary=voxcharta"; '));
		}
		////////////////////////if (AgendaOffset('next', 'an', $today) != 0) return;

		$discussiontimes = explode(',',$institution->discussiontime);
		$discussiondays = explode(',',$institution->normaldays);
		if ($dayindex > 0 && count($discussiontimes) == 1) {
			$discussiontimes = array_fill(0, count($discussiondays), $discussiontimes[0]);
		}
		$curdelay = round((strtotime($discussiontimes[$dayindex] . ' ' . $disc_date) - time()) / 60);
		if (date('m/d/Y', $cur_time) != $disc_date) {
			if (strtotime($disc_date) - strtotime(date('m/d/Y', $cur_time))) {
				$time_str = 'on ' . date('l', strtotime($disc_date));
			} else {
				$time_str = 'tomorrow';
			}
		} elseif ($curdelay > 59) {
			$time_str = 'in ' . round($curdelay/60, 1) . ' hours';
		} else {
			$time_str = 'in ' . $curdelay . ' minutes';
		}
			
		if ($institution->announceplain) {
			$br = '';
			$br2 = '';
		} else {
			$br = '<br>';
			$br2 = '<br><br>';
		}

		$mail_content = '';
		if (is_numeric($agenda_info) && !$event_canceled) {
			$event = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_events WHERE ID='{$agenda_info}'");
			if (count($event) == 1) {
				if (!$institution->announceplain) $mail_content = "\n--voxcharta\nContent-Type: text/html; charset=\"UTF-8\"\nContent-Transfer-Encoding: quoted-printable\n\n";
				if ($event->noagenda) {
					$post_string = "There is no official topic for this morning's discussion. Please come to coffee for an informal chat with your fellow astronomers!\n".$br2;
				} elseif ($event->categoryslug != '') {
					$my_query = new WP_Query();
					add_filter('posts_where', 'filter_club');
					$my_query->query("category_name={$event->categoryslug}&orderby=date&order=DESC&showposts=1");
					remove_filter('posts_where', 'filter_club');
					if ($my_query->have_posts()) {
						$my_query->the_post();
						$post_string = "Today's {$event->nicename} agenda:\n\n".$br2;
						$post_string = $post_string.get_the_content();
						$post_string = apply_filters('the_content', $post_string);
						$post_string = str_replace(']]>', ']]&gt;', $post_string);
					} else {
						$post_string = "The person leading the {$event->nicename} has yet to post the papers to the website, so please check your e-mail for today's agenda!\n".$br2;
					}
				}
				$mail_content .= wordwrap(imap_8bit($post_string), 120, "\n", true);
				if (!$institution->announceplain) $mail_content .= "\n\n--voxcharta--\n";
				foreach($email_tos as $email_to) {
					wp_mail($email_to, "{$event->nicename} {$institution->location} {$time_str}!", $mail_content);
					//For testing
					//wp_mail("guillochon@gmail.com", "{$event->nicename} {$institution->location} {$time_str}!", $mail_content);
				}
				return;
			}
		}
		$a = SortVotes($today, 'announcement');
        
		if (CountMostVoted($a) > 0 || $institution->noemptyagenda) {
			if (CountMostVoted($a) == 0 && $institution->noemptyagenda) {
				$a = SortVotes($today, 'announcement', true);
				$show_everyone = true;
			} else {
				$show_everyone = false;
			}
			if (!$institution->announceplain) {
				$mail_content = "\n--voxcharta\nContent-Type: text/html; charset=\"UTF-8\"\nContent-Transfer-Encoding: quoted-printable\n\n";
				$mail_content .= wordwrap(imap_8bit(MostVotedAllTime_Cron($a, $institution->announceabstracts, $institution->announcemeta, $show_everyone)), 120, "\n", true);
				$mail_content .= "\n\n--voxcharta--\n";
			} else {
				$mail_content = wordwrap(MostVotedAllTime_Cron($a, $institution->announceabstracts, false, $show_everyone), 120, "\n", true);
				echo $mail_content;
			}
		} else {
			if (!$institution->announceplain) $mail_content = "\n--voxcharta\nContent-Type: text/html; charset=\"UTF-8\"\nContent-Transfer-Encoding: quoted-printable\n\n";
			$mail_content .= "The ".strtolower($institution->primaryevent);
			$mail_content .= " agenda is currently empty. Please browse the latest astro-ph postings at the main Vox Charta website (https://";
			$mail_content .= strtolower($institution->name).".voxcharta.org) and cast your votes now before coming to ";
			$mail_content .= strtolower($institution->primaryevent)."!\n".$br2;
			if (!$institution->announceplain) $mail_content .= "\n\n--voxcharta--\n";
		}
		foreach($email_tos as $email_to) {
			wp_mail($email_to, "{$institution->primaryevent} {$institution->location} {$time_str}!", $mail_content);
			//For testing
			//wp_mail("guillochon@gmail.com", "{$institution->primaryevent} {$institution->location} {$time_str}!", $mail_content);
		}
		remove_all_filters('wp_mail_from');
		remove_all_filters('wp_mail_from_name');
		remove_all_filters('wp_mail_content_type');
	} else $wpomatic->log('Error! cron.php was called without specifying an institution!');
	} else $wpomatic->log('Error! cron.php was called with the wrong password or without one!');
