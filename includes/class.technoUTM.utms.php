<?php 
/**
 * UTMs Class for the UTM Generator Plugin for Wordpress
 * Author: Technoyer Solutions Ltd.
 * http://tch-utm-generator.technoyer.com
 */
if (!defined('ABSPATH')){exit; // Exit if get it directly!
}

if ( !class_exists( 'technoUTM_utms' ) )
{
class technoUTM_utms
{
	/**
	 * @vars the UTM properties
	 */
	public $utm_source;
	public $utm_campaign;
	public $utm_content;
	public $utm_medium;
	public $utm_term;
	public $wpnonce_attr;
	public $admin_uri;
	public $currentCampaignID;
	public $currentCampaignURL;
	public $post;
	public $adminbar;
	public $active_posts;
	public $active_pages;
	public $post_type;
	public $donate_domain = "https://codecanyon.net/item/";
	public $donate_url;
	public $envato_auther="Technoyer";
	
	//The Construct // Define all props // Add actions
	public function __construct()
	{
		if ( empty( $this->utm_source ) ) {$this -> utm_source = esc_html( get_option( 'technoUTM_default_utm_source' ) );}
		if ( empty( $this->utm_medium ) ) {$this -> utm_medium = esc_html( get_option( 'technoUTM_default_utm_medium' ) );}
		if ( empty( $this->utm_campaign ) ) {$this -> utm_campaign = esc_html( get_option( 'technoUTM_default_utm_campaign' ) );}
		if ( empty( $this->utm_term ) ) {$this -> utm_term = esc_html( get_option( 'technoUTM_default_utm_term' ) );}
		if ( empty( $this->utm_content ) ) {$this -> utm_content = esc_html( get_option( 'technoUTM_default_utm_content' ) );}
		if ( empty( $this->active_posts ) ) {$this -> active_posts = esc_html( get_option( 'technoUTM_active_posts' ) );}
		if ( empty( $this->active_pages ) ) {$this -> active_pages = esc_html( get_option( 'technoUTM_active_pages' ) );}
		if ( empty( $this->adminbar ) ) {$this -> adminbar = esc_html( get_option( 'technoUTM_enable_adminbar' ) );}
		
		//wpnonce
		$this->wpnonce_attr = "_wpnonce";
		//admin URL
		$this->admin_uri = admin_url('admin.php?page='.TECHNOUTM_SLUG);
		
		//current campaign ID
		if(!empty( $_GET['post'] ))
		{
			$this->currentCampaignID = sanitize_text_field( $_GET['post'] );
		} else if (!empty( $this->post->ID ))
		{
			$this->currentCampaignID = esc_html( $this->post->ID );
		}
		$this->currentCampaignID = (int)$this->currentCampaignID;
		
		//add action to wp ajax
		add_action( 'wp_ajax_techno_utm_builder' , array ( $this , 'techno_utm_builder' ));
		add_action( 'wp_ajax_technoUTM_verifyPurchaseAjax' , array ( $this , 'technoUTM_verifyPurchaseAjax' ));
		//add action to save meta post
		add_action( 'save_post' , array ( $this , 'save_utms_meta_box' ));
		
		//Donate URL
		$donate_url = $this->donate_domain."utm-code-generator-for-google-analytics-tracking-url-wordpress-plugin/19808242";
		$this->donate_url = add_query_arg( array( 'ref' => $this->envato_auther ) , $donate_url );
		
	}
	
	/**
	 * Create wordpress URL nonces for more security
	 *
	 * @param string $link
	 * @param string $action
	 * @return string
	 */
	public function create_nonce_url ($link, $action)
	{
		$nonce = wp_nonce_url($link, $action, $this->wpnonce_attr);
		return $nonce;
	}
	
	/**
	 * Build new UTM URL
	 * All parameters must be sanitized ,escaped and verified before calling this function
	 *
	 * @param string $url -required
	 * @param string $utm_source -required
	 * @param string $utm_medium -optional
	 * @param string $utm_campaign -optional
	 * @param string $utm_term -optional
	 * @param string $utm_content -optional
	 * @return string
	 */
	public function buildNow ($url, $utm_source, $utm_medium=false, $utm_campaign=false, $utm_term=false, $utm_content=false)
	{
		$args['utm_source'] = trim($utm_source);
		if(isset($utm_campaign) && !empty($utm_campaign)) {$args['utm_campaign'] = trim($utm_campaign);}
		if(isset($utm_term) && !empty($utm_term)) {$args['utm_term'] = trim($utm_term);}
		if(isset($utm_content) && !empty($utm_content)) {$args['utm_content'] = trim($utm_content);}
		
		$link = add_query_arg ($args, trim( $url ));
		
		return $link;
	}
	
	/**
	 * Form HTML to build new UTM URL
	 *
	 * @return string
	 */
	public function buildURL_form ()
	{
		if(!empty( $this->currentCampaignID ))
		{
			$this->currentCampaignURL = esc_url( get_page_link ( $this->currentCampaignID ) );
		}
		
		$output[] = "<form action='".$this->create_nonce_url($this->admin_uri.'-buildnew', 'buildnew')."' method=post>";
		$output[] = "<table border=0 width=100% cellspacing=0>".$this->goProButton();
		$output[] = "<tbody>";
		
		//URL
		$output[] = "<tr class='underTD'><td><strong>".__('Website URL', TECHNOUTM_TRANS)."</strong>: <span class='imp_utm'>*</span><br>";
		$output[] = "<input type='text' name='url' value='".$this->currentCampaignURL."' style='width:350px;' required><br>
		<span class='smallfont'>".__('Full website url like e.g: http://example.com/thankyou.html', TECHNOUTM_TRANS)."</span></td></tr>";
		
		//utm_source
		$output[] = "<tr class='underTD'><td><strong>".__('Campaign Source', TECHNOUTM_TRANS)."</strong>: <span class='imp_utm'>*</span><br>";
		$output[] = "<input type='text' name='utm_source' value='".$this->utm_source."' required><br>
		<span class='smallfont'>".__('The campaign source like e.g: Facebook, Google, email or newsletter .. etc', TECHNOUTM_TRANS)."</span></td></tr>";
		
		//utm_medium
		$output[] = "<tr class='underTD'><td><strong>".__('Campaign Medium', TECHNOUTM_TRANS)."</strong>: ".TECHNOUTM_PROTEXT."<br>";
		$output[] = "<input type='text' name='utm_medium' disabled><br>
		<span class='smallfont'>".__('The marketing medium like e.g: cpc, cpm, banner or email .. etc', TECHNOUTM_TRANS)."</span></td></tr>";
		
		//utm_campaign
		$output[] = "<tr class='underTD'><td><strong>".__('Campaign Name', TECHNOUTM_TRANS)."</strong>:<br>";
		$output[] = "<input type='text' name='utm_campaign' value='".$this->utm_campaign."'><br>
		<span class='smallfont'>".__('The campaign name like e.g: productname_promo, april_sales or any_others .. etc', TECHNOUTM_TRANS)."</span></td></tr>";
		
		//utm_term
		$output[] = "<tr class='underTD'><td><strong>".__('Campaign Term', TECHNOUTM_TRANS)."</strong>:<br>";
		$output[] = "<input type='text' name='utm_term' value='".$this->utm_term."'><br>
		<span class='smallfont'>".__('The campaign term to identify the paid keywords', TECHNOUTM_TRANS)."</span></td></tr>";
		
		//utm_content
		$output[] = "<tr class='underTD'><td><strong>".__('Campaign Content', TECHNOUTM_TRANS)."</strong>:<br>";
		$output[] = "<input type='text' name='utm_content' value='".$this->utm_content."'><br>
		<span class='smallfont'>".__('The campaign content used for A/B testing and content-targeted ads to differentiate ads or links.', TECHNOUTM_TRANS)."</span></td></tr>";
		
		$output[] = "<tr class='uderTD'><td><button class='submit_utm' id='submit_utm_url' type='submit'>".__('Build / Update', TECHNOUTM_TRANS)."</button>
		</td></tr>";
		
		$output[] = "</tbody>";
		$output[] = "</table>";
		$output[] = "</form>";
		#self::check_license();
		return implode("\n", $output);
	}
	
	/**
	 * Meta box HTML form
	 *
	 */
	public function meta_box_form ()
	{
		if(!empty($_GET['post'])){
			$post_id = (int)sanitize_text_field( $_GET['post'] );
		} else {
			$post_id = "";
		}
		
		$output[] = "<table border=0 width=100% cellspacing=0>";
		$output[] = "<tbody>";

		//URL
		$output[] = "<tr class='underTD'><td><strong>".__('Website URL', TECHNOUTM_TRANS)."</strong>: <span class='imp_utm'>*</span><br>";
		$output[] = "<input type='text' name='url' value='".$this->currentCampaignURL."' style='width:350px;' required><br>
		<span class='smallfont'>".__('Full website url like e.g: http://example.com/thankyou.html', TECHNOUTM_TRANS)."</span></td></tr>";
		
		//utm_source
		$output[] = "<tr class='underTD'><td><strong>".__('Campaign Source', TECHNOUTM_TRANS)."</strong>: <span class='imp_utm'>*</span><br>";
		$output[] = "<input type='text' id='utm_source' name='_techno_utm_source' value='".$this->utm_source."'><br>
		<span class='smallfont'>".__('The campaign source like e.g: Facebook, Google, email or newsletter .. etc', TECHNOUTM_TRANS)."</span></td></tr>";
		
		//utm_medium
		$output[] = "<tr class='underTD'><td><strong>".__('Campaign Medium', TECHNOUTM_TRANS)."</strong>: ".TECHNOUTM_PROTEXT."<br>";
		$output[] = "<input type='text' id='utm_medium' name='_techno_utm_medium' disabled><br>
		<span class='smallfont'>".__('The marketing medium like e.g: cpc, cpm, banner or email .. etc', TECHNOUTM_TRANS)."</span></td></tr>";
		
		//utm_campaign
		$output[] = "<tr class='underTD'><td><strong>".__('Campaign Name', TECHNOUTM_TRANS)."</strong>:<br>";
		$output[] = "<input type='text' id='utm_campaign' name='_techno_utm_campaign' value='".$this->utm_campaign."'><br>
		<span class='smallfont'>".__('The campaign name like e.g: productname_promo, april_sales or any_others .. etc', TECHNOUTM_TRANS)."</span></td></tr>";
		
		//utm_term
		$output[] = "<tr class='underTD'><td><strong>".__('Campaign Term', TECHNOUTM_TRANS)."</strong>:<br>";
		$output[] = "<input type='text' id='utm_term' name='_techno_utm_term' value='".$this->utm_term."'><br>
		<span class='smallfont'>".__('The campaign term to identify the paid keywords', TECHNOUTM_TRANS)."</span></td></tr>";
		
		//utm_content
		$output[] = "<tr class='underTD'><td><strong>".__('Campaign Content', TECHNOUTM_TRANS)."</strong>:<br>";
		$output[] = "<input type='text' id='utm_content' name='_techno_utm_content' value='".$this->utm_content."'><br>
		<span class='smallfont'>".__('The campaign content used for A/B testing and content-targeted ads to differentiate ads or links.', TECHNOUTM_TRANS)."</span></td></tr>";
		
		$output[] = "<tr class='uderTD'><td>".__('If you added new values, please make sure you updated the post/page before click on the Show button', TECHNOUTM_TRANS)."<p>
		<button class='submit_utm' id='show_utm_url' type='button'>".__('Show UTM Link', TECHNOUTM_TRANS)."</button>
		</td></tr>";
		
		$ajax_link = add_query_arg(array(
		'action' => 'techno_utm_builder',
		$this->wpnonce_attr => wp_create_nonce( 'techno_utm_builder' )
		), admin_url( 'admin-ajax.php' ));
		
		$output[] = "</tbody>";
		$output[] = "</table>";
		$output[] = "<span id='resUtm'></span>";
		$output[] = "<script>";
		$output[] = "jQuery('#show_utm_url').click(function( $ )";
		$output[] = "{";
		$output[] = "jQuery('#resUtm').html('<img src=".plugins_url(TECHNOUTM_SLUG.'/images/loading.gif').">');";
		$output[] = "jQuery('#resUtm').load('".$ajax_link."&post=".$post_id."');";
		$output[] = "});";
		$output[] = "</script>";
		
		echo implode("\n", $output);
	}
	
	/**
	 * UTM link builder for meta box Ajax
	 *
	 */
	public function techno_utm_builder()
	{
		$post_id = (int)sanitize_text_field( $_GET['post'] );
		
		if(empty($_GET[ $this->wpnonce_attr ]) || wp_verify_nonce($_GET[ $this->wpnonce_attr ], 'techno_utm_builder') == false){exit;}
		if(current_user_can( 'manage_options' ))
		{
			if( get_post_meta( $post_id, '_techno_utm_url' , true) == '' && get_post_meta( $post_id, '_techno_utm_source' , true) == '')
			{
				$link = $this->admin_uri."-buildnew&post=".$post_id;
				echo "<div class='resUTM'>";
				echo _e('You should update Post/Page then click this button to save UTM properties, ', TECHNOUTM_TRANS)."<br />";
				echo _e('or use the inline builder from admin bar icon or ', TECHNOUTM_TRANS);
				echo "<a href='$link' target=_blank>".__('Click here',TECHNOUTM_TRANS)."</a>";
				echo "</div>";
			} else {
				$utm_url = get_post_meta( $post_id, '_techno_utm_url' , true);
				$utm_url = str_replace("{%link%}", esc_url(get_page_link( $post_id )), $utm_url);
				$utm_source = esc_html( get_post_meta( $post_id, '_techno_utm_source' , true) );
				$utm_campaign = esc_html( get_post_meta( $post_id, '_techno_utm_campaign' , true) );
				$utm_term = esc_html( get_post_meta( $post_id, '_techno_utm_term' , true) );
				$utm_content = esc_html( get_post_meta( $post_id, '_techno_utm_content' , true) );
				
				#$url, $utm_source, $utm_medium=false, $utm_campaign=false, $utm_term=false, $utm_content=false
				
				if(empty( $utm_url ))
				{
					echo "<div class='resUTM utm_div_scrolling'>";
					echo _e('UTM URL must be a real link or you can use this symbol {%link}', TECHNOUTM_TRANS);
					echo "</div>";
				} else {
					$link = $this->buildNow($utm_url, $utm_source, $utm_medium, $utm_campaign, $utm_term, $utm_content);
					echo "<div class='resUTM utm_div_scrolling'>";
					echo "<pre>".$link."</pre>";
					echo "</div>";
				}
				
			}
		}
		exit();
	}
	
	/**
	 * Save post meta when post is saved
	 *
	 * @param int $post_id
	 */
	public function save_utms_meta_box ( $post_id )
	{
		if (array_key_exists('_techno_utm_url', $_POST)) { $this->update_metabox_one_item($post_id,'_techno_utm_url',trim(esc_html($_POST['_techno_utm_url'])));} 
		if (array_key_exists('_techno_utm_source', $_POST)) { $this->update_metabox_one_item($post_id,'_techno_utm_source',trim(esc_html($_POST['_techno_utm_source'])));} 
		if (array_key_exists('_techno_utm_medium', $_POST)) { $this->update_metabox_one_item($post_id,'_techno_utm_medium',trim(esc_html($_POST['_techno_utm_medium'])));} 
		if (array_key_exists('_techno_utm_campaign', $_POST)) { $this->update_metabox_one_item($post_id,'_techno_utm_campaign',trim(esc_html($_POST['_techno_utm_campaign'])));} 
		if (array_key_exists('_techno_utm_term', $_POST)) { $this->update_metabox_one_item($post_id,'_techno_utm_term',trim(esc_html($_POST['_techno_utm_term'])));} 
		if (array_key_exists('_techno_utm_content', $_POST)) { $this->update_metabox_one_item($post_id,'_techno_utm_content',trim(esc_html($_POST['_techno_utm_content'])));}
	}
	
	/**
	 * Update one item in post meta table by post id
	 *
	 * @param int $post_id
	 * @param string $item
	 * @param string $newvalue
	 */
	protected function update_metabox_one_item($post_id, $item, $newvalue)
	{
		$newvalue = sanitize_text_field( $newvalue ) ;
		
        update_post_meta(
            $post_id,
            $item,
            $newvalue
        );
	}
	/**
	 * installing the meta box for posts, pages or both 
	 *
	 */
	public function install_meta_box ( )
	{
		global $post_id;
		#if(get_option('technoUTM_item_id') == '' && get_option('technoUTM_item_checked') == ''){return ; exit;}
		if(empty(get_post_meta($post_id, '_techno_utm_url' )))
		{
			$this->currentCampaignURL = "{%link%}";
		} else {
			$this->currentCampaignURL = get_post_meta( $post_id, '_techno_utm_url' , true );
		}
		if( $this->currentCampaignURL != "{%link%}")
		{
			$this->currentCampaignURL = esc_url( $this->currentCampaignURL );
		} else {$this->currentCampaignURL = esc_html( $this->currentCampaignURL );}
		
		$this->utm_source = esc_html( get_post_meta( $post_id, '_techno_utm_source' , true ) );
		$this->utm_medium = esc_html( get_post_meta( $post_id, '_techno_utm_medium' , true ) );
		$this->utm_campaign = esc_html( get_post_meta( $post_id, '_techno_utm_campaign' , true ) );
		$this->utm_term = esc_html( get_post_meta( $post_id, '_techno_utm_term' , true ) );
		$this->utm_content = esc_html( get_post_meta( $post_id, '_techno_utm_content' , true ) );
		
		if($this->active_posts == 'checked')
		{
			add_meta_box( 
			TECHNOUTM_SLUG, 
			__( 'UTM Builder', TECHNOUTM_TRANS ), 
			array( $this , 'meta_box_form' ), 
			'post', 
			'normal', 
			'low' );
		}
		
		
	}
	
	/**
	 * Retrieve from post meta
	 *
	 * @param string $param
	 * @param int $post
	 * @return string
	 */
	public function get_from_meta ( $param , $post)
	{
		$param = trim( esc_html ( $param ) );
		return esc_html( get_post_meta ( $post->ID , $param , true ) );
	}
	
	/**
	 * Print the help HTML table
	 *
	 * @return string
	 */
	public function print_help_table ()
	{
		$output[] = "<table border=0 width=100% cellspacing=0>";
		$output[] = "<tbody>";
		
		//utm_source
		$output[] = "<tr class='underTD'><td width='25%' class='tdLeft'><strong>".__('Campaign Source', TECHNOUTM_TRANS)."</strong><br />
		<span class='selected_text_utm'>utm_source</span></td>";
		$output[] = "<td class='tdRight'><strong>".__('Required!', TECHNOUTM_TRANS)."</strong><br />
		".__('Use it to identify the traffic source such as search engine, social media or email newsletter', TECHNOUTM_TRANS)."<br />
		<span class='smallfont'>".__('e.g: google, facebook, adfly, newsletter or another traffic source', TECHNOUTM_TRANS)."</span>";
		
		//utm_medium
		$output[] = "<tr class='underTD'><td width='25%' class='tdLeft'><strong>".__('Campaign Medium', TECHNOUTM_TRANS)."</strong><br />
		<span class='selected_text_utm'>utm_medium</span></td>";
		$output[] = "<td class='tdRight'><strong><i>".__('Optional!', TECHNOUTM_TRANS)."</i></strong><br />
		".__('Use it to identify a medium such as email or cost-per-click(cpc).', TECHNOUTM_TRANS)."<br />
		<span class='smallfont'>".__('e.g: cpc or cpm', TECHNOUTM_TRANS)."</span>";
		
		//utm_campaign
		$output[] = "<tr class='underTD'><td width='25%' class='tdLeft'><strong>".__('Campaign Name', TECHNOUTM_TRANS)."</strong><br />
		<span class='selected_text_utm'>utm_campaign</span></td>";
		$output[] = "<td class='tdRight'><strong><i>".__('Optional!', TECHNOUTM_TRANS)."</i></strong><br />
		".__('Used for keyword analysis. Use it to identify a specific product promotion or strategic campaign.', TECHNOUTM_TRANS)."<br />
		<span class='smallfont'>".__('e.g: utm_campaign=spring_sale', TECHNOUTM_TRANS)."</span>";
		
		//utm_term
		$output[] = "<tr class='underTD'><td width='25%' class='tdLeft'><strong>".__('Campaign Term', TECHNOUTM_TRANS)."</strong><br />
		<span class='selected_text_utm'>utm_term</span></td>";
		$output[] = "<td class='tdRight'><strong><i>".__('Optional!', TECHNOUTM_TRANS)."</i></strong><br />
		".__('Used for paid search. Use it to note the keywords for this ad.', TECHNOUTM_TRANS)."<br />
		<span class='smallfont'>".__('e.g: running+shoes', TECHNOUTM_TRANS)."</span>";
		
		//utm_term
		$output[] = "<tr class='underTD'><td width='25%' class='tdLeft'><strong>".__('Campaign Content', TECHNOUTM_TRANS)."</strong><br />
		<span class='selected_text_utm'>utm_content</span></td>";
		$output[] = "<td class='tdRight'><strong><i>".__('Optional!', TECHNOUTM_TRANS)."</i></strong><br />
		".__('Used for A/B testing and content-targeted ads. Use it to differentiate ads or links that point to the same URL.', TECHNOUTM_TRANS)."<br />
		<span class='smallfont'>".__('e.g: logolink or textlink', TECHNOUTM_TRANS)."</span>";
		
		$output[] = "</tbody>";
		$output[] = "</table>";
		
		return implode("\n", $output);
	}
	
	/**
	 * Google Analytics Code
	 *
	 */
	public function install_analytics_tracking_code()
	{
		if(get_option('technoUTM_item_id') == '' && get_option('technoUTM_item_checked') == ''){return ; exit;}
		if(get_option('technoUTM_install_analytics') == 'checked' && !empty( get_option('technoUTM_analytics_id') ))
		{
			$output[] = "<script>";
			$output[] = "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){";
			$output[] = "(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),";
			$output[] = "m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)";
			$output[] = "})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');";
			$output[] = "";
			$output[] = "ga('create', '".esc_html( get_option('technoUTM_analytics_id') )."', 'auto');";
			$output[] = "ga('send', 'pageview');";
			$output[] = "</script>";
			
			echo implode("\n", $output);
		}
	}
	
	public function goProButton ()
	{
		$output[] = "<div class='go_pro' id='technoUTMgo_pro'>";
		$output[] = "<a href='".$this->donate_url."' target=_blank>&hearts; Go Pro only $9</a>";
		$output[] = "</div>";
		
		return implode("\n", $output);
	}
}
}
?>