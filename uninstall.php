<?php

/*
 * Uninstall WP Tuning Plugin
 */
 
if (!defined('WP_UNINSTALL_PLUGIN'))
	exit();

delete_option('wp_tuning_settings');
delete_site_option('wp_tuning_settings');

?>