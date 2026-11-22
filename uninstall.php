<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
delete_option('lt_verified');
delete_option('lt_calc_overrides');
