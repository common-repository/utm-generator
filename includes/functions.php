<?php 
/**
 * Functions will be required in all the plugin files
 * All of it written by Technoyer @2017
 */
if (!defined('ABSPATH')){exit; // Exit if get it directly!
}

/**
 * To clean the string before pass it.
 * 
 * @param $str the string
 */
if (!function_exists('technoUTM_xss_clean'))
{
	function technoUTM_xss_clean ($str)
	{
		// Wordpress ESCAPES
		$str = esc_html($str);
		$str = esc_attr($str);
		// END Wordpress ESCAPES
		
		$str = preg_replace('/\0+/', '', $str);
        $str = preg_replace('/(\\\\0)+/', '', $str);
        #$str = preg_replace('#(&\#*\w+)[\x00-\x20]+;#u',"\\1;",$str);
        #$str = preg_replace('#(&\#x*)([0-9A-F]+);*#iu',"\\1\\2;",$str);
        #$str = preg_replace("/%u0([a-z0-9]{3})/i", "&#x\\1;", $str);
       # $str = preg_replace("/%([a-z0-9]{2})/i", "&#x\\1;", $str);        
        
        $str = preg_replace("#\t+#", " ", $str);
        $str = str_replace(array('<?php', '<?PHP', '<?', '?>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
        $words = array('javascript', 'vbscript', 'script', 'applet', 'alert', 'document', 'write', 'cookie', 'window');
        foreach ($words as $word) {
            $temp = '';
            for ($i = 0; $i < strlen($word); $i++) {
                $temp .= substr($word, $i, 1)."\s*";
            }
            $temp = substr($temp, 0, -3);
            $str = preg_replace('#'.$temp.'#s', $word, $str);
            $str = preg_replace('#'.ucfirst($temp).'#s', ucfirst($word), $str);
        }

         $str = preg_replace("#<a.+?href=.*?(alert\(|alert&\#40;|javascript\:|window\.|document\.|\.cookie|<script|<xss).*?\>.*?</a>#si", "", $str);
         $str = preg_replace("#<img.+?src=.*?(alert\(|alert&\#40;|javascript\:|window\.|document\.|\.cookie|<script|<xss).*?\>#si", "", $str);
         $str = preg_replace("#<(script|xss).*?\>#si", "", $str);
         $str = preg_replace('#</*(onblur|onchange|onclick|onfocus|onload|onmouseover|onmouseup|onmousedown|onselect|onsubmit|onunload|onkeypress|onkeydown|onkeyup|onresize)[^>]*>#iU',"\\1>",$str);
        $str = preg_replace('#<(/*\s*)(alert|applet|basefont|base|behavior|bgsound|blink|body|expression|form|frameset|frame|head|html|ilayer|iframe|input|layer|link|meta|plaintext|style|script|textarea|title|xml|xss)([^>]*)>#is', "&lt;\\1\\2\\3&gt;", $str);
        $str = preg_replace('#(alert|cmd|passthru|eval|exec|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);
        $bad = array(

                        'document.cookie'    => '',

                        'document.write'    => '',

                        'window.location'    => '',

                        "javascript\s*:"    => '',

                        "Redirect\s+302"    => '',

                    );
        foreach ($bad as $key => $val) {
            $str = preg_replace("#".$key."#i", $val, $str);   
        }

        $str = str_replace('<iframe', '', $str);
        $str = str_replace('</scr', '', $str);
        $str = str_replace('alert(', '', $str);
#        $str = addslashes($str);
        return $str;
	}
}
if(!function_exists('technoUTM_selfURL')){
	/**
	 * to get the current URL
	 *
	 * @return string
	 */
function technoUTM_selfURL() 
{ 
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; 
	$protocol = technoUTM_strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s; 
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
	return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']; 
}
}
if(!function_exists('technoUTM_strleft')){
	/**
	 * to fint and cut string
	 *
	 * @param 1st String $s1
	 * @param 2nd String $s2
	 * @return output
	 */
function technoUTM_strleft($s1, $s2) 
{ 
	return substr($s1, 0, strpos($s1, $s2)); 
}
}
if(!function_exists('technoUTM_getDomainUrl')){
	/**
	 * Get domain name from URL
	 *
	 * @param full link $url
	 * @return string the domain name with ltd
	 */
function technoUTM_getDomainUrl($url)
{
	$domain= preg_replace(
	array(
	'~^https?\://~si' ,// strip protocol
	'~[/:#?;%&].*~',// strip port, path, query, anchor, etc
	'~\.$~',// trailing period
	),
	'',$url);
	
	if(preg_match('#^www.(.*)#i',$domain))
	{
	$domain=preg_replace('#www.#i','',$domain);
	}
	return $domain;
}
}

if(!function_exists( 'technoUTM__cURL' )){
	/**
	 * CURL function
	 *
	 * @param string $url
	 * @return json
	 */
function technoUTM__cURL ( $url , $args = false)
{
	return wp_remote_get( $url , $args );
}
}
?>