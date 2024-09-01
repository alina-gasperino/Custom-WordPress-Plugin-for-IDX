<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Dashboard extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	private static $agents = [];

	private static $filters = [];

	public function install() {

		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_init', [ $this, 'save_lead_details' ] );
		add_action( 'admin_init', [ $this, 'save_new_lead' ] );

		
		add_filter( 'login_redirect', function ( $url ) {
			if ( current_user_can( 'use_dashboard' ) ) {
				$url = admin_url( 'admin.php?page=leads' );
			}
			
			return $url;
			
		} ); 

		add_action( 'admin_menu', function () {
			if ( current_user_can( 'use_dashboard' ) ) {
				remove_menu_page( 'index.php' );
			}
		}, 99 );		

		
		add_action( 'admin_bar_menu', function ( $wp_admin_bar ) {
			
			if ( ! current_user_can( 'use_dashboard' ) ) {
				return;
			}

			$dash = $wp_admin_bar->get_node( 'dashboard' );
			if ( $dash ) {
				$dash->href = admin_url( 'admin.php?page=leads' );
				$dash->title = 'Agent Dashboard';
				$wp_admin_bar->remove_node( 'dashboard' );
				$wp_admin_bar->add_node( $dash );
			}

		}, 9999 );

		
		add_filter('wp_nav_menu_items', function ( $items, $args ) {
			if ( ! current_user_can( 'use_dashboard' ) ) {
				return $items;
			}
			if( $args->theme_location === 'site-navigation' ) {
				$items .= '<li class="menu-item mobile-only">'
					    . sprintf( '<a class="nav-link" href="%s">Agent Dashboard</a>', admin_url( 'admin.php?page=leads' ) )
					    . '</li>';
			}
			return $items;
		}, 10, 2);

	}

	public function add_page() {

		add_menu_page(
			__( 'Agent Dashboard', 'vestorfilter' ),
			'Agent Dashboard',
			'use_dashboard',
			'leads',
			array( $this, 'admin_page' ),
			'dashicons-admin-users',
			1
		);

        add_menu_page(
            __( 'Email report', 'vestorfilter' ),
            'Email report',
            'use_dashboard',
            'email-report',
            array( $this, 'getEmailReport' ),
            'dashicons-admin-users',
            95
        );

	}

	public function save_lead_details() {

		if ( ! current_user_can( 'see_leads' ) ) {
			return;
		}

		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'leads' ) {
			return;
		}

		if ( empty( $_REQUEST['user'] ) && empty( $_REQUEST['assign'] ) ) {
			return;
		}

		$user_id = absint( filter_var( $_REQUEST['user'] ?? $_REQUEST['assign'], FILTER_SANITIZE_NUMBER_INT ) );
		$to      = filter_var( $_REQUEST['to'] ?? '', FILTER_SANITIZE_STRING );
		$notes   = filter_var( $_REQUEST['notes'] ?? '', FILTER_SANITIZE_STRING );
		$contact = filter_var( $_REQUEST['contact'] ?? 0, FILTER_SANITIZE_STRING );
		if ( isset( $_REQUEST['tag'] ) ) {
			$tag = filter_var( $_REQUEST['tag'] ?? '', FILTER_SANITIZE_STRING );
			if ( ! in_array( $tag, Leads_Table::tags_allowed() ) ) {
				unset( $tag );
			}
		}
		$favorite = filter_var( $_REQUEST['new_favorite'] ?? '', FILTER_SANITIZE_STRING );
		$search = filter_var( $_REQUEST['new_search'] ?? '', FILTER_SANITIZE_STRING );
		if ( isset( $_REQUEST['subscription'] ) ) {
			$subscription = filter_var( $_REQUEST['subscription'], FILTER_SANITIZE_STRING );
		}
		if ( isset( $_REQUEST['search_name'] ) ) {
			$search_name = filter_var( $_REQUEST['search_name'], FILTER_SANITIZE_STRING );
		}
		$hash = filter_var( $_REQUEST['hash'] ?? '', FILTER_SANITIZE_STRING );

		if ( empty( $user_id ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'lead_management_' . $user_id ) ) {
			return;
		}

		if ( isset( $_REQUEST['delete'] ) ) {

			Favorites::delete_search( $user_id, $hash );

			wp_safe_redirect( add_query_arg( 'lead-saved', $user_id, remove_query_arg( [ 'delete', '_wpnonce', 'hash' ] ) ) );

			exit;

		}

		if ( ! empty( $to ) ) {

			if ( $to === 'self' && user_can( get_current_user_id(), 'see_leads' ) ) {
				$to = get_current_user_id();
			} elseif ( $to === 'clear' ) {
				$to = '';
			} else {
				$to = absint( $to );
			}
			if ( $to === false || $to === 0 ) {
				return;
			}

			update_user_meta( $user_id, '_assigned_agent', $to );

		}

		if ( ! empty( $notes ) ) {

			update_user_meta( $user_id, '_user_notes', $notes );

		}

		if ( ! empty( $contact ) ) {

			if ( is_numeric( $contact ) && $contact > 1 ) {

				update_user_meta( $user_id, '_last_contact', $contact );

			} else {

				update_user_meta( $user_id, '_last_contact', time() );

			}

		}

		if ( isset( $tag ) ) {

			$old_tag = get_user_meta( $user_id, '_lead_tag', true );
			if ( $old_tag !== $tag ) {
				update_user_meta( $user_id, '_lead_tag', sanitize_title( $tag ) );
				Calendar::generate_lead_tasks( $user_id, $tag );
			}

		}

		if ( isset( $search_name ) && ! empty( $hash ) ) {
			
			if ( Favorites::is_search_saved( $hash, $user_id ) ) {
		
				Favorites::set_search_name( $hash, $search_name, $user_id );

			}	

		}

		if ( ! empty( $subscription ) && ! empty( $hash ) ) {
			
			if ( Favorites::is_search_saved( $hash, $user_id ) ) {

				if ( $subscription !== 'never' ) {
					$subscription = absint( $subscription );
					if ( $subscription <= 0 || $subscription > 30 ) {
						$subscription = 7;
					}
				}
				
				Log::add( [ 'action' => 'search-subscribed', 'value' => $hash, 'user' => $user_id ] );
				Favorites::add_email_subscription( $hash, $subscription, $user_id );
			}	

		}

		if ( ! empty( $favorite ) ) {

			Favorites::add_agent_recommendation( $user_id, get_current_user_id(), $favorite );

		}

		if ( ! empty( $search ) ) {

			$filters = Search::parse_url_to_filters( $search );
			if ( $filters ) {
				$hash = md5( json_encode( $filters ) . implode( '', VF_ALLOWED_FEEDS ) );
				$all = Favorites::get_searches( $user_id );
				if ( ! isset( $all[ $hash ] ) ) {
					$filters['added_by'] = get_current_user_id();
					$all[ $hash ] = json_encode( $filters );
					update_user_meta( $user_id, '_favorite_searches', $all );
					wp_cache_delete( 'favorite_searches__' . $user_id, 'vestorfilter' );

					Log::add( [ 'action' => 'search-saved', 'value' => $hash, 'user' => $user_id ] );
				}
			}

		}

		wp_safe_redirect( add_query_arg( 'lead-saved', $user_id, remove_query_arg( [ '_wpnonce', 'assign', 'to', 'contact' ] ) ) );

		exit;

	}

	public function admin_page() {

		if ( isset( $_GET['user'] ) ) {
			$this->show_user_page( $_GET['user'] );
			return;
		}

		if ( isset( $_GET['create-new'] ) ) {
			$this->show_creation_page();
			return;
		}

		$lead_table = new Leads_Table();

		?>

		<div class="wrap">
			
			<h2>Incoming Leads <a href="<?= admin_url( 'admin.php?page=leads&create-new' ) ?>" class="button button-primary">Add New</a></h2>

			<?php if ( ! empty( $_GET['lead-saved'] ) ) : ?>
			<div class="notice notice-success notice-alt"><p>Lead data saved</p></div>
			<?php endif; ?>

			<div id="poststuff" class="lead-table">
				<div class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								
								$lead_table->prepare_items();
								$lead_table->search_box( 'Search', 'search' );
								$lead_table->display(); 
								
								?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
		<style>
			@media screen and (min-width:767px) {
				a[download] {
					display: none;
				}	
			}
			th#tag {
				width: 100px;
			}
			.tag.column-tag:hover .agent-tag,
			.tag.column-tag:not(:hover) select {
				display: none;
			}
			
			p.search-box {
				float: left;
			}
			.tablenav {
				clear: none;
			}
			tr .row-actions {
				position: static;
			}
		</style>
		<script>
			jQuery( function($) {
				$('select[data-agent-tag]').on('change', function(e) {
					var $this = $(e.currentTarget);
					var $tag = $this.parent().find('.agent-tag');
					$tag.attr( 'class', 'agent-tag ' + $this.val() );
					$tag.html( $this.val() );
					$.post('<?=$_SERVER['REQUEST_URI']?>',{
						tag: $this.val(),
						user: $this.data('user'),
						_wpnonce: $this.data('nonce')
					}).done(function() {
						
					});
				});
			} );
		</script>

		<?php

	}

	public function show_creation_page() {

		$agents = get_users( [
			'role__in' => [ 'agent', 'manager' ],
			'number'   => -1,
		] );

		$fields = [
			'user' => array(),
			'meta' => array(),
		];

		if ( isset( $_POST['user'] ) ) {
			foreach( $_POST['user'] as $key => $value ) {
				$fields['user'][ $key ] = filter_var( $value, FILTER_SANITIZE_STRING );
			}
		}
		if ( isset( $_POST['meta'] ) ) {
			foreach( $_POST['meta'] as $key => $value ) {
				$fields['meta'][ $key ] = filter_var( $value, FILTER_SANITIZE_STRING );
			}
		}

		global $post_errors;

		$goals = [
			'Find my next home.',
			'Find my next investment',
			'Sell then buy my next home.',
			'Sell then buy my next investment.',
			'Just looking for now.'
		];
		
		?>

		<div class="wrap">
			<h2>Create New Lead</h2>

			<?php if ( ! empty( $post_errors ) ) : ?>
			<div class="notice notice-error notice-alt"><p><?= esc_html( $post_errors ) ?></p></div>
			<?php endif; ?>

			<form action="" method="post">
				
				<?php wp_nonce_field( 'lead_create' ) ?>

				<table class="form-table" role="presentation">
					<tbody>

						<tr>
							<th scope="row"><label>First Name*</label></th>
							<td><input type="text" class="regular-text" name="user[first_name]" value="<?= esc_attr( $fields['user']['first_name'] ?? '' ) ?>" /></td>
						</tr>

						<tr>
							<th scope="row"><label>Last Name*</label></th>
							<td><input type="text" class="regular-text" name="user[last_name]" value="<?= esc_attr( $fields['user']['last_name'] ?? '' ) ?>" /></td>
						</tr>

						<tr>
							<th scope="row"><label>Phone</label></th>
							<td><input type="text" class="regular-text" name="meta[phone]" value="<?= esc_attr( $fields['meta']['phone'] ?? '' ) ?>" /></td>
						</tr>

						<tr>
							<th scope="row"><label>Email*</label></th>
							<td><input type="text" class="regular-text" name="user[user_email]" value="<?= esc_attr( $fields['user']['user_email'] ?? '' ) ?>" /></td>
						</tr>

						<tr>
							<th scope="row"><label>Password*</label></th>
							<td><input type="text" class="regular-text" name="user[user_pass]" value="<?= esc_attr( $fields['user']['user_pass'] ?? wp_generate_password() ) ?>" /></td>
						</tr>

						<tr>
							<th scope="row"><label>Goal</label></th>
							<td><select name="meta[goal]" >
								<option value=""></option>
								<?php foreach( $goals as $goal ) : ?>
								<option <?php selected( $goal, $fields['meta']['goal'] ?? '' ) ?>><?= $goal; ?></option>
								<?php endforeach; ?>
							</select></td>
						</tr>

						<?php if ( current_user_can( 'tag_leads' ) ) : ?>
						<tr>
							<th scope="row"><label for="lead_assignment">Assign to Agent</label></th>
							<td><select name="meta[_assigned_agent]" id="lead_assignment">

								<option value="clear">Unassigned</option>
								<?php foreach( $agents as $agent ) : ?>
								<option value="<?php echo $agent->ID; ?>" <?php selected( $agent->ID, $fields['meta']['_assigned_agent'] ?? '' ) ?>><?php echo $agent->display_name; ?></option>
								<?php endforeach; ?>

							</select></td>
						</tr>
						<?php endif; ?>

						<tr>
							<th scope="row"><label for="tag_assignment">Tag</label></th>
							<td><select name="meta[_lead_tag]" id="tag_assignment">
								<option value=""></option>
								<?php 
								foreach( Leads_Table::tags_allowed() as $tagopt ) {
									printf( '<option value="%s" %s>%s</option>', $tagopt, selected( $tagopt, $fields['meta']['_lead_tag'] ?? '' ), ucwords( $tagopt ) );
								}
								?>
							</select></td>
						</tr>

						<tr>
							<th scope="row"><label for="lead_notes">Notes</label></th>
							<td><textarea name="meta[_user_notes]" rows="10" cols="50" id="lead_notes" class="large-text code"><?= esc_attr( $fields['meta']['_user_notes'] ?? '' ) ?></textarea></td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" class="button button-primary" value="Save">
				</p>

			</form>

		</div>

		<?php


	}

	public function save_new_lead() {

		global $post_errors;

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'lead_create' ) ) {
			return;
		}

		if ( ! current_user_can( 'see_leads' ) ) {
			$post_errors = 'You do not have permission to create a new user.';
			return;
		}

		
		
		$user_fields = [
			'first_name',
			'last_name',
			'user_email',
			'user_pass',
		];

		$meta_fields = [
			'phone',
			'goal',
			'_assigned_agent',
			'_lead_tag',
			'_user_notes',
		];

		$user = [];
		foreach ( $user_fields as $key ) {
			$user[ $key ] = filter_var( $_POST['user'][ $key ] ?? '', FILTER_SANITIZE_STRING );
			if ( empty( $user[ $key ] ) ) {
				$post_errors = 'Please fill out all required fields';
				return;
			}
		}		

		if ( $existing = get_user_by( 'email', $user['user_email'] ) ) {
			$post_errors = 'A user with this email address already exists.';
			return;
		}

		$meta = [];
		foreach ( $meta_fields as $key ) {
			$meta[ $key ] = filter_var( $_POST['meta'][ $key ] ?? '', FILTER_SANITIZE_STRING );
		}

		$user['role']         = 'subscriber';
		$user['user_login']   = $user['user_email'];
		$user['display_name'] = $user['first_name'] . ' ' . $user['last_name'];
		$user['nickname']     = $user['first_name'];

		$user_id = wp_insert_user( $user );
		if ( empty( $user_id ) || is_wp_error( $user_id ) ) {
			$post_errors = 'There was an error creating this user. Please contact support.';
			return;
		}

		$meta['_created_by'] = get_current_user_id();
		foreach( $meta as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}

		wp_redirect( admin_url( "admin.php?page=leads&user={$user_id}" ) );
		exit;

	}

	public function show_user_page( $user_id ) {

		$user_id = absint( $user_id );
		if ( empty( $user_id ) ) {
			echo 'An error occurred loading the user details.';
		}

		$user = get_user_by( 'id', $user_id );

		

		$tabs = [ 'user' => 'Details', 'tasks' => 'Tasks', 'homes' => 'Homes', 'saved' => 'Searches', 'forms' => 'Forms Filled Out', 'activity' => 'Activity Log' ];

		$active_tab = sanitize_title( $_GET['tab'] ?? 'user' );

		add_action( 'agent_dashboard_active_tab__user', [ $this, 'tab_html' ] );
		
		?>

		<div class="wrap">
			<h2 style="display:flex;flex-wrap:wrap">Lead Details - <?php echo $user->display_name ?> 
				<small><a  style="margin-left: 10px;" class="button button-secondary" href="<?= admin_url( 'admin.php?page=leads' ) ?>">See all Leads</a></small>
				<a style="margin-left: auto;" target="_blank" class="button button-primary" href="<?= Favorites::get_favorites_url( $user->ID ) ?>">User's Public Page</a>
			</h2>

			<?php if ( ! empty( $_GET['lead-saved'] ) ) : ?>
			<div class="notice notice-success notice-alt"><p>Lead data saved</p></div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper lead-tabs">
				<?php foreach ( $tabs as $key => $label ) : ?>
				<a href="<?php echo add_query_arg( 'tab', $key ); ?>" class="nav-tab <?= $active_tab === $key ? 'nav-tab-active' : ''; ?>"><?= $label ?></a>
				<?php endforeach; ?>
			</nav>

			<div class="tab-content">

				<?php do_action( 'agent_dashboard_active_tab__' . $active_tab, $user ); ?>

			</div>

		</div>
		<style>
			.metabox-holder .tablenav {
				display: none;
			}
		</style>

		<?php

	}

	public function tab_html( $user ) {

		$agents = get_users( [
			'role__in' => [ 'agent', 'manager' ],
			'number'   => -1,
		] );

		$current_agent = get_user_meta( $user->ID, '_assigned_agent', true );
		$notes = get_user_meta( $user->ID, '_user_notes', true );
		$date = get_user_meta( $user->ID, '_last_contact', true );
		$tag = get_user_meta( $user->ID, '_lead_tag', true );

		$phone = get_user_meta( $user->ID, 'phone', true );
		$goal = get_user_meta( $user->ID, 'goal', true );

		$creator = absint( get_user_meta( $user->ID, '_created_by', true ) );
		if ( $creator ) {
			$created_by = get_user_by( 'id', $creator );
		}

		?>

		<form action="<?php add_query_arg( [ 'page' => 'leads', 'tab' => 'user', 'user' => $user->ID ] ) ?>" method="POST">
			
			<?php wp_nonce_field( 'lead_management_' . $user->ID ) ?>

			<table class="form-table" role="presentation">
				<tbody>

					<tr>
						<th scole="row"><label>Public Page</label></th>
						<td><a href="<?= Favorites::get_favorites_url( $user->ID ) ?>" target="_blank">View</a></td>

					</tr>

					<tr>
						<th scope="row"><label>Name</label></th>
						<td><?= $user->display_name ?></td>
					</tr>

					<tr>
						<th scope="row"><label>Phone</label></th>
						<td><a href="tel:<?= $phone ?>"><?= $phone ?></td>
					</tr>

					<tr>
						<th scope="row"><label>Email</label></th>
						<td><a href="mailto:<?= $user->user_email ?>"><?= $user->user_email ?></td>
					</tr>

					<tr>
						<th scope="row"><label>Goal</label></th>
						<td><?= $goal ?></td>
					</tr>

					<tr>
						<th scope="row"><label>Registered</label></th>
						<td><?= get_date_from_gmt( $user->user_registered, 'm/d/Y h:i a' ); ?></td>
					</tr>

					<?php if ( current_user_can( 'tag_leads' ) ) : ?>
					<tr>
						<th scope="row"><label for="lead_assignment">Assign to Agent</label></th>
						<td><select name="to" id="lead_assignment">

							<option value="clear">Unassigned</option>
							<?php foreach( $agents as $agent ) : ?>
							<option value="<?php echo $agent->ID; ?>" <?php selected( $agent->ID, $current_agent ) ?>><?php echo $agent->display_name; ?></option>
							<?php endforeach; ?>

						</select></td>
					</tr>
					<?php endif; ?>

					<tr>
						<th scope="row"><label for="tag_assignment">Tag</label></th>
						<td><select name="tag" id="tag_assignment">
							<option value=""></option>
							<?php 
							foreach( Leads_Table::tags_allowed() as $tagopt ) {
								printf( '<option value="%s" %s>%s</option>', $tagopt, selected( $tag, $tagopt, false ), ucwords( $tagopt ) );
							}
							?>
						</select></td>
					</tr>
					

					<?php //if ( ! empty( $date ) ) : ?>
					<tr>
						<th scope="row"><label for="contact_attempt">Last Contact Attempt</label></th>
						<td><?php if ( empty( $date ) ) : ?>
							<input type="checkbox" name="contact" id="contact_attempt"> Set to now
						<?php else: ?>
							<?php echo get_date_from_gmt( date( 'm/d/Y h:i a', $date ), 'F j, Y h:i a' ); ?>
						<?php endif; ?></td>
					</tr>
					<?php //endif; ?>

					<?php if ( ! empty( $created_by ) ) : ?>
					<tr>
						<th scope="row"><label for="contact_attempt">User Created By</label></th>
						<td><?= $created_by->display_name ?></td>
					</tr>
					<?php endif; ?>

					<tr>
						<th scope="row"><label for="lead_notes">Notes</label></th>
						<td><textarea name="notes" rows="10" cols="50" id="lead_notes" class="large-text code"><?= esc_html( $notes ) ?></textarea></td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<input type="submit" class="button button-primary" value="Save">
			</p>

		</form>

		<?php

	}

    public function getEmailReport() {

        global $vfdb;
        global $wpdb;

        ob_start();

        $time = strtotime(date('Y-m-d'));
        $all_subscriptions = $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} WHERE `meta_key` = '_query_subscriptions'" );

        ?>
        <style>
            .tgg td {
                text-align: center;
                border: 1px solid;
                padding: 5px;
            }
        </style>
        <?php if(!$_GET['customer']) { ?>
        <h1>Total count of users with subscription: <span style="color: green;"><?= count($all_subscriptions); ?></span></h1>
        <h2>Date: <?= date('m/d/Y', strtotime('now')); ?></h2>

        <table class="tgg" style="undefined; width: 100%">
            <thead>
            <tr>
                <th class="tg-0pky">User ID</th>
                <th class="tg-0pky">User Email</th>
                <th class="tg-0pky">LAST LOGIN</th>
                <th class="tg-0pky">Subscription count</th>
                <th class="tg-0pky">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if(empty($all_subscriptions)) {
                echo '<tr><td colspan="4"><h2>We have no subscriptions!</h2></td></tr>';
            } else {
               foreach ($all_subscriptions as $subscription) {
                   $userData = get_userdata($subscription->user_id);

                   ?>
                    <tr>
                        <td><a target="_blank" href="https://portlandhomesforsale.com/wp-admin/admin.php?page=leads&user=<?= $subscription->user_id; ?>"><?= $subscription->user_id; ?></a></td>
                        <td><?= $userData->data->user_email; ?></td>
                        <td><?= get_user_meta($subscription->user_id, 'last_login', true); ?></td>
                        <td><?= count(unserialize($subscription->meta_value)); ?></td>
                        <td><a href="https://portlandhomesforsale.com/wp-admin/admin.php?page=email-report&customer=<?= $userData->data->ID; ?>">Details</a></td>
                    </tr>
                <?php } ?>
            <?php } ?>
            </tbody>
        </table>
        <?php } else {

            $subscriptions = array();
            foreach ($all_subscriptions as $row) {
                if($row->user_id != $_GET['customer']) continue;
                $subs = maybe_unserialize($row->meta_value);

                if (is_array($subs)) foreach ($subs as $hash => $freq_val) {
                    $subscriptions[$hash] = $freq_val;
                }
            }
            $queries = Favorites::get_searches( $_GET['customer'] );
            $date = $_GET['date'] ?? date('Y-m-d');
            $onMarket = strtotime($date . ' - 1 day') * 100;
            echo '<pre>';
            print_r($onMarket);
            echo '</pre>';
            $allProperties = $vfdb->get_results("SELECT * FROM `wp_property_v2` where `onmarket` > '$onMarket' and `hidden` = 0 and `post_id` = '647';");
            if(empty($subscriptions)) {
                echo '<h2>User has not subscriptions!</h2>';
            } else {
                foreach ($subscriptions as $hash => $frequency) {
                    switch ($frequency)
                    {
                        case ('immediate')  : {$days = 0; $dom = '2:1';} break;
                        case (1)      : {$days = 1; $dom = '2:1';} break;
                        case (7)     : {$days = 7; $dom = '8:1';} break;
                        case (30)    : {$days = 30;$dom = '30:1';} break;
                        default : return false;
                    }
                    foreach ($queries as $key => $val)
                    {
                        $json_val = json_decode($val,true);
                        $json_val = array_merge($json_val,['dom'=>$dom]);
                        $queries[$key] = json_encode($json_val);
                    }
                    echo '<div class="queries" style="border-bottom: 1px solid black; ' . (!isset($queries[$hash]) ? ' display:none;' : '') . '">';
                    echo '<h2>Hash:  ' . $hash . ' <br> Frequency: <span style="' . ($frequency == 'never' ? 'color: red;' : 'color: green;') . '">' . $frequency . '</span></h2>';
                    if(isset($queries[$hash])) {
                        echo '<h3 style="padding-bottom: 15px; color: green;">Status: Active</h3>';
                        echo '<h2> Subscription config: <br>';
                        echo '<pre>';
                        print_r(json_decode($queries[$hash]));
                        echo '</pre>';
                        echo '<h2> Subscription query: <br>';
                        $query_filters = Search::get_query_filters(json_decode($queries[$hash], true));
                        echo '<pre>';
                        print_r($query_filters);
                        echo '</pre>';
                        $properties_send_list = [];
                        $properties_sent_list = [];
                        $properties_user_list = get_user_meta($_GET['customer'], '_properties_email_sent', true);
                        $properties = new Query(['filters' => $query_filters], 0, $_GET['customer']);
                        $propertiesByFilters = $properties->get_all();
                        $addressTest = '<span style="color: red">Failed</span>';
                        $statusTest = '<span style="color: red">Failed</span>';

                        echo 'Count properties were imported last ' . $frequency . ' days : ' . (!empty($allProperties) ? count($allProperties) : 0) . '<br>';
                        echo '<br>List of founded properties for the subscriptions based on DOM: <br>';
                        ?>
                        <table class="tgg" style="undefined; width: 100%">
                            <thead>
                            <tr>
                                <th class="tg-0pky">MLSID</th>
                                <th class="tg-0pky">Last modified(UTC)</th>
                                <th class="tg-0pky">Photos</th>
                                <th class="tg-0pky">Hidden</th>
                                <th class="tg-0pky">Status</th>
                                <th class="tg-0pky">Locations</th>
                                <th class="tg-0pky">Latitude</th>
                                <th class="tg-0pky">Longitude</th>
                                <th class="tg-0pky">Property type</th>
                                <th class="tg-0pky">Bedrooms</th>
                                <th class="tg-0pky">Bathrooms</th>
                                <th class="tg-0pky">On market(UTC)</th>
                                <th class="tg-0pky">Was sent</th>
                                <th class="tg-0pky">Reason</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($allProperties as $property) {
                                if(!empty($query_filters['location'])) {
                                    $locations = explode(',', $query_filters['location']);
                                    $propertyLocations = explode(',', $property->location);
                                    foreach ($locations as $location) {
                                        if(in_array($location, $propertyLocations)) {
                                            $addressTest = '<span style="color: green;">OK</span>';
                                        }
                                    }
                                } else {
                                    $addressTest = '<span style="color: yellow;">Not used</span>';
                                }

                                if(!empty($query_filters['status'])) {

                                    $value = apply_filters( "vestorfilter_get_query_index__status", $query_filters[ 'status' ] );
                                    if ( is_string( $value ) ) {
                                        $value = [ $value ];
                                    }

                                    $options = Settings::get_filter_options( 'status' );
                                    $values = [];
                                    foreach ( $options as $option ) {
                                        if ( in_array( $option['value'], $value ) ) {
                                            foreach ( $option['terms'] as $term_id ) {
                                                $values[] = $term_id;
                                            }
                                        }
                                    }

                                    if(in_array($property->status, $values)) {
                                        $statusTest = '<span style="color: green;">OK</span>';
                                    } else {
                                        $statusTest = '<span style="color: red">Failed</span>';
                                    }

                                } else {
                                    $statusTest = '<span style="color: yellow;">Not used</span>';
                                }


                                $validationMessage = "Location test: " . $addressTest . "\n" . "Status test: " . $statusTest;
                                ?>
                                <tr>
                                    <td><a target="_blank" href="https://portlandhomesforsale.com/real-estate/<?= $property->mlsid; ?>"><?= $property->mlsid; ?></a></td>
                                    <td><?= date('Y-m-d H:i', $property->modified); ?></td>
                                    <td><?= $property->photos; ?></td>
                                    <td><?= $property->hidden; ?></td>
                                    <td><?= $property->status; ?></td>
                                    <td><?= $property->location; ?></td>
                                    <td><?= $property->lat; ?></td>
                                    <td><?= $property->lon; ?></td>
                                    <td><?= $property->property_type; ?></td>
                                    <td><?= $property->bedrooms; ?></td>
                                    <td><?= $property->bathrooms; ?></td>
                                    <td><?= date('Y-m-d H:i',$property->onmarket / 100); ?></td>
                                    <td><?= (in_array($property->mlsid, $properties_user_list)) ? '<span style="color: #1c7430;">Yes</span>' : '<span style="color: red">No</span>'; ?></td>
                                    <td><?= (in_array($property->mlsid, $properties_user_list)) ? '<span style="color: #1c7430;">Ok</span>' : $validationMessage; ?></td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>


                        <table class="tgg" style="undefined; width: 100%">
                            <thead>
                            <tr>
                                <th class="tg-0pky">MLSID</th>
                                <th class="tg-0pky">Last modified(UTC)</th>
                                <th class="tg-0pky">Photos</th>
                                <th class="tg-0pky">Hidden</th>
                                <th class="tg-0pky">Status</th>
                                <th class="tg-0pky">Locations</th>
                                <th class="tg-0pky">Latitude</th>
                                <th class="tg-0pky">Longitude</th>
                                <th class="tg-0pky">Property type</th>
                                <th class="tg-0pky">Bedrooms</th>
                                <th class="tg-0pky">Bathrooms</th>
                                <th class="tg-0pky">On market(UTC)</th>
                                <th class="tg-0pky">Was sent</th>
                                <th class="tg-0pky">Reason</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if(!empty($propertiesByFilters)) {
                            foreach ($propertiesByFilters as $property) {
                                if(!empty($query_filters['location'])) {
                                    $locations = explode(',', $query_filters['location']);
                                    $propertyLocations = explode(',', $property->location);
                                    foreach ($locations as $location) {
                                        if(in_array($location, $propertyLocations)) {
                                            $addressTest = '<span style="color: green;">OK</span>';
                                        }
                                    }
                                } else {
                                    $addressTest = '<span style="color: yellow;">Not used</span>';
                                }

                                if(!empty($query_filters['status'])) {

                                    $value = apply_filters( "vestorfilter_get_query_index__status", $query_filters[ 'status' ] );
                                    if ( is_string( $value ) ) {
                                        $value = [ $value ];
                                    }

                                    $options = Settings::get_filter_options( 'status' );
                                    $values = [];
                                    foreach ( $options as $option ) {
                                        if ( in_array( $option['value'], $value ) ) {
                                            foreach ( $option['terms'] as $term_id ) {
                                                $values[] = $term_id;
                                            }
                                        }
                                    }

                                    if(in_array($property->status, $values)) {
                                        $statusTest = '<span style="color: green;">OK</span>';
                                    } else {
                                        $statusTest = '<span style="color: red">Failed</span>';
                                    }

                                } else {
                                    $statusTest = '<span style="color: yellow;">Not used</span>';
                                }


                                $validationMessage = "Location test: " . $addressTest . "\n" . "Status test: " . $statusTest;
                                ?>
                                <tr>
                                    <td><a target="_blank" href="https://portlandhomesforsale.com/real-estate/<?= $property->mlsid; ?>"><?= $property->mlsid; ?></a></td>
                                    <td><?= date('Y-m-d H:i', $property->modified); ?></td>
                                    <td><?= $property->photos; ?></td>
                                    <td><?= $property->hidden; ?></td>
                                    <td><?= $property->status; ?></td>
                                    <td><?= $property->location; ?></td>
                                    <td><?= $property->lat; ?></td>
                                    <td><?= $property->lon; ?></td>
                                    <td><?= $property->property_type; ?></td>
                                    <td><?= $property->bedrooms; ?></td>
                                    <td><?= $property->bathrooms; ?></td>
                                    <td><?= date('Y-m-d H:i',$property->onmarket / 100); ?></td>
                                    <td><?= (in_array($property->mlsid, $properties_user_list)) ? '<span style="color: #1c7430;">Yes</span>' : '<span style="color: red">No</span>'; ?></td>
                                    <td><?= (in_array($property->mlsid, $properties_user_list)) ? '<span style="color: #1c7430;">Ok</span>' : $validationMessage; ?></td>
                                </tr>
                            <?php } } else { ?>
                                <td>0 properties were found by this filters.</td>
                            <?php } ?>
                            </tbody>
                        </table>
                        <?php
                    } else {
                        echo '<h3 style="padding-bottom: 15px; color: red; border-bottom: 1px solid black;">Status: Inactive</h3>';
                    }
                    echo '</div>';
                }
            }

        }
        echo ob_get_clean();
    }

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Dashboard', 'init' ) );

