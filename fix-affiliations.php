<?php
	require_once(dirname(__FILE__) . '/../../../wp-config.php');

	global $wpdb;
	$users = $wpdb->get_col('SELECT id FROM wp_users');
	foreach ($users as $user) {
		SetUser($user);
	}

?>
