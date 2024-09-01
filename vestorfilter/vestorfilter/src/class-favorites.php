<?php
    
    namespace VestorFilter;
    use DateInterval;
    use DateTime;
    
    if ( ! defined( 'ABSPATH' ) ) {
        die();
    }
    
/*
    // Email sandbox code.
    function mailtrap($phpmailer) {
        $phpmailer->isSMTP();
        $phpmailer->Host = 'smtp.mailtrap.io';
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = 2525;
        $phpmailer->Username = 'ac68410f139bc8';
        $phpmailer->Password = '652ad819fbd19b';
    }
    
    add_action('phpmailer_init', 'VestorFilter\mailtrap');
*/
    
    class Favorites extends \VestorFilter\Util\Singleton {
        
        /**
         * A pointless bit of self-reference
         *
         * @var object
         */
        public static $instance = null;
        
        private static $properties = [];
        
        public function __construct()
        {
        }
        
        public function install() {
            
            add_action( 'rest_api_init', array( $this, 'init_rest' ) );
            add_action( 'vestorfilter_auth_payload', array( $this, 'add_favorites_to_payload' ), 10, 2 );
            /*
                        add_filter( 'cron_schedules', array( $this, 'subscription_schedule' ) );
                        add_action( 'vf_send_subscriptions', array( $this, 'send_subscriptions' ) );
            */
            //add_action( 'vestorfilter_user_created', [ $this, 'setup_default_subscription' ] );
            add_action( 'vestorfilter_favorites_changed', [ 'VestorFilter\Favorites', 'update_smart_search' ] );
            
            /*
                    if ( ! wp_next_scheduled( 'vf_send_subscriptions' ) ) {
                        wp_schedule_event( time(), 'favorites_int', 'vf_send_subscriptions' );
                    }
            */
        }
        
        public function init_rest() {
            
            $this->user_id = get_current_user_id();
            register_rest_route( 'vestorfilter/v1','/email/unsubscribe', array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'unsubscribe_message' ),
                'permission_callback' => '__return_true',
            ) );
            
            register_rest_route( 'vestorfilter/v1','/email/search-alerts', array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'cron_email_jobs' ),
                'permission_callback' => '__return_true',
            ) );
            
            register_rest_route( 'vestorfilter/v1', '/favorites/count', array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'rest_favorite_count' ),
                'permission_callback' => '__return_true',
                'args' => ['user_id' => ['validate_callback' => get_current_user_id()]]
            ) );
            
            
            ####################################################################
            
            register_rest_route( 'vestorfilter/v1', '/favorites/toggle', array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_toggle_favorite' ),
                'permission_callback' => 'is_user_logged_in',
            ) );
            
            register_rest_route( 'vestorfilter/v1', '/favorites/friend-save', array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_save_friend_favorite' ),
                'permission_callback' => 'is_user_logged_in',
            ) );
            
            register_rest_route( 'vestorfilter/v1', '/favorites/friend-remove', array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_remove_friend_favorite' ),
                'permission_callback' => 'is_user_logged_in',
            ) );
            
            register_rest_route( 'vestorfilter/v1', '/favorites/toggle-search', array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_toggle_search' ),
                'permission_callback' => 'is_user_logged_in',
            ) );
            
            register_rest_route( 'vestorfilter/v1', '/favorites/trash-search', array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_trash_search' ),
                'permission_callback' => 'is_user_logged_in',
            ) );
            
            register_rest_route( 'vestorfilter/v1', '/favorites/subscribe', array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_subscribe' ),
                'permission_callback' => 'is_user_logged_in',
            ) );
            
            register_rest_route( 'vestorfilter/v1', '/favorites/name', array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_save_search_name' ),
                'permission_callback' => 'is_user_logged_in',
            ) );
            
        }
        
        public function unsubscribe_message()
        {
            global $wpdb;
            
            if(isset($_GET['user_id']) and $_GET['user_id'] != '') $user_id = addslashes($_GET['user_id']);
            else wp_redirect( get_bloginfo( 'url' ) );
            
            $sql = "SELECT * FROM {$wpdb->usermeta} WHERE `meta_key` = '_query_subscriptions' AND `user_id` = '$user_id'";
            
            $subscription = $wpdb->get_results($sql);
            $values = maybe_unserialize( $subscription[0]->meta_value );
            
            foreach ($values as $val_key => $value)
            {
                $unsub[$val_key] = 'never';
            }
            
            $status = update_user_meta($user_id,'_query_subscriptions',$unsub);
            
            if($status) {
                wp_redirect( get_bloginfo( 'url' ).'/unsubscribed' );
                exit; } else {
                wp_redirect( get_bloginfo( 'url' ).'/previously-unsubscribed' );
                exit;
            }
            
        }
        
        // this function must be triggered
        // by the server cron system for
        // daily, weekly and monthly alerts.
        // Cron can trigger the method
        // and method will sort email
        // frequencies and send them accordingly.
        //
        // trigger example:
        // https://example.com/wp-json/vestorfilter/v1/email/search-alerts?frequency=immediate
        //
        // Use frequency query string only for immediate email alerts.
        
        
        public function cron_email_jobs()
        {
            /*
            
            //header('Content-Type: text/html');
            // set the day of the week
            // to send weekly email alerts
            
            $weekly_day  = 'Sun';
            
            // set the month date to
            // send monthly email alerts
            $monthly_date = 01;
            
            
            
            //echo $date->format('M/D/Y');exit;
            //header("Content-Type: text/html");
    
    
            // send immidiate email alerts by
            // defining get frequency query
            // string set to immediate
            if(isset($_GET['frequency']) and $_GET['frequency'] == 'immediate')
            {
                $fav->send_subscriptions('immediate');
                return true;
            }
            
            // sending monthly email alerts
            if($date->format('d') == $monthly_date)
            {
                $fav->send_subscriptions('monthly');
            }
            
            // sending weekly email alerts
            if($date->format('D') == $weekly_day)
            {
                $fav->send_subscriptions('weekly');
            }
            
            // sending daily email alerts
            $fav->send_subscriptions('daily');
            */
            $date = new \DateTime('now');
            $fav = new \VestorFilter\Favorites;
            
            if(isset($_GET['frequency']) and $_GET['frequency']!='')
            {
                $freq = $_GET['frequency'];
                $fav->send_subscriptions($freq);
                // this log each time cron job
                // trigger the event
                $log_file = __DIR__.'/sent.txt';
                $log_load = file_get_contents($log_file);
                $log_time = "TRIGGER LOG:".$date->format('H:i:s d-m-Y');
                $log_data = $log_load."$log_time / $freq\n";
                $log_save = file_put_contents($log_file, $log_data);
                return true;
            }
            else
            {
                $log_file = __DIR__.'/sent.txt';
                $log_load = file_get_contents($log_file);
                $log_time = "TRIGGER LOG:".$date->format('H:i:s d-m-Y');
                $log_data = $log_load."$log_time / ILLEGAL TRIGGER\n";
                $log_save = file_put_contents($log_file, $log_data);
                return false;
            }
        }
        
        public function rest_favorite_count()
        {
            $user_id = $this->user_id;
            
            $final_fav_list = [];
            $property_fav_list = self::get_all( $user_id );
            
            // SOME PROPERTIES GET HIDDEN SOME POINT IN TIME
            // AND DON'T DISPLAY IN SEARCH SUCH PROPERTIES
            // NEED TO BE REMOVED FROM THE FAV LIST.
            foreach($property_fav_list as $property_exist_id)
            {
                $property_lookup = Cache::get_property_by( 'ID', $property_exist_id );
                if($property_lookup[0]->hidden == 0) $final_fav_list[] = $property_exist_id;
                //print_r($property_lookup);
            }
            //echo count( $final_fav_list);
            
        }
        
        public function rest_toggle_favorite( $request ) {
            
            if ( empty( $request['property'] ) || ! is_numeric( $request['property'] ) ) {
                return new \WP_Error( 'incomple_data', 'Bad or incomplete property ID set', [ 'status' => 403 ] );
            }
            //print_r($request['property']);
            
            $property = absint( $request['property'] );
            if ( empty( $property ) ) {
                return new \WP_Error( 'incomple_data', 'No property ID set', [ 'status' => 401 ] );
            }
            $user_id = get_current_user_id();
            
            $all = self::get_all( $user_id );
            //echo "<pre>";print_r($all);echo "</pre>";
            
            
            if ( isset( $request['state'] ) ) {
                
                if ( $request['state'] === 'off' ) {
                    delete_user_meta( $user_id, '_favorite_property', $property );
                } elseif ( ! in_array( $property, $all ) && $request['state'] === 'on' ) {
                    add_user_meta( $user_id, '_favorite_property', $property );
                    Log::add( [ 'action' => 'favorite-saved', 'property' => $property, 'user' => $user_id ] );
                }
                
            } else {
                
                if ( in_array( $property, $all ) ) {
                    delete_user_meta( $user_id, '_favorite_property', $property );
                } else {
                    add_user_meta( $user_id, '_favorite_property', $property );
                    Log::add( [ 'action' => 'favorite-saved', 'property' => $property, 'user' => $user_id ] );
                }
                
            }
            
            wp_cache_delete( 'favorite_properties__' . $user_id, 'vestorfilter' );
            
            //do_action( 'vestorfilter_favorites_changed', $user_id );
            
            $final_fav_list = [];
            $property_fav_list = self::get_all( $user_id );
            
            // SOME PROPERTIES GET HIDDEN SOME POINT IN TIME
            // AND DON'T DISPLAY IN SEARCH SUCH PROPERTIES
            // NEED TO BE REMOVED FROM THE FAV LIST.
            foreach($property_fav_list as $property_exist_id)
            {
                $property_lookup = Cache::get_property_by( 'ID', $property_exist_id );
                if($property_lookup[0]->hidden == 0) $final_fav_list[] = $property_exist_id;
                //print_r($property_lookup);
            }
            
            return $final_fav_list;
            //return self::get_all( $user_id );
            
        }
        
        public function rest_save_friend_favorite( $request ) {
            
            if ( empty( $request['property_id'] ) || ! is_numeric( $request['property_id'] ) ) {
                return new \WP_Error( 'incomple_data', 'Bad or incomplete property ID set', [ 'status' => 403 ] );
            }
            
            $property = absint( $request['property_id'] );
            if ( empty( $property ) ) {
                return new \WP_Error( 'incomple_data', 'No property ID set', [ 'status' => 401 ] );
            }
            
            if ( empty( $request['friend_id'] ) || ! is_numeric( $request['friend_id'] ) ) {
                return new \WP_Error( 'incomple_data', 'Bad or incomplete friend ID set', [ 'status' => 403 ] );
            }
            
            $friend_id = absint( $request['friend_id'] );
            if ( empty( $friend_id ) ) {
                return new \WP_Error( 'incomple_data', 'No friend ID set', [ 'status' => 401 ] );
            }
            $friend = get_user_by( 'id', $friend_id );
            if ( empty( $friend ) ) {
                return new \WP_Error( 'incomple_data', 'This friend does not exist.', [ 'status' => 401 ] );
            }
            $friend_friends = get_user_meta( $friend_id, '_friends' ) ?: [];
            
            $user_id = get_current_user_id();
            
            if ( ! in_array( $user_id, $friend_friends ) && ! current_user_can( 'see_leads' ) ) {
                return new \WP_Error( 'incomple_data', 'You do not have permission to save favorites for this user.', [ 'status' => 403 ] );
            }
            
            if ( current_user_can( 'see_leads' ) ) {
                
                self::add_agent_recommendation( $friend_id, $user_id, $property );
                
            } else {
                $friend_favorites = self::get_friend_properties( $friend_id );
                if ( ! in_array( $property, $friend_favorites ) ) {
                    add_user_meta( $friend_id, '_friend_favorite', $property );
                    add_user_meta( $friend_id, '_friend_favorite_' . $property, $user_id );
                }
            }
            
            
            wp_cache_delete( 'favorite_friend_properties__' . $friend_id, 'vestorfilter' );
            
            return [ 'message' => 'Favorite saved' ];
            
        }
        
        public function rest_remove_friend_favorite( $request ) {
            
            if ( empty( $request['property_id'] ) || ! is_numeric( $request['property_id'] ) ) {
                return new \WP_Error( 'incomple_data', 'Bad or incomplete property ID set', [ 'status' => 403 ] );
            }
            
            $property = absint( $request['property_id'] );
            if ( empty( $property ) ) {
                return new \WP_Error( 'incomple_data', 'No property ID set', [ 'status' => 401 ] );
            }
            
            if ( empty( $request['user_id'] ) || ! is_numeric( $request['user_id'] ) ) {
                return new \WP_Error( 'incomple_data', 'Bad or incomplete friend ID set', [ 'status' => 403 ] );
            }
            
            $friend_id = absint( $request['user_id'] );
            if ( empty( $friend_id ) ) {
                return new \WP_Error( 'incomple_data', 'No friend ID set', [ 'status' => 401 ] );
            }
            $friend = get_user_by( 'id', $friend_id );
            if ( empty( $friend ) ) {
                return new \WP_Error( 'incomple_data', 'This friend does not exist.', [ 'status' => 401 ] );
            }
            $friend_friends = get_user_meta( $friend_id, '_friends' ) ?: [];
            
            $user_id = get_current_user_id();
            
            if ( $user_id !== $friend_id && ! in_array( $user_id, $friend_friends ) ) {
                return new \WP_Error( 'incomple_data', 'You do not have permission to delete favorites for this user.', [ 'status' => 403 ] );
            }
            
            delete_user_meta( $friend_id, '_friend_favorite', $property );
            delete_user_meta( $friend_id, '_friend_favorite_' . $property, $user_id );
            
            wp_cache_delete( 'favorite_friend_properties__' . $friend_id, 'vestorfilter' );
            
            return [ 'message' => 'Favorite saved' ];
            
        }
        
        public function rest_toggle_search( $request ) {
            
            global $vfdb;
            
            if ( empty( $request['filters'] ) || empty( $request['hash'] ) || empty( $request['verification'] ) ) {
                return new \WP_Error( 'incomple_data', 'Bad or incomplete search set', [ 'status' => 403 ] );
            }
            
            $filters = $request['filters'];
            if ( ! is_string( $filters ) ) {
                return new \WP_Error( 'incomple_data', 'Bad or incomplete search filters', [ 'status' => 403 ] );
            }
            $hash = $request['hash'];
            $nonce = $request['verification'];
            
            $filters = json_decode( $filters, true );
            $filters = Search::get_query_filters( $filters );
            if ( isset( $filters['geo'] ) ) {
                unset( $filters['geo'] );
            }
            foreach( $filters as $key => $filter ) {
                $filter = trim( $filter, ' :' );
                if ( empty( $filter ) ) {
                    unset( $filters[ $key ] );
                }
            }
            ksort( $filters );
            
            if ( ! empty( $filters['location'] ) && $pos = strpos( $filters['location'], '[' ) ) {
                $filters['location'] = substr( $filters['location'], $pos+1, -1 );
            }
            
            $name = Cache::$results_table_name;
            $cached = $vfdb->get_var( $vfdb->prepare( "SELECT filters FROM $name WHERE `hash` = %s", $hash ) );
            
            if ( empty( $cached ) ) {
                if ( ! wp_verify_nonce( $nonce, md5( serialize( $filters ) . $hash ) ) ) {
                    return new \WP_Error( 'incomplete_data', 'Could not verify search filters.', [ 'status' => 401 ] );
                }
            } else {
                $filters = json_decode( $cached );
            }
            
            $user_id = get_current_user_id();
            
            $all = self::get_searches( $user_id );
            $state = $request['state'] ?? '';
            
            if ( isset( $all[ $hash ] ) && $state === 'off' ) {
                unset( $all[ $hash ] );
            } else {
                $all[ $hash ] = json_encode( $filters );
            }
            
            update_user_meta( $user_id, '_has_saved_search', time() );
            
            update_user_meta( $user_id, '_favorite_searches', $all );
            
            wp_cache_delete( 'favorite_searches__' . $user_id, 'vestorfilter' );
            
            return $all;
            
        }
        
        public function rest_trash_search( $request ) {
            
            if ( empty( $request['verification'] ) || empty( $request['hash'] ) ) {
                return new \WP_Error( 'incomple_data', 'Bad or incomplete search set', [ 'status' => 403 ] );
            }
            
            $hash = $request['hash'];
            $nonce = $request['verification'];
            $user_id = absint( $request['user'] ?? get_current_user_id() );
            
            if ( $user_id !== get_current_user_id() && ! current_user_can( 'see_leads' ) ) {
                return new \WP_Error( 'not_allowed', 'Not allowed', [ 'status' => 403 ] );
            }
            
            if ( ! wp_verify_nonce( $nonce, $hash ) ) {
                return new \WP_Error( 'incomple_data', 'Could not verify search filters', [ 'status' => 401 ] );
            }
            
            self::delete_search( $user_id, $hash );
            
            wp_cache_delete( 'favorite_searches__' . $user_id, 'vestorfilter' );
            
            return self::get_searches( $user_id );
            
        }
        
        public static function delete_search( $user_id, $hash ) {
            
            $all = self::get_searches( $user_id );
            
            if ( isset( $all[ $hash ] ) ) {
                unset( $all[ $hash ] );
            }
            
            update_user_meta( $user_id, '_favorite_searches', $all );
            
        }
        
        public function rest_subscribe( $request ) {
            
            if ( empty( $request['nonce'] ) || empty( $request['hash'] ) || ! isset( $request['frequency'] ) ) {
                return new \WP_Error( 'incomple_data', 'Bad or incomplete search set', [ 'status' => 403 ] );
            }
            
            $hash = $request['hash'];
            $nonce = $request['nonce'];
            $freq = $request['frequency'];
            $user_id = absint( $request['user'] ?? get_current_user_id() );
            
            
            if ( $user_id !== get_current_user_id() && ! current_user_can( 'see_leads' ) ) {
                return new \WP_Error( 'not_allowed', 'Not allowed', [ 'status' => 403 ] );
            }
            
            if ( ! wp_verify_nonce( $nonce, $hash ) ) {
                return new \WP_Error( 'incomplete_data', 'Could not verify search filters', [ 'status' => 401 ] );
            }
            
            $hash = sanitize_title( $hash );
            if ( ! Favorites::is_search_saved( $hash, $user_id ) ) {
                return false;
            }
            
            if ( $freq !== 'never' ) {
                $freq = absint( $freq );
            }
            
            if ( $freq < 0 || $freq > 30 ) {
                $freq = 7;
            }
            Log::add( [ 'action' => 'search-subscribed', 'value' => $hash, 'user' => $user_id ] );
            return Favorites::add_email_subscription( $hash, $freq, $user_id );
            
            return true;
            
        }
        
        public function rest_save_search_name( $request ) {
            
            if ( empty( $request['nonce'] ) || empty( $request['hash'] ) || empty( $request['name'] ) ) {
                return new \WP_Error( 'incomple_data', 'Bad or incomplete search set', [ 'status' => 403 ] );
            }
            
            $hash = $request['hash'];
            $nonce = $request['nonce'];
            $name = $request['name'];
            $user_id = absint( $request['user'] ?? get_current_user_id() );
            
            if ( $user_id !== get_current_user_id() && ! current_user_can( 'see_leads' ) ) {
                return new \WP_Error( 'not_allowed', 'Not allowed', [ 'status' => 403 ] );
            }
            
            if ( ! wp_verify_nonce( $nonce, $hash ) ) {
                return new \WP_Error( 'incomplete_data', 'Could not verify search filters', [ 'status' => 401 ] );
            }
            
            $hash = sanitize_title( $hash );
            if ( ! Favorites::is_search_saved( $hash, $user_id ) ) {
                return false;
            }
            
            $name = filter_var( $name, FILTER_SANITIZE_STRING );
            
            Favorites::set_search_name( $hash, $name, $user_id );
            
            return self::get_searches( $user_id );
            
        }
        
        public function setup_default_subscription( $user ) {
            
            $searches = self::get_searches( $user->ID );
            
            if ( ! empty( $searches ) ) {
                return;
            }
            
            $default_location = Settings::get( 'default_location_id' );
            if ( empty( $default_location ) ) {
                return;
            }
            
            $default_location = is_array( $default_location ) ? implode( ',', $default_location ) : $default_location;
            
            $filters = [
                'dynamic'       => true,
                'location'      => $default_location,
                'property-type' => 'all',
                'status'        => 'active',
                'vf'            => 'ppsf',
            ];
            ksort( $filters );
            
            $hash = md5( json_encode( $filters ) . implode( '', VF_ALLOWED_FEEDS ) );
            
            $filters['name'] = 'Dynamic Smart Search';
            
            $my_searches = [ $hash => json_encode( $filters ) ];
            
            update_user_meta( $user->ID, '_has_saved_search', time() );
            update_user_meta( $user->ID, '_favorite_searches', $my_searches );
            update_user_meta( $user->ID, '_dynamic_search', $hash );
            
            wp_cache_delete( 'favorite_searches__' . $user->ID, 'vestorfilter' );
            
            Favorites::add_email_subscription( $hash, 7, $user->ID );
            
        }
        
        public function add_favorites_to_payload( $payload, $user ) {
            
            $all = self::get_all( $user->ID );
            $payload[ 'favorites' ] = $all;
            
            return $payload;
            
        }
        
        public static function get_search( $hash, $user_id = null ) {
            
            if ( empty( $user_id ) ) {
                $user_id = get_current_user_id();
            }
            
            if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
                return [];
            }
            
            $searches = self::get_searches( $user_id );
            
            return $searches[ $hash ] ?? false;
            
        }
        
        public static function get_searches( $user_id = null ) {
            
            if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
                return [];
            }
            
            $searches = wp_cache_get( 'favorite_searches__' . $user_id, 'vestorfilter' );
            if ( ! empty( $searches ) ) {
                return $searches;
            }
            
            $searches = get_user_meta( $user_id, '_favorite_searches', true );
            $searches = maybe_unserialize( $searches );
            
            if ( ! empty( $searches ) ) {
                wp_cache_set( 'favorite_searches__' . $user_id, $searches, 'vestorfilter' );
            } else {
                $searches = [];
            }
            
            return $searches;
            
        }
        
        public static function is_search_saved( $hash, $user_id ) {
            
            $searches = self::get_searches( $user_id );
            if ( empty( $searches ) ) {
                return false;
            }
            
            return isset( $searches[ $hash ] );
            
        }
        
        public static function set_search_name( $hash, $name, $user_id ) {
            
            $searches = get_user_meta( $user_id, '_favorite_searches', true );
            
            if ( ! isset( $searches[ $hash ] ) ) {
                return;
            }
            
            $this_search = json_decode( $searches[ $hash ], true );
            $this_search['name'] = $name;
            
            $searches[ $hash ] = json_encode( $this_search );
            
            update_user_meta( $user_id, '_favorite_searches', $searches );
            
            wp_cache_delete( 'favorite_searches__' . $user_id, 'vestorfilter' );
            
            return;
            
        }
        
        public static function get_all( $user_id = null ) {
            
            if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
                return [];
            }
            
            /*
            // why did I do this?
            if ( ! empty( self::$properties ) ) {
                return self::$properties;
            }
            */
            
            $properties = wp_cache_get( 'favorite_properties__' . $user_id, 'vestorfilter' );
            if ( ! empty( $properties ) ) {
                self::$properties = $properties;
                return $properties;
            }
            
            $properties = get_user_meta( $user_id, '_favorite_property' );
            if ( ! empty( $properties ) ) {
                wp_cache_set( 'favorite_properties__' . $user_id, $properties, 'vestorfilter' );
                self::$properties = $properties;
            }
            return $properties;
            
        }
        
        public static function get_agent_suggestions( $user_id = null ) {
            
            if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
                return [];
            }
            
            /*
            // why did I do this?
            if ( ! empty( self::$properties ) ) {
                return self::$properties;
            }
            */
            
            $properties = wp_cache_get( 'favorite_agent_properties__' . $user_id, 'vestorfilter' );
            if ( ! empty( $properties ) ) {
                self::$properties = $properties;
                return $properties;
            }
            
            $recommendations = [];
            $properties = get_user_meta( $user_id, '_agent_recommendation' );
            foreach( $properties as $rec ) {
                $recommendations[] = $rec['property'];
            }
            
            if ( ! empty( $recommendations ) ) {
                wp_cache_set( 'favorite_agent_properties__' . $user_id, $recommendations, 'vestorfilter' );
            }
            
            return $recommendations;
            
        }
        
        public static function get_friend_properties( $user_id = null ) {
            
            if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
                return [];
            }
            
            /*
            // why did I do this?
            if ( ! empty( self::$properties ) ) {
                return self::$properties;
            }
            */
            
            $properties = wp_cache_get( 'favorite_frield_properties__' . $user_id, 'vestorfilter' );
            if ( ! empty( $properties ) ) {
                self::$properties = $properties;
                return $properties;
            }
            
            $properties = get_user_meta( $user_id, '_friend_favorite' );
            
            if ( ! empty( $properties ) ) {
                wp_cache_set( 'favorite_frield_properties__' . $user_id, $properties, 'vestorfilter' );
            }
            
            return $properties;
            
        }
        
        public static function is_property_user_favorite( $property_id, $user_id = null ) {
            
            if ( empty( self::$properties ) ) {
                self::get_all( $user_id );
            }
            
            return in_array( $property_id, self::$properties );
            
        }
        
        public static function is_property_friend_favorite( $property_id, $user_id ) {
            
            $properties = self::get_friend_properties( $user_id );
            
            return in_array( $property_id, $properties );
            
        }
        
        public static function find_user_from_slug( $slug ) {
            
            global $wpdb;
            
            $slug = sanitize_title( $slug );
            
            $user_id = wp_cache_get( 'user__' . $slug, 'vestorfilter' );
            if ( ! empty( $user_id ) ) {
                return $user_id;
            }
            
            $user_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT `user_id` FROM {$wpdb->usermeta} WHERE `meta_key` = '_public_slug' AND `meta_value` = %s",
                $slug
            ) );
            
            wp_cache_set( 'user__' . $slug, $user_id, 'vestorfilter' );
            
            return $user_id;
            
        }
        
        public static function create_user_slug( $user ) {
            
            if ( is_numeric( $user ) ) {
                $user = get_user_by( 'id', absint( $user ) );
            }
            if ( empty( $user ) || ! is_object( $user ) ) {
                throw new \Exception( "Could not create slug for new user" );
            }
            
            $slug = $user->first_name . '-' . substr( wp_hash( $user->user_email . '|' . $user->ID, 'nonce' ), -12, 10 );
            $slug = sanitize_title( $slug );
            
            update_user_meta( $user->ID, '_public_slug', $slug );
            
            return $slug;
            
        }
        
        public static function get_user_slug( $user_id ) {
            
            $slug = wp_cache_get( 'user_slug__' . $user_id, 'vestorfilter' );
            if ( ! empty( $slug ) ) {
                return $slug;
            }
            
            $slug = get_user_meta( $user_id, '_public_slug', true );
            
            if ( empty( $slug ) ) {
                $slug = self::create_user_slug( $user_id );
            }
            
            wp_cache_set( 'user_slug__' . $user_id, $slug, 'vestorfilter' );
            
            return $slug;
            
        }
        
        public static function get_favorites_url( $user_id = null ) {
            
            if ( empty( $user_id ) ) {
                $user_id = get_current_user_id();
            }
            
            $base_url = \VestorFilter\Settings::get_page_url( 'saved' );
            
            return untrailingslashit( $base_url ) . '/' . self::get_user_slug( $user_id );
            
        }
        
        public static function get_subscriptions( $user_id = null ) {
            
            if ( empty( $user_id ) ) {
                $user_id = get_current_user_id();
            }
            if ( empty( $user_id ) ) {
                return [];
            }
            
            return get_user_meta( $user_id, '_query_subscriptions', true ) ?: [];
            
        }
        
        public static function add_email_subscription( $query_hash, $freq, $user_id = null ) {
            
            if ( empty( $user_id ) ) {
                $user_id = get_current_user_id();
            }
            if ( empty( $user_id ) ) {
                return;
            }
            
            $current_subscriptions = self::get_subscriptions( $user_id );
            if ( empty( $current_subscriptions ) ) {
                $current_subscriptions = [];
            }
            
            $current_subscriptions[ $query_hash ] = $freq;
            
            update_user_meta( $user_id, '_subscription_last_sent', time() );
            return update_user_meta( $user_id, '_query_subscriptions', $current_subscriptions );
            
        }
        
        public function subscription_schedule( $schedules ) {
            
            $schedules['favorites_int'] = array(
                'interval' => 60 * 20,
                'display'  => esc_html__( 'Every 20 minutes' ),
            );
            return $schedules;
            
        }
        
        public function send_subscriptions($frequency) {

            if(!$frequency) return false;
            
            global $wpdb;
            
            switch ($frequency)
            {
                case ('immediate')  : {$days = 0; $dom = '2:1';} break;
                case ('daily')      : {$days = 1; $dom = '2:1';} break;
                case ('weekly')     : {$days = 7; $dom = '8:1';} break;
                case ('monthly')    : {$days = 30;$dom = '30:1';} break;
                default : return false;
            }
            
            $start_date = '2023-05-13';
            
            $all_subscriptions = $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} WHERE `meta_key` = '_query_subscriptions'" );
            foreach( $all_subscriptions as $row ) {
                if(!in_array($row->user_id, [337, 156])) {
//                if(!in_array($row->user_id, [156])) {
//                if(!in_array($row->user_id, [337])) {
                    continue;
                }

                /**********************************************
                 *
                 * code to check if user is inactive more than
                 * 6 months then skip the subscription email.
                 *
                 * NOTE:`last_login` meta date updating code
                 *       is in the theme functions.php file
                 *
                 ******************************* 01/27/2022 **/
                // geting the last user login date
                $last_login = get_user_meta( $row->user_id, 'last_login', true );
                // if not user login date is regitered
                // then assign the default date
                if(!$last_login) $last_login = $start_date;
                $login_date = strtotime($last_login . " + 6 month");

                // checking if the user logded in duration
                // is more than 6 months or not
                $current_date = strtotime('now');
                // skiping the user if in active more than 6 months
                if($current_date > $login_date) continue;

//              settype($row->user_id, "integer");

                //echo "Hello Shakeel";

                $last_sent = get_user_meta( $row->user_id, '_subscription_last_sent', true );
                $last_sent = 0;
                $queries = self::get_searches( $row->user_id );
                
                foreach ($queries as $key => $val)
                {
                    $json_val = json_decode($val,true);
                    $json_val = array_merge($json_val,['dom'=>$dom]);
                    $queries[$key] = json_encode($json_val);
                }
//                echo "<pre>";print_r($queries);exit;
                
                $subs = maybe_unserialize( $row->meta_value );
                $subscriptions = array();
                
                if(is_array($subs)) foreach ($subs as $hash => $freq_val)
                {
                    if($freq_val === $days)
                    {
                        $subscriptions[$hash] = $freq_val;
                    }
                }

                if(empty($subscriptions)) continue;
                self::maybe_send_subscription_email( $row->user_id, $subscriptions, $queries, $last_sent, $frequency );
            }
            // note: temperorily switching off time logging by commenting following code
            /*
                    if ( ! wp_next_scheduled( 'vf_send_subscriptions' ) ) {
                        wp_schedule_event( time(), 'favorites_int', 'vf_send_subscriptions' );
                    }
            */
        }
        
        public static function maybe_send_subscription_email( $user_id, $subscriptions, $queries, $last_sent = 0, $frequency ) {
            
            $user = get_user_by( 'id', $user_id );
            if ( ! $user ) {
                return;
            }
            
            $agent_id = get_user_meta( $user_id, '_assigned_agent', true );
            if ( $agent_id ) {
                $agent = get_user_by( 'id', $agent_id );
            }
            $agent_name = empty( $agent ) ? '' : 'Team Member: ' . $agent->display_name;
            
            $now = time();
//            if ( $now < $last_sent + 3600 * 4 ) {
                // don't send an email if one was sent less than 4 hours ago
                // NTF note : commneting the following line will stop time check
                //return;
//            }
            $email = self::get_subscription_email( $subscriptions, $queries, $last_sent, $user, $frequency );
            /*
                       //echo $user_id.": ";var_dump(empty($email));echo "\n";
                       
                       if ( empty($email) ) {
                           return;
                       }
                       
                       $email->set_tags( [ 'TEAM_MEMBER' => $agent_name ] );
                       $email->set_var( 'to', $user->user_email );
                       //NOTE: comment/uncomment src/class-email.php wpmail() to stop/start sending actual emails.
                       if($email->send() === true)
                       {
                           die('Emailed successfuly...');
                       }
                       //Log::add( [ 'action' => 'subscription-sent', 'user' => $user_id, 'performed_by' => null ] );
                       
                       //update_user_meta( $user_id, '_subscription_last_sent', time() );
            */
            //echo "<pre>";print_r($properties);echo "</pre>";exit;
            
        }
        
        public static function get_subscription_email( $subscriptions, $queries, $last_sent = 0, $user = null, $frequency )
        {

//            echo "<pre>";print_r($user); exit;
            if (!is_numeric($last_sent)) {
                return false;
            }
            $results = [];
            $updated = 0;
            $should_send = true;
            $properties = [];

            $properties_send_list = [];
            $properties_sent_list = [];

            $properties_user_list = get_user_meta($user->ID, '_properties_email_sent', true);

            //var_dump($properties_user_list);
            if (!$properties_user_list) {
                $properties_user_list = [];
                //$properties_user_list = array(0 => '00000000000');
                update_user_meta( $user->ID, '_properties_email_sent', $properties_user_list );
            }
            //print_r($properties_user_list);exit;
            echo '<pre>';
            print_r($subscriptions);
            echo '</pre>';
            foreach ($subscriptions as $hash => $freq) {

                if ($freq === 'never' || !isset($queries[$hash])) {
                    continue;
                }
//                print_r($queries);exit;

                $freq = absint($freq);

                $query = json_decode($queries[$hash], true);
                //BREAKPOINT
                //echo '<br>'; print_r($query);echo "</pre>";
                if (isset($query['name'])) {
                    $name = $query['name'];
                    unset($query['name']);
                } elseif (!empty($query['dynamic'])) {
                    $name = 'Dynamic Smart Search';
                } else {
                    $name = '';
                }

                //echo __LINE__.'<br>';
                // todo: still needs logic to separate immediate/daily/weekly/etc
                try {
                    $query_filters = Search::get_query_filters($query);
                    echo '<pre>';
                    print_r($query_filters);
                    echo '</pre>';
                    //$query_filters['user'] = $user->ID;
                    //BREAKPOINT: show quries data.
                    //echo "<pre>";print_r($query_filters);echo "</pre>";
                    if (is_wp_error($query_filters)) {
                        continue;
                    }

                    //echo "<pre>";print_r($query_filters);echo "</pre>";exit;
                    $properties = new Query(['filters' => $query_filters], $last_sent, $user->ID);
//                    echo "<pre>";print_r($properties);echo "</pre>";exit;
                    //echo "<pre>";print_r($properties->filter('property-type'));echo "</pre>";exit;
                } catch (\Exception $e) {
                    continue;
                }

                echo '<pre>';
                var_dump($properties->total_results());
                echo '</pre>';

                if ($properties->total_results() > 0) {
                    $results[$hash] = [
                        'name' => $name,
                        'filters' => $query,
                        'query' => $properties,
                        'dynamic' => !empty($query['dynamic'])
                    ];
                    $updated += $properties->total_results();

//                    if ( $freq === 0 || $last_sent + $freq * 24 * 3600 < time() )
//                    {
//                        $should_send = false;
//                    }

                }



//            print_r($results);
//            exit;
                // TODO: manage the following code
                if (empty($results) || $should_send === false) {
//                    return false;
                    continue;
                }

                // send list is for new properties
                // sent is for properties already
                // sent and removed from the list


                $all = $properties->get_all();

                foreach ($all as $prop_key => $property_data) {
                    if (in_array($property_data->mlsid, $properties_user_list)) {
                        $properties_sent_list[] = $property_data->mlsid;
                    } else {
                        $properties_send_list[] = $property_data->mlsid;
                    }
                }

                if($_GET['frequency']) {
                    echo '<pre>';
                    echo 'property_sent_list';
                    print_r($properties_sent_list);
                    echo '</pre>';
                }

                if($_GET['frequency']) {
                    echo '<pre>';
                    echo 'property_send_list';
                    print_r($properties_send_list);
                    echo '</pre>';
                }

                /*
                    Stop If there is no new
                    property which is not sent
                    NOTE: UNCOMMENT FOR PRODUCTION
               */
                //echo "<pre>";print_r($properties_send_list);exit;
                // UNCOMMENT FOR PRODUCTION
                //            if(empty($properties_send_list)) return false;

                //COMMENT OR DELETE THE FOLLOWING CODE FOR PRODUCTION
            }
            if (empty($properties_send_list)) {
                /*
                                $ftime = (new DateTime('now'))->format('h:i:sa');
                                $to = $user->user_email;
                                $email = get_option('admin_email');
                                $subject = "Trigger: No new $frequency email alert property is found.";
                                $headers = 'From: '. $email . "\r\n" . 'Reply-To: ' . $email . "\r\n";
                                $message = "This message is sent because there is no new property enlisted for $frequency email alert.. \nTime: $ftime";

                                if(wp_mail($to, $subject, strip_tags($message), $headers));
                */
                return false;
            }

            $updated = count($properties_send_list);
            if ($updated > 1) $props = 'properties';
            else $props = 'property';

            $email = new Email('searches');
            $email->set_var('header_title', 'Your ' . $frequency . ' home alert digest for ' . date('F d, Y'));
            //$email->set_var( 'header_text', $updated . ' new matching properties since ' . date( 'M d, Y \\a\\t h:i a ', $last_sent ) );
            $email->set_var('header_text', $updated . ' new matching ' . $props . ' found');
            $email->set_var('address', Settings::get('email_footer_address'));
            $email->set_var('footer_text', str_replace('{{DATE}}', date('F d, Y'), Settings::get('email_footer_text')));
            $email->set_var('unsubscribe_url', get_bloginfo('url') . '/wp-json/vestorfilter/v1/email/unsubscribe?user_id=' . $user->ID);
            $email->set_var('subject', $updated . ' New ' . ucfirst($props) . ' - Your ' . ucfirst($frequency) . ' Home Alert Digest ' . date('M d, Y'));

            $custom_logo_id = get_theme_mod('custom_logo');
            if ($custom_logo_id) {
                $image = wp_get_attachment_image_src($custom_logo_id, 'brand-logo');
                $email->set_var('logo', $image[0]);
            }


            $all_filters = Data::get_allowed_filters();

            foreach ($results as $result) {

                $url = Settings::get_page_url('search');
                //$text = [];
                $location_name = '';
                if (!empty($result['filters']['location'])) {
                    if (!is_numeric($result['filters']['location']) && strpos($result['filters']['location'], ',') === false) {
                        $location_name = 'Custom map';
                    } else {
                        $location = Location::get($result['filters']['location']);
                        if ($location) {
                            $url .= Location::get_slug($location) . '/';
                        }
                    }
                }
                $max_preview = $result['dynamic'] ? 9 : 6;
                //echo "<pre>";print_r($result['query']);echo "</pre>";exit;
                //$loop = $result['query']->get_page_loop( [ 'per_page' => $max_preview ] );
                $loop = $result['query']->get_page_loop(['per_page' => 200]);
                $preview = null;
                while ($loop->has_properties()) {
                    $property = $loop->current_property();

                    if (!in_array($property->MLSID(), $properties_send_list)) {
                        $loop->next();
                        continue;
                    }
                    //note: property variables
                    //echo "<pre>"; print_r($property->MLSID());echo "</pre>";//continue;//exit;continue
                    $jdata = json_decode($property->get_prop('__data', true));
                    global $vfdb;
                    $mlsid = $property->MLSID();
                    $ptIndex = $vfdb->get_results("SELECT `pt_index` from `wp_property_v2` where `mlsid` = '$mlsid'");
                    switch ($ptIndex[0]->pt_index) {
                        case '4152':
                            $ptIndex = 'Single Family';
                            break;
                        case '4154':
                            $ptIndex = 'Land';
                            break;
                        case '4155':
                            $ptIndex = 'Condos / Townhomes';
                            break;
                        case '4156':
                            $ptIndex = 'Commercial';
                            break;
                        case '4157':
                            $ptIndex = '55+';
                            break;
                        case '4158':
                            $ptIndex = 'Multi-Family';
                            break;
                    }
                    //if($jdata->PropertyCategory == 'Commercial Sale') {print_r($property);continue;} else continue;
                    //print_r($jdata);
                    //echo "<pre>";print_r($jdata);echo "</pre>";exit;
                    $added = (new DateTime($jdata->DateList))->format('F d, Y');
                    //echo $jdata->DateList; exit;
                    $preview .= <<<EOT
                   <table style="color: #000000;" cellspacing="10">
                    <tr>
                        <td colspan="2">
                            <a href="{$property->get_page_url()}"><img src="{$property->get_thumbnail_url()}" alt="{$property->get_address_string(true)}" style="max-width:600px; border-radius: 10px;"></a>
                            <h3 style="text-align: center;">{$property->get_address_string(true)}</h3>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dddddd;">
                        <td style="width: 20%;">Price</td>
                        <td>\${$property->get_price()}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dddddd;">
                        <td>Status</td>
                        <td>{$property->get_prop('status', true)}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dddddd;">
                        <td>Type</td>
                        <td>{$ptIndex}</td>
                    </tr>
                EOT;
                    if ($property->get_prop('sqft', true)) $preview .= '<tr style="border-bottom: 1px solid #dddddd;"><td>Square Feet</td><td>' . number_format($property->get_prop('sqft', true)) . ' Sq. Ft</td></tr>';
                    if ($property->get_prop('int_features', true)) $preview .= '<tr style="border-bottom: 1px solid #dddddd;"><td>Interior</td><td>' . $property->get_prop('int_features', true) . '</td></tr>';
                    if ($property->get_prop('ext_features', true)) $preview .= '<tr style="border-bottom: 1px solid #dddddd;"><td>Exterior</td><td>' . $property->get_prop('ext_features', true) . '</td></tr>';
                    if ($property->get_prop('bedrooms', true)) $preview .= '<tr style="border-bottom: 1px solid #dddddd;"><td>Bedrooms</td><td>' . $property->get_prop('bedrooms', true) . '</td></tr>';
                    if ($property->get_prop('bathrooms', true)) $preview .= '<tr style="border-bottom: 1px solid #dddddd;"><td>Bathrooms</td><td>' . $property->get_prop('bathrooms', true) . '</td></tr>';
                    if ($property->get_prop('year_built', true)) $preview .= '<tr style="border-bottom: 1px solid #dddddd;"><td>Year Built</td><td>' . $property->get_prop('year_built', true) . '</td></tr>';
                    $preview .= '<tr style="border-bottom: 1px solid #dddddd;"><td>Added</td><td>' . (new DateTime($jdata->DateList))->format('F d, Y') . '</td></tr>';
                    $preview .= '<tr><td colspan="2">&nbsp;</td></tr>';
                    if ($property->get_prop('description', true)) $preview .= '<tr><td colspan="2">' . $property->get_prop('description', true) . '</td></tr>';
                    $preview .= '<tr style="text-align: center;"><td colspan="2"><a href="' . $property->get_page_url() . '"><button style="color:#ffffff; font-size: 18px; border: none;  border-radius: 8px; background-color: #0AA3C2; padding: 20px 20px 20px 20px; width: 220px; margin-top:50px; margin-bottom: 30px; cursor: pointer;">View Home</button></a></td></tr>';
                    $preview .= '<tr><td colspan="2"><hr style="opacity: .5"></td></tr></table>';
                    //echo $jdata->PropertyCategory;
                    $loop->next();
                }

                //BREAKPOINT
//                var_dump($preview);exit;

                $filters = [];
                foreach ($result['filters'] as $key => $value) {

                    if ($key === 'vf') {
                        $label = 'VestorFilter&trade;';
                        $formatted = Filters::get_filter_name($value);
                    } elseif ($key === 'location' && !empty($location_name)) {
                        $formatted = 'Custom Map';
                        $label = 'Location';
                        $value = strpos($value, '[') === false ? $user . '[' . $value . ']' : $value;
                    } elseif (isset($all_filters[$key]) && isset($all_filters[$key]['label'])) {
                        $label = $all_filters[$key]['label'];
                        $formatted = Data::get_filter_value($all_filters[$key], $value, $key);
                    } else {
                        $label = null;
                    }

                    if (!empty($label) && !empty($formatted)) {
                        $filters[] = $label . ": " . esc_html($formatted);
                        if ($key == 'dom') {
                            if ($value == '2:1') {
                                $value = '1:2';
                            } else if ($value == '8:1') {
                                $value = '1:8';
                            } else if ($value == '30:1') {
                                $value = '1:30';
                            }
                        }
                        $url = add_query_arg($key, $value, $url);
                       }
                }

                $url = add_query_arg('since', $last_sent, $url);

                $email->add_section([
                    'title' => $result['name'] ?: 'Search query',
                    'content' => $preview,
                    'set_filters' => implode("<br>", $filters) . "\n\nUpdates: " . $result['query']->total_results(),
                    'link_label' => $result['dynamic'] ? null : 'View Entire Map',
                    'link_href' => $result['dynamic'] ? null : $url,
                ]);
            }

            $email->set_tags(['TEAM_MEMBER' => $agent_name]);
            $email->set_var('to', $user->user_email);
//            $email->set_var('to', 'artesym@gmail.com');
            //NOTE: comment/uncomment src/class-email.php wpmail() to stop/start sending actual emails.

            if ($email->send() === true) {
                $properties_new_list = array_merge($properties_user_list, $properties_send_list);
                update_user_meta($user->ID, '_properties_email_sent', $properties_new_list);
                //echo "echo new user prop list<br><pre>";print_r($properties_new_list);
            }


            return $email;
//            return ['email' => $email, 'properties-list' => $properties_list];
        }
        
        public static function get_subscription_email_for_user( $user_id, $since = null ) {
            
            $subs      = get_user_meta( $user_id, '_query_subscriptions', true );
            $last_sent = is_null( $since ) ? get_user_meta( $user_id, '_subscription_last_sent', true ) : $since;
            $queries   = self::get_searches( $user_id );
            
            $user = get_user_by( 'id', $user_id );
            $agent_id = get_user_meta( $user_id, '_assigned_agent', true );
            if ( $agent_id ) {
                $agent = get_user_by( 'id', $agent_id );
            }
            $agent_name = empty( $agent ) ? '' : 'Team Member: ' . $agent->display_name;
            
            $email = self::get_subscription_email( $subs, $queries, $last_sent );
            
            if ( ! empty( $email ) ) {
                $email->set_tags( [ 'TEAM_MEMBER' => $agent_name ] );
                $email->set_var( 'to', $user->user_email );
            }
            return $email;
            
        }
        
        public static function update_smart_search( $user_id ) {
            
            $hash = get_user_meta( $user_id, '_dynamic_search', true );
            if ( empty( $hash ) ) {
                $create_new = true;
            } else {
                $search = self::get_search( $hash, $user_id );
                if ( empty( $search ) ) {
                    $create_new = true;
                    $search = null;
                    $hash = null;
                }
            }
            
            $favorites = self::get_all( $user_id );
            if ( empty( $favorites ) || count( $favorites ) < 2 ) {
                if ( empty( $create_new ) ) {
                    $my_searches = self::get_searches( $user_id );
                    unset( $my_searches[$hash] );
                    update_user_meta( $user_id, '_favorite_searches', $my_searches );
                    delete_user_meta( $user_id, '_dynamic_search' );
                }
                return false;
            }
            $params = [
                'dynamic' => true,
                'status' => 'active',
                'vf' => 'ppsf',
                'location' => [],
                'property-type' => [],
            ];
            foreach( $favorites as $favorite ) {
                $property = new Property( $favorite, false );
                //if ( $property->get_data( 'hidden' ) ) {
                //	continue;
                //}
                $types = $property->get_index( 'property-type' );
                foreach( $types as $type ) {
                    if ( ! in_array( $type, $params['property-type'] ) ) {
                        $params['property-type'][] = $type;
                    }
                }
                $zips = Location::get_for_property( $property->ID(), 'zip' );
                if ( ! empty( $zips ) ) {
                    foreach( $zips as $row ) {
                        if ( ! in_array( $row->location_id, $params['location'] ) ) {
                            $params['location'][] = $row->location_id;
                        }
                    }
                }
                $props = [
                    'bedrooms' => $property->get_prop( 'bedrooms' ) ?: $property->get_prop( 'bedrooms_mf' ) ?: null,
                    'bathrooms' => $property->get_prop( 'bathrooms' ) ?: $property->get_prop( 'bathrooms_mf' ) ?: null,
                    'sqft' => $property->get_prop( 'sqft' ) ?: $property->get_prop( 'sqft_gross' ) ?: $property->get_prop( 'sqft_mf' ) ?: null,
                    'price' => $property->get_price() ?: null,
                ];
                foreach( $props as $key => $value ) {
                    if ( empty( $value ) || ! is_numeric( $value ) ) {
                        continue;
                    }
                    if ( empty( $params[$key] ) ) {
                        $params[$key] = [
                            'min' => 999999999999999, 'max' => 0,
                        ];
                    }
                    if ( $value > $params[$key]['max'] ) {
                        $params[$key]['max'] = (float) $value;
                    }
                    if ( $value < $params[$key]['min'] ) {
                        $params[$key]['min'] = (float) $value;
                    }
                }
            }

            foreach( $params as $key => $param ) {
                if ( is_array( $param ) ) {
                    if ( isset( $param['min'] ) ) {
                        if($key == 'sqft' && $param['min'] === $param['max']) {
                            $params[$key] = '0:100000';
                        } else {
                            $params[$key] = ($param['min'] === $param['max']) ? $param['min'] : $param['min'] . ':' . $param['max'];
                        }
                    } else {
                        $params[$key] = implode(',', $param);
                    }
                }
            }
            
            $encoded = json_encode( $params );
            $new_hash = md5( $encoded . implode( '', VF_ALLOWED_FEEDS ) );
            
            $my_searches = self::get_searches( $user_id );
            
            if ( empty( $create_new ) ) {
                
                unset( $my_searches[$hash] );
                
                $current_subscriptions = self::get_subscriptions( $user_id );
                if ( ! empty( $current_subscriptions ) ) {
                    if ( isset( $current_subscriptions[ $hash ] ) ) {
                        $sub = $current_subscriptions[ $hash ];
                        unset( $current_subscriptions[ $hash ] );
                        
                        $current_subscriptions[ $new_hash ] = $sub;
                        update_user_meta( $user_id, '_query_subscriptions', $current_subscriptions );
                    }
                }
                
            } else {
                
                $params['name'] = 'Dynamic Smart Search';
                $encoded = json_encode( $params );
                
                update_user_meta( $user_id, '_has_saved_search', time() );
                Favorites::add_email_subscription( $new_hash, 7, $user_id );
                
            }
            
            $my_searches[$new_hash] = $encoded;
            
            update_user_meta( $user_id, '_favorite_searches', $my_searches );
            update_user_meta( $user_id, '_dynamic_search', $new_hash );
            
            return $new_hash;
            
        }
        
        public static function add_agent_recommendation( $for_id, $agent_id, $property_id ) {
            
            $favorites = Favorites::get_all( $for_id );
            if ( ! in_array( $property_id, $favorites ) ) {
                add_user_meta( $for_id, '_favorite_property', $property_id );
                add_user_meta( $for_id, '_agent_recommendation', [ 'agent' => get_current_user_id(), 'property' => $property_id ] );
                wp_cache_delete( 'favorite_properties__' . $for_id, 'vestorfilter' );
                
                Log::add( [ 'action' => 'favorite-saved', 'property' => $property_id, 'user' => $for_id ] );
                do_action( 'vestorfilter_favorites_changed', $for_id );
                
            }
            
        }
        
    }
    
    add_action( 'vestorfilter_installed', array( 'VestorFilter\Favorites', 'init' ) );



