<?php 
/**
 * Plugin Name: UTM Generator
 * Plugin URI: http://tch-utm-generator.technoyer.com
 * Description: Build and generate the UTM URLs for the campaigns. It is an easy tool to build new UTM URL in just one second.
 * Author: Technoyer Solutions Ltd.
 * Author URI: http://technoyer.com
 * Version: 1.0
 * License: GPL V3
 */
#error_reporting(E_ALL);

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

//Defination
if(!defined( 'TECHNOUTM_SLUG' )){define( 'TECHNOUTM_SLUG' , 'tch-utm-generator' );}
if(!defined( 'TECHNOUTM_PLUGINNAME' )){define( 'TECHNOUTM_PLUGINNAME' , 'UTM Generator' );}
if(!defined( 'TECHNOUTM_VERSION' )){define( 'TECHNOUTM_VERSION' , '1.0' );}
if(!defined( 'TECHNOUTM_PATH' )){define( 'TECHNOUTM_PATH' , __FILE__ );}
if(!defined( 'TECHNOUTM_DIR' )){define( 'TECHNOUTM_DIR' , dirname(__FILE__) );}
if(!defined( 'TECHNOUTM_PLUGINPREFIX' )){define( 'TECHNOUTM_PLUGINPREFIX' , 'technoUTM' );}
if(!defined( 'TECHNOUTM_TRANS' )){define( 'TECHNOUTM_TRANS' , 'technoUTM' );}
if(!defined( 'TECHNOUTM_PROTEXT')){define( 'TECHNOUTM_PROTEXT' , "<span style='border:solid 1px #D26464;color:#D26464;padding:2px;font-size:0.8em'>Pro Only</span>");}
if(!defined( 'TECHNOUTM_PROACTION')){define( 'TECHNOUTM_PROACTION' , "make-alert");}

//Minimum PHP Required!
if(is_admin())
{
	if (version_compare(PHP_VERSION, '5.4.0', '<')) {
	  throw new Exception(TECHNOUTM_PLUGINNAME.' v'.TECHNOUTM_VERSION.' requires PHP version 5.4 or higher.');
	}
}

//Include Files
include ( 'includes/class.technoUTM.install.php' );
include ( 'includes/class.technoUTM.utms.php' );
include ( 'includes/class.technoUTM.main.php' );
include ( 'includes/functions.php' );

//Run Plugin
if ( !function_exists( 'technoUTMRun' ) )
{
	function technoUTMRun ()
	{
		if( class_exists( 'technoUTM' ))
		{
			return technoUTM::instance();
		}
	}
	$GLOBALS['technoUTM'] = technoUTMRun();
}


?>