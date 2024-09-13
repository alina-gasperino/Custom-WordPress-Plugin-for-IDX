<?php

$vf_host = "wordpress-509904-1618652.cloudwaysapps.com:8082";
$vf_user = "rfvjfqkjdr";
$vf_db_password = "YGXgW2wKfe";
$vf_db_name = "rfvjfqkjdr";

global $vfdb, $wpdb, $table_prefix;

$vfdb = new wpdb( $vf_user, $vf_db_password, $vf_db_name, $vf_host );
$vfdb->set_prefix( defined( 'VF_DB_PREFIX' ) ? VF_DB_PREFIX : $table_prefix );