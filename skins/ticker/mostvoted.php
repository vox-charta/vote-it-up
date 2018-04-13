<?php 
  include_once("/var/www/html/voxcharta/wp-blog-header.php");
  include_once(VoteItUp_Path()."/votingfunctions.php");
  global $today, $institution, $schedaffil, $show_everyone, $ishome, $where_stringa, $firstload, $ismostvoted, $agenda_info;
  $ismostvoted = true;
  if (!$firstload) header('HTTP/1.1 200 OK'); //Wordpress sends a 404 for some reason, override this (added by JFG).
  include_once("/var/www/html/voxcharta/wp-content/themes/arclite/setupglobals.php");
  date_default_timezone_set($institution->timezone);
  $reset_time = GetResetTime($today);
  //if (!$ishome) $reset_time -= 86400; 
  //echo date('H:i:s m/d/Y', $today) . ' ' . date('H:i:s m/d/Y', $reset_time). '<br>';
  $next_coffee = AgendaOffset('next', 'co', $today);
  $next_an_offset = AgendaOffset('next', 'an', $today);
  $ec = $event_canceled;
  $club_check = $agenda_info;
  $next_co_time = $reset_time+$next_coffee*86400;
  $next_an_time = $reset_time+$next_an_offset*86400;
  $sorted = SortVotes($today);
  $date_extra = date((($next_an_time - time() > 7*86400) ? 'l, F j' : 'l'), $next_an_time);
?>
	<div class="boxheadr"><div class="boxheadl"><h2><?php echo $institution->name;?>'s Discussion Agenda for <?php echo $date_extra; ?></h2></div></div>
<?php
	  if (is_numeric($club_check)) {
		  $event = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}votes_events WHERE ID='{$club_check}' LIMIT 1");
		  $prev_club = AgendaOffset('prev', $club_check);
		  $club_where_string = " AND post_date > '" . date('Y-m-d H:i:s', $reset_time - $prev_club*86400) . "' AND post_date < '".date('Y-m-d H:i:s', $today + $next_an_offset*86400)."'";
	  } else {
		  $club_where_string = "1=0";
	  }
	  $where_stringa = " AND post_date > '" . date('Y-m-d 00:00:00', $today) . "' AND post_date < '".date('Y-m-d 00:00:00', $today + 86400)."'";

	  function filter_event($where = '') {
		global $club_where_string;
		return $where.$club_where_string;
	  }
	  function fannounce($where = '') {
	  	global $where_stringa;
	  	return $where.$where_stringa;
	  } ?>

	<!--Announcements-->

	<?php //echo '<h3 style="color:red">Urgent notice: The RSS parser is currently on the fritz, I am working to restore it. Apologies for the inconvenience.</h3>'; ?>
	<?php if (!$ishome) add_filter('posts_where', 'fannounce');
	$aquery = new WP_Query('category_name=announcements&orderby=post_date&order=ASC');
	if (!$ishome) remove_filter('posts_where', 'fannounce');
	if ((!$ishome || ($ishome && get_option('sticky_posts'))) && count($aquery->posts) > 0) { ?>
	<div class="lightpostbody"><div style="padding: 0px; height: 15px;"></div></div>
	<div class="darkpostbody"><div><div class="announcehead">Announcements</div></div></div>
	<div class="lightpostbody"><div style="padding: 10px 30px 11px;">
	<?php $counter = 0;
	while ($aquery->have_posts()) { $aquery->the_post(); if($ishome && !is_sticky()) continue; ?>
	<!-- post -->
	<?php if ($counter > 0) echo '</div></div><div class="darkpostbody"><div style="height: 5px;"></div></div><div class="lightpostbody"><div style="padding: 10px 30px 11px;">'; ?>
	<div id="post-<?php the_ID(); ?>" <?php if (function_exists("post_class")) post_class(); else print 'class="post"'; ?>>
	  <div class="post-header">
	   <h3><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e('Permanent Link:','arclite'); echo ' '; the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
		<?php
		$post_time = strtotime($post->post_date);
		echo '<p class="postinfodate">Originally posted';
		$poster = get_userdata($post->post_author);
		$poster_url = get_author_posts_url($poster->ID);
		$poster_str = "<a href='{$poster_url}'>{$poster->display_name}</a>";
		$inst_str = $wpdb->get_var("SELECT affiliation FROM {$wpdb->prefix}votes_users WHERE user='{$poster->ID}'");
		echo ' by ' . $poster_str . ' from ' . $inst_str . ' on';
		echo ' <a href="https://'.$institution->subdomain.'.voxcharta.org/'.date('Y/m/d', $post_time).'">' . date('m/d/Y', $post_time) . '</a>. ';
		comments_popup_link(__('No Comments', 'arclite'), __('1 Comment', 'arclite'), __('% Comments', 'arclite'), 'comments', __('Comments off', 'arclite')); echo '. '; edit_post_link(__('Edit this post','arclite'),'','.');
		echo '</p>';
		?>
	  </div>
	
	  <div class="post-content clearfix" style="margin-top: 0.5em;">
	  <?php if(get_option('arclite_indexposts')=='excerpt') the_excerpt(); else the_content(__('Read the rest of this entry &raquo;', 'arclite')); ?>
	
	  </div>
	
	</div>
	<!-- /post -->
	
	<?php $counter++;
	} ?>
	</div></div>
	<?php if (!is_numeric($club_check) && count($aquery->posts) > 0) {?>
	<div class="darkpostbody"><div style="margin-top: -0.5em; padding: 5px 30px 5px 30px; font-size: 10pt;">End of announcements, today's discussion agenda:</div></div>
	<?php }} ?>

<!--End announcements-->

	<?php if (is_numeric($club_check)) {
       if ($event->categoryslug == '') { ?>
<!--Free-Form Discussion-->
	   <?php if (count($aquery->posts) == 0) { ?>
	   <div class="lightpostbody"><div style="padding: 0px; height: 15px;"></div></div>
	   <?php } ?>
	   <div class="darkpostbody"><div style="padding: 5px 30px 5px 30px;">
	   <?php if ($today > time() - 86400) { ?>
		   There will be no formal astro-ph discussion today, today's discussion will be free-form. Any papers receiving votes today will appear in the agenda for <?php echo date('l', $next_co_time); ?>'s morning coffee discussion.
		   <?php if ($ishome) { ?>
			   Here's the agenda for <?php echo date('l', $next_co_time); ?>:
		   <?php } else { ?>
			   </div></div><div class="lightpostbody"><div style="padding: 0px; height: 15px;">
	   <?php }} else { ?>
		   There was no formal astro-ph discussion today, today's discussion was free-form. Any papers receiving votes on this day appeared in the agenda for <?php echo date('l', $next_co_time); ?>'s morning coffee discussion.
		   </div></div><div class="lightpostbody"><div style="padding: 0px; height: 15px;">
	   <?php } ?>
	   </div></div>
<!--Free-Form Discussion-->

<!--Events-->
	<?php
		} else {
		if (!$ec) {
        add_filter('posts_where', 'filter_event');
        $equery = new WP_Query('category_name='.$event->categoryslug.'&orderby=date&order=DESC&showposts=1');
        remove_filter('posts_where', 'filter_event');
	    if (count($aquery->posts) == 0) {
			echo '<div class="lightpostbody"><div style="padding: 0px; height: 15px;"></div></div>';
	    }
		echo '<div class="darkpostbody"><div><div class="'.$event->theme.'head">'.$event->nicename.'</div></div></div>';
		echo '<div class="lightpostbody"><div style="padding: 10px 30px 11px;">';
        if ($equery->have_posts()) {
        while ($equery->have_posts()) : $equery->the_post(); ?>
        <!-- post -->
        <div id="post-<?php the_ID(); ?>" <?php if (function_exists("post_class")) post_class(); else print 'class="post"'; ?>>

          <div class="post-header">
           <p class="post-author">
		    <div style="margin-top: -2.9em; margin-left: 0.5em; height: 1.7em;">
            <?php printf(__('Posted by %s in %s','arclite'),'<a href="'. get_author_posts_url(get_the_author_ID()) .'" title="'. sprintf(__("Posts by %s","arclite"), esc_attr(get_the_author())).' ">'. get_the_author() .'</a>',get_the_category_list(', '));
            ?> | <?php comments_popup_link(__('No Comments', 'arclite'), __('1 Comment', 'arclite'), __('% Comments', 'arclite'), 'comments', __('Comments off', 'arclite')); ?>  <?php edit_post_link(__('Edit','arclite'),' | '); ?>
			<div style="margin-top: -2.2em;"><?php echo get_category_graphics(get_the_ID()); ?></div>
			</div>
           </p>
          </div>

          <div class="post-content clearfix" style="margin-top: 1.5em;">
          <?php if(get_option('arclite_indexposts')=='excerpt') the_excerpt(); else the_content(__('Read the rest of this entry &raquo;', 'arclite')); ?>
          </div>

        </div>
        <!-- /post -->

       <?php endwhile; ?>
       <?php } elseif ($event->noagenda) { ?>
	   There is no set agenda for <?php echo $event->nicename;?> events.
       <?php } else { ?>
       The person responsible for selecting this week's agenda for <?php echo $event->nicename; ?> has yet to post the list of papers. Please bear with us.
       <?php } ?>
	   <?php echo '</div></div>'; ?>
	   <?php if ($ishome && $event->noagenda) { ?>
		   <div class="darkpostbody"><div style="padding: 5px 30px 5px 30px;">
		   Any papers receiving votes today will appear in the agenda for <?php echo date('l', $next_co_time); ?>'s morning coffee discussion. Here's the agenda for <?php echo date('l', $next_co_time); ?>:</div></div>
	   <?php } ?>
       <?php } else { ?>
	   <div class="darkpostbody"><div style="padding: 5px 30px 5px 30px;">
		   <?php echo $event->nicename . ' ' . (($ishome) ? 'has been' : 'was'); ?> canceled for <?php echo date('l', $next_an_time); ?>.
	       <?php if (!$ec) echo 'Here\'s the agenda for '.date('l', $next_co_time).':'; ?>
		   </div></div>
       <?php }}} ?>

<!--End Events-->

  <?php
  if ((!$ishome && $next_coffee == $next_an_offset) || $ishome) {
	  if ($ishome && $next_coffee != $next_an_offset && $ec) {
		  echo '<div class="darkpostbody"><div style="padding: 5px 30px 5px 30px;">';
		  echo 'Additionally, the regular astro-ph discussion '. (($ishome) ? 'has also been' : 'was also') . ' canceled today. ';
		  echo 'Any papers receiving votes today will appear in the agenda for ' . date('l', $next_co_time) .'\'s morning coffee discussion. ';
		  echo 'Here\'s the agenda for ' . date('l', $next_co_time) . ':</div></div>';
	  } ?>
      <div class="lightpostbody" style="text-align: center;"><div>
	  <div class="loadingtext" id="agendaloading"><img src="<?php echo get_option('siteurl'); ?>/wp-content/themes/arclite/loading.gif">Reloading agenda...</div>
	  <div id="showotherinsts">
      <form method="get" id="voteform" action="">
      <input type=checkbox name="show_everyone" id="everyone_check" <?php if ($show_everyone) echo 'checked="yes"';?> onchange="
      	set_cookie('show_everyone',((this.checked == true) ? '1' : '0'),365,'/','.voxcharta.org','');
      	if (document.getElementById('mostvoted')) {
      		scripturl = '<?php get_option("siteurl") ?>/wp-content/plugins/vote-it-up/skins/ticker/mostvoted.php';
      		lg_AJAXagenda(scripturl, <?php echo $today; ?>, <?php echo $ishome; ?>);
      	}
	  "> <label for="everyone_check">Show votes from other institutions.</label>
      </form>
	  </div>
	  <?php
	  if (CountMostVoted($sorted, false) > 0) { ?>
		  </div></div><div class="darksep"><div>
		  </div></div><div class="lightpostbody"><div id="visibleagendaspace" style="padding: 10px 30px 11px;"><?php
	  } else { 
		  $last_time_to_vote = GetResetTime($next_co_time)-max($institution->announcedelay,$institution->closedelay)*60;
		  $agenda_day = date('l', $last_time_to_vote);
		  echo '</div></div><div class="darkpostbody"><div style="padding: 5px 30px 5px 30px;">';
		  echo ($ishome) ? "<strong>{$institution->name}'s agenda is currently empty!</strong> Please vote for papers you'd like to discuss before ".date('g:i a',$last_time_to_vote)." on {$agenda_day}." : "No discussion agenda for this day.";
		  echo '</div></div><div class="lightpostbody" style="text-align: center;"><div style="padding: 10px 30px 11px;">';
	  }
	  if (CountMostVoted($sorted, true) > 0) {
		  MostVotedAllTime($sorted);
	  }
	  if ($ishome) echo '<div class="asof">Agenda auto-refreshes every 5 minutes, last refreshed at '.date('g:i:s a', time()).'.</div>';
	  echo '</div></div>';
  }?>
	<div class="boxfootr"><div class="boxfootl"></div></div>
