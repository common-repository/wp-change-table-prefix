<?php
/**
 * Plugin Name: WP Change Table Prefix
 * Plugin URI: http://www.niblettindustries.com/wordpress/plugins/wp-change-table-prefix
 * Description: Safely and Quickly change Wordpress' Table Prefix
 * Version: 0.1
 * Author: Niblett Industries
 * Author URI: http://www.niblettindustries.com

 * @package Table-Prefix
 * @version 0.1
 * @author Niblett Industries <wordpress@niblettindustries.com>
 * @copyright Copyright (c) 2006 - 2012, Niblett Industries
 * @link http://www.niblettindustries.com/wordpress/plugins/wp-change-table-prefix
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

	# create class
	class TablePrefix {

		# public variables
		public $plugin_path;
		public $wp_config_path;
		public $new_prefix;

		/**
		 * PHP5 constructor method.
		 * @since 0.1
		 */
		function __construct() {

			# set varibles
			$this->plugin_path = ABSPATH . 'wp-content/plugins/wp-change-table-prefix/';

			/* Load the Options Page into the admin menu hook */
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

		# close function __construct() {
		}

		/**
		 * Admin Menu Function called above
		 * @since 0.1
		 */
		function admin_menu () {

			# load the page
			add_options_page( 'Change Table Prefix', 'Table Prefix', 'manage_options', 'change-table-prefix', array( $this, 'settings_page' ) );

		# close function admin_menu () {
		}

		/**
		 * Settings Page Function called above
		 * @since 0.1
		 */
		function settings_page() {

			# include page
			include $this->plugin_path . 'includes/settings.inc.php';

		# close function settings_page() {
		}

		/**
		 * validate table prefix string
		 * @since 0.1
		 */
		 function validate_string( $string ) {

			# if the string is correct
			if( preg_match('/^[a-zA-Z_\d]+$/', $string ) ) {

				# return string
				return $string;

			# close if( preg_match('/^[\w\d]$/', $string ) ) {
			}

			# return false
			return false;

		 # close function validate_string( $string ) {
		 }


		/**
		 * process change
		 * @since 0.1
		 */
		 function Process_Change() {

			# return array
			$return = array();

			# set initial status to process, only needs to change IF there is a problem
			$return[ 'status' ] = 'process';

			# make sure we can alter the db
			if( $this->validate_mysql_permissions() == false ) {

				# set error message
				$return[ 'mysql' ][] = array(
					"type"		=>	"error",
					"message"	=>	"Sorry, you don't have the correct MySQL (database) permissions to alter your tables. You Must have 'Alter', 'Drop' &amp; 'Create' permissions",
				);

				# change return status
				$return[ 'status' ] = 'fail';

			# close if( $TablePrefix->validate_mysql_permissions() == false ) {
			}

			# make sure we can alter the db
			if( $this->validate_file_write_permissions() == false ) {

				# set error message
				$return[ 'file-write' ][] = array(
					"type"		=>	"error",
					"message"	=>	"Sorry, you don't have the correct permissions to write to your wp-config.php file.",
				);

				# change return status
				$return[ 'status' ] = 'fail';

			# close if( $TablePrefix->validate_file_write_permissions() == false ) {
			}

			# if return status is process
			if( $return[ 'status' ] == 'process' ) {

				# set $return array with new values
				$return[ 'options' ] 	= $this->update_options();
				$return[ 'mysql' ] 		= $this->update_tables();
				$return[ 'file-write' ] = $this->update_wpconfig();

			# close if( $return[ 'status' ] == 'process' ) {
			}

			# return value
			return $return;

		# close function Process_Change() {
		}

		/**
		 * validate MySQL permissions
		 * @since 0.1
		 */
		 function validate_mysql_permissions() {

			 # get MySQL grants for this user
			$sql = "SHOW GRANTS FOR CURRENT_USER";
			$exe = mysql_query( $sql );

			# loop through all the results
			while( $row = mysql_fetch_array( $exe ) ) {

				# format string
				$string = stripslashes( $row[ 0 ] );
				$string = str_replace( "GRANT ", "", $string );
				trim( $string );

				# make sure its the correct table
				if( preg_match( "/". DB_NAME ."/", $string ) ) {

					# to make sure someone didn't do something stupid like name their database 
					# with 'ALTER' in it, we will split up the query
					$split = explode( "ON `". DB_NAME ."`.*", $string );
					$string = $split[ 0 ];

					# make sure we have permissions
					if( ( preg_match( "/ALTER/", $string ) && preg_match( "/DROP/", $string ) && preg_match( "/CREATE/", $string ) ) || preg_match( "/ALL PRIVILEGES/", $string ) ) {

						# we are good to modify the table
						return true;

					# close if( preg_match( "/ALTER/", $string ) || preg_match( "/ALTER/", $string ) ) {
					}

				# close if( preg_match( "/". DB_NAME ."/", $string ) ) {
				}

			# close while( $row = mysql_fetch_array( $exe ) ) {
			}

			# return false
			return false;

		 # close function validate_string( $string ) {
		 }

		/**
		 * validate file write permissions
		 * @since 0.1
		 */
		 function validate_file_write_permissions() {

			# code taken from wp-load.php in wordpress root
			# if the config file is in ABSPATH
			if( file_exists( ABSPATH . 'wp-config.php' ) ) {

				# path
				$wp_config_path = ABSPATH . 'wp-config.php';

			# otherwise, if the config file resides one level above ABSPATH but is not part of another install
			} else if( file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {

				# path
				$wp_config_path = dirname( ABSPATH ) . '/wp-config.php';

			# close if( file_exists( ABSPATH . 'wp-config.php' ) ) {
			}

			# make sure file is writable
			if( is_writable( $wp_config_path ) ) {

				# set wp_config_path
				$this->wp_config_path = $wp_config_path;

				# return true
				return true;

			# close if( is_writable( $wp_config_path ) ) {
			}

			# return false
			return false;

		 # close function validate_string( $string ) {
		 }

		/**
		 * update tables
		 * @since 0.1
		 */
		function update_tables() {

			# global
			global $wpdb;

			# GET TABLE NAMES
			$tables = $wpdb -> get_results( 'SHOW TABLES IN '.DB_NAME );
			$tables_in_DB_NAME = 'Tables_in_'.DB_NAME;

			# loop through all the tables
			for( $i=0; $i<count( $tables ); $i++ ) {

				# table name
				$old_table_name = $tables[$i]->$tables_in_DB_NAME;
				$new_table_name = $this->new_prefix . substr( $old_table_name, strlen( $wpdb->prefix ) );

				# if the table starts with the prefix
				if( substr( $old_table_name, 0, strlen( $wpdb->prefix ) ) == $wpdb->prefix ) {

					# rename tables
					$sql = "RENAME TABLE `". $old_table_name ."` TO `". $new_table_name ."`";
					$exe = mysql_query( $sql );

					# if the write is successful
					if( $exe ) {

						# return array
						$return[] = array(
							'type'		=>	'updated',
							'message'	=>	'Table Re-Name: '. $tables[$i]->$tables_in_DB_NAME .' was renamed successfully!'
						);

					# otherwise
					} else {

						# return array
						$return[] = array(
							'type'		=>	'error',
							'message'	=>	'Table Re-Name: ERROR! '. $tables[$i]->$tables_in_DB_NAME .' was NOT renamed! Please load database backup!'
						);

					# close if( $exe ) {
					}

				# close if( substr( $old_table_name, 0, strlen( $wpdb->prefix ) ) == $wpdb->prefix ) {
				}

			# close for( $i=0; $i<count( $tables ); $i++ ) {
			}

			# return $return
			return $return;

		# close function update_tables() {
		}

		/**
		 * update wp-config.php
		 * @since 0.1
		 */
		function update_wpconfig() {

			# wp-content file & other variables
			$file	 = file_get_contents( $this->wp_config_path );
			$string  = "table_prefix";
			$rebuild = "";

			# explode data
			$data = explode( "\n", $file );

			# loop through the lines of code
			for( $line=0; $line<count( $data ); $line++ ) {

				# if this is the correct line
				if( preg_match( "/table_prefix/", $data[ $line ] ) ) {

					# re-build data
					$rebuild .= "\$table_prefix = '". $this->new_prefix ."';\n";

				# otherwise if( strpos( $data[ $line ], $string) >= 0 ) {
				} else {

					# re-build data
					$rebuild .= $data[ $line ] . "\n";

				# close if( preg_match( "/table_prefix/", $data[ $line ] ) ) {
				}

			# close for( $line=0; $line<count( $data ); $line++ ) {
			}

			# open wp-config.php
			$handle = fopen( $this->wp_config_path, 'w' );

			# if the write is successful
			if( fwrite( $handle, $rebuild ) ) {

				# return array
				$return[] = array(
					'type'		=>	'updated',
					'message'	=>	'wp-config.php: File was written to successfully!'
					);

			# otherwise
			} else {

				# return array
				$return[] = array(
					'type'		=>	'error',
					'message'	=>	'wp-config.php: ERROR! File could not be written to. Replace the file ASAP!'
				);

			# close if( fwrite( $handle, $rebuild ) ) {
			}

			# close connection to file
			fclose( $handle );

			# return $return
			return $return;

		# close function update_wpconfig() {
		}

		/**
		 * update website options
		 * @since 0.1
		 */
		function update_options() {

			# global
			global $wpdb;

			# query values for options table
			$table		= $wpdb->prefix ."options";
			$old_option = $wpdb->prefix ."user_roles";
			$new_option = $this->new_prefix ."user_roles";

			# query database for user_roles
			$sql = "UPDATE `". $table ."` SET `option_name` = '". $new_option ."' WHERE `option_name` = '". $old_option ."'";
			$exe = mysql_query( $sql );

			# if the results are set
			if( mysql_affected_rows() > 0 ) {

				# return array
				$return[] = array(
					'type'		=>	'updated',
					'message'	=>	'Options: user_roles hase been updated successfully'
					);

			# otherwise
			} else {

				# return array
				$return[] = array(
					'type'		=>	'error',
					'message'	=>	'Options: `user_roles` WAS NOT UPDATED IN '. $table
				);

			# close if( mysql_affected_rows() > 0 ) {
			}

			# query values for user-meta table
			$table = $wpdb->prefix ."usermeta";

			# query database for user_roles
			$sql = "SELECT `umeta_id`, `meta_key` FROM `". $table ."` WHERE `meta_key` LIKE( '". $wpdb->prefix ."%' )";
			$exe = mysql_query( $sql );

			# loop through results
			while( $row = mysql_fetch_array( $exe ) ) {

				# umeta_id
				$umeta_id = $row[ 'umeta_id' ];
				$meta_key = $row[ 'meta_key' ];
				$new_key  = $this->new_prefix . substr( $meta_key, strlen( $wpdb->prefix ) );

				# update database
				$sql1 = "UPDATE `". $table ."` SET `meta_key` = '". $new_key ."' WHERE `umeta_id` = '". $umeta_id ."'";
				$exe1 = mysql_query( $sql1 );

				# if the results are set
				if( mysql_affected_rows() > 0 ) {

					# return array
					$return[] = array(
						'type'		=>	'updated',
						'message'	=>	'Options: '. $meta_key .' has been updated successfully'
					);

				# otherwise
				} else {

					# return array
					$return[] = array(
						'type'		=>	'error',
						'message'	=>	'Options: '. $meta_key .' WAS NOT UPDATED IN '. $table
					);

				# close if( mysql_affected_rows() > 0 ) {
				}

			# close while( $row = mysql_fetch_array( $exe ) ) {
			}

			# return output array
			return $return;

		# close function update_options() {
		}

	# close class TablePrefix {
	}

	# start new class
	$TablePrefix = new TablePrefix();
?>