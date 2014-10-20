<?php
defined( 'WPINC' ) or die;

require_once HMBKP_S3_PLUGIN_PATH . 'assets/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Common\Enum\Size;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;

/**
 * Class HMBKP_S3_Backup_Service
 */
class HMBKP_S3_Backup_Service extends HMBKP_Service {

	/**
	 * Human Friendly service name
	 *
	 * @var string
	 */
	public $name = 'S3';

	/**
	 * Whether to show this service in the tabbed interface of destinations
	 *
	 * @var boolean
	 */
	public $is_tab_visible = true;

	/**
	 * Instance of the S3 SDK class.
	 *
	 * @var null
	 */
	protected $s3 = null;

	/**
	 * Upload the backup to S3 on the hmbkp_backup_complete
	 *
	 * @see  HM_Backup::do_action
	 *
	 * @param  string $action The action received from the backup
	 *
	 * @return void
	 */
	public function action( $action ) {

		if ( ( 'hmbkp_backup_complete' === $action ) && $this->get_field_value( 'S3' ) ) {

			$file = $this->schedule->get_archive_filepath();

			if ( defined( 'HMBKP_AWS_ACCESS_KEY' ) ) {
				$key = HMBKP_AWS_ACCESS_KEY;
			} else {
				$key = $this->get_field_value( 'access_key' );
			}
			if ( defined( 'HMBKP_AWS_SECRET_KEY' ) ) {
				$secret = HMBKP_AWS_SECRET_KEY;
			} else {
				$secret = $this->get_field_value( 'secret_key' );
			}
			if ( defined( 'HMBKP_AWS_REGION' ) ) {
				$region = HMBKP_AWS_ACCESS_KEY;
			} else {
				$region = $this->get_field_value( 'aws_region' );
			}

			$bucket = $this->get_field_value( 'bucket' );

			$this->fetch_s3_connection( $key, $secret, $region );

			if ( ! empty( $this->s3 ) ) {

				$this->schedule->set_status( __( 'Uploading to Amazon S3', 'backupwordpress-pro-s3' ) );

				$this->upload_backup( $file, $bucket );

				$this->delete_old_backups( $bucket );

			} else {
				$this->schedule->error( 'S3', sprintf( __( 'Could not connect to %s', 'backupwordpress-pro-s3' ), $this->get_field_value( 'bucket' ) ) );
			}
		}

	}

	/**
	 * @param $file
	 * @param $bucket
	 */
	protected function upload_backup( $file, $bucket ) {

		$filename = pathinfo( $file, PATHINFO_BASENAME );

		// Upload an object by streaming the contents of a file
		// $pathToFile should be absolute path to a file on disk
		$result = $this->s3->putObject( array(
			'Bucket'     => $bucket,
			'Key'        => $filename,
			'SourceFile' => $file,
			'Metadata'   => array()
		) );

		// We can poll the object until it is accessible
		$this->s3->waitUntilObjectExists( array(
			'Bucket' => $bucket,
			'Key'    => $filename,
		) );

	}

	/**
	 * Delete extra backup files on S3 storage.
	 *
	 * @param $bucket
	 */
	protected function    delete_old_backups( $bucket ) {

		// get max backups number
		$max_backups = absint( $this->get_field_value( 's3_max_backups' ) );

		// get list of existing remote backups
		$iterator = $this->s3->getIterator( 'ListObjects', array(
			'Bucket' => $bucket,
		) );

		$response = $iterator->toArray();

		$backup_files = wp_list_pluck( $response, 'Key' );

		$backup_files = array_filter( $backup_files, array( $this, 'filter_files' ) );

		if ( count( $backup_files ) <= $max_backups ) {
			return;
		}

		krsort( $backup_files );

		$files_to_delete = array_slice( $backup_files, $max_backups );

		foreach ( $files_to_delete as $filename ) {

			$response = $this->s3->deleteObject( array(
				'Bucket' => $bucket,
				'Key'    => $filename,
			) );

		}
	}

	/**
	 * Callback to filter an array based on the backup prefix
	 *
	 * @param $element
	 *
	 * @return bool
	 */
	protected function filter_files( $element ) {

		$pattern = implode( '-', array(
				sanitize_title( str_ireplace( array( 'http://', 'https://', 'www' ), '', home_url() ) ),
				$this->schedule->get_id(),
				$this->schedule->get_type(),
			)
		);

		return ( false === ( strpos( $element, $pattern ) ) ) ? false : true;
	}

	/**
	 * Connect to Amazon S3 and return it on success
	 *
	 * @param $key
	 * @param $secret
	 * @param $bucket
	 *
	 * @return AmazonS3|bool
	 */
	function fetch_s3_connection( $key, $secret, $region = 'us-east-1' ) {

		$this->s3 = S3Client::factory( array(
			'key'    => $key,
			'secret' => $secret,
			'region' => $region,
		) );

	}

	/**
	 * Output the S3 form fields
	 */
	public function form() {

		$bucket = $this->get_field_value( 'bucket' );

		if ( empty( $bucket ) ) {

			$options = $this->fetch_destination_settings();

			if ( ! empty( $options ) ) {
				$bucket = $options['bucket'];
			}
		}

		$access_key = $this->get_field_value( 'access_key' );

		if ( empty( $access_key ) && ( isset( $options['access_key'] ) ) ) {
			$access_key = $options['access_key'];
		}

		$secret_key = $this->get_field_value( 'secret_key' );

		if ( empty( $secret_key ) && ( isset( $options['secret_key'] ) ) ) {
			$secret_key = $options['secret_key'];
		}

		$max_backups = $this->get_field_value( 's3_max_backups' );

		if ( empty( $max_backups ) && isset( $options['s3_max_backups'] ) ) {
			$max_backups = $options['s3_max_backups'];
		}

		?>

		<table class="form-table">

		<tbody>

		<tr>

			<th scope="row"><?php esc_html_e( 'Send a copy of each backup to Amazon S3', 'backupwordpress-pro-s3' ); ?></th>

			<td>
				<label for="<?php echo $this->get_field_name( 'S3' ); ?>">

					<input type="checkbox" <?php checked( $this->get_field_value( 'S3' ) ) ?> id="<?php echo $this->get_field_name( 'S3' ); ?>" name="<?php echo $this->get_field_name( 'S3' ); ?>" value="1"/><?php _e( 'Active', 'backupwordpress-pro-s3' ); ?>
				</label>
			</td>

		</tr>

		<tr>

			<th scope="row">

			<label for="<?php echo $this->get_field_name( 'access_key' ); ?>"><?php _e( 'Access Key', 'backupwordpress-pro-s3' ); ?></label>

			</th>

			<td>

			<input type="password" id="<?php echo $this->get_field_name( 'access_key' ); ?>" name="<?php echo $this->get_field_name( 'access_key' ); ?>" value="<?php echo $access_key; ?>"/>

			<p class="description">Find your credentials here: <a href="https://console.aws.amazon.com/iam/home?#security_credential">AWS console</a></p>

			</td>

		</tr>

		<tr>

			<th scope="row">

			<label for="<?php echo $this->get_field_name( 'secret_key' ); ?>"><?php _e( 'Secret Key', 'backupwordpress-pro-s3' ); ?></label>

			</th>

			<td>

			<input type="password" id="<?php echo $this->get_field_name( 'secret_key' ); ?>" name="<?php echo $this->get_field_name( 'secret_key' ); ?>" value="<?php echo $secret_key ?>"/>

			</td>

		</tr>

		<tr>

			<th scope="row"">

			<label for="<?php echo $this->get_field_name( 'bucket' ); ?>"><?php _e( 'Bucket', 'backupwordpress-pro-s3' ); ?></label>

			</th>

			<td>

			<input type="text" id="<?php echo $this->get_field_name( 'bucket' ); ?>" name="<?php echo $this->get_field_name( 'bucket' ); ?>" value="<?php echo $bucket ?>"/>

			<p class="description"><?php _e( 'The Bucket to save the backups to, you\'ll need to create it first.', 'backupwordpress-pro-s3' ); ?></p>

			</td>

		</tr>

		<tr>

			<th scope="row">

			<label for="<?php echo esc_attr( $this->get_field_name( 'aws_region' ) ); ?>"><?php _e( 'Region', 'backupwordpress-pro-s3' ); ?></label>

			</th>

			<?php

			$region = $this->get_field_value( 'aws_region' );

			// set a default value if none is set
			if ( empty( $region ) ) {
				$region = 'us-east-1';
			}

			?>

			<td>

			<select name="<?php echo esc_attr( $this->get_field_name( 'aws_region' ) ); ?>" id="<?php echo esc_attr( $this->get_field_name( 'aws_region' ) ); ?>">

				<option <?php selected( $region, 'us-east-1' ); ?> value="us-east-1"><?php _e( 'US Standard', 'backupwordpress-pro-s3' ); ?></option>

				<option <?php selected( $region, 'us-west-2' ); ?> value="us-west-2"><?php _e( 'US West (Oregon) Region', 'backupwordpress-pro-s3' ); ?></option>

				<option <?php selected( $region, 'us-west-1' ); ?> value="us-west-1"><?php _e( 'US West (Northern California) Region', 'backupwordpress-pro-s3' ); ?></option>

				<option <?php selected( $region, 'eu-west-1' ); ?>
					value="eu-west-1"><?php _e( 'EU (Ireland) Region', 'backupwordpress-pro-s3' ); ?></option>

				<option <?php selected( $region, 'ap-southeast-1' ); ?> value="ap-southeast-1"><?php _e( 'Asia Pacific (Singapore) Region', 'backupwordpress-pro-s3' ); ?></option>

				<option <?php selected( $region, 'ap-southeast-2' ); ?> value="ap-southeast-2"><?php _e( 'Asia Pacific (Sydney) Region', 'backupwordpress-pro-s3' ); ?></option>

				<option <?php selected( $region, 'ap-northeast-1' ); ?> value="ap-northeast-1"><?php _e( 'Asia Pacific (Tokyo) Region', 'backupwordpress-pro-s3' ); ?></option>

				<option <?php selected( $region, 'sa-east-1' ); ?> value="sa-east-1"><?php _e( 'South America (Sao Paulo) Region', 'backupwordpress-pro-s3' ); ?></option>

			</select>

			</td>

		</tr>

		<tr>

			<th scope="row">

			<label for="<?php echo $this->get_field_name( 's3_max_backups' ); ?>"><?php _e( 'Max backups', 'backupwordpress-pro-s3' ); ?></label>

			</th>

			<td>

			<input class="small-text" type="number" min="1" step="1" id="<?php echo $this->get_field_name( 's3_max_backups' ); ?>" name="<?php echo $this->get_field_name( 's3_max_backups' ); ?>" value="<?php echo( empty( $max_backups ) ? 3 : $max_backups ); ?>"/>

			<p class="description"><?php _e( 'The maximum number of backups to store.', 'backupwordpress-pro-s3' ); ?></p>

			</td>

		</tr>

		</tbody>

		</table>

		<input type="hidden" name="is_destination_form" value="1"/>

	<?php
	}

	/**
	 * Output the S3 Constant help text
	 */
	public static function constant() {
		?>

		<tr<?php if ( defined( 'HMBKP_AWS_ACCESS_KEY' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_AWS_ACCESS_KEY</code></td>

			<td>

				<?php if ( defined( 'HMBKP_AWS_ACCESS_KEY' ) ) { ?>
					<p><?php printf( __( 'You\'ve set it to: %s', 'hmbkp' ), '<code>' . HMBKP_AWS_ACCESS_KEY . '</code>' ); ?></p>
				<?php } ?>

				<p><?php _e( 'Your Amazon S3 Access Key', 'backupwordpress-aws' ); ?> <?php _e( 'e.g.', 'backupwordpress-aws' ); ?>
					<code>YOUR_AWS_ACCESS_KEY</code></p>

			</td>

		</tr>

		<tr<?php if ( defined( 'HMBKP_AWS_SECRET_KEY' ) ) { ?> class="hmbkp_active"<?php } ?>>

			<td><code>HMBKP_AWS_SECRET_KEY</code></td>

			<td>

				<?php if ( defined( 'HMBKP_AWS_SECRET_KEY' ) ) { ?>
					<p><?php printf( __( 'You\'ve set it to: %s', 'hmbkp' ), '<code>' . HMBKP_AWS_SECRET_KEY . '</code>' ); ?></p>
				<?php } ?>

				<p><?php _e( 'Your Amazon S3 Secret Key', 'backupwordpress-aws' ); ?> <?php _e( 'e.g.', 'backupwordpress-aws' ); ?>
					<code>YOUR_AWS_SECRET_KEY</code></p>
			</td>

		</tr>

	<?php
	}

	/**
	 * Define as an empty function as we are using form
	 */
	public function field() {}

	/**
	 * Validate the data before saving, if there are errors, return them to the user
	 *
	 * @param  array $new_data the new data thats being saved
	 * @param  array $old_data the old data thats being overwritten
	 *
	 * @return array           any errors encountered
	 */
	public function update( &$new_data, $old_data ) {

		$errors = array();

		if ( ! isset( $new_data['S3'] ) ) {
			return;
		} // destination was disabled

		// TODO we could specifically check that the bucket exists
		if ( isset( $new_data['bucket'] ) ) {

			if ( empty( $new_data['bucket'] ) ) {
				$errors['bucket'] = __( 'You need to enter the S3 bucket', 'backupwordpress-pro-s3' );
			}
		}

		if ( isset( $new_data['access_key'] ) ) {

			if ( empty( $new_data['access_key'] ) ) {
				$errors['access_key'] = __( 'You need to enter the S3 access key', 'backupwordpress-pro-s3' );
			}

		}

		if ( isset( $new_data['secret_key'] ) ) {

			if ( empty( $new_data['secret_key'] ) ) {
				$errors['secret_key'] = __( 'You need to enter the S3 secret key', 'backupwordpress-pro-s3' );
			}
		}

		if ( isset( $new_data['aws_region'] ) ) {

			if ( empty( $new_data['aws_region'] ) ) {
				$errors['aws_region'] = __( 'You need to select a region', 'backupwordpress-pro-s3' );
			}
		}

		if ( empty( $new_data['s3_max_backups'] ) || ! ctype_digit( $new_data['s3_max_backups'] ) || absint( $new_data['s3_max_backups'] ) < 1 ) {
			$errors['s3_max_backups'] = __( 'Max backups must be a number', 'backupwordpress-pro-s3' );
		}

		// Test that we can connect to S3
		if ( ! empty( $new_data['bucket'] ) && ! empty( $new_data['access_key'] ) && ! empty( $new_data['secret_key'] ) ) {

			$this->fetch_s3_connection( $new_data['access_key'], $new_data['secret_key'], $new_data['aws_region'] );

			if ( ! $this->s3 ) {
				$errors['bucket'] = __( 'Could not connect to S3', 'backupwordpress-pro-s3' );
			} else {
				try {
					$result = $this->s3->listBuckets( array() );
				} catch ( Exception $e ) {
					$errors['bucket'] = sprintf( __( '%s', 'backupwordpress-pro-s3' ), $e->getMessage() );
				}
				$buckets = '';
				if ( isset( $result['Buckets'] ) ) {
					$buckets = wp_list_pluck( $result['Buckets'], 'Name' );
				} else {
					$errors['bucket'] = __( 'No buckets retrieved', 'backupwordpress-pro-s3' );
				}

				if ( $buckets && ! in_array( $new_data['bucket'], $buckets ) ) {
					$errors['bucket'] = __( 'Bucket does not exist', 'backupwordpress-pro-s3' );
				}
			}
		}

		return $errors;

	}

	/**
	 * The words to append to the main schedule sentence
	 *
	 * @return string The words that will be appended to the main schedule sentence
	 */
	public function display() {

		if ( $this->is_service_active() ) {
			return __( $this->name, 'backupwordpress-pro-s3' );
		}

	}

	/**
	 * Used to determine if the service is in use or not
	 *
	 * @return bool True if service is active
	 */
	public function is_service_active() {
		return (bool) $this->get_field_value( 'S3' );
	}

	/**
	 * @return array
	 */
	public static function intercom_data() {

		require_once HMBKP_S3_PLUGIN_PATH . 'inc/class-requirements.php';

		$info = array();

		foreach ( HMBKP_Requirements::get_requirements( 'amazon' ) as $requirement ) {
			$info[ $requirement->name() ] = $requirement->result();
		}

		return $info;
	}

	/**
	 *
	 */
	public static function intercom_data_html() {

		require_once HMBKP_S3_PLUGIN_PATH . 'inc/class-requirements.php';

		?>

		<h3><?php _e( 'Amazon', 'backupwordpress-pro-s3' ); ?></h3>

		<table class="fixed widefat">

			<tbody>

			<?php foreach ( HMBKP_Requirements::get_requirements( 'amazon' ) as $requirement ) : ?>

				<tr>
					<td><?php echo $requirement->name(); ?></td>
					<td>
						<pre><?php echo $requirement->result(); ?></pre>
					</td>
				</tr>

			<?php endforeach; ?>

			</tbody>

		</table>

	<?php
	}

}

HMBKP_Services::register( __FILE__, 'HMBKP_S3_Backup_Service' );
