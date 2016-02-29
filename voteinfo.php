<?php
include_once("/var/www/html/voxcharta/wp-blog-header.php");
include_once(VoteItUp_Path()."/votingfunctions.php");
header('HTTP/1.1 200 OK'); //Wordpress sends a 404 for some reason, override this (added by JFG).

global $institution, $today, $ishome;
include_once("/var/www/html/voxcharta/wp-content/themes/arclite/setupglobals.php");

display_vote_info($_GET['pid'],$_GET['uid']);
?>
