<?php
class EM_Calendar extends EM_Object {
	
	function init(){
		add_action('wp_head', array('EM_Calendar', 'insert_js'));
	}
	
	function output($args = array()) {
	 	global $wpdb; 
	 	
		$args = self::get_default_search($args);
		$full = $args['full']; //For ZDE, don't delete pls
		$month = $args['month']; 
		$year = $args['year'];
		$long_events = $args['long_events'];
		
		$week_starts_on_sunday = get_option('dbem_week_starts_sunday');
	   	$start_of_week = get_option('start_of_week');
		
		if( !(is_numeric($month) && $month <= 12 && $month > 0) )   {
			$month = date('m'); 
		}
		if( !( is_numeric($year) ) ){
			$year = date('Y');
		}  
		  
		// Get the first day of the month 
		$month_start = mktime(0,0,0,$month, 1, $year);
		// Get friendly month name 		
		$month_name = date('M',$month_start);
		// Figure out which day of the week 
		// the month starts on. 
		$month_start_day = date('D', $month_start);
	  
	  	switch($month_start_day){ 
			case "Sun": $offset = 0; break; 
			case "Mon": $offset = 1; break; 
			case "Tue": $offset = 2; break; 
			case "Wed": $offset = 3; break; 
			case "Thu": $offset = 4; break; 
			case "Fri": $offset = 5; break; 
			case "Sat": $offset = 6; break;
		}       
		//We need to go back to the WP defined day when the week started, in case the event day is near the end
		$offset -= $start_of_week;
		if($offset<0)
			$offset += 7;
		
		// determine how many days are in the last month.
		$month_last = $month-1;
		$month_next = $month+1;
		$year_last = $year; 
		$year_next = $year;
		if($month == 1) { 
		   $month_last = 12;
		   $year_last = $year -1;
		}
		$num_days_last = self::days_in_month($month_last, $year_last);
		 
		// determine how many days are in the current month. 
		$num_days_current = self::days_in_month($month, $year);
		// Build an array for the current days 
		// in the month 
		for($i = 1; $i <= $num_days_current; $i++){ 
		   $num_days_array[] = mktime(0,0,0,$month, $i, $year); 
		}
		// Build an array for the number of days 
		// in last month 
		for($i = 1; $i <= $num_days_last; $i++){ 
		    $num_days_last_array[] = mktime(0,0,0,$month_last, $i, $year_last); 
		}
		// If the $offset from the starting day of the 
		// week happens to be Sunday, $offset would be 0, 
		// so don't need an offset correction. 
	
		if($offset > 0){ 
		    $offset_correction = array_slice($num_days_last_array, -$offset, $offset); 
		    $new_count = array_merge($offset_correction, $num_days_array); 
		    $offset_count = count($offset_correction); 
		} else { // The else statement is to prevent building the $offset array. 
		    $offset_count = 0; 
		    $new_count = $num_days_array;
		}
		// count how many days we have with the two 
		// previous arrays merged together 
		$current_num = count($new_count); 
	
		// Since we will have 5 HTML table rows (TR) 
		// with 7 table data entries (TD) 
		// we need to fill in 35 TDs 
		// so, we will have to figure out 
		// how many days to appened to the end 
		// of the final array to make it 35 days. 
	
	
		if($current_num > 35){ 
		   $num_weeks = 6; 
		   $outset = (42 - $current_num); 
		} elseif($current_num < 35){ 
		   $num_weeks = 5; 
		   $outset = (35 - $current_num); 
		} 
		if($current_num == 35){ 
		   $num_weeks = 5; 
		   $outset = 0; 
		} 
		// Outset Correction 
		for($i = 1; $i <= $outset; $i++){ 
		   $new_count[] = mktime(0,0,0,$month_next, $i, $year_next);  
		}
		// Now let's "chunk" the $all_days array 
		// into weeks. Each week has 7 days 
		// so we will array_chunk it into 7 days. 
		$weeks = array_chunk($new_count, 7); 
		
		
	
		// Build Previous and Next Links 
		$base_link = "?".$_SERVER['QUERY_STRING']."&amp;";       
		  
		$full ? $link_extra_class = "full-link" : $link_extra_class = '';
		//Get an array of arguments that don't include default valued args
		$link_args = self::get_link_args($args);

		//Get the previous link
		if($month == 1){ 
			 $back_month = 12;
			 $back_year = $year-1;
		} else { 
		   $back_month = $month -1;
			 $back_year = $year;
		}
		$previous_link = "<a class='em-calnav $link_extra_class' href='?ajaxCalendar=1&amp;month={$back_month}&amp;year={$back_year}&amp;{$link_args}'>&lt;&lt;</a>"; 

		//Now the next
		if($month == 12){ 
			$next_month = 1;
			$next_year = $year+1;
		} else { 
			$next_month = $month + 1;
			$next_year = $year;	
		} 
		$next_link = "<a class='em-calnav $link_extra_class' href='?ajaxCalendar=1&amp;month={$next_month}&amp;year={$next_year}&amp;{$link_args}'>&gt;&gt;</a>";
		
		
		$class = ($full) ? 'dbem-calendar-full' : 'dbem-calendar';
		$calendar="<div class='$class'><div style='display:none' class='month_n'>$month</div><div class='year_n' style='display:none' >$year</div>";
		
	 	$weekdays = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
	   $n = 0 ;
		while( $n < $start_of_week ) {   
			$last_day = array_shift($weekdays);     
			$weekdays[]= $last_day; 
			$n++;
		}
	   
		$days_initials = "";
		foreach($weekdays as $weekday) {
			$days_initials .= "<td>".self::translate_and_trim($weekday)."</td>";
		} 
		$full ? $fullclass = 'fullcalendar' : $fullclass='';
		// Build the heading portion of the calendar table 
		$calendar .=  "<table class='dbem-calendar-table $fullclass'>\n". 
		   	"<thead>\n<tr>\n".
			"<td>$previous_link</td><td class='month_name' colspan='5'>". apply_filters('dbem_calendar_output_month', ucfirst(date_i18n('M', $month_start)), $month_start, $args)." $year</td><td>$next_link</td>\n". 
			"</tr>\n</thead>\n".	
		    "<tr class='days-names'>\n". 
		    $days_initials. 
		    "</tr>\n"; 
	
		// Now we break each key of the array  
		// into a week and create a new table row for each 
		// week with the days of that week in the table data 
	  
		$i = 0;
		$current_date = date('Y-m-d', current_time('timestamp'));
		foreach ( $weeks as $week ) {
			$calendar .= "<tr>\n";
			foreach ( $week as $d ) {
				if ($i < $offset_count) { //if it is PREVIOUS month
					$calendar .= "<td class='eventless-pre'>" . date ( 'j', $d ) . "</td>\n";
				}
				if (($i >= $offset_count) && ($i < ($num_weeks * 7) - $outset)) { // if it is THIS month
					$day = date ( 'j', $d );
					if ( $current_date == date('Y-m-d', $d) ){					
						$calendar .= "<td class='eventless-today'>$day</td>\n";
					} else {
						$calendar .= "<td class='eventless'>$day</td>\n";
					}
				} elseif (($outset > 0)) { //if it is NEXT month
					if (($i >= ($num_weeks * 7) - $outset)) {
						$calendar .= "<td class='eventless-post'>" . date ( 'j', $d ) . "</td>\n";
					}
				}
				$i ++;
			}
			$calendar .= "</tr>\n";
		} 
		
		$calendar .= " </table>\n</div>";
		
			// query the database for events in this time span
		if ($month == 1) {
			$month_pre=12;
			$month_post=2;
			$year_pre=$year-1;
			$year_post=$year;
		} elseif($month == 12) {
			$month_pre=11;
			$month_post=1;
			$year_pre=$year;
			$year_post=$year+1;
		} else {
			$month_pre=$month-1;
			$month_post=$month+1;
			$year_pre=$year;
			$year_post=$year;
		}
		$args['year'] = array($year_pre, $year_post);
		$args['month'] = array($month_pre, $month_post);
		$events = EM_Events::get($args);
	
		$eventful_days= array();
		if($events){
			//Go through the events and slot them into the right d-m index
			foreach($events as $event) {
				$event = apply_filters('em_calendar_output_loop_start', $event);
				if( $long_events ){
					//If $long_events is set then show a date as eventful if there is an multi-day event which runs during that day
					$event_start_date = mktime(0,0,0,$month_pre,1,$year_pre);
					$event_end_date = mktime(0,0,0,$month_post,date('t', $event_start_date),$year_post );
					if( $event_end_date == '' ) $event_end_date = $event_start_date;
					while( $event_start_date <= $event->end ){
						//Ensure date is within event dates, if so add to eventful days array
						if( $event_start_date > $event->start - (86400) ){ //subtract a day since start may be later in day
							$event_eventful_date = date('Y-m-d', $event_start_date);
							if( array_key_exists($event_eventful_date, $eventful_days) && is_array($eventful_days[$event_eventful_date]) ){
								$eventful_days[$event_eventful_date][] = $event; 
							} else {
								$eventful_days[$event_eventful_date] = array($event);  
							}	
						}	
						$event_start_date += (86400); //add a day		
					}
				}else{
					//Only show events on the day that they start
					if( isset($eventful_days[$event->start_date]) && is_array($eventful_days[$event->start_date]) ){
						$eventful_days[$event->start_date][] = $event; 
					} else {
						$eventful_days[$event->start_date] = array($event);  
					}
				}
				$event = apply_filters('em_calendar_output_loop_end', $event);
			}
		}
	   
		$event_format = get_option('dbem_full_calendar_event_format'); 
		$event_title_format = get_option('dbem_small_calendar_event_title_format');
		$event_title_separator_format = get_option('dbem_small_calendar_event_title_separator');
		$cells = array() ;
		foreach($eventful_days as $day_key => $events) {
			//Set the date into the key
			$event_start_date = explode('-', $day_key);
			$cells[$day_key]['day'] = ltrim($event_start_date[2],'0');  
			$cells[$day_key]['month'] = $event_start_date[1];  
			$cells[$day_key]['year'] = $event_start_date[0];
			$events_titles = array();
			foreach($events as $event) { 
				$events_titles[] = $event->output($event_title_format);
			}   
			$link_title = implode( $event_title_separator_format, $events_titles);  
			
			$events_page_id = get_option('dbem_events_page');
			$event_page_link = get_permalink($events_page_id);
			if (stristr($event_page_link, "?"))
				$joiner = "&amp;";
			else
				$joiner = "?";
			
			
			$cells[$day_key]['cell'] = "<a title='$link_title' href='".$event_page_link.$joiner."calendar_day={$day_key}'>{$cells[$day_key]['day']}</a>";
			if ($full) {
				$cells[$day_key]['cell'] .= "<ul>";
			
				foreach($events as $event) {
					$cells[$day_key]['cell'] .= $event->output($event_format);
				} 
				$cells[$day_key]['cell'] .= "</ul>";  
	   		}
		}
		
		if($events){
			foreach($cells as $cell) {  
				if ($cell['month'] == $month_pre && $cell['year'] == $year_pre) {
				 	$calendar = str_replace("<td class='eventless-pre'>".$cell['day']."</td>","<td class='eventful-pre'>".$cell['cell']."</td>",$calendar);
				} elseif($cell['month'] == $month_post && $cell['year'] == $year_post) {
				 	$calendar = str_replace("<td class='eventless-post'>".$cell['day']."</td>","<td class='eventful-post'>".$cell['cell']."</td>",$calendar);
				} elseif( date('Y-m-j', current_time('timestamp')) == $cell['year']."-".$cell['month']."-".$cell['day'] ) {
	  			 	$calendar = str_replace("<td class='eventless-today'>".$cell['day']."</td>","<td class='eventful-today'>".$cell['cell']."</td>",$calendar);
				} elseif( $cell['month'] == $month && $cell['year'] == $year){   
			    	$calendar = str_replace("<td class='eventless'>".$cell['day']."</td>","<td class='eventful'>".$cell['cell']."</td>",$calendar);
		   		}
			}
		}       
		return apply_filters('em_calendar_output', '<div id="em-calendar-'.rand(100,200).'" class="em-calendar-wrapper">'.$calendar.'</div>');
	}

	/**
	 * Echoes the calendar external JS contents directly into the head of the document
	 * @return unknown_type
	 */
	function insert_js() { 
		?>
<script type='text/javascript'>
		<?php include(WP_PLUGIN_DIR.'/events-manager/includes/js/em_calendar_ajax.js'); ?>	
		</script>
<?php
	}


	function days_in_month($month, $year) {
		return date('t', mktime(0,0,0,$month,1,$year));
	}
	 
	function translate_and_trim($string, $length = 1) {
		return substr(__($string), 0, $length);
	}  
	
	/**
	 * Helper function to create a link querystring from array which contains arguments with only values that aren't defuaults. 
	 */
	function get_link_args($args = array(), $html_entities=true){
		unset($args['month']); unset($args['year']);
		$default_args = self::get_default_search(array());
		foreach($default_args as $arg_key => $arg_value){
			if( !isset($args[$arg_key]) || $args[$arg_key] == $arg_value ){
				unset($args[$arg_key]);				
			}
		}
		$qs_array = array();
		foreach($args as $key => $value){
			if(is_array($value)){
				$value = implode(',',$value);
			}
			$qs_array[] = "$key=".urlencode($value);
		}
		return ($html_entities) ? implode('&amp;', $qs_array) : implode('&', $qs_array);
	}
		
	
	function get_default_search($array=array()){
		//These defaults aren't for db queries, but flags for what to display in calendar output
		$defaults = array( 
			'full' => 0, //Will display a full calendar with event names
			'long_events' => 0, //Events that last longer than a day
			'scope' => 'future',
			'owner' => false
		);
		$atts = parent::get_default_search($defaults, $array);
		$atts['full'] = ($atts['full']==true) ? 1:0;
		$atts['long_events'] = ($atts['long_events']==true) ? 1:0;
		return apply_filters('em_calendar_get_default_search', $atts, $array, $defaults);
	}
} 
add_action('init', array('EM_Calendar', 'init'));
?>