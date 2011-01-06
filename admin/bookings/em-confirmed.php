<?php

/**
 * Generates a "widget" table of confirmed bookings for a specific event.
 * 
 * @param int $event_id
 */
function em_bookings_confirmed_table(){
	global $EM_Event, $wpdb, $current_user;
	
	$action_scope = ( !empty($_REQUEST['em_obj']) && $_REQUEST['em_obj'] == 'em_bookings_confirmed_table' );
	$action = ( $action_scope && !empty($_GET ['action']) ) ? $_GET ['action']:'';
	$order = ( $action_scope && !empty($_GET ['order']) ) ? $_GET ['order']:'ASC';
	$limit = ( $action_scope && !empty($_GET['limit']) ) ? $_GET['limit'] : 20;//Default limit
	$page = ( $action_scope && !empty($_GET['p']) ) ? $_GET['p']:1;
	$offset = ( $action_scope && $page > 1 ) ? ($page-1)*$limit : 0;
	
	if( is_object($EM_Event) ){
		$bookings = $EM_Event->get_bookings()->get_bookings();
	}else{
		return false;
	}
	$bookings_count = (is_array($bookings)) ? count($bookings):0;
	?>
		<div class='wrap em_bookings_pending_table em_obj'>
			<form id='bookings-filter' method='get' action='<?php bloginfo('wpurl') ?>/wp-admin/edit.php'>
				<input type="hidden" name="em_obj" value="em_bookings_pending_table" />
				<!--
				<ul class="subsubsub">
					<li>
						<a href='edit.php?post_type=post' class="current">All <span class="count">(1)</span></a> |
					</li>
				</ul>
				<p class="search-box">
					<label class="screen-reader-text" for="post-search-input"><?php _e('Search'); ?>:</label>
					<input type="text" id="post-search-input" name="em_search" value="<?php echo (!empty($_GET['em_search'])) ? $_GET['em_search']:''; ?>" />
					<input type="submit" value="<?php _e('Search'); ?>" class="button" />
				</p>
				-->
				<?php if ( $bookings_count >= $limit ) : ?>
				<div class='tablenav'>
					<!--
					<div class="alignleft actions">
						<select name="action">
							<option value="-1" selected="selected">
								<?php _e('Bulk Actions'); ?>
							</option>
							<option value="approve">
								<?php _e('Approve', 'dbem'); ?>
							</option>
							<option value="decline">
								<?php _e('Decline', 'dbem'); ?>
							</option>
						</select> 
						<input type="submit" id="post-query-submit" value="Filter" class="button-secondary" />
					</div>
					-->
					<!--
					<div class="view-switch">
						<a href="/wp-admin/edit.php?mode=list"><img class="current" id="view-switch-list" src="http://wordpress.lan/wp-includes/images/blank.gif" width="20" height="20" title="List View" alt="List View" name="view-switch-list" /></a> <a href="/wp-admin/edit.php?mode=excerpt"><img id="view-switch-excerpt" src="http://wordpress.lan/wp-includes/images/blank.gif" width="20" height="20" title="Excerpt View" alt="Excerpt View" name="view-switch-excerpt" /></a>
					</div>
					-->
					<?php 
					if ( $bookings_count >= $limit ) {
						$page_link_template = em_add_get_params($_SERVER['REQUEST_URI'], array('p'=>'%PAGE%', 'em_ajax'=>0, 'em_obj'=>'em_bookings_confirmed_table'));
						$bookings_nav .= em_admin_paginate( $page_link_template, $bookings_count, $limit, $page, 5);
						echo $bookings_nav;
					}
					?>
					<div class="clear"></div>
				</div>
				<?php endif; ?>
				<div class="clear"></div>
				<?php if( $bookings_count > 0 ): ?>
				<div class='table-wrap'>
				<table id='dbem-bookings-table' class='widefat post fixed'>
					<thead>
						<tr>
							<th class='manage-column column-cb check-column' scope='col'>
								<input class='select-all' type="checkbox" value='1' />
							</th>
							<th class='manage-column' scope='col'>Booker</th>
							<th class='manage-column' scope='col'>E-mail</th>
							<th class='manage-column' scope='col'>Phone number</th>
							<th class='manage-column' scope='col'>Spaces</th>
							<th class='manage-column' scope='col'>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						$rowno = 0;
						$event_count = 0;
						foreach ($bookings as $EM_Booking) {
							if( ($rowno < $limit || empty($limit)) && ($event_count >= $offset || $offset === 0) ) {
								$rowno++;
								?>
								<tr>
									<th scope="row" class="check-column" style="padding:7px 0px 7px;"><input type='checkbox' value='<?php echo $EM_Booking->id ?>' name='bookings[]'/></th>
									<td><a href="<?php bloginfo ( 'wpurl' )?>/wp-admin/admin.php?page=events-manager-bookings&amp;person_id=<?php echo $EM_Booking->person_id; ?>"><?php echo $EM_Booking->person->name ?></a></td>
									<td><?php echo $EM_Booking->person->email ?></td>
									<td><?php echo $EM_Booking->person->phone ?></td>
									<td><?php echo $EM_Booking->seats ?></td>
									<td>
										<?php
										$unapprove_url = em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_unapprove', 'bookings'=>$EM_Booking->id));
										?>
										<a class="em-bookings-unapprove" href="<?php echo $unapprove_url ?>"><?php _e('Unapprove','dbem'); ?></a>
									</td>
								</tr>
								<?php
							}
							$event_count++;
						}
						?>
					</tbody>
				</table>
				</div>
				<?php else: ?>
					<?php _e('No confirmed bookings.', 'dbem'); ?>
				<?php endif; ?>
			</form>
			<?php if( $bookings >= $limit ) : ?>
			<div class='tablenav'>
				<?php echo $bookings_nav; ?>
				<div class="clear"></div>
			</div>
			<?php endif; ?>
		</div>	
	<?php
	
}
?>