<?php 
/**
 * Main Class for the UTM Generator Plugin for Wordpress
 * Author: Technoyer Solutions Ltd.
 * http://tch-utm-generator.technoyer.com
 */
if (!defined('ABSPATH')){exit; // Exit if get it directly!
}

if ( !class_exists( 'technoUTM' ) ) {
class technoUTM {
	
	//Set vetsion number
	public $version;
	//Set the absolute plugins local dir
	public $abs_dir;
	//Set the absolute plugin local path
	public $abs_path;
	
	protected static $_instance = null;
	
	protected $install;
	protected $utm;
	protected $admin_uri;
	protected $admin_bar_icon;
	
	// Setup one instance for UTM Generator
	public static function instance()
	{
		if(is_null(self::$_instance))
		{
			self::$_instance = new self();
		}
		
		return self::$_instance;
	}
	//The Construct // Define all props // Add actions
	public function __construct()
	{
		//Set Props
		$this->version = TECHNOUTM_VERSION;
		$this->abs_path = TECHNOUTM_PATH;
		$this->abs_dir = TECHNOUTM_DIR;
		$this->install = new technoUTM_insall();
		$this->utm = new technoUTM_utms();
		$this->admin_uri = admin_url('admin.php?page='.TECHNOUTM_SLUG);	
		$this->admin_bar_icon = plugins_url('images/emblem-symbolic-link.png', $this->abs_path);	
		
		//Install and active the plugin
		register_activation_hook( $this->abs_path , array( $this, 'activate' ) );

		//Deactivate the plugin
		register_deactivation_hook ( $this->abs_path , array ( $this , 'de_activate' ) );
		
		//UnInstall the plugin
		register_uninstall_hook ( $this->abs_path , 'techno_utm_doUnInstall' );
		
		//Include CSS and JS files
		add_action ( 'admin_enqueue_scripts' , array ($this , 'include_scrips_and_css' ) );
		//Install Menus
		if(is_admin())
		{
			add_action ('admin_menu', array ( $this , 'menu' ));
		}
		
		//Install Admin Bar
		if($this->utm->adminbar == 'checked')
		{
			add_action ('admin_bar_menu', array ( $this , 'admin_bar' ) , 999);
			add_action ('admin_bar_menu', array ( $this , 'admin_bar_submenu' ) , 999);
		}
		
		//Install meta box for posts,pages or both
		add_action( 'add_meta_boxes', array ( $this , 'meta_box') );
		
		//Install Google analytics code in header or footer in frontend
		if(get_option('technoUTM_install_analytics') == 'checked' && !empty( get_option('technoUTM_analytics_id') ))
		{
			if( esc_html( get_option( 'technoUTM_analytics_position' ) ) == 'header'){
				add_action( 'wp_head' , array ( $this->utm , 'install_analytics_tracking_code'));
			} else if( esc_html( get_option( 'technoUTM_analytics_position' ) ) == 'footer'){
				add_action( 'wp_footer' , array ( $this->utm , 'install_analytics_tracking_code'), 999);
			}
		}
		
		
		//Plugins Links
		add_filter( 'plugin_action_links_' . plugin_basename( $this->abs_path ), array( $this , 'action_links') );
		
	}
	
	/**
	 * Meta box content in posts, pages or both
	 *
	 */
	public function meta_box (  )
	{
		global $post_id;
		
		if(empty(get_post_meta( $post_id, '_techno_utm_url' , true)))
		{
			$this->utm->currentCampaignURL = "{%link%}";
		} else {
			$this->utm->currentCampaignURL = esc_html( get_post_meta( $post_id, '_techno_utm_url' , true) );
		}
		$this->utm->utm_source = esc_html( get_post_meta( $post_id, '_techno_utm_source' , true ) );
		$this->utm->utm_medium = esc_html( get_post_meta( $post_id, '_techno_utm_medium' , true ) );
		$this->utm->utm_campaign = esc_html( get_post_meta( $post_id, '_techno_utm_campaign' , true ) );
		$this->utm->utm_term = esc_html( get_post_meta( $post_id, '_techno_utm_term' , true ) );
		$this->utm->utm_content = esc_html( get_post_meta( $post_id, '_techno_utm_content' , true) );
		
		$this->utm->install_meta_box();
	}
	/**
	 * Print Admin Menu
	 *
	 */
	public function menu ()
	{
		if(current_user_can ('manage_options') == true)
		{
			add_menu_page('UTM Generator',
			'UTM Generator',
			'manage_options',
			'tch-utm-generator-buildnew',
			array ($this,'build_url'),
			plugins_url('images/icon.png', $this->abs_path));
			
			add_submenu_page('tch-utm-generator-buildnew',
			'Settings',
			'Settings',
			'manage_options',
			'tch-utm-generator',
			array ($this,'dashboard'));
		}
	}
	
	/**
	 * The admin bar icon in order to build UTM links for posts and pages
	 *
	 * @param object $wp_admin_bar
	 */
	public function admin_bar ( $wp_admin_bar )
	{
		global $post;
		/*$args['title'] = "<img src='".$this->admin_bar_icon."'>";
		$args['href'] = $this->admin_uri."-buildnew&post=".$this->post->ID;
		$args['parent'] = false;
		*/
		$args = array(
		"id" => "technoUTM",
		"title" => "<img class='utm_adminbar_icon' src='".$this->admin_bar_icon."'>",
		"parent" => false,
		);
		
		if(current_user_can( 'manage_options' ))
		{
			if(is_singular( 'post' ) && $this->utm->active_posts == 'checked')
			{
				$wp_admin_bar->add_node( $args );	
			} else if(is_singular( 'page' ) && $this->utm->active_pages == 'checked')
			{
				$wp_admin_bar->add_node( $args );	
			} else if (is_admin ())
			{
				$wp_admin_bar->add_node( $args );	
			}
		}
	}
	
	/**
	 * Create admin bar submenu
	 *
	 * @param object $wp_admin_bar
	 */
	public function admin_bar_submenu ( $wp_admin_bar )
	{
		global $post;
		/*$args['title'] = "<img src='".$this->admin_bar_icon."'>";
		$args['href'] = $this->admin_uri."-buildnew&post=".$this->post->ID;
		$args['parent'] = false;
		*/
		$args = array();
		
		if(is_singular( 'post' ) or is_singular( 'page' ))
		{
			$post_id = $post->ID;
		} else {
			$post_id = "";
		}
		
		array_push(
			$args,array(
			"id" => "technoUTM_buildnew",
			"title" => "Build New UTM ".TECHNOUTM_PROTEXT,
			"href" => "#",
			"parent" => "technoUTM",
			)
		);
		
		array_push(
			$args,array(
			"id" => "technoUTM_editPost",
			"title" => "Edit in Post/Page ".TECHNOUTM_PROTEXT,
			"href" => "#",
			"parent" => "technoUTM",
			)
		);
		
		array_push(
			$args,array(
			"id" => "technoUTM_settings",
			"title" => "Settings",
			"href" => $this->admin_uri,
			"parent" => "technoUTM",
			)
			);
		
		
		sort($args);
		if(current_user_can( 'manage_options' ))
		{
			if(is_singular( 'post' ) && $this->utm->active_posts == 'checked')
			{
				$this->get_adminbar_submenu( $wp_admin_bar , $args );	
			} else if(is_singular( 'page' ) && $this->utm->active_pages == 'checked')
			{
				$this->get_adminbar_submenu( $wp_admin_bar , $args );	
			} else if(is_admin()){
				
				$args2 = array();
				
				array_push(
				$args2,array(
				"id" => "technoUTM_buildnew",
				"title" => "Build New UTM",
				"href" => $this->admin_uri."-buildnew",
				"parent" => "technoUTM",
				)
				);
		
				array_push(
				$args2,array(
				"id" => "technoUTM_settings",
				"title" => "Settings",
				"href" => $this->admin_uri,
				"parent" => "technoUTM",
				)
				);
				sort($args2);
				$this->get_adminbar_submenu( $wp_admin_bar , $args2 );	
			}
		}
	}
	
	/**
	 * Loop sub-menus for admin bar
	 *
	 * @param object $wp_admin_bar
	 * @param array $args
	 */
	public function get_adminbar_submenu ( $wp_admin_bar , $args )
	{
			for($a=0;$a<sizeOf($args);$a++)
			{
				$wp_admin_bar->add_node($args[$a]);
			}
	}
	/**
	 * Buil New URL Form and HELP then SUBMIT with nonce verification!
	 *
	 */
	public function build_url ()
	{
		$output = "<div class='wrap'><div class='homeContainer'><h1>".__('Generate UTM URL', TECHNOUTM_TRANS)."</h1><hr>";
		
		//Print Build Form
		$output .= $this->utm->buildURL_form();
		$output .= "</div>";
		
		//Print HELP Table
		$output .= "<br />\n<div class='homeContainer'><h1>".__('HELP', TECHNOUTM_TRANS)."</h1><hr>";
		$output .= $this->utm->print_help_table();
		$output .= "\n</div>";
		$output .= "\n</div>";
		
		if(! $_POST)
		{
			echo $output;
		} else {
			if( !empty( $this->utm->wpnonce_attr ) && false != wp_verify_nonce( $_GET[$this->utm->wpnonce_attr] , 'buildnew' ) )
			{
				$url = sanitize_text_field( $_POST['url'] );
				$utm_source = sanitize_text_field( $_POST['utm_source'] );
				$utm_medium = sanitize_text_field( $_POST['utm_medium'] );
				$utm_campaign = sanitize_text_field( $_POST['utm_campaign'] );
				$utm_term = sanitize_text_field( $_POST['utm_term'] );
				$utm_content = sanitize_text_field( $_POST['utm_content'] );
				
				if(empty($url)){$errors[] = __('Please fill the URL field', TECHNOUTM_TRANS);}
				if(filter_var($url, FILTER_VALIDATE_URL) == FALSE){$errors[] = __('Please fill the URL field with the correct way http:// or https://', TECHNOUTM_TRANS);}
				if(empty($utm_source)){$errors[] = __('Please fill the utm_source field', TECHNOUTM_TRANS);}
				
				$linkOutput[] = "<div class='wrap'><div class='homeContainer'><h1>".__('Generate UTM URL', TECHNOUTM_TRANS)."</h1><hr>";
				if (!empty($errors))
				{
					$err_msg="<strong><i>".__('You can not continue before fixing the following errors', TECHNOUTM_TRANS)."</i></strong><br /><br />";
					for($i=0; $i<count($errors); $i++)
					{
						$err_msg .= "\n".$errors[$i]."<br />";
					}
					#$err_msg .="</ul>";
					$linkOutput[] = $this->print_errors($err_msg);
					$linkOutput[] = "<button class='submit_utm' type='button' onclick='javascript:history.back(-1)'>".__('Back', TECHNOUTM_TRANS)."</button>";
				} else {
					$link = $this->utm->buildNow($url,$utm_source,$utm_medium,$utm_campaign,$utm_term,$utm_content);
					$linkOutput[] = "<strong>Your UTM URL is ready!</strong><br /><br />";
					$linkOutput[] = "<div class='utm_div_scrolling'><pre>$link</pre></div><br />";
					$linkOutput[] = "<a href='$link' target=_blank>".__('Preview Link', TECHNOUTM_TRANS)."</a> | ";
					$linkOutput[] = "<a href='".$this->admin_uri."-buildnew'>".__('Build New UTM', TECHNOUTM_TRANS)."</a>";
				}
				
				$linkOutput[] = "\n</div>";
				$linkOutput[] = "\n</div>";
				
				echo implode("\n", $linkOutput);
			}
		}
	}
	/**
	 * Dashboard HTML for settings
	 *
	 */
	public function dashboard ()
	{
		$wpnonce_to_savesettings = "dashboard";
		
		$output_first[] = "<div class='wrap'><div class='homeContainer'><h1>UTM Generator Settings</h1>".$this->utm->goProButton()."<hr>";
		
		// Print Messages if found!
		if(!empty($_COOKIE['success_msg']))
		{
			$output_first[] = $this -> success_message($_COOKIE['success_msg']);
		}
		
		if(!empty($_COOKIE['site_error']))
		{
			$output_first[] = $this -> print_errors($_COOKIE['site_error']);
		}
		echo implode("\n", $output_first);
		#$this->utm->check_license();
		//Form Starting
		$output[] = "<form action='".$this->utm->create_nonce_url($this->admin_uri, $wpnonce_to_savesettings)."' method='post'>";
		$output[] = "<table border='0' width=100% cellspacing=0>";
		$output[] = "<tbody>";
		
		//Check Adminbar Activation
		$output[] = "<tr class='underTD'><td><input type='checkbox' value='checked' name='technoUTM_enable_adminbar' id='technoUTM_enable_adminbar'";
		if(get_option('technoUTM_enable_adminbar') == 'checked'){$output[] = " checked";}
		$output[] = ">";
		$output[] = "<label for='technoUTM_enable_adminbar'>Enable UTM Manager in Admin Bar</label><br />
		<font class='smallfont'> &nbsp; - Build UTM for exists post ".TECHNOUTM_PROTEXT."</font><br />
		<font class='smallfont'> &nbsp; - Build UTM from postmeta ".TECHNOUTM_PROTEXT."</font></tr>";
		
		//Check for posts
		$output[] = "<tr class='underTD'><td><input type='checkbox' value='checked' name='technoUTM_active_posts' id='technoUTM_active_posts'";
		if(get_option('technoUTM_active_posts') == 'checked'){$output[] = " checked";}
		$output[] = ">";
		$output[] = "<label for='technoUTM_active_posts'>Enable for posts</label></tr>";
		
		//Check for pages
		$output[] = "<tr class='underTD'><td><input type='checkbox' disabled value='checked' name='technoUTM_active_pages' id='technoUTM_active_pages'";
		$output[] = ">";
		$output[] = "<label for='technoUTM_active_pages'>Enable for pages</label> ".TECHNOUTM_PROTEXT."</tr>";
		
		//Default utm_source
		$output[] = "<tr class='underTD'><td><strong>Default Value for <span class='selected_text_utm'>utm_source</span></strong>:<br>";
		$output[] = "<input type='text' name='technoUTM_default_utm_source' value='".$this->utm->utm_source."'></td></tr>";
		
		//Default utm_campaign
		$output[] = "<tr class='underTD'><td><strong>Default Value for <span class='selected_text_utm'>utm_campaign</span></strong>:<br>";
		$output[] = "<input type='text' name='technoUTM_default_utm_campaign' value='".$this->utm->utm_campaign."'></td></tr>";
		
		//Default utm_medium
		$output[] = "<tr class='underTD'><td><strong>Default Value for <span class='selected_text_utm'>utm_medium</span></strong>:<br>";
		$output[] = "<input type='text' name='technoUTM_default_utm_medium' value='".$this->utm->utm_medium."'></td></tr>";
		
		//Default utm_content
		$output[] = "<tr class='underTD'><td><strong>Default Value for <span class='selected_text_utm'>utm_content</span></strong>:<br>";
		$output[] = "<input type='text' name='technoUTM_default_utm_content' value='".$this->utm->utm_content."'></td></tr>";
		
		//Default utm_term
		$output[] = "<tr class='underTD'><td><strong>Default Value for <span class='selected_text_utm'>utm_term</span></strong>:<br>";
		$output[] = "<input type='text' name='technoUTM_default_utm_term' value='".$this->utm->utm_term."'></td></tr>";
		
		$output[] = "<tr class=''><td><h2>Google Analytics Tracking Code <sub>".TECHNOUTM_PROTEXT."</sub></h2></td></tr>";
		
		//Check for Google anayltics tracking installation
		$output[] = "<tr class='underTD'><td><input type='checkbox' value='checked' disabled name='technoUTM_install_analytics' id='technoUTM_install_analytics'";
		$output[] = ">";
		$output[] = "<label for='technoUTM_install_analytics'>Install Google Analytics Tracking Code</label></tr>";
		
		$output[] = "<tr class='underTD'><td><strong>Google Analytics Tacking ID</strong>:<br>";
		$output[] = "<input type='text' name='technoUTM_analytics_id' disabled placeholder='UA-XXXXXXXX-X'><br />
		<span class='smallfont'>Tracking Code like e.g: UA-91234567-1</td></tr>";
		
		$output[] = "<tr class='underTD'><td><input type='radio' value='header' disabled checked name='technoUTM_analytics_position' id='technoUTM_analytics_position_header'";
		$output[] = ">";
		$output[] = "<label for='technoUTM_analytics_position_header'>Install it in header</label></tr>";
		
		$output[] = "<tr class='underTD'><td><input type='radio' disabled value='footer' name='technoUTM_analytics_position' id='technoUTM_analytics_position_footer'";
		$output[] = ">";
		$output[] = "<label for='technoUTM_analytics_position_footer'>Install it in footer</label></tr>";
		
		$output[] = "</tbody></table>";
		#$output[] = "<hr>";
		$output[] = "<p><button id='utmSubmit' class='submit_utm' type='submit'>Save Settings</button></form>
		</div></div>";
		
		if(!$_POST)
		{
			echo implode("\n", $output);
		} else {
			//Do Submit - Update Options
			if(!empty($this->utm->wpnonce_attr) && false != wp_verify_nonce($_GET[$this->utm->wpnonce_attr], $wpnonce_to_savesettings ))
			{
				$this->updateOption('technoUTM_enable_adminbar', sanitize_text_field( $_POST['technoUTM_enable_adminbar'] ) );
				$this->updateOption('technoUTM_active_posts', sanitize_text_field( $_POST['technoUTM_active_posts'] ) );
				$this->updateOption('technoUTM_default_utm_source', sanitize_text_field( $_POST['technoUTM_default_utm_source'] ) );
				$this->updateOption('technoUTM_default_utm_campaign', sanitize_text_field( $_POST['technoUTM_default_utm_campaign']) );
				$this->updateOption('technoUTM_default_utm_medium', sanitize_text_field( $_POST['technoUTM_default_utm_medium'] ) );
				$this->updateOption('technoUTM_default_utm_content', sanitize_text_field( $_POST['technoUTM_default_utm_content'] ) );
				$this->updateOption('technoUTM_default_utm_term', sanitize_text_field( $_POST['technoUTM_default_utm_term'] ));
				
				setcookie('success_msg','01', time()+120);
				header("Location: ".$this->admin_uri);
			} else {
				//Print Error for no permission
				echo "<br /><h3>".$this -> errors( '03' )."</h3>";
			}
		}
	}
	
	/**
	 * Call the CSS and/or JS files
	 *
	 */
	public function include_scrips_and_css ()
	{
		if ( is_admin() && current_user_can( 'manage_options' ) == true)
		{
			wp_enqueue_style (TECHNOUTM_SLUG."_css", plugins_url('css/dashboard.css', $this->abs_path));
		}
	}
	
	/**
	 * Update Settings using update/add_option from codex Wordpress
	 *
	 * @param string $option_name
	 * @param string $option_value
	 */
	public function updateOption ( $option_name , $option_value )
	{
		$option_name = esc_html ( $option_name );
		$option_value = sanitize_text_field ( $option_value );
		
		update_option ( $option_name , $option_value );
	}
	/**
	 * Calling doInstall() function from the install class and execute it!
	 *
	 */
	public function activate ()
	{
		$this->install->doInstall();
		do_action('technoUTM_activate');
	}
	
	/**
	 * Calling doUnInstall() function from the install class and execute it!
	 *
	 */
	public function de_activate ()
	{
		//Go to uninstall
		do_action('technoUTM_deactivate');
	}
	
	/**
	 * Delete Database Tables if exists
	 *
	 */
	public function un_install ()
	{
		//If we have in the future some database structres for new tables we will delete it here!
		$this->install->doUnInstall();
		do_action('technoUTM_uninstall');
	}
	
	/**
	 * Define Error numbers and return it into WP errors
	 *
	 * @param int $errno
	 * @return string
	 */
	public function errors ( $errno )
	{
		$errors = array(
		"01" => "License Error!",
		"02" => "Access Denied!",
		"03" => "You have no permission to do this action!"
		);
		
		foreach ($errors as $key => $value)
		{
			if ($errno == $key)
			{
				$the_error = new WP_Error( 'broke', __($value) );
				if(is_wp_error ( $the_error ) )
				{
					return $the_error->get_error_message();
				}
			}
		}
	}
	
	/**
	 * Define Messages to add it to the WP filter
	 *
	 * @param int $no
	 * @return string
	 */
	public function success_message ($no)
	{
		$msgs = array
		(
		"01" => "Settings Updated Successfully.",
		"02" => "Link Created Successfully.",
		);
		
		foreach ($msgs as $key => $value)
		{
			if ( $key == $no ) {$message = $value;}
		}
		
		if(!empty ( $message ) ) {
			setcookie('success_msg','');
			return '<div id="message" class="updated notice is-dismissible"><p>'.$message.'</p></div>';
		}
	}
	
	/**
	 * Print errors in the errors div
	 *
	 * @param string $error
	 * @return string
	 */
	public function print_errors ($error)
	{
		$output[] = "<div class='error'><p>";
		$output[] = $error;
		$output[] = "</p></div>";
		
		return implode("\n", $output);
	}
	
	public function action_links ( $links )
	{
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page='.TECHNOUTM_SLUG ) ) . '">' . __( 'Settings', TECHNOUTM_TRANS ) . '</a>';
		if( defined( 'TECHNOUTM_PROACTION' ) && TECHNOUTM_PROACTION == 'make-alert'){
			$links[] = '<a target="_blank" href="'.$this->utm->donate_url.'"><span style="border:solid 1px #C73939;color:#C73939;padding:2px">' . __( 'Premium Version', TECHNOUTM_TRANS ) . '</a>';
		}

		return $links;
	}
}
}
?>