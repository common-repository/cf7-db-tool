<?php
namespace CF7DBTOOL;

class Plugin
{
	/**
	 * config class instance
	 * @var object
	 */
	private $config;
	/**
	 * forms class instance
	 * @var object
	 */
	private $forms;
	/**
	 * entries class instance
	 * @var object
	 */
	private $formEntries;
	/**
	 * method __construct()
	 */

    private $report;
    /**
     * method __construct()
     */
   // private $integration;
    /**
     * method __construct()
     */
    private $bulkMail;
    /**
     * method __construct()
     */
	public function __construct()
	{
		/**
		 * call initialize plugin
		 */
		$this->init();
	}

	/**
	 * initialize plugin
	 * @return void
	 */
	public function init()
	{
		require_once 'Config.php';
		require_once 'Forms.php';
		require_once 'FormEntries.php';
		require_once 'ListForms.php';
		require_once 'ListEntries.php';
		require_once 'CsvExport.php';
		require_once 'Mail.php';
		require_once 'Report.php';
		require_once'BulkMail.php';
		$this->config = new Config();
		add_action('admin_menu', [$this, 'addOptionsPage'], 5);
		add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
		add_action('admin_enqueue_scripts', [$this, 'loadScriptSpecificPage']);
		add_action('admin_notices', [$this, 'adminNotice']);
		$this->forms = new Forms($this->config);
		$this->report = new Report($this->config);
		$this->formEntries = new FormEntries($this->config);
        add_action('wp_ajax_bulkMailAjaxDataAction', [$this,'bulkMailAjaxDataAction']);

	}


	/**
	 * add plugin option page in admin menu / Register field for mail provider
	 * @return void
	 */
	public function addOptionsPage()
	{
		add_menu_page(
			'CF7 DB Tool',
			'CF7 DB Tool',
			'manage_options',
			'cf7_dbt',
			[$this, 'optionsPageContent'],
			'dashicons-book',
			50
		);

		/**
		 * Report Submenu for cf7
		 * */
        add_submenu_page(
                'cf7_dbt',
                'Report',
                'Report',
                'manage_options',
                'cf7-db-report',
                [$this, 'cf7Report']
        );
        /**
         * Submenu for bulkmail cf7
         * */
        add_submenu_page(
            'cf7_dbt',
            'Bulk Mail',
            'Bulk Mail',
            'manage_options',
            'cf7-db-bulkmail',
            [$this, 'cf7BulkMail']
        );
		/**
		 * Submenu for mail settings
		 * */
		add_submenu_page( 'cf7_dbt', 'Mail Settings', 'Mail settings', 'manage_options', 'Mail-Settings',[$this, 'cf7BulkMailSettings'] );

		/**
		 * Register filed function call for mail provider
		 * */
		add_action( 'admin_init', [$this, 'custom_settings_fields'] );

	}



	/**
	 * callback for option page content
	 * @return void
	 */
	public function optionsPageContent()
	{
		// check is cf7 is exists
		if ( ! class_exists('WPCF7_ContactForm') ) {
			$this->cf7NotFound();
			return;
		}
		// list form entries
		if (isset($_GET['form_id'])) {
			$this->formEntries->allEntries($_GET['form_id']);
			return;
		}
		// show entry details
		if (isset($_GET['entry_id'])) {
			$this->formEntries->renderDetails($_GET['entry_id']);
			return;
		}
		// list forms
		$this->forms->allForms();
	}


    /**
     * Callback for Report class
     * @return void
     */
    public function cf7Report(){
        $this->report->renderReport();
        $this->report->cf7AllEntry();
    }


    /**
     * Callback for BulkMail class
     * @return void
     */
    public function cf7BulkMail(){
        $this->bulkMail = new BulkMail();
    }

    /**
     * Callback for BulkMail File include
     * @return void
     */
    public function cf7BulkMailSettings(){
	    require_once 'setting-options-fileds/mail-fields-support.php';
    }


	/**
	 * Register field for api & url for MailGun, sendgrid
	 */

	function custom_settings_fields() {
		/**
		 * Mail Provider registered fileds
		 * */
		register_setting( 'mail-provider-support-group', 'select_mailer' );
		register_setting('mail-provider-support-group', 'active_mailer');
		// Mailgun api url & Api Key
		register_setting('mail-provider-support-group', 'mailgun-url',[$this, 'handle_mailgun_url_sanitization']);
		register_setting('mail-provider-support-group', 'mailgun-key', [$this, 'handle_mailgun_text_sanitization']);
		// sendgrid credentials
		register_setting('mail-provider-support-group', 'sendgrid-user', [$this, 'handle_sendgrid_user_sanitization']);
		register_setting('mail-provider-support-group', 'sendgrid-password', [$this, 'handle_sendgrid_password_sanitization']);

		/**
		 * Mail Provider Sections
		 * */
		add_settings_section( 'section_mail_setting', 'Active & Choose Mail Provider', [$this, 'active_mail_provider__section_cb'], 'mail-support-options' );
		add_settings_section( 'mailgun_mail_setting', 'Mailgun Mail Settings ', [$this, 'mailgun_section_cb'], 'mail-support-options' );
		add_settings_section( 'sendgrid_mail_setting', 'SendGrid Mail Settings ', [$this, 'sendgrid_section_cb'], 'mail-support-options' );

		/**
		 * Mail Provider fileds
		 * */
		// Active & Choose mail provider
		add_settings_field('active_mail_provider','Custom Mailer', [$this, 'active_mail_provider_cb'],'mail-support-options','section_mail_setting');
		add_settings_field( 'select_mailer', 'Select Mail Provider',  [$this, 'target_mail_provider'], 'mail-support-options', 'section_mail_setting');

		// For Mailgun
		add_settings_field('mailgun_url','Mailgun API URL', [$this, 'mailgun_url_field_cb'],'mail-support-options','mailgun_mail_setting');
		add_settings_field('mailgun_key','Mailgun API KEY', [$this, 'mailgun_key_field_cb'],'mail-support-options','mailgun_mail_setting');

		//For Sendgrid
		add_settings_field('sendgrid_username','SendGrid Username', [$this, 'sendgrid_userName'],'mail-support-options','sendgrid_mail_setting');
		add_settings_field('sendgrid_password','SendGrid Password', [$this, 'sendgrid_userPassword'],'mail-support-options','sendgrid_mail_setting');

	}


	/**
	 * Mail provider enable/disable
	 * */
	//Active / Deactive mail provider
	public function active_mail_provider_cb(){
		$active_mailer = get_option('active_mailer');
		$checkded =  ($active_mailer? 'checked' : '');

		 printf("<label><input type='checkbox' %s  name='active_mailer' class='active_mail_provider' value='1'> %s </label>", $checkded, esc_html__('Active Thirdparty Mail Service','cf7-db-tool'));

	}


	//Choose mail provider
	public function target_mail_provider(){
		$selec_provider = get_option( 'select_mailer' );

		$mailerList = array('Mailgun','SendGrid');
		$output='';
		echo '<select name="select_mailer"  required id="choose-mailer">';
		printf("<option value=''>%s</option>", esc_html__('Select Provider','cf7-db-tool'));
		foreach ($mailerList as $mailer ) {
		    if($selec_provider == $mailer ){
		        $selected = 'selected';
            }else{
			    $selected = '';
            }
			$output .='<option value="'.$mailer.'"  '.$selected.'> '.$mailer.'</option>';
		}
		echo $output;
		echo '</select>';
	}

	/**
	 * Mailgun Input Fields
	 * */
    //Mailgun URL INPUT
	public function mailgun_url_field_cb(){
		$mailgunUrl = esc_url(get_option('mailgun-url'));
		$mailgunUrl =  ($mailgunUrl? $mailgunUrl : '');
		echo '<input type="url" class="mailgun-input"  value="'.$mailgunUrl.'" name="mailgun-url" placeholder="https://api.mailgun.net/v3/domain"> ';
    }
    //Mailgun API KEY
    public function mailgun_key_field_cb(){
	    $api_key = get_option('mailgun-key');
	    $api_key =  ($api_key? $api_key : '');
	    echo '<input type="text"  class="mailgun-input"  value="'.$api_key.'" name="mailgun-key" placeholder="xxxxxxxxxxxxxxx">';
	    echo "<br><br>";
	    echo _e('How to Get Mailgun API Keys?', 'cf7-db-tool').' <a href="http://orangetoolz.com/mailgunsetup" target="_blank">Get API KEY</a>';
    }

	/**
	 * SendGrid Input Fields
	 * */

    public function sendgrid_userName(){
	    $sendGridUser = get_option('sendgrid-user');
	    $sendGridUser =  ($sendGridUser? $sendGridUser : '');
	    echo '<input type="email"  class="sendgrid-input"  value="'.$sendGridUser.'" name="sendgrid-user" placeholder="Enter SendGrid UserName"> ';
    }
    public function sendgrid_userPassword(){
	    $sendGridPassword = get_option('sendgrid-password');
	    $sendGridPassword =  ($sendGridPassword? $sendGridPassword : '');
	    echo '<input type="password"  class="sendgrid-input"   value="'.$sendGridPassword.'" name="sendgrid-password" placeholder="Enter SendGrid Password"> ';
	    //echo '<br><br>How to Get SendGrid API Keys? <a href="http://orangetoolz.com/sendgridsetup" target="_blank">Get API KEY</a>';
    }



	/**
	 * Section Filed sanitization functions
	 * */
	public  function  handle_mailgun_url_sanitization($url){
		$url = esc_url($url);
	    return $url;
    }
    public function handle_mailgun_text_sanitization($key){
	    $key = sanitize_text_field($key);
	    return $key;
    }

    public function handle_sendgrid_user_sanitization($user){
	    $user = sanitize_email($user);
	    return $user;
    }

    public function handle_sendgrid_password_sanitization($password){
	    $password = sanitize_text_field($password);
	    return $password;
    }

	/**
	 * Section callback functions
	 * */
	public function active_mail_provider__section_cb(){}
	public function mailgun_section_cb(){

	}
	public function sendgrid_section_cb(){

	}
	public function cf7DbToolCaptcha__section_cb(){}

    /**
	 * enqueue assets in admin
	 * @return void
	 */
	public function enqueueAdminAssets()
	{
		wp_enqueue_style('cf7-dbt-style', CF7_DBT_URL . '/assets/css/cf7-db-tool.css', '', CF7_DBT_VERSION);
		wp_enqueue_style('cf7-dbt-bulkmail', CF7_DBT_URL . '/assets/css/cf7-bulk-mail.css', '', CF7_DBT_VERSION);
		wp_enqueue_script('cf7-dbt-script', CF7_DBT_URL . '/assets/js/cf7-db-tool.js', ['jquery'], CF7_DBT_VERSION,true);

		wp_localize_script('cf7-dbt-script','cf7DbtObj',[
			'ajaxUrl' => admin_url( 'admin-ajax.php'),
			'nonce' => wp_create_nonce('cf7-dbt-reply-nonce')
		]);

	}



	/**
	 * enqueue assets in admin for specific page
	 * @return void
	 */
	public function loadScriptSpecificPage($hook){

		if( 'cf7-db-tool_page_cf7-db-bulkmail'==$hook ){
			wp_enqueue_script('cf7-dbt-bulkmail-script', CF7_DBT_URL . '/assets/js/cf7-db-bulkmail.js', ['jquery'], CF7_DBT_VERSION,true);
			wp_localize_script(
				'cf7-dbt-bulkmail-script',
				'ajax_object',array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce('bulkmail_nonce'))
			);
		}elseif ('cf7-db-tool_page_Mail-Settings' == $hook){
			// Load mailprovider script in specific page
			wp_enqueue_script('cf7-dbt-mail-provider', CF7_DBT_URL . '/assets/js/cf7-db-mail-provider.js', ['jquery'], CF7_DBT_VERSION,true);
        }elseif ('cf7-db-tool_page_cf7-db-report' == $hook){
		    // Load chart script in specific page
			wp_enqueue_script('cf7-dbt-report-chart', CF7_DBT_URL . '/assets/js/chart.js', ['jquery']);
        }

		else{
			return;
		}
	}


	/**
	 * notice if cf7 is not available
	 */
	public function cf7NotFound()
	{
		ob_start();
		?>
			<div class="wrap">
				<h2><?php _e('CF7 DB Tool - Warning','cf7-db-tool')?></h2>
				<div class="cf7-dbt-warning">
					<h4 style="margin: 10px 0 0"><?php _e('This plugin required CONTACT FORM 7 Plugin','cf7-db-tool')?></h4>
					<p><?php _e('Please install & activate ','cf7-db-tool')?>
                        <a href="https://wordpress.org/plugins/contact-form-7/" target="_blank"><?php _e('contact form 7','cf7-db-tool')?></a> <?php _e(' plugin','cf7-db-tool')?>.
                    </p>
				</div>
			</div>
		<?php
		return ob_get_flush();
	}
	/**
	 * admin notice on plugin activation
	 */
	public function adminNotice()
	{
		ob_start();
		if(get_transient('cf7-dbt-warning')):
			?>
			<div class="notice notice-warning is-dismissible">
				<h4 style="margin: 10px 0 0"><?php _e('This plugin required CONTACT FORM 7 Plugin','cf7-db-tool')?></h4>
				<p><?php _e('Please install & activate ','cf7-db-tool')?><a href="https://wordpress.org/plugins/contact-form-7/" target="_blank"><?php _e('contact form 7','cf7-db-tool')?></a> <?php _e(' plugin','cf7-db-tool')?>.</p>
			</div>
			<?php
		endif;
		return ob_get_flush();
	}


    /**
     * Load users mail from target form
     */
    public function bulkMailAjaxDataAction(){

        $cf7form = '';
        if(isset($_POST['cf7form'])){
            $cf7form = sanitize_text_field($_POST['cf7form']);
        }
        global $wpdb;
        $results = $wpdb->get_results("SELECT fields FROM " .$wpdb->prefix. "cf7_dbt_entries where form_id=" .$cf7form );
        $usersEmail=[];
        foreach ($results as $mail){
            $fields = unserialize($mail->fields);
            if(!in_array($fields["your-email"],$usersEmail)){
                array_push($usersEmail,$fields["your-email"]);
            }
        }
        foreach ($usersEmail as $email){
            echo '<option value='.$email.'>'.$email.'</option>';
        }

        wp_die();
    }
}

