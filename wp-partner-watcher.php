<?php

/*
  Plugin Name: WP Partner Watcher
  Plugin URI: http://webweb.ca/site/products/wp-partner-watcher/
  Description: WP Partner Watcher allows you to monitor the site(s) of your link partners for certain keywords. If your link partner starts linking to illegal stuff you want to be the first to know.
  Tags: wordpress,link,links,link exchange,link exchange manager,backlink,backlinks,ads,affiliate, affiliate marketing,marketing,affiliate plugin, affiliate tool, affiliates, online sale, partner, referral, referral links, referrer
  Version: 1.0.0
  Author: Svetoslav Marinov (Slavi)
  Author URI: http://WebWeb.ca
  License: GPL v2
 */

/*
  Copyright 2011-2020 Svetoslav Marinov (slavi@slavi.biz)

  This program ais free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; version 2 of the License.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// we can be called from the test script
if (empty($_ENV['WEBWEB_WP_PARTNER_WATCHER_TEST'])) {
    // Make sure we don't expose any info if called directly
    if (!function_exists('add_action')) {
        echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
        exit;
    }
    
	$webweb_wp_partner_watcher_obj = WebWeb_WP_PartnerWatcher::get_instance();
	
    add_action('init', array($webweb_wp_partner_watcher_obj, 'init'));

    register_activation_hook(__FILE__, array($webweb_wp_partner_watcher_obj, 'on_activate'));
    register_deactivation_hook(__FILE__, array($webweb_wp_partner_watcher_obj, 'on_deactivate'));
    register_uninstall_hook(__FILE__, array($webweb_wp_partner_watcher_obj, 'on_uninstall'));
}

class WebWeb_WP_PartnerWatcher {
    private $log = 1;
    private static $instance = null; // singleton
    private $plugin_url = null; // filled in later
    private $plugin_settings_key = null; // filled in later
    private $plugin_partners_key = null; // filled in later
    private $plugin_dir_name = null; // filled in later
    private $plugin_data_dir = null; // plugin data directory. for reports and data storing. filled in later
    private $plugin_keywords_file = null; // filled in later
    private $plugin_name = 'WP Partner Watcher'; //
    private $plugin_id_str = 'wp_partner_watcher'; //
    private $plugin_support_email = 'help@WebWeb.ca'; //
    private $plugin_support_link = 'http://miniads.ca/widgets/contact/profile/wp-partner-watcher?font=Arial,Sans-Serif&font_size=12&height=200&width=500&description=Please enter your enquiry below.'; //
    private $plugin_admin_url_prefix = null; // filled in later
    private $plugin_home_page = 'http://webweb.ca/site/products/wp-partner-watcher/';
    private $plugin_tinymce_name = 'wwwpuiwpsppsc'; // if you change it update the tinymce/editor.js;
    private $plugin_cron_hook = __CLASS__;
    private $plugin_cron_freq = 'daily';
    private $plugin_default_opts = array(
        'status' => 0,
        'notification_threshold' => 3,
        'notification_email' => '',
    );

    // can't be instantiated; just using get_instance
    private function __construct() {
        
    }

    /**
     * handles the singleton
     */
    function get_instance() {
		if (is_null(self::$instance)) {
			$cls = __CLASS__;	
			$inst = new $cls;
			
			$site_url = get_settings('siteurl');

			$inst->plugin_dir_name = basename(dirname(__FILE__)); // e.g. wp-command-center; this can change e.g. a 123 can be appended if such folder exist
			$inst->plugin_data_dir = dirname(__FILE__) . '/data';
			$inst->plugin_url = $site_url . '/wp-content/plugins/' . $inst->plugin_dir_name . '/';
			$inst->plugin_settings_key = $inst->plugin_id_str . '_settings';
			$inst->plugin_partners_key = $inst->plugin_id_str . '_partners';
			$inst->plugin_partners_file = $inst->plugin_data_dir . '/partners.php';
			$inst->plugin_keywords_file = $inst->plugin_data_dir . '/global_keywords.php';

			// not sure if this will work here
			// Use when develing to trigger cron sooner e.g. every 3 mins.
			//add_filter('cron_schedules', array($webweb_wp_partner_watcher_obj, 'define_cron_frequencies'));
			//add_filter('cron_schedules', array($inst, 'define_cron_frequencies'));
			//$inst->plugin_cron_freq = $inst->plugin_id_str . '3min';


            $inst->plugin_admin_url_prefix = $site_url . '/wp-admin/admin.php?page=' . $inst->plugin_dir_name;
			$inst->delete_partner_url = $inst->plugin_admin_url_prefix . '/menu.partners.php&do=delete';
			$inst->edit_partner_url = $inst->plugin_admin_url_prefix . '/menu.partners_add.php';

			$inst->delete_keywords_url = $inst->plugin_admin_url_prefix . '/menu.keywords.php&do=delete';
			$inst->edit_keywords_url = $inst->plugin_admin_url_prefix . '/menu.keywords_add.php';

			define('WEBWEB_WP_PARTNER_WATCHER_BASE_DIR', dirname(__FILE__)); // e.g. // htdocs/wordpress/wp-content/plugins/wp-command-center
			define('WEBWEB_WP_PARTNER_WATCHER_DIR_NAME', $inst->plugin_dir_name);

			if ($inst->log) {
				ini_set('log_errors', 1);
				ini_set('error_log', $inst->plugin_data_dir . '/error.log');
			}

			add_action('plugins_loaded', array($inst, 'init'), 100);

            self::$instance = $inst;
        }
		
		return self::$instance;
	}

    public function __clone() {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

    public function __wakeup() {
        trigger_error('Unserializing is not allowed.', E_USER_ERROR);
    }
    
    /**
     * handles the init
     */
    function init() {
        global $wpdb;

        add_action($this->plugin_cron_hook, array($this, 'process_cron'));

        if (is_admin()) {
            // Administration menus
            add_action('admin_menu', array($this, 'administration_menu'));
            //add_action('admin_init', array($this, 'add_buttons'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_notices', array($this, 'notices'));

            wp_register_style($this->plugin_dir_name, $this->plugin_url . 'css/main.css', false, 0.1);
            wp_enqueue_style($this->plugin_dir_name);
        } else {
            // Do nothing on public side
            if (!is_feed()) {
                add_action('wp_head', array($this, 'add_meta_header'));
            }
        }
    }

    /**
     * Handles the plugin activation. Setup cron and set default configs
     */
    function install_cron() {
        $when = mktime(23, 30, 0, date('m'), date('d'), date('Y'));
        $when = time();

        wp_schedule_event($when, $this->plugin_cron_freq, $this->plugin_cron_hook);
    }

    /**
     * Handles the plugin activation. Setup cron and set default configs
     */
    function uninstall_cron() {
        wp_clear_scheduled_hook($this->plugin_cron_hook);
    }

    /**
     * Handles the plugin activation. Setup cron and set default configs
     */
    function process_cron() {
        $opts = $this->get_options();
        $partners = $this->get_partners();
        $keywords = $this->get_keywords(); // could we have separate keywords for each partner ?

        set_time_limit(100 * 60); // 100 mins
        $report_file = $this->plugin_data_dir . '/report-' . date('Y-m-d') . '.txt';
        
        $crawler = new WebWeb_WP_PartnerWatcherCrawler();

        $alerts = 0;
        $report_buffer = "Report\n===============================================================\nStarted at: " . date('r') . "\n\n";

        if (empty($opts['status']) && empty($_ENV['WEBWEB_WP_PARTNER_WATCHER_TEST'])) {
            $report_buffer .= "Error: Plugin is not active. Stopping.\n\n";
            $partners = array();  // forcing not to process links
            // Not active plugin will not trigger alerts
            //$alerts++;
        } elseif (empty($keywords)) {
            $report_buffer .= "Error: No keywords have been entered. Stopping.\n\n";
            $partners = array();  // forcing not to process links
            $alerts++;
        } elseif (empty($partners)) {
            $report_buffer .= "Error: No partners have been entered. Stopping.\n\n";
            $alerts++;
        }

        foreach ($partners as $idx => $rec) {
            $report_buffer .= "URL: {$rec['url']}\n";
            $report_buffer .= "Owner: {$rec['name']}";

            if (!empty($rec['email'])) {
                $report_buffer .= ' ' . $rec['email'];
            }

            $report_buffer .= "\n";

            if ($crawler->fetch($rec['url'])) {
                $buffer = $crawler->get_content();

                $buffer = WebWeb_WP_PartnerWatcherUtil::html2text($buffer);

                // We want to NOT have the keywords e.g. torrents, cracks, crack, serial, serials
                $match_status = WebWeb_WP_PartnerWatcherUtil::match($buffer, $keywords);

                $report_buffer .= "Matches: {$match_status['hits']}\n";

                // false positive if 1 ?
                if ($match_status['hits']) {
                    foreach ($match_status['matches'] as $key => $rec) {
                        $report_buffer .= "{$rec['keyword']}: {$rec['hits']}\n";
                    }

                    if ($match_status['hits'] >= $opts['notification_threshold']) {
                        $alerts++;
                    }
                }
            } else {
                $report_buffer .= "Error: Couldn't fetch URL. Error: " . $crawler->getError() . "\n";
            }

            $report_buffer .= "\n";
        }

        $report_buffer .= "Funished at: " . date('r') . "\n===============================================================\n\n\n";

        WebWeb_WP_PartnerWatcherUtil::write($report_file, $report_buffer, WebWeb_WP_PartnerWatcherUtil::FILE_APPEND);

        if ($alerts && !empty($opts['notification_email'])) {
            $headers = "From: {$_SERVER['HTTP_HOST']} Wordpress <wordpress@{$_SERVER['HTTP_HOST']}>\r\n";
            wp_mail($opts['notification_email'], $this->plugin_name . " Alert: $alerts item(s) require your attention.", $report_buffer, $headers);
        }
    }

    /**
     * defines custom con frequencies.
     * 
     * @param type $schedules
     * @return type
     */
    function define_cron_frequencies($schedules) {
        $schedules[$this->plugin_id_str . '3min'] = array(
            'interval' => 180,
            'display' => __('Once Every 3 mins')
        );

        $schedules[$this->plugin_id_str . '5min'] = array(
            'interval' => 300,
            'display' => __('Once Every 5 mins')
        );

        $schedules[$this->plugin_id_str . 'weekly'] = array(
            'interval' => 604800,
            'display' => __('Once Every Week')
        );

        return $schedules;
    }

    /**
     * checks if WP has installed the hook
     * @return bool
     */
    function is_cron_scheduled() {
        $status = wp_get_schedule($this->plugin_cron_hook);

        return $status !== false;
    }

    /**
     * Handles the plugin activation. Setup cron and set default configs
     */
    function on_activate() {
        $this->install_cron();
    }

    /**
     * Handles the plugin deactivation. Remove cron and set default configs
     */
    function on_deactivate() {
        $opts['status'] = 0;
        $this->set_options($opts);
        $this->uninstall_cron();
    }

    /**
     * Handles the plugin uninstallation. remove cron and set default configs
     */
    function on_uninstall() {
        delete_option($this->plugin_settings_key);
        $this->uninstall_cron();
    }

    /**
     * Allows access to some private vars
     * @param str $var
     */
    public function get($var) {
        if (isset($this->$var) /* && (strpos($var, 'plugin') !== false) */) {
            return $this->$var;
        }
    }

    /**
     * gets current options and return the default ones if not exist
     * @param void
     * @return array
     */
    function get_options() {
        $opts = get_option($this->plugin_settings_key);
        $opts = empty($opts) ? array() : (array) $opts;

        // if we've introduced a new default key/value it'll show up.
        $opts = array_merge($this->plugin_default_opts, $opts);

        if (empty($opts['notification_email'])) {
            $opts['notification_email'] = get_option('admin_email');
        }

        return $opts;
    }

    /**
     * Gets all the existing reports from data dir.
     *
     * @param void
     * @return array
     */
    function get_report_files() {
        $files = glob($this->plugin_data_dir . '/report-*.txt');
        $files = empty($files) ? array() : $files;
        $files = array_map('basename', $files);

        return $files;
    }

    /**
     * Gets all the existing reports from data dir.
     *
     * @param void
     * @return array
     */
    function get_report($file) {
        $buffer = 'Cannot load report file.';

        if (!preg_match('#^report-[-\d]+\.txt$#si', $file)) {
            return $buffer;
        }

        $file = $this->plugin_data_dir . '/' . $file;
                
        if (file_exists($file)) {
            $buffer = WebWeb_WP_PartnerWatcherUtil::read($file);
        }

        return $buffer;
    }

    /**
     * Updates options but it merges them unless $override is set to 1
     * that way we could just update one variable of the settings.
     */
    function set_options($opts = array(), $override = 0) {
        if (!$override) {
            $old_opts = $this->get_options();
            $opts = array_merge($old_opts, $opts);
        }

        update_option($this->plugin_settings_key, $opts);

        return $opts;
    }

    /**
     * This is what the plugin admins will see when they click on the main menu.
     * @var string
     */
    private $plugin_landing_tab = '/menu.dashboard.php';

    /**
     * Adds the settings in the admin menu
     */
    public function administration_menu() {
        // Settings > WP Partner Watcher
        add_options_page(__($this->plugin_name, "WEBWEB_WP_PARTNER_WATCHER"), __($this->plugin_name, "WEBWEB_WP_PARTNER_WATCHER"), 'manage_options', __FILE__, array($this, 'options'));

        add_menu_page(__($this->plugin_name, $this->plugin_dir_name), __($this->plugin_name, $this->plugin_dir_name), 'manage_options', $this->plugin_dir_name . '/menu.dashboard.php', null, $this->plugin_url . '/images/magnifier.png');

        add_submenu_page($this->plugin_dir_name . '/' . $this->plugin_landing_tab, __('Dashboard', $this->plugin_dir_name), __('Dashboard', $this->plugin_dir_name), 'manage_options', $this->plugin_dir_name . '/menu.dashboard.php');

        add_submenu_page($this->plugin_dir_name . '/' . $this->plugin_landing_tab, __('Partners', $this->plugin_dir_name), __('Partners', $this->plugin_dir_name), 'manage_options', $this->plugin_dir_name . '/menu.partners.php');

        add_submenu_page($this->plugin_dir_name . '/' . $this->plugin_landing_tab, __('Add Partner', $this->plugin_dir_name), __('Add Partner', $this->plugin_dir_name), 'manage_options', $this->plugin_dir_name . '/menu.partners_add.php');

        add_submenu_page($this->plugin_dir_name . '/' . $this->plugin_landing_tab, __('Keywords', $this->plugin_dir_name), __('Keywords', $this->plugin_dir_name), 'manage_options', $this->plugin_dir_name . '/menu.keywords.php');

        add_submenu_page($this->plugin_dir_name . '/' . $this->plugin_landing_tab, __('Add Keywords', $this->plugin_dir_name), __('Add Keywords', $this->plugin_dir_name), 'manage_options', $this->plugin_dir_name . '/menu.keywords_add.php');

        add_submenu_page($this->plugin_dir_name . '/' . $this->plugin_landing_tab, __('Reports', $this->plugin_dir_name), __('Reports', $this->plugin_dir_name), 'manage_options', $this->plugin_dir_name . '/menu.reports.php');

        add_submenu_page($this->plugin_dir_name . '/' . $this->plugin_landing_tab, __('Settings', $this->plugin_dir_name), __('Settings', $this->plugin_dir_name), 'manage_options', $this->plugin_dir_name . '/menu.settings.php');

        add_submenu_page($this->plugin_dir_name . '/' . $this->plugin_landing_tab, __('Help', $this->plugin_dir_name), __('Help', $this->plugin_dir_name), 'manage_options', $this->plugin_dir_name . '/menu.support.php');

        add_submenu_page($this->plugin_dir_name . '/' . $this->plugin_landing_tab, __('About', $this->plugin_dir_name), __('About', $this->plugin_dir_name), 'manage_options', $this->plugin_dir_name . '/menu.about.php');

        // when plugins are show add a settings link near my plugin for a quick access to the settings page.
        add_filter('plugin_action_links', array($this, 'add_plugin_settings_link'), 10, 2);
    }

    /**
     * Outputs some options info. No save for now.
     */
    function options() {
		$webweb_wp_partner_watcher_obj = WebWeb_WP_PartnerWatcher::get_instance();
        $opts = get_option('settings');

        include_once(WEBWEB_WP_PARTNER_WATCHER_BASE_DIR . '/menu.settings.php');
    }

    /**
     * Sets the setting variables
     */
    function register_settings() { // whitelist options
        register_setting($this->plugin_dir_name, $this->plugin_settings_key);
    }

    // Add the ? settings link in Plugins page very good
    function add_plugin_settings_link($links, $file) {
        if ($file == plugin_basename(__FILE__)) {
            $settings_link = '<a href="options-general.php?page='
                    . dirname(plugin_basename(__FILE__)) . '/' . basename(__FILE__) . '">' . (__("Settings", "WEBWEB_WP_PARTNER_WATCHER")) . '</a>';
            array_unshift($links, $settings_link);
        }

        return $links;
    }

    function add_meta_header() {
        printf("\n" . '<meta name="generator" content="Powered by ' . $this->plugin_name . ' (' . $this->plugin_home_page . ') " />' . PHP_EOL);
    }

    // kept for future use if necessary

    /**
     * Adds buttons only for RichText mode
     * @return void
     */
    function add_buttons() {
        return; // no need to add tinymce button at this time.
        // Don't bother doing this stuff if the current user lacks permissions
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }

        // Add only in Rich Editor mode
        if (get_user_option('rich_editing') == 'true') {
            // add the button for wp2.5 in a new way
            add_filter("mce_external_plugins", array(&$this, "add_tinymce_plugin"), 5);
            add_filter('mce_buttons', array(&$this, 'register_button'), 5);
        }
    }

    // used to insert button in wordpress 2.5x editor
    function register_button($buttons) {
        array_push($buttons, "separator", $this->plugin_tinymce_name);

        return $buttons;
    }

    // Load the TinyMCE plugin : editor_plugin.js (wp2.5)
    function add_tinymce_plugin($plugin_array) {
        $plugin_array[$this->plugin_tinymce_name] = $this->plugin_url . 'tinymce/editor_plugin.min.js';

        return $plugin_array;
    }

    /**
     * Checks if WP simpple shopping cart is installed.
     */
    function notices() {
        $opts = $this->get_options();

        if (empty($opts['status'])) {	
			$steps = array();
					
			$kwd = $this->get_keywords();
			
			if (empty($kwd)) {
				$steps[] = '[1] Add Keywords';
			} else {
				$steps[] = $this->m('[1] Add Keywords (done)', 1);
			}
			
			$partners = $this->get_partners();
						
			if (empty($partners)) {
				$steps[] = '[2] Add Partners';
			} else {
				$steps[] = $this->m('[2] Add Partners (done)', 1);
			}

			// we're here so it's not enabled
			$steps[] = sprintf('[3] Enable it from <a href="%s">%s &gt; Settings</a>', $this->plugin_admin_url_prefix . '/menu.settings.php', $this->plugin_name);
				
            echo $this->message(sprintf('%s is currently disabled. Please, %s', $this->plugin_name, join(', ', $steps)));
        }

        // makre sure data dir is writeable
        if (!is_writable($this->plugin_data_dir) && @chmod($this->plugin_data_dir, 0777) && !is_writable($this->plugin_data_dir)) {
            echo $this->message($this->plugin_data_dir . ' is not wriable. Please update the permissions to 777 using your favourite FTP client.');
        }
    }

    /**
     * Outputs a message (adds some paragraphs)
     */
    function message($msg, $status = 0) {
        $id = $this->plugin_id_str;
        $cls = empty($status) ? 'error fade' : 'success';

        $str = <<<MSG_EOF
<div id='$id-notice' class='$cls'><p><strong>$msg</strong></p></div>
MSG_EOF;
        return $str;
    }

    /**
     * a simple status message, no formatting except color
     */
    function msg($msg, $status = 0) {
        $id = $this->plugin_id_str;
        $cls = empty($status) ? 'app_error' : 'app_success';

        $str = <<<MSG_EOF
<div id='$id-notice' class='$cls'><strong>$msg</strong></div>
MSG_EOF;
        return $str;
    }
	
    /**
     * a simple status message, no formatting except color, simpler than its brothers
     */
    function m($msg, $status = 0) {
        $cls = empty($status) ? 'app_error' : 'app_success';

        $str = <<<MSG_EOF
<span class='$cls'>$msg</span>
MSG_EOF;
        return $str;
    }

    /**
     * Returns the serialized array of partners and their URLs
     */
    function get_partners() {
        $data = WebWeb_WP_PartnerWatcherUtil::read($this->plugin_partners_file, WebWeb_WP_PartnerWatcherUtil::UNSERIALIZE_DATA);
        $data = empty($data) ? array() : $data;

        return $data;
    }

    /**
     * Updates the partners file
     * @return bool
     */
    function save_partners($data) {
        $st = WebWeb_WP_PartnerWatcherUtil::write($this->plugin_partners_file, $data, WebWeb_WP_PartnerWatcherUtil::SERIALIZE_DATA);

        return $st;
    }

    /**
     * Returns the keywords
     */
    function get_keywords() {
        $data = WebWeb_WP_PartnerWatcherUtil::read($this->plugin_keywords_file, WebWeb_WP_PartnerWatcherUtil::UNSERIALIZE_DATA);
        $data = empty($data) ? array() : $data;

        return $data;
    }

    /**
     * Updates the partners file
     * @return bool
     */
    function save_keywords($data) {
        $st = WebWeb_WP_PartnerWatcherUtil::write($this->plugin_keywords_file, $data, WebWeb_WP_PartnerWatcherUtil::SERIALIZE_DATA);

        return $st;
    }

    /**
     * Adds or updates a partner. unique key is the email
     * 
     * @param array $rec
     * @return bool 1 ok add; 0 error (permissions?)
     */
    function admin_partner($rec = array(), $id = null) {
        $st = 0;

        if (!empty($rec['name']) && !empty($rec['url'])) { // && !empty($rec['email']) 
            $data = $this->get_partners();

            if (!preg_match("@^(?:ht|f)tps?://@si", $rec['url'])) {
                $rec['url'] = "http://" . $rec['url'];
            }

            if (!is_null($id)) {
                $data[$id] = $rec;
            } else {
                $data[] = $rec;
            }

            $st = $this->save_partners($data);
        }

        return $st;
    }

    /**
     * Adds or updates keywords
     * 
     * @param string $keywords_buff
     * @return bool 1 ok add; 0 error (permissions?)
     */
    function admin_keyword($keywords_buff) {
        $st = 0;

        if (!empty($keywords_buff)) {
            // spit by command and by several chars
            $keywords = preg_split('#[\r\n,\t]+#si', $keywords_buff);
            $keywords_processed = array();

            foreach ($keywords as $idx => $kwd) {
                $kwd = trim($kwd); // rm spaces
                $kwd = trim($kwd, ',.!@#$%^&*()_\'" '); // some signs
                				
                // we need just one space in the kwd
                // http://stackoverflow.com/questions/2368539/php-replacing-multiple-spaces-with-a-single-space by ghostdog74
                $kwd = preg_replace("#[[:blank:]]+#", ' ', $kwd);

                if (empty($kwd)) {
                    continue;
                }

                $keywords_processed[] = $kwd;
            }

            if (!empty($keywords_processed)) {
                // we will  merge keywords so not to bother to edit them.
                $data = $this->get_keywords();
                $data = array_merge($data, $keywords_processed);
                $data = array_unique($data);

                $st = $this->save_keywords($data);
            }
        }

        return $st;
    }

    /**
     * deletes a partner by array index
     *
     * @param int $idx
     * @return bool 1 ok; 0 error (when saving)
     */
    function delete_partner($idx = -1) {
        $data = $this->get_partners();
        unset($data[$idx]);
        $st = $this->save_partners($data);
        return $st;
    }

    /**
     * deletes a partner by array index
     *
     * @param int $idx
     * @return bool 1 ok; 0 error (when saving)
     */
    function delete_keyword($idx = -1) {
        $data = $this->get_keywords();
        unset($data[$idx]);
        $st = $this->save_keywords($data);
        return $st;
    }

}

class WebWeb_WP_PartnerWatcherUtil {
    // options for read/write methods.
    const FILE_APPEND = 1;
    const UNSERIALIZE_DATA = 2;
    const SERIALIZE_DATA = 3;

    /**
     * Gets the content from the body, removes the comments, scripts
     * Credits: http://php.net/manual/en/function.strip-tags.phpm /  http://networking.ringofsaturn.com/Web/removetags.php
     * @param string $buffer
     * @string string $buffer
     */
    public static function html2text($buffer = '') {
        // we care only about the body so it must be beautiful.
        $buffer = preg_replace('#.*<body[^>]*>(.*?)</body>.*#si', '\\1', $buffer);
        $buffer = preg_replace('#<script[^>]*>.*?</script>#si', '', $buffer);
        $buffer = preg_replace('#<style[^>]*>.*?</style>#siU', '', $buffer);
//        $buffer = preg_replace('@<style[^>]*>.*?</style>@siU', '', $buffer); // Strip style tags properly
        $buffer = preg_replace('#<[a-zA-Z\/][^>]*>#si', ' ', $buffer); // Strip out HTML tags  OR '@<[\/\!]*?[^<>]*\>@si',
        $buffer = preg_replace('@<![\s\S]*?--[ \t\n\r]*>@', '', $buffer); // Strip multi-line comments including CDATA
        $buffer = preg_replace('#[\t\ ]+#si', ' ', $buffer); // replace just one space
        $buffer = preg_replace('#[\n\r]+#si', "\n", $buffer); // replace just one space
        //$buffer = preg_replace('#(\s)+#si', '\\1', $buffer); // replace just one space
        $buffer = preg_replace('#^\s*|\s*$#si', '', $buffer);

        return $buffer;
    }

    /**
     * Gets the content from the body, removes the comments, scripts
     *
     * @param string $buffer
     * @param array $keywords
     * @return array - for now it returns hits; there could be some more complicated results in the future so it's better as an array
     */
    public static function match($buffer = '', $keywords = array()) {
        $status_arr['hits'] = 0;

        foreach ($keywords as $keyword) {
            $cnt = preg_match('#\b' . preg_quote($keyword) . '\b#si', $buffer);

            if ($cnt) {
                $status_arr['hits']++; // total hits
                $status_arr['matches'][$keyword] = array('keyword' => $keyword, 'hits' => $cnt,); // kwd hits
            }
        }

        return $status_arr;
    }

    /**
     * @desc write function using flock
     *
     * @param string $vars
     * @param string $buffer
     * @param int $append
     * @return bool
     */
    public static function write($file, $buffer = '', $option = null) {
        $buff = false;
        $tries = 0;
        $handle = '';

        $write_mod = 'wb';

        if ($option == self::SERIALIZE_DATA) {
            $buffer = serialize($buffer);
        } elseif ($option == self::FILE_APPEND) {
            $write_mod = 'ab';
        }

        if (($handle = @fopen($file, $write_mod))
                && flock($handle, LOCK_EX)) {
            // lock obtained
            if (fwrite($handle, $buffer) !== false) {
                @fclose($handle);
                return true;
            }
        }

        return false;
    }

    /**
     * @desc read function using flock
     *
     * @param string $vars
     * @param string $buffer
     * @param int $option whether to unserialize the data
     * @return mixed : string/data struct
     */
    public static function read($file, $option = null) {
        $buff = false;
        $read_mod = "rb";
        $tries = 0;
        $handle = false;

        if (($handle = @fopen($file, $read_mod))
                && (flock($handle, LOCK_EX))) { //  | LOCK_NB - let's block; we want everything saved
            $buff = @fread($handle, filesize($file));
            @fclose($handle);
        }

        if ($option == self::UNSERIALIZE_DATA) {
            $buff = unserialize($buff);
        }

        return $buff;
    }

    /**
     *
     * Appends a parameter to an url; uses '?' or '&'
     * It's the reverse of parse_str().
     *
     * @param string $url
     * @param array $params
     * @return string
     */
    public static function add_url_params($url, $params = array()) {
        $str = '';

        $params = (array) $params;

        if (empty($params)) {
            return $url;
        }

        $query_start = (strpos($url, '?') === false) ? '?' : '&';

        foreach ($params as $key => $value) {
            $str .= ( strlen($str) < 1) ? $query_start : '&';
            $str .= rawurlencode($key) . '=' . rawurlencode($value);
        }

        $str = $url . $str;

        return $str;
    }

    // generates HTML select
    public static function html_select($name = '', $options = array(), $sel = null, $attr = '') {
        $html = "\n" . '<select name="' . $name . '" ' . $attr . '>' . "\n";

        foreach ($options as $key => $label) {
            $selected = $sel == $key ? ' selected="selected"' : '';
            $html .= "\t<option value='$key' $selected>$label</option>\n";
        }

        $html .= '</select>';
        $html .= "\n";

        return $html;
    }

    // generates status msg
    public static function msg($msg = '', $status = 0) {
        $cls = empty($status) ? 'error' : 'success';
        $cls = $status == 2 ? 'notice' : $cls;

        $msg = "<p class='status_wrapper'><div class=\"status_msg $cls\">$msg</div></p>";

        return $msg;
    }

}

class WebWeb_WP_PartnerWatcherCrawler {

    private $user_agent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0) Gecko/20100101 Firefox/6.0";
    private $error = null;
    private $buffer = null;

    function __construct() {
        ini_set('user_agent', $this->user_agent);
    }

    /**
     * Error(s) from the last request
     * 
     * @return string
     */
    function getError() {
        return $this->error;
    }

    // checks if buffer is gzip encoded
    function is_gziped($buffer) {
        return (strcmp(substr($buffer, 0, 8), "\x1f\x8b\x08\x00\x00\x00\x00\x00") === 0) ? true : false;
    }

    /*
      henryk at ploetzli dot ch
      15-Feb-2002 04:28
      http://php.online.bg/manual/hu/function.gzencode.php
     */

    function gzdecode($string) {
        if (!function_exists('gzinflate')) {
            return false;
        }

        $string = substr($string, 10);
        return gzinflate($string);
    }

    /**
     * Fetches a url and saves the data into an instance variable. The returned status is whether the request was successful.
     *
     * @param string $url
     * @return bool
     */
    function fetch($url) {
        $ok = 0;
        $buffer = '';

        $url = trim($url);

        if (!preg_match("@^(?:ht|f)tps?://@si", $url)) {
            $url = "http://" . $url;
        }

        // try #1 cURL
        // http://fr.php.net/manual/en/function.fopen.php
        if (empty($ok)) {
            if (function_exists("curl_init") && extension_loaded('curl')) {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Encoding: gzip'));
                curl_setopt($ch, CURLOPT_TIMEOUT, 45);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 5); /* Max redirection to follow */
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

                /* curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ; // in the future pwd protected dirs
                  curl_setopt($ch, CURLOPT_USERPWD, "username:password"); */ //  http://php.net/manual/en/function.curl-setopt.php

                $string = curl_exec($ch);
                $curl_res = curl_error($ch);

                curl_close($ch);

                if (empty($curl_res) && strlen($string)) {
                    if ($this->is_gziped($string)) {
                        $string = $this->gzdecode($string);
                    }

                    $this->buffer = $string;

                    return 1;
                } else {
                    $this->error = $curl_res;
                    return 0;
                }
            }
        } // empty ok*/
        // try #2 file_get_contents
        if (empty($ok)) {
            $buffer = @file_get_contents($url);

            if (!empty($buffer)) {
                $this->buffer = $buffer;
                return 1;
            }
        }

        // try #3 fopen
        if (empty($ok) && preg_match("@1|on@si", ini_get("allow_url_fopen"))) {
            $fp = @fopen($url, "r");

            if (!empty($fp)) {
                $in = '';

                while (!feof($fp)) {
                    $in .= fgets($fp, 8192);
                }

                @fclose($fp);
                $buffer = $in;

                if (!empty($buffer)) {
                    $this->buffer = $buffer;
                    return 1;
                }
            }
        }

        return 0;
    }

    function get_content() {
        return $this->buffer;
    }

}