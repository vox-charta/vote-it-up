<?php
	require_once(dirname(__FILE__) . '/../../../wp-config.php');

	global $wpdb;
	$users = $wpdb->get_col("SELECT ID FROM {$wpdb->users}");
	$validposts_array = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post'");
	echo count($validposts_array) . '<br>';
	$postswithentries = $wpdb->get_col("SELECT post FROM {$wpdb->prefix}votes");
	$newentries = array_diff($validposts_array, $postswithentries);
	//foreach ($newentries as $newentry) {
	//	SetPost($newentry);
	//}
	//echo count($newentries) . ' new entries added to wp_votes.<br>';
	$entriestoremove = array_diff($postswithentries, $validposts_array);
	foreach ($entriestoremove as $rementry) {
		$wpdb->query("DELETE FROM {$wpdb->prefix}votes WHERE (post = {$rementry} AND votes = '' AND usersinks = '' AND guests = '' AND guestsinks = '')");
	}
	echo count($entriestoremove) . ' entries removed from wp_votes.<br>';
	$posts = $wpdb->get_results("SELECT post, votes, usersinks FROM {$wpdb->prefix}votes");
	$changed_posts = array();
	//echo implode(',',$validposts_array);
	foreach ($users as $u => $user) {
		$uservotes_string = $wpdb->get_var("SELECT votes FROM {$wpdb->prefix}votes_users WHERE user='{$user}'");
		$usersinks_string = $wpdb->get_var("SELECT sinks FROM {$wpdb->prefix}votes_users WHERE user='{$user}'");
		$uservotes_array = array();
		$uvsize = 0;
		if ($uservotes_string !== null && $uservotes_string !== '' && $uservotes_string !== ',') {
			$uservotes_array = explode(',', $uservotes_string);
			$uvsize = count($uservotes_array);
			$uservotes_array = array_unique(array_intersect($uservotes_array, $validposts_array));
			if (count($uservotes_array) != $uvsize) echo "Removed vote because post doesn't exist!<br>";
		}
		$usersinks_array = array();
		$ussize = 0;
		if ($usersinks_string !== null && $usersinks_string !== '' && $usersinks_string !== ',') {
			$usersinks_array = explode(',', $usersinks_string);
			$ussize = count($usersinks_array);
			$usersinks_array = array_unique(array_intersect($usersinks_array, $validposts_array));
			if (count($usersinks_array) != $ussize) echo "Removed sink because post doesn't exist!<br>";
		}
		foreach ($posts as $i => $post) {
			$postvotes_array = array();
			$postsinks_array = array();
			if ($post->votes !== null && $post->votes !== '' && $post->votes !== ',') $postvotes_array = explode(',', $post->votes);
			if ($post->usersinks !== null && $post->usersinks !== '' && $post->usersinks !== ',') $postsinks_array = explode(',', $post->usersinks);
			foreach ($postvotes_array as $postvote) {
				$psize = count($uservotes_array);
				if ($postvote == $user && !in_array($post->post, $uservotes_array)) {
					$uservotes_array = array_merge($uservotes_array, array($post->post));
					echo "Added vote because it's not in votes_users!<br>";
				}
			}
			foreach ($postsinks_array as $postsink) {
				$psize = count($usersinks_array);
				if ($postsink == $user && !in_array($post->post, $usersinks_array)) {
					$usersinks_array = array_merge($usersinks_array, array($post->post));
					echo "Added sink because it's not in votes_users!<br>";
				}
			}
			//$vsize = count($postvotes_array);
			//foreach ($uservotes_array as $uservote) {
			//	if ($uservote == $post->post && !in_array($user, $postvotes_array)) {
			//		$postvotes_array = array_merge($postvotes_array, array($user));
			//		echo "Added vote because it's not in votes!<br>";
			//	}
			//}
			//$ssize = count($postsinks_array);
			//foreach ($usersinks_array as $usersink) {
			//	if ($usersink == $post->post && !in_array($user, $postsinks_array)) {
			//		$postsinks_array = array_merge($postsinks_array, array($user));
			//		echo "Added sink because it's not in usersinks!<br>";
			//	}
			//}
			//if ($vsize != count($postvotes_array)) {
			//	$posts[$i]->votes = ','.implode(',',$postvotes_array);
			//	echo "New post votes for {$post->post}: " . $posts[$i]->votes . '<br>';
			//	$changed_posts = array_merge($changed_posts, array($i));
			//}
			//if ($ssize != count($postsinks_array)) {
			//	$posts[$i]->usersinks = ','.implode(',',$postsinks_array);
			//	echo 'New post sinks: ' . $posts[$i]->sinks . '<br>';
			//	$changed_posts = array_merge($changed_posts, array($i));
			//}
		}
		if (count($uservotes_array) != $uvsize) {
			echo 'Votes: ' . $uservotes_string;
			$uservotes_string = (count($uservotes_array) > 0) ? implode(',', $uservotes_array) : '';
			$wpdb->query("UPDATE ".$wpdb->prefix."votes_users set votes = '".$uservotes_string."' WHERE user='".$user."'");
			echo ' --> ' . $uservotes_string . '<br>';
		}
		if (count($usersinks_array) != $ussize) {
			echo 'Sinks: ' . $usersinks_string;
			$usersinks_string = (count($usersinks_array) > 0) ? implode(',', $usersinks_array) : '';
			$wpdb->query("UPDATE ".$wpdb->prefix."votes_users set sinks = '".$usersinks_string."' WHERE user='".$user."'");
			echo ' --> ' . $usersinks_string . '<br>';
		}
	}

	//foreach ($changed_posts as $i) {
	//	// Don't know the vote time, have to guess.
	//	$lastvotetime = strtotime($wpdb->get_var("SELECT post_date FROM {$wpdb->prefix}posts WHERE ID = '{$posts[$i]->post}'")) + 3600;
	//	echo "UPDATE {$wpdb->prefix}votes set votes = '{$posts[$i]->votes}', lastvotetime = '{$lastvotetime}' WHERE post='{$posts[$i]->post}'";
	//	$wpdb->query("UPDATE {$wpdb->prefix}votes set votes = '{$posts[$i]->votes}', lastvotetime = '{$lastvotetime}' WHERE post='{$posts[$i]->post}'");
	//}

?>
