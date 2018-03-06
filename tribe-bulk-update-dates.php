<?php
/*
Plugin Name: Tribe Bulk Update Event Dates
Plugin URI: http://tri.be
Version: .5

Description: Test to see if your site can validate a URL

Author: Brian Jessee
Author URI: http://jesseeproductions.com

Text Domain: coupon_creator
Domain Path: /languages/

License: GPL2

*/

add_action( 'admin_menu', 'tribe_validate_url_admin', 500 );

function tribe_validate_url_admin() {
	add_submenu_page( 'edit.php?post_type=tribe_events', 'Bulk Change Events Dates', 'Bulk Change Dates', 'manage_options', 'bulk-update', 'tribe_validate_page_callback' );
}

function tribe_validate_page_callback() {
	if ( current_user_can( 'manage_options' ) ) {
		?>
		<div class="wrap">

			<h2>Bulk Change Event Dates</h2>

			<p>Choose the amount of time you would like to add or subtract time to events</p>
			<p>Add an id, a comma delimited list of ids, or type all to select events to change.</p>
			<p>Then select options in the dropdowns, all fields are required.</p>

			<form method="post" action="">
				<?php $value = isset( $_POST["tribe_fast_forward"] ) ? $_POST["tribe_fast_forward"] : ''; ?>
				<input type="text" name="tribe_fast_forward" id="tribe_fast_forward" class="tribe_fast_forward" value="<?php echo $value; ?>"/>

				<?php
				$selected_num = isset( $_POST["tribe_fast_forward_number"] ) ? $_POST["tribe_fast_forward_number"] : '';
				?>
				<select id="tribe_fast_forward_number" class="select" name="tribe_fast_forward_number">
					<option value="">Choose Time</option>
					<?php
					$value = 1;
					do {

						echo '<option value="' . absint( $value ) . '"  ' . selected( $selected_num, $value, false ) . ' >' . absint( $value ) . '</option>';
						$value ++;

					} while ( $value <= 31 );
					?>
				</select>

				<?php
				$selected_type = isset( $_POST["tribe_fast_forward_date_type"] ) ? $_POST["tribe_fast_forward_date_type"] : '';
				?>
				<select id="tribe_fast_forward_date_type" class="select" name="tribe_fast_forward_date_type">
					<option value="">Choose Date Type</option>
					<option value="days" <?php echo selected( $selected_type, "days", false ); ?>>Day(s)</option>
					<option value="weeks" <?php echo selected( $selected_type, "weeks", false ); ?>>Week(s)</option>
					<option value="month" <?php echo selected( $selected_type, "month", false ); ?>>Month(s)</option>
					<option value="year" <?php echo selected( $selected_type, "year", false ); ?>>Year(s)</option>
				</select>
				<?php
				$selected_type = isset( $_POST["tribe_fast_forward_date_operator"] ) ? $_POST["tribe_fast_forward_date_operator"] : '';
				?>
				<select id="tribe_fast_forward_date_operator" class="select" name="tribe_fast_forward_date_operator">
					<option value="">Choose +|-</option>
					<option value="+" <?php echo selected( $selected_type, "+", false ); ?>>Add Time</option>
					<option value="-" <?php echo selected( $selected_type, "-", false ); ?>>Subtract Time</option>
				</select>

				<?php
				$selected_type = isset( $_POST["tribe_fast_forward_date_kindof"] ) ? $_POST["tribe_fast_forward_date_kindof"] : '';
				?>
				<select id="tribe_fast_forward_date_kindof" class="select" name="tribe_fast_forward_date_kindof">
					<option value="">Choose Query Type</option>
					<option value="custom" <?php echo selected( $selected_type, "custom", false ); ?>>All</option>
					<option value="upcoming" <?php echo selected( $selected_type, "upcoming", false ); ?>>Upcoming Events</option>
					<option value="past" <?php echo selected( $selected_type, "past", false ); ?>>Past Events</option>
				</select>

				<?php
				$selected_type = isset( $_POST["tribe_fast_forward_date_change"] ) ? $_POST["tribe_fast_forward_date_change"] : '';
				?>
				<select id="tribe_fast_forward_date_change" class="select" name="tribe_fast_forward_date_change">
					<option value="">Choose Change|Test</option>
					<option value="change" <?php echo selected( $selected_type, "change", false ); ?>>Change Dates</option>
					<option value="test" <?php echo selected( $selected_type, "test", false ); ?>>Test Change</option>
				</select>

				<input type="submit" id="fast_forward_submit" name="tribe_fast_forward_button" value="<?php _e( 'Fast Forward Event Dates', 'tribe-fast-forward' ); ?>" class="button-primary"/>

			</form>
			<p></p>
		</div>
		<?php

		if ( ! isset( $_POST["tribe_fast_forward"] ) || ! isset( $_POST["tribe_fast_forward_date_type"] ) || ! isset( $_POST["tribe_fast_forward_button"] ) || ! isset( $_POST["tribe_fast_forward_date_operator"] ) || ! isset( $_POST["tribe_fast_forward_date_kindof"] ) || ! isset( $_POST["tribe_fast_forward_date_change"] ) ) {
			return;
		}

		$postsid = $postsids = $postper = '';
		if ( 'all' === $_POST["tribe_fast_forward"] ) {
			$postper = '-1';
		} elseif ( is_numeric( $_POST["tribe_fast_forward"] ) ) {
			$postsid = absint( $_POST["tribe_fast_forward"] );
		} elseif ( isset( $_POST["tribe_fast_forward"] ) ) {
			$postsids = explode( ',', $_POST["tribe_fast_forward"] );
		}

		$eventDisplay = $_POST["tribe_fast_forward_date_kindof"];

		$args = array(
			'p'              => $postsid,
			'post__in'       => $postsids,
			'posts_per_page' => $postper,
			'post_type'      => 'tribe_events',
			'eventDisplay'   => $eventDisplay,
			'post_status'    => 'publish',
		);

		$events = new WP_Query( $args );

		$date_format = Tribe__Date_Utils::DBDATETIMEFORMAT;
		$op          = $_POST["tribe_fast_forward_date_operator"];
		$change      = $_POST["tribe_fast_forward_date_change"];
		$forward     = ' ' . esc_attr( $op ) . ' ' . absint( $_POST["tribe_fast_forward_number"] ) . ' ' . esc_attr( $_POST["tribe_fast_forward_date_type"] );

		echo 'Changing Dates by ' . $forward . ' type<br><br>';

		// Loop and change
		while ( $events->have_posts() ) {

			$events->the_post();

			$event_id       = $events->post->ID;
			$start_time     = get_post_meta( $event_id, '_EventStartDate', true );
			$end_time       = get_post_meta( $event_id, '_EventEndDate', true );
			$start_time_utc = get_post_meta( $event_id, '_EventStartDateUTC', true );
			$end_time_utc   = get_post_meta( $event_id, '_EventEndDateUTC', true );

			echo get_the_title( $event_id ) . '<br>';
			echo $event_id . ' id changing date time to:<br>';
			echo date( $date_format, strtotime( $start_time . $forward ) ) . ' start<br>';
			echo date( $date_format, strtotime( $start_time_utc . $forward ) ) . ' start utc<br>';
			echo date( $date_format, strtotime( $end_time . $forward ) ) . ' end<br>';
			echo date( $date_format, strtotime( $end_time_utc . $forward ) ) . ' end utc<br><br>';

			if ( 'change' === $change ) {
				update_post_meta( $event_id, '_EventStartDate', date( $date_format, strtotime( $start_time . $forward ) ) );
				update_post_meta( $event_id, '_EventEndDate', date( $date_format, strtotime( $end_time . $forward ) ) );
				update_post_meta( $event_id, '_EventStartDateUTC', date( $date_format, strtotime( $start_time_utc . $forward ) ) );
				update_post_meta( $event_id, '_EventEndDateUTC', date( $date_format, strtotime( $end_time_utc . $forward ) ) );
			}

		} //End While
	}
}