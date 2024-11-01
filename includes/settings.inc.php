<?php
	# security addition - Only load if super admin (multi-site) or regular admin (non mulit-site)
	if( current_user_can( 'update_core' ) ) {

		# global
		global $wpdb, $TablePrefix;

		# if action is found
		if( isset( $_POST[ 'Submit' ] ) && $_POST[ 'Submit' ] == 'Save Changes' ) {

			# prefix
			$prefix = $TablePrefix->validate_string( esc_attr( $_POST[ 'table-prefix' ] ) );

			# if the string is valid
			if( $prefix != false ) {

				# set new prefix
				$TablePrefix->new_prefix = $prefix;

				# process upgrade
				$process = $TablePrefix->Process_Change();

			# otherwise
			} else {

				# set error message
				$process = array(
					"type"		=>	"error",
					"message"	=>	"Sorry, you can only have \"Numbers, Letters &amp; Underscores\" in your table prefix",
				);

			# close if( $prefix != false ) {
			}

		# if the form isn't submitted
		} else {

			# make sure we can alter the db
			if( $TablePrefix->validate_mysql_permissions() == false ) {

				# set error message
				$process[ 'mysql' ][] = array(
					"type"		=>	"error",
					"message"	=>	"Sorry, you don't have the correct MySQL (database) permissions to alter your tables. You Must have 'Alter', 'Drop' &amp; 'Create' permissions",
				);

			# otherwise
			} else {

				# set error message
				$process[ 'mysql' ][] = array(
					"type"		=>	"updated",
					"message"	=>	"Good News! You have the correct MySQL (database) permissions to alter your tables. You have 'Alter', 'Drop' &amp; 'Create' permissions :)",
				);

			# close if( $TablePrefix->validate_mysql_permissions() == false ) {
			}

			# make sure we can alter the db
			if( $TablePrefix->validate_file_write_permissions() == false ) {

				# set error message
				$process[ 'file-write' ][] = array(
					"type"		=>	"error",
					"message"	=>	"Sorry, you don't have the correct permissions to write to your wp-config.php file.",
				);

			# otherwise
			} else {

				# set error message
				$process[ 'mysql' ][] = array(
					"type"		=>	"updated",
					"message"	=>	"Good News! You have the correct permissions to write to your wp-config.php file. :)",
				);

			# close if( $TablePrefix->validate_file_write_permissions() == false ) {
			}

		# close if( isset( $_POST[ 'Submit' ] ) && $_POST[ 'Submit' ] == 'Save Changes' ) {
		}
?>

<style type="text/css">
    div.wrap div#message { padding:10px; }
</style>

<div class="wrap">
  <form id="wp-change-table-prefix" action="options-general.php?page=change-table-prefix" method="post">
  <h2><?php echo __( 'Change Table Prefix', 'menu-change-table-prefix' ); ?></h2>
<?php
		settings_fields( 'wp-change-table-prefix' );
		do_settings_sections( 'wp-change-table-prefix' );

		# if process is set
		if( isset( $process ) ) {

			# loop through process array
			foreach( $process as $var => $val ) {

				# don't need to process the status
				if( $var != 'status' && is_array( $val ) ) {

					# loop through messages
					foreach( $val as $id => $array ) {

						# print message
						echo "<div id=\"message\" class=\"". $array[ 'type' ] ."\">". $array[ 'message' ] ."</div>";

					# close foreach( $val as $id => $array ) {
					}

				# close if( $var != 'status' && is_array( $val ) ) {
				}

			# close foreach( $process as $item ) {
			}

		# close if( isset( $message ) ) {
		}
?>
    <div class="metabox-holder">
      <!-- // TODO Move style in css -->
      <div class='postbox-container' style='width: 99.5%'>
        <div id="" class="meta-box-sortables" >

          <div  class="postbox " >
            <div class="handlediv" title=""><br />
            </div>
            <h3 class='hndle'><span>Table Prefix</span></h3>
            <div class="inside">
              <table class="form-table">
                <tr>
                  <td colspan="2"><p><strong>It is really important that you backup your mysql database, and save a copy of your wp-config.php file in case you have to restore those backups.</strong></p>
                  <p><strong>wp-change-table-prefix will first make sure you have proper mysql permissions to change table names, update table fields and write access to your wp-config.php file before any action is taken.</strong></p></td>
                </tr>
                <tr>
                  <td width="175"><span><strong>Enter Table Prefix</strong>:</span></td>
                  <td><input type="text" name="table-prefix" id="table-prefix" value="<? echo $TablePrefix->new_prefix != "" ? $TablePrefix->new_prefix : $wpdb->prefix; ?>" /></td>
                </tr>
              </table>
            </div>
            <!-- . inside -->
          </div>
          <!-- .postbox -->



        </div>
        <!-- .metabox-sortables -->
      </div>
      <!-- .postbox-container -->
    </div>
    <!-- .metabox-holder -->

    <!-- form button controls -->
    <div id="form-buttons"><input type="submit" name="Submit" value="Save Changes" /></div>

  </form>
</div>
<?php
	# close if( current_user_can( 'update_core' ) ) {
	}
?>