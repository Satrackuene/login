<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

delete_option('satrack_egp_options');
// Limpia transients del rate limit y cache básica
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_satrack_egp_%' OR option_name LIKE '_transient_timeout_satrack_egp_%'");