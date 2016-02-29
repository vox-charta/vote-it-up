<?php

	require_once(dirname(__FILE__) . '/../../../wp-config.php');
	global $wpdb;
	$votes = $wpdb->get_results("SELECT ID, votes from wp_votes WHERE FIND_IN_SET('1', votes) > 0", ARRAY_A);
	foreach ($votes as $v => $vote) {
		$oldvotes = explode(",",$vote['votes']);
		foreach ($oldvotes as $ov => $oldvote) {
			if ($oldvote === '1') $oldvotes[$ov] = '4308';
		}
		$newvotes = implode(",",$oldvotes);
		echo $v . ": " . $vote['ID'] . ": " . $newvotes . "<br>";
		$status = $wpdb->query("UPDATE wp_votes SET votes = '{$newvotes}' WHERE ID = '{$vote['ID']}'"); 
		echo $status . "<br>";
	}
?>
