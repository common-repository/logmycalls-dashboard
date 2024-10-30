<?php
/*
Plugin Name: LogMyCalls Dashboard
Plugin URI: http://aveight.com
Description: Display LogMyCalls stats on your WP Dashboard
Version: 1.0
Author: Bryan Purcell, avEIGHT
Author URI: http://aveight.com
License: GPLv2
Copyright 2013 avEIGHT (email : bryan@aveight.com)
*/

require_once('lib/lmcconnector.class.php');
require_once('lib/lmcmetric.class.php');
require_once('lib/lmccalldata.class.php');
require_once('lib/lmccallrecord.class.php');
require_once('lib/lmcerror.class.php');

define("LMC_TRANSIENT_TIMEOUT", 180); //3 minute html caching default
define("LMC_SECONDS_IN_DAY", 86450); //3 minute html caching default
define("LMC_RECORD_LIMIT", 100); //3 minute html caching default
define("LMC_DEBUG", false); //3 minute html caching default

LogMyCalls_Integration::logmycalls_include_from('lib/metrics'); //Include custom metrics

/*
*	class LogMyCalls_Integration
* Main Plugin Class, handles wordpress specific stuff, like settings, html caching (for
* rapid dashboard reloads, and API validation on the settings page using the LogMyCallsApi Class
* 
*/

class LogMyCalls_Integration {
	private $api;
	private $api_test_key;
	private $api_test_secret;
	private $use_cache;
	private $errors;
	
	public function __construct(){
		
		$this->use_cache = true;
		$this->api = new LmcConnector();
		$this->errors = array();
		//Todo: Create seperate settings page.
		
		$this->supported_metrics = array(
			"LmcTotalCalls",
			"LmcTotalCallsAnswered", 
			"LmcTotalCallsUnAnswered",
			"LmcLongestCall", 
			"LmcTotalCallDuration",
			"LmcAverageCallDuration"
		);
		
		add_action( 'admin_init',array( &$this,  'logmycalls_settings_init'));	
		add_action('admin_menu', array( &$this, 'logmycalls_admin_add_menu_item'));
		add_action('wp_dashboard_setup', array($this, "logmycalls_add_dashboard_widgets") );
		add_action('woocommerce_update_option', array($this, "logmycalls_force_subscription_refresh"));
		add_action('admin_head-index.php', array($this,'logmycalls_css_includes')); //Add custom css for table.
		register_uninstall_hook( __FILE__,  'logmycarts_uninstall'  );

	}
	
	

	function logmycalls_css_includes() {
		wp_enqueue_style( 'logmycalls-integration-css', plugins_url() . '/logmycalls-dashboard/assets/css/style.css');
	}

	function logmycalls_admin_add_menu_item() {
		add_options_page('LogMyCalls Settings', 'LogMyCalls Settings', 'manage_options', 'logmycalls-settings', array( &$this, 'logmycalls_options_page') );
	}

	/*
	 * Markup for LogMyCalls settings page.
	 */

	function logmycalls_options_page() {
		?>
		
		<div>
		<h2>LogMyCalls Dashboard Settings</h2>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_donations">
			<input type="hidden" name="business" value="admin@aveight.com">
			<input type="hidden" name="lc" value="US">
			<input type="hidden" name="item_name" value="avEIGHT">
			<input type="hidden" name="no_note" value="0">
			<input type="hidden" name="currency_code" value="USD">
			<input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_LG.gif:NonHostedGuest">
			<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
		</form>

		<p>Developed by <a href='http://www.aveight.com/'>avEIGHT</a>. Donate to help support this plugin :)</p>
		<br />
		<form action="options.php" method="post">
	
		<?php

		settings_fields( 'logmycalls-settings-group' );
		do_settings_sections( 'logmycalls-settings' );

		echo '<input name="Submit" type="submit" value="Save Changes" />';
		echo '</form></div>';

	}

	/**
	*  Add API Key and API secret fields Settings
	*/

	function logmycalls_settings_init()
	{
		register_setting( 'logmycalls-settings-group', 'logmycalls-settings', array( &$this, 'logmycalls_settings_validate' ));
		add_settings_section('logmycalls-settings-section-one', 'API Connection Settings', array( &$this, 'logmycalls_settings_text_general'), 'logmycalls-settings');
		add_settings_section('logmycalls-settings-section-metrics', 'Metrics Display', array( &$this, 'logmycalls_settings_text_metrics'), 'logmycalls-settings');
		
		add_settings_field('metrics_today', 'Lifetime Metrics', array( &$this, 'logmycalls_settings_lifetime_metrics'), 'logmycalls-settings', 'logmycalls-settings-section-metrics');
		add_settings_field('metrics_lifetime', 'Today Metrics', array( &$this, 'logmycalls_settings_today_metrics'), 'logmycalls-settings', 'logmycalls-settings-section-metrics');

		add_settings_field('api_key', 'API Key', array( &$this, 'logmycalls_settings_api_key'), 'logmycalls-settings', 'logmycalls-settings-section-one');
		add_settings_field('api_secret', 'API Secret', array( &$this, 'logmycalls_settings_api_secret'), 'logmycalls-settings', 'logmycalls-settings-section-one');
	}

	function logmycalls_settings_text_general() {
		echo '<p>General settings for WP-LogMyCalls</p>';
	}

	function logmycalls_settings_text_metrics() {
		echo '<p>Choose metrics to show on the WP Dashboard.</p>';
	}

	function logmycalls_settings_text() {
		echo '<p>LogMyCalls API Credentials</p>';
	}
	
	function logmycalls_settings_api_secret() {
		$options = get_option('logmycalls-settings');
		echo "<input id='api_secret' name='logmycalls-settings[api_secret]' size='40' type='text' value='{$options['api_secret']}' />";
	}

	function logmycalls_settings_api_key() {
		$options = get_option('logmycalls-settings');
		echo "<input id='api_key' name='logmycalls-settings[api_key]' size='40' type='text' value='{$options['api_key']}' />";
	}

	function logmycalls_settings_lifetime_metrics() {

		$options= get_option('logmycalls-settings');
		
		if(!isset($options['metricslifetime']))
			$options['metricslifetime'] = array();
		?>
		<tr><th scope="row"><?php _e( 'Metrics to show on the "Lifetime" section of the dashboard.' ); ?></th>
			<td>
				<select multiple="multiple" class="lcmmetrics" size="6" name="logmycalls-settings[metricslifetime][]">
					<?php
 
						foreach ( $this->supported_metrics as $metric_name ) {
							$metric_object = new $metric_name;
							$label = $metric_object->label;
							if ( in_array( $metric_name, $options['metricslifetime'] ))
								echo "\n\t<option selected='selected' value='" . esc_attr( $metric_name ) . "'>$label</option>";
							else
								echo "\n\t<option value='" . esc_attr( $metric_name ) . "'>$label</option>";
						}                             
					?>
				</select>
				<!--<label class="description" for="my_theme_options[selectmetrics]"><?php _e( 'Sample select input' ); ?></label>-->
			</td>
		</tr>

<?php }

	function logmycalls_settings_today_metrics() {
		$options= get_option('logmycalls-settings');
		if(!isset($options['metricstoday']))
			$options['metricstoday'] = array();
		?>
		<tr><th scope="row"><?php _e( 'Metrics to show on the "Today" section of the dashboard.' ); ?></th>
			<td>
				<select multiple="multiple" class="lcmmetrics" size="6" name="logmycalls-settings[metricstoday][]">
					<?php
 
						foreach ( $this->supported_metrics as $metric_name ) {
							$metric_object = new $metric_name;
							$label = $metric_object->label;
							if ( in_array( $metric_name, $options['metricstoday'] ))
								echo "\n\t<option selected='selected' value='" . esc_attr( $metric_name ) . "'>$label</option>";
							else
								echo "\n\t<option value='" . esc_attr( $metric_name ) . "'>$label</option>";
						}                             
					?>
				</select>
				<!--<label class="description" for="my_theme_options[selectmetrics]"><?php _e( 'Sample select input' ); ?></label>-->
			</td>
		</tr>
<?php }
	
	/*
	* Validate LogMyCalls connection credentials with the Service, display warning if not valid.
	*/	
	
	function logmycalls_settings_validate($input) {

		if($input['api_key'] != '' && isset($input['api_key'])){
			$this->api_test_key = $input['api_key'];
		}
		if($input['api_secret']!= '' && isset($input['api_secret'])){
			$this->api_test_secret = $input['api_secret'];
		}

		if($this->api_test_secret == '' && $this->api_test_key == '' ):
			add_settings_error( 'logmycalls_text_string_key', 'texterror', 'Please enter an API Key and an API Secret below to use this service.', 'error' );
		else:
			if ( LmcConnector::validate_credentials($input['api_key'],$input['api_secret']))
				add_settings_error( 'logmycalls_text_string_key', 'texterror', 'Invalid API connector details. Please check your API Key and API Secret below.', 'error' );
		endif;
	
		//Force reload
		$this->logmycalls_force_subscription_refresh();
	
		return $input;
	}

	/**
	*  Force refresh for subscription info if the user changes the api settings in the settings section.
	*/
	
	function logmycalls_force_subscription_refresh()
	{
			//Force subscription reload
			update_option('logmycalls_force_reload','yes');
			update_option('_transient_logmycalls_dashboard_widget_html_cache','');
			update_option('logmycalls_historical','');
	}

	/**
	* Now Add a Dashboard Widget with the stats from the logmycallsinteg_get_stats function
	*/

	function logmycallsinteg_dashboard_widget() {	
		//Check the transient to see if we can load this from cache Or not.
		$error = false;
		$identifier = 'dashboard_widget';
		$call_data = new LmcCallData(new LmcConnector());
		
		//Try to load call data, add error on failure. But only load if there's no transient cache entry available.
		//A bit of a hack, would probably like to seperate template from model access php - maybe different folders?
		
		if(!$this->has_template_transient($identifier) || !$this->use_cache): //Check to see whether we don't have transient for cache
			if(!$call_data->load()):
				$this->add_error("Could not connect to LogMyCalls service. Please <a href='".get_admin_url()."options-general.php?page=logmycalls-settings'>check your API credentials</a> and try again.");
				//Connection problem, probably due to invalid credentials.
			else:

			$metrics_today = array();
			$metrics_lifetime = array();
			$options = get_option("logmycalls-settings");
			if(isset($options['metricstoday']))
				$metrics_today_options = $options['metricstoday'];
			if(isset($options['metricslifetime']))
				$metrics_lifetime_options = $options['metricslifetime'];

			foreach($this->supported_metrics as $metric):
			
				if(isset($metrics_today_options) && $metrics_today_options != false){
					if ( in_array( $metric, $metrics_today_options ))
						$metrics_today[] = new $metric;
				}

				if(isset($metrics_lifetime_options) && $metrics_lifetime_options != false){
				if ( in_array( $metric, $metrics_lifetime_options ))
					$metrics_lifetime[] = new $metric;
				}
			endforeach;
			
			//Fill in metrics for today...
			$call_data->get_metrics($metrics_today,time() - LMC_SECONDS_IN_DAY, time());
			
			//Fill in metrics for lifetime...
			$call_data->get_metrics($metrics_lifetime);

			//arguments to pass to the template
		
			$args = array(		
				'today_metrics'=> $metrics_today,
				'lifetime_metrics'=> $metrics_lifetime,
			);
			endif;
		else:
			$args = array();
		endif;

		if(!$this->has_errors()):
			echo $this->get_lmc_template($identifier, $args);
		else:
		?>
		<div class='lmcerror'>	
			<?php echo $this->print_errors(); ?>
		</div>
		<?php
		endif;
		//Load template 'dashboard_widget.php' in templates/
	
	}
	

	public function logmycalls_add_dashboard_widgets() {
		wp_add_dashboard_widget('logmycallsinteg_dashboard_widget', 'LogMyCalls Dashboard', array($this, 'logmycallsinteg_dashboard_widget'));   
	} 

	/*
	 * Get template in templates folder, and load the passed arguments into the template.
	 */
	private function get_lmc_template($identifier, $args = false){
		if ( $args && is_array($args) )
		extract( $args ); //Add the arguments passed over to the environment

		$html_transient = $this->get_template_transient($identifier);
	
		if(!$html_transient || !$this->use_cache):

			ob_start(); // pull in the dashboard template from 'templates/'
			require('templates/'.$identifier.'.php');
			$output = ob_get_clean(); //ENd Template capture.
	
			$this->set_template_transient($identifier,$output);
		else:
			//Load from transient
			$output = $html_transient;
		endif;
	
		return $output;
	}
	
	/*
 	 * Use this function to include all custom metrics in lib/metrics
  	 */

	function logmycalls_include_from($dir = ''){
 
		foreach (scandir(dirname(__FILE__ ) . '/'. $dir) as $filename) {
			$path = dirname(__FILE__) . '/'  .$dir . '/'. $filename;
			if (is_file($path)) {
				require $path;
			}
		}
	}	
	
	/* Return true if errors exist in $this->errors */
	
	function has_errors() {
		if(sizeof($this->errors) > 0)
			return true;
		else
			return false;
	}
	
	/* Add an error */
	
	function add_error($error_text, $error_code = '')
	{
		$this->errors[] = new LmcError($error_text, $error_code);
	}
	
	/* Print available errors to standard out */
	
	function print_errors($echo = true)
	{
		foreach($this->errors as $error):
			if($echo)
				echo $error->get_error_message();
		endforeach;
	}
	
	function get_template_transient($identifier)
	{
		return get_transient("logmycalls_" . $identifier. "_html_cache");
	}
	
	function has_template_transient($identifier)
	{
		$trans =  get_transient("logmycalls_" . $identifier. "_html_cache");
		
		if(!$trans)
			return false;
		else
			return true;
	}
	
	function set_template_transient($identifier, $val)
	{
		return set_transient("logmycalls_" . $identifier. "_html_cache",$val, LMC_TRANSIENT_TIMEOUT);
	}
	
} // END CLASS

$LogMyCalls_Integration = new LogMyCalls_Integration();

function logmycarts_uninstall()
	{
		/* delete all options */
		delete_option('logmycalls-settings');
		delete_option('logmycalls_historical');
		delete_option('logmycalls_last_updated');
		delete_option('logmycalls_force_reload');

	}