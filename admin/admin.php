<?php
defined( 'WPINC' ) or die;

/**
 * Register admin settings
 * @return null
 */
function bmwp_admin() {
	register_setting( 'bmwp_settings', 'bmwp_settings' );
}
add_action( 'admin_menu', 'bmwp_admin' );

?>