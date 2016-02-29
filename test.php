<?php
	require_once(dirname(__FILE__) . '/../../../wp-config.php');
	require_once(dirname(__FILE__) . '/../vote-it-up/votingfunctions.php');
	wp_mail('guillochon@gmail.com', "a subject", "some content");
?>
