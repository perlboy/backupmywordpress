<?php
namespace BMWP\BackupMyWordpress;

use HM\BackUpWordPress;

require_once plugin_dir_path( __FILE__ ) . 'class-cloud.php';

/**
 * Class Cloud_Backup_Service
 */
class Cloud_Backup_Service extends BackUpWordPress\Service {

	/**
	 * Human readable name
	 * @var string
	 */
	public $name = 'Backup My Wordpress Cloud Storage';

	/**
	 * BMWP Login details
	 * @var Array
	 */
	protected $credentials;
	protected $connection;

	/**
	 * Fire the transfer to the cloud on completion from parent plugin
	 *
	 * @param  string $action The action received from the backup
	 *
	 * @return void
	 */
	public function action( $action, BackUpWordPress\Backup $backup ) {
				
		if ( ( 'hmbkp_backup_complete' === $action ) && $this->get_field_value( 'BMWP' ) ) {
			
			$this->schedule->set_status('Uploading to Backup My Wordpress');

			$file = $backup->get_archive_filepath();

			$metadata = array(
				'description' => sprintf('Name: %s Type: %s Date: %s', get_bloginfo(), $backup->get_type(), date("Y-m-d h:i:sa")),
				'backup_type' => $backup->get_type(),
				'date' => date("Y-m-d h:i:sa"),
				'blog_name' => get_bloginfo(),
				'blog_url' => get_site_url()
			);
			$this->credentials = array(
				'email'        => $this->get_field_value( 'username' ),
				'subscription_id'        => $this->get_field_value( 'subscription_id' ),
				'metadata' => $metadata
			);
			
			$this->connection = new BMWP( $this->credentials );

			$this->send_backup( $file );
		}
	}

	/**
	 * Backup runner
	 * @param $file
	 */
	public function send_backup( $file ) {
		$this->schedule->set_status("Uploading a copy to Backup My Wordpress");

		$result = $this->connection->upload( $file );

		if ( is_wp_error( $result ) ) {
			$backup->error( 'BMWP', sprintf('An error occurred: %s', $result->get_error_message() ) );
		}

	}

	/**
	 * Delete Old Backups Caller
	 * BMWP COULD delete files but we choose not to.
	 */
	protected function delete_old_backups() {

	}

	/**
	 * Displays the settings form for the BMWP Backup
	 */
	public function form() {
		
		$username = $this->get_field_value( 'username' );

		if ( empty( $username ) && ( isset( $options['username'] ) ) ) {
			$username = $options['username'];
		}
		
				
		$subscription_id = $this->get_field_value( 'subscription_id' );

		if ( empty( $subscription_id ) && ( isset( $options['subscription_id'] ) ) ) {
			$subscription_id = $options['subscription_id'];
		}
		
			
		// Output errors
		if ( is_wp_error( $form_error ) ) {
			foreach ( $form_error->get_error_messages() as $error ) {
				 echo '<div>';
				 echo '<strong>ERROR</strong>:';
				 echo $error . '<br/>';
				 echo '</div>';
			}
		}
	?>

		<table class="form-table">

			<tbody>

			<?php
				if(empty($username) || empty($subscription_id)) {
					?>
					
			<tr>
				<td colspan=2>You need to register this schedule as a BMWP subscription. Once this is completed these settings will be locked for this schedule.</td>
			</tr>
			<tr>

				<th scope="row">

					<label for="<?php echo $this->get_field_name( 'username' ); ?>">BMWP Username</label>

				</th>

				<td>

					<input type="text" id="<?php echo $this->get_field_name( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" value=""/>

				</td>

			</tr>

			<tr>

				<th scope="row">

					<label for="<?php echo $this->get_field_name( 'password' ); ?>">BMWP Password</label>

				</th>

				<td>

					<input type="password" id="<?php echo $this->get_field_name( 'password' ); ?>" name="<?php echo $this->get_field_name( 'password' ); ?>" value=""/>

				</td>

			</tr>
			
			<?php
				} else {
			?>			
			<tr>

				<th scope="row">

					<label for="<?php echo $this->get_field_name( 'username' ); ?>">BMWP Username</label>

				</th>

				<td>

					<input type="text" disabled="yes" id="<?php echo $this->get_field_name( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" value="<?php echo esc_attr( $username ); ?>"/>

				</td>

			</tr>

			<tr>

				<th scope="row">

					<label for="<?php echo $this->get_field_name( 'subscription_id' ); ?>">BMWP Subscription ID</label>

				</th>

				<td>

					<input disabled="yes" type="text" id="<?php echo $this->get_field_name( 'subscription_id' ); ?>" name="<?php echo $this->get_field_name( 'subscription_id' ); ?>" value="<?php echo esc_attr( $subscription_id ); ?>"/>

				</td>

			</tr>			
			<?php
				}
			?>

			</tbody>

		</table>

	<?php
	}

	/**
	 * Form so empty
	 */
	public function field() {
	}

	/**
	 * Validate the data before saving, if there are errors, return them to the user
	 *
	 * @param  array $newData the new data thats being saved
	 * @param  array $oldData the old data thats being overwritten
	 *
	 * @return array           any errors encountered
	 */
	public function update( &$newData, $oldData ) {

		$myErrors = array();

		/**
		 * Short circuit an already filled subscription id, we do not allow resets.
		 */
		if(isset($oldData['subscription_id'])) {
			$newData = $oldData;
			return $myErrors;
		}
		
		if ( isset( $newData['username'] ) ) {
			if ( empty( $newData['username'] ) ) {
				$myErrors['username'] = 'You must supply a valid username';
			}
		}
		
		if ( isset( $newData['password'] ) ) {
			if ( empty( $newData['password'] ) ) {
				$myErrors['password'] = 'You must supply a valid password';
			}
		}

		if ( empty ( $myErrors ) ) {
			$this->credentials = array(
				'username'        => $newData['username'],
				'password'    => $newData['password'],
			);
			
			$this->connection = new BMWP($this->credentials);
			
			$result = $this->connection->register_subscription( $this->credentials );

			if ( is_wp_error( $result ) ) {
				$myErrors['authentication'] = sprintf('Backup My Wordpress received a %s',  $result->get_error_message() );
				return $myErrors;
			}
			
			/**
			 * Successful registration
			 */
			$newData['password'] = '';
			$newData['subscription_id'] = $result['key'];
			$newData['BMWP'] = 1;
		}
		return $myErrors;
	}

	/**
	 * The words to append to the main schedule sentence
	 * @return string The words that will be appended to the main schedule sentence
	 */
	public function display() {

		if ( $this->is_service_active() ) {
			return "Backup My Wordpress Cloud Storage";
		}

	}

	/**
	 * Used to determine if the service is in use or not
	 */
	public function is_service_active() {
		return (bool) $this->get_field_value( 'BMWP' );
	}

	/**
	 * Intercom data just going to return blank on this
	 *
	 * @return array
	 */
	public static function intercom_data() {
		$info = array();
		return $info;
	}
	
	/**
	 * BMWP specific data to show in the admin help tab.
	 */
	public static function intercom_data_html() {
		?>
		<h3>Backup My Wordpress Cloud Plugin</h3>

		<table class="fixed widefat">

			<tbody>

			<tr><td colspan=2><b>Backup My Wordpress Cloud Extension</b></td></tr>
			<tr><td colspan=2>Please refer to BackupMyWordpress.net for all support queries</td></tr>

			</tbody>

		</table>

	<?php
	}

}

BackUpWordPress\Services::register( __FILE__, 'BMWP\BackupMyWordpress\Cloud_Backup_Service' );
