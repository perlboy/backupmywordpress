<?php
/*
Plugin Name: Backup My Wordpress
Plugin URI: http://www.backupmywordpress.net/
Description: Backup your Wordpress Install to the Cloud!
Author: Backup My Wordpress
Version: 1.0.0
Author URI: http://www.backupmywordpress.net/
License: GPLv2
Network: true
Text Domain: backupwordpress
Depends: backupwordpress
Domain Path: /languages
*/

/*
Copyright 2013-2014 Backup My Wordpress (support@backupmywordpress.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
namespace BMWP\BackupMyWordpress;

use HM\BackUpWordPress;

register_activation_hook( __FILE__, array( 'BMWP\BackupMyWordpress\Plugin', 'on_activation' ) );

register_deactivation_hook( __FILE__, array( 'BMWP\BackupMyWordpress\Plugin', 'on_deactivation' ) );

/**
 * Class Plugin
 */
class Plugin {

	/**
	 * The plugin version number.
	 */
	const PLUGIN_VERSION = '1.0.0';

	/**
	 * Minimum version of BackUpWordPress compatibility.
	 */
	const MIN_BWP_VERSION = '3.1.2';

	/**
	 * @var The instance of this class.
	 */
	private static $instance;

	/**
	 * Instantiates a new object
	 */
	private function __construct() {

		add_action( 'backupwordpress_loaded', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'maybe_self_deactivate' ) );
	}

	/**
	 * @return Plugin
	 */
	public static function get_instance() {

		if ( ! ( self::$instance instanceof Plugin ) ) {
			self::$instance = new Plugin();
		}

		return self::$instance;
	}

	/**
	 * Fires on plugin activation. Checks plugin requirements, and interrupts activation if not met.
	 */
	public static function on_activation() {}

	/**
	 * Performs a cleanup on deactivation.
	 */
	public static function on_deactivation() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		delete_option( 'bmwp_settings' );
	}

	/**
	 * PLugin setup routine.
	 */
	public function init() {

		$this->includes();

		$this->hooks();

	}

	/**
	 * Self deactivate ourself if incompatibility found.
	 */
	public function maybe_self_deactivate() {

		if ( $this->meets_requirements() ) {
			return;
		}

		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

	}

	/**
	 * Include required scripts and classes.
	 */
	protected function includes() {
		require_once plugin_dir_path( __FILE__ ) . 'admin/admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'inc/class-transfer.php';
	}


	/**
	 * Register our hooked functions.
	 */
	protected function hooks() {}

	/**
	 * Returns an array of default values for plugin settings.
	 *
	 * @return array
	 */
	public function default_settings() {

		$defaults = array(
		);

		return $defaults;
	}

	/**
	 * Fetch the plugin settings
	 *
	 * @return array
	 */
	public function fetch_settings() {
		return array_merge( $this->default_settings(), get_option( 'bmwp_settings', array() ) );
	}

	/**
	 * Displays a user friendly message in the WordPress admin.
	 */
	public function display_admin_notices() {

		echo '<div class="error"><p>' . esc_html( self::get_notice_message() ) . '</p></div>';

	}

	/**
	 * Returns a localized user friendly error message.
	 *
	 * @return string
	 */
	public function get_notice_message() {

		return sprintf(
			__( 'Backup My Wordpress requires BackUpWordPress version %1$s. Please install or update it first.', 'backupwordpress' ),
			self::MIN_BWP_VERSION
		);
	}

	/**
	 * Check if current WordPress install meets necessary requirements.
	 *
	 * @return bool True is passes checks, false otherwise.
	 */
	public function meets_requirements() {

		if ( ! class_exists( 'HM\BackUpWordPress\Plugin' ) ) {
			return false;
		}

		$bwp = BackUpWordPress\Plugin::get_instance();

		if ( version_compare( BackUpWordPress\Plugin::PLUGIN_VERSION, self::MIN_BWP_VERSION, '<' ) ) {
			return false;
		}

		return true;
	}

}
Plugin::get_instance();
