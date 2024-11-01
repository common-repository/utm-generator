<?php 
// The plugins installer
if (!defined('ABSPATH')){exit; // Exit if get it directly!
}

if ( !class_exists( 'technoUTM_insall' ) ) {
// The Installation class
class technoUTM_insall {
	
	protected $version;
	
	public function __construct()
	{
		$this->version = TECHNOUTM_VERSION;
	}
	
	//Add options in not exists
	public function newOption($option_name, $option_value = false)
	{
		$option_name = esc_html ( $option_name );
		$option_value = esc_html ( $option_value );
		
		if(empty(get_option( $option_name )))
		{
			add_option( $option_name , $option_value );
		} else {
			update_option( $option_name , $option_value );
		}
	}
	
	//Delete options from wordpress
	public function delOption($option_name)
	{
		$option_name = esc_attr ( $option_name );
		
		delete_option ( $option_name );
	}
	
	//Empty options if exists
	public function emptyOption($option_name)
	{
		$option_name = esc_html ( $option_name );
		
		$this->newOption( $option_name );
	}
	
	/**
	 * Install UTM Generator by adding new options
	 *
	 */
	public function doInstall ()
	{
		$this->newOption('technoUTM_enable_adminbar', 'checked');
		$this->newOption('technoUTM_active_posts', 'checked');
		$this->newOption('technoUTM_active_pages', 'checked');
		$this->newOption('technoUTM_default_utm_source', 'facebook');
		$this->newOption('technoUTM_default_utm_medium', 'cpm');
		$this->newOption('technoUTM_default_utm_campaign', 'promo');
		$this->newOption('technoUTM_default_utm_term', '');
		$this->newOption('technoUTM_default_utm_content', '');
		$this->newOption('technoUTM_install_analytics', '');
		$this->newOption('technoUTM_analytics_id', '');
		$this->newOption('technoUTM_analytics_position', 'footer');
		$this->newOption('technoUTM_item_id', '');
		$this->newOption('technoUTM_item_checked', '');
		$this->newOption('technoUTM_item_last_check', '');
		$this->newOption('technoUTM_databse_version', $this->version);
	}
	
}
}
if(!function_exists( 'techno_utm_doUnInstall' )){
	/**
	 * uninstall function
	 *
	 */
function techno_utm_doUnInstall ()
{
	$this->delOption('technoUTM_enable_adminbar');
	$this->delOption('technoUTM_active_posts');
	$this->delOption('technoUTM_active_pages');
	$this->delOption('technoUTM_default_utm_source');
	$this->delOption('technoUTM_default_utm_medium');
	$this->delOption('technoUTM_default_utm_campaign');
	$this->delOption('technoUTM_default_utm_term');
	$this->delOption('technoUTM_default_utm_content');
	$this->delOption('technoUTM_install_analytics');
	$this->delOption('technoUTM_analytics_id');
	$this->delOption('technoUTM_analytics_position');
	$this->delOption('technoUTM_item_id');
	$this->delOption('technoUTM_item_checked');
	$this->delOption('technoUTM_item_last_check');
	$this->delOption('technoUTM_databse_version');
}
}
?>