<?php
	
	require_once(dirname(__FILE__) . '/../../../wp-config.php');
	require_once(dirname(__FILE__) . '/../vote-it-up/votingfunctions.php');
	                                     
	nocache_headers();
	
	// check password
	if(isset($_REQUEST['code']) && $_REQUEST['code'] == get_option('wpo_croncode')) 
	{
		global $wpdb, $agenda_info, $institution;
		$inst_name = urldecode($_GET['institution']);
		$institution = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_institutions WHERE name='{$inst_name}'");
		date_default_timezone_set($institution->timezone);
		if ($institution->canceleddays != '') {
			$nocoffee = explode(',',$institution->canceleddays);
			foreach($nocoffee as $except) {
				if (strtotime($except) == mktime(0, 0, 0)) return;
			}
		}
		//if (AgendaOffset('next', 'co') != 1) return;
		$club_check = $agenda_info;
		if ($club_check == 'cc' || $club_check == 'jc') return;
		//$a = SortVotes();
		//if (CountMostVoted($a) > 0) return;

		$users = $wpdb->get_results("SELECT u.ID, u.user_email as email, u.display_name as name FROM {$wpdb->users} AS u
							 		 INNER JOIN {$wpdb->prefix}votes_users AS vu ON (u.ID = vu.user) WHERE vu.affiliation = '{$inst_name}'");
		$emailed_list = array();
		foreach ($users as $user) {
			SetRecommend($user->ID);
			$vr = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."votes_recommend WHERE user='{$user->ID}'", ARRAY_A);
			$sendemail = $vr['sendemail'];
			if (!$sendemail) continue;
			$reminddays = $vr['reminddays'];
			$dontemail = $vr['dontemail'];
			$lastemailtime = $vr['lastemailtime'];
			if (time() - $reminddays*86400 < $lastemailtime) continue;
			if ($dontemail) {
				$uservotetimes = $wpdb->get_results("SELECT votes, usersinks FROM ".$wpdb->prefix."votes WHERE lastvotetime > '".strval(time()-$reminddays*86400)."'");
				$userstring = '';
				foreach ($uservotetimes as $uservotetime) {
					$userstring .= $uservotetime->votes . $uservotetime->usersinks. ',';
				}
				$userarray = explode(',', $userstring);
				if (in_array($user->ID, $userarray)) continue;
			}

			$user_raw = $wpdb->get_row("SELECT votes,sinks FROM {$wpdb->prefix}votes_users WHERE user='{$user->ID}' LIMIT 1;");
			if (empty($user_raw->votes)) continue;

			$bannedtags = '"'.implode('", "', explode(",", $vr['bannedtags'])).'"';
			$showreplace = $vr['showreplace'];
			$showcrosslist = $vr['showcrosslist'];
			$tags = $wpdb->get_results("SELECT t.name as name, tt.term_id as ID, COUNT(tt.term_id) AS counter FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
				INNER JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id)
				WHERE (tt.taxonomy = 'post_tag' AND p.ID IN ({$user_raw->votes}))
				AND name NOT IN ({$bannedtags})
				AND p.post_status = 'publish'
				AND p.post_type = 'post'
				GROUP BY tt.term_id
				ORDER BY counter DESC;");
			$authors = $wpdb->get_results("SELECT t.name as name, tt.term_id as ID, COUNT(tt.term_id) AS counter FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
				INNER JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id)
				WHERE (tt.taxonomy = 'post_author' AND p.ID IN ({$user_raw->votes}))
				AND name NOT IN ({$bannedtags})
				AND p.post_status = 'publish'
				AND p.post_type = 'post'
				GROUP BY tt.term_id
				ORDER BY counter DESC;");
			if (empty($user_raw->sinks)) {
				$stags = array();
				$sauthors = array();
			} else {
				$stags = $wpdb->get_results("SELECT t.name as name, tt.term_id as ID, COUNT(tt.term_id) AS counter FROM {$wpdb->posts} AS p
					INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
					INNER JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id)
					WHERE (tt.taxonomy = 'post_tag' AND p.ID IN ({$user_raw->sinks}))
					AND name NOT IN ({$bannedtags})
					AND p.post_status = 'publish'
					AND p.post_type = 'post'
					GROUP BY tt.term_id
					ORDER BY counter DESC;");
				$sauthors = $wpdb->get_results("SELECT t.name as name, tt.term_id as ID, COUNT(tt.term_id) AS counter FROM {$wpdb->posts} AS p
					INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
					INNER JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id)
					WHERE (tt.taxonomy = 'post_author' AND p.ID IN ({$user_raw->sinks}))
					AND name NOT IN ({$bannedtags})
					AND p.post_status = 'publish'
					AND p.post_type = 'post'
					GROUP BY tt.term_id
					ORDER BY counter DESC;");
			}

			$tag_list = '"';
			foreach ($tags as $tag) {
				$tag_list .= $tag->ID . '", "';
			}
			foreach ($authors as $tag) {
				$tag_list .= $tag->ID . '", "';
			}
			foreach ($stags as $tag) {
				$tag_list .= $tag->ID . '", "';
			}
			foreach ($sauthors as $tag) {
				$tag_list .= $tag->ID . '", "';
			}
			$tag_list = substr($tag_list, 0, strlen($tag_list) - 3);

			// Include category
			$include_cat_sql = '';
			$inner_cat_sql = '';
			$include_cat = '8';
			if ($showreplace) $include_cat .= ',6';
			if ($showcrosslist) $include_cat .=  ',314';
			if ($include_cat != '') {
				$include_cat = (array) explode(',', $include_cat);
				$include_cat = array_unique($include_cat);
				foreach ( $include_cat as $value ) {
					$value = (int) $value;
					if( $value > 0 ) {
						$sql_cat_in .= '"'.$value.'", ';
					}
				}
				$sql_cat_in = substr($sql_cat_in, 0, strlen($sql_cat_in) - 2);
				$include_cat_sql = " AND (ctt.taxonomy = 'category' AND ctt.term_id IN ({$sql_cat_in})) ";
				$inner_cat_sql = " INNER JOIN {$wpdb->term_relationships} AS ctr ON (p.ID = ctr.object_id) ";
				$inner_cat_sql .= " INNER JOIN {$wpdb->term_taxonomy} AS ctt ON (ctr.term_taxonomy_id = ctt.term_taxonomy_id) ";
			}

			if (empty($tag_list)) continue;
			$allposts = $wpdb->get_results("SELECT p.ID, p.post_title, p.guid, GROUP_CONCAT(tt.term_id) as terms_id FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
				{$inner_cat_sql}
				WHERE ((tt.taxonomy = 'post_tag' OR tt.taxonomy = 'post_author') AND tt.term_id IN ({$tag_list}))
				{$include_cat_sql}
				AND p.ID NOT IN (".$user_raw->votes.")
				AND p.post_status = 'publish'
				AND p.post_type = 'post' AND p.post_date > '".date('Y-m-d H:i:s', time() - $reminddays*86400)."'
				GROUP BY tr.object_id
				ORDER BY tr.object_id;");
			if (empty($allposts)) continue;
			$pcounts = array_fill(0, count($allposts), 0);
			foreach ($allposts as $p => $post) {
				$p_ID = $post->ID;
				$ptags = explode(",",$post->terms_id);
				$count = 0;
				foreach ($ptags as $ptag) {
					$newval = 0;
					foreach ($tags as $tag) {
						if ($tag->ID == $ptag) $newval += $tag->counter;
					}
					foreach ($authors as $tag) {
						if ($tag->ID == $ptag) $newval += $tag->counter;
					}
					foreach ($stags as $tag) {
						if ($tag->ID == $ptag) $newval -= $tag->counter;
					}
					foreach ($sauthors as $tag) {
						if ($tag->ID == $ptag) $newval -= $tag->counter;
					}
					//if ($newval != 0) $newval /= $wpdb->get_var("SELECT COUNT(tt.term_id) AS counter FROM {$wpdb->posts} AS p
					//	INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
					//	INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
					//	WHERE ((tt.taxonomy = 'post_tag' OR tt.taxonomy = 'post_author') AND tt.term_id = {$ptag})
					//	AND p.post_status = 'publish'
					//	AND p.post_type = 'post'
					//	GROUP BY tt.term_id;"); 
					$count += $newval;
				}
				$pcounts[$p] = $count;
			}
			array_multisort($pcounts, SORT_DESC, $allposts); 

			$content = "Dear ".$user->name.", this automatically generated e-mail has been sent to you as a gentle reminder to participate in our regular astro-ph discussion. Based on your previous voting history, you might find the following papers interesting:\n\n";
			$i = 0;
			foreach ($allposts as $post) {
				$content .= strval($i+1).". \"".html_entity_decode(strip_tags($post->post_title))."\"\n".$post->guid."\n";
				$i++;
				if ($i >= 5) break;
			}
			$content .= "\nTo see the latest astro-ph postings, visit the main page of Vox Charta:\nhttps://".strtolower($institution->subdomain).".voxcharta.org\n\nFor a more complete list of recommended papers from the last ".$reminddays." days, visit your \"Recommended Papers\" page:\nhttps://".strtolower($institution->subdomain).".voxcharta.org/recommended-papers\n\n"; 
			$content .= "If you wish to no longer receive these periodic reminders or to alter your recommendation preferences, please visit the recommendation settings page on Vox Charta and uncheck the \"Send recommendation e-mails\" option:\nhttps://voxcharta.org/wp-admin/options-general.php?page=voteituprecommend";

			$email_from = (empty($institution->announcefrom)) ? 'vox.charta.notifications@gmail.com' : $institution->announcefrom;
			$headers = "From: Vox Charta for {$institution->name} <{$email_from}>\n\r";
			//$email_to = 'jfg@ucolick.org';
			echo $user->email . "\n";
			//wp_mail($email_to, 'Astro-ph Paper Recommendations', $content, $headers);
			$emailed_list[] = $user->email;
			wp_mail($user->email, 'Astro-ph Paper Recommendations', $content, $headers);
			$wpdb->query("UPDATE ".$wpdb->prefix."votes_recommend SET lastemailtime = '".strval(time()-60)."' WHERE user='{$user->ID}'");
		}	
		if (count($emailed_list) == 0) return;
		$headers = "From: Vox Charta for {$institution->name} <{$email_from}>\n\r";
		$summary_text = 'Total number of e-mails sent: ' . count($emailed_list) . "\n\n";
		$summary_text .= 'List of e-mails: ' . implode(', ', $emailed_list);
		wp_mail('guillochon@gmail.com', 'Astro-ph Recommendations Summary', $summary_text, $headers);
	} else                                                                          
    $wpomatic->log('Warning! cron.php was called with the wrong password or without one!');
