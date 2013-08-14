<?php
/*
Plugin Name: Domaintools Whois Plugin
Plugin URI: http://www.domaintools.com/whois-applications/wordpress-plugin/
Author: domaintools
Author URI: http://domaintools.com
Description: Inserts link to whois page for domains found on wordpress articles.
Version: 1.1
*/
require_once(dirname(__FILE__) . '/php/idna_convert.class.php');
require_once(dirname(__FILE__) . '/php/simple_html_dom.php');
if (!class_exists("DomaintoolsPlugin"))
{
    class DomaintoolsPlugin
    {
        var $adminOptionsName = "DomaintoolsPluginAdminOptions";
        var $domain_pattern = '/(?<=[^\p{L}0-9-\.\/_@$]|^)(([\p{L}0-9\-]+\.){1,2}[\p{L}0-9\-]+)(?=\.$|\.[^\p{L}0-9-\.]|[^\p{L}0-9-\.\/]|$)/ui';
        var $bad_tld_pattern = '/\.[0-9]+$|\.txt$|\.[a-z]$/ui';
        var $content = '';
        var $linkTarget = '_blank';
        var $linkedDomains;
        var $pluginOptions;
        var $bubbleIcon;
        
        function __construct()
        {
            //constructor
            $this->idna_converter = new idna_convert();
        }
        
        function initPlugin()
        {
            //get Settings panel option
            $this->getAdminOptions();
        }

        function loadLoopEndCode()
        {

            //write div that will be used by tooltips only once all posts are loaded
            echo '<div id="tooltip_div" style="overflow: hidden; border: 0; padding: 0 0 0 0; margin: 0 0 0 0; text-align: center; display: none;"></div>' . "\n";
            echo '<script language="javascript" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/domaintools-whois-plugin/js/cluetip.init.js"></script>' . "\n";
            $options = $this->getAdminOptions();
            if($options["link_a_tag_whois"] == "yes")
            {
                //echo '<script language="javascript" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/domaintools-whois-plugin/js/legacylinks.js"></script>' . "\n";
                //echo '<script language="javascript">LegacyLinks("' . $options["show_tooltip"] . '","' . $options["window_type"] . '","' . get_bloginfo('wpurl') . '/wp-content/plugins/domaintools-whois-plugin/images/icon.gif"); </script>' . "\n";
            }
        }

        function loadHeaderCode()
        {
            if (!is_admin())
            {
                echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/domaintools-whois-plugin/css/tooltip.css" />' . "\n";
            }
        }

        function loadJSFiles()
        {
            if (function_exists('wp_enqueue_script'))
            {
                //do not load if admin page
                if (!is_admin())
                {
                    $options = $this->getAdminOptions();
                }
            }
        }
        
        function getAdminOptions() 
        {
            $DomaintoolsAdminOptions = array('link_a_tag_whois' => 'yes',
                                             'link_unique_domains' => 'no',
                                             'link_domain' => 'yes',
                                             'show_tooltip' => 'yes', 
                                             'window_type' => 'new', 
                                             'data_dates' => 'yes',
                                             'data_registrant' => 'yes',
                                             'data_ip' => 'yes',
                                             'data_tlds' => 'yes');
            $pluginOptions = get_option($this->adminOptionsName);
            if (!empty($pluginOptions)) 
            {
                foreach ($pluginOptions as $key => $option)
                $DomaintoolsAdminOptions[$key] = $option;
            }               
            update_option($this->adminOptionsName, $DomaintoolsAdminOptions);
            return $DomaintoolsAdminOptions;
        }
        
        function printAdminPage() 
        {
            $pluginOptions = $this->getAdminOptions();
            if (isset($_POST['update_dt_settings'])) 
            { 
                if (isset($_POST['dt_link_unique_domains'])) 
                {
                    $pluginOptions['link_unique_domains'] = $_POST['dt_link_unique_domains'];
                }   
                if (isset($_POST['dt_link_a_tag_whois'])) 
                {
                    $pluginOptions['link_a_tag_whois'] = $_POST['dt_link_a_tag_whois'];
                }   
                if (isset($_POST['dt_link_domain']))
                {
                    $pluginOptions['link_domain'] = $_POST['dt_link_domain'];
                }
                if (isset($_POST['dt_show_tooltip']))
                {
                    $pluginOptions['show_tooltip'] = $_POST['dt_show_tooltip'];
                }
                if (isset($_POST['dt_window_type'])) 
                {
                    $pluginOptions['window_type'] = $_POST['dt_window_type'];
                }   
                if (isset($_POST['dt_data_dates'])) 
                {
                    $pluginOptions['data_dates'] = $_POST['dt_data_dates'];
                }   
                if (isset($_POST['dt_data_registrant'])) 
                {
                    $pluginOptions['data_registrant'] = $_POST['dt_data_registrant'];
                }
                if (isset($_POST['dt_data_ip'])) 
                {
                    $pluginOptions['data_ip'] = $_POST['dt_data_ip'];
                }
                if (isset($_POST['dt_data_tlds'])) 
                {
                    $pluginOptions['data_tlds'] = $_POST['dt_data_tlds'];
                }
                update_option($this->adminOptionsName, $pluginOptions);
                ?>
                <div class="updated"><p><strong><?php _e("Settings Updated.", "DomaintoolsPlugin");?></strong></p></div>
                <?php
            }?>
<div class=wrap>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<h2>Domaintools Settings Panel</h2>

<h3>Only link first occurrence of domain?</h3>
<p>Selecting "Yes" will only link the first occurrence of each domain in the post.</p>
<p><label for="dt_link_unique_domains_yes"><input type="radio" id="dt_link_unique_domains_yes" name="dt_link_unique_domains" value="yes" 
<?php if ($pluginOptions['link_unique_domains'] == "yes") { _e('checked="checked"', "DomaintoolsPlugin"); }?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="dt_link_unique_domains_no"><input type="radio" id="dt_link_unique_domains_no" name="dt_link_unique_domains" value="no" 
<?php if ($pluginOptions['link_unique_domains'] == "no") { _e('checked="checked"', "DomaintoolsPlugin"); }?>/> No</label></p>

<h3>Choose link target window:</h3>
<p><label for="dt_window_type_new"><input type="radio" id="dt_window_type_new" name="dt_window_type" value="new" 
<?php if ($pluginOptions['window_type'] == "new") { _e('checked="checked"', "DomaintoolsPlugin"); }?>/> New Window</label>&nbsp;&nbsp;&nbsp;&nbsp;
<label for="dt_window_type_same"><input type="radio" id="dt_window_type_same" name="dt_window_type" value="same" 
<?php if ($pluginOptions['window_type'] == "same") { _e('checked="checked"', "DomaintoolsPlugin"); }?>/> Same Window</label></p>

<h3>Show Domaintools domain flyout data?</h3>
<p>Selecting "No" will disable the flyout window but maintain the link to the whois page.</p>
<p><label for="dt_show_tooltip_yes"><input type="radio" id="dt_show_tooltip_yes" name="dt_show_tooltip" value="yes" 
<?php if ($pluginOptions['show_tooltip'] == "yes") { _e('checked="checked"', "DomaintoolsPlugin"); }?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="dt_show_tooltip_no"><input type="radio" id="dt_show_tooltip_no" name="dt_show_tooltip" value="no" 
<?php if ($pluginOptions['show_tooltip'] == "no") { _e('checked="checked"', "DomaintoolsPlugin"); }?>/> No</label></p>

<h3>Replace domain name text with link to whois page?</h3>
<p><label for="dt_link_domain_yes"><input type="radio" id="dt_link_domain_yes" name="dt_link_domain" value="yes" 
<?php if ($pluginOptions['link_domain'] == "yes") { _e('checked="checked"', "DomaintoolsPlugin"); }?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="dt_link_domain_no"><input type="radio" id="dt_link_domain_no" name="dt_link_domain" value="no" 
<?php if ($pluginOptions['link_domain'] == "no") { _e('checked="checked"', "DomaintoolsPlugin"); }?>/> No</label></p>

<h3>Apply these settings to links that already point to the whois pages?</h3>
<p>Selecting "No" will NOT change any links that point to whois.domaintools.com.</p>
<p><label for="dt_link_a_tag_whois_yes"><input type="radio" id="dt_link_a_tag_whois_yes" name="dt_link_a_tag_whois" value="yes" 
<?php if ($pluginOptions['link_a_tag_whois'] == "yes") { _e('checked="checked"', "DomaintoolsPlugin"); }?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label for="dt_link_a_tag_whois_no"><input type="radio" id="dt_link_a_tag_whois_no" name="dt_link_a_tag_whois" value="no" 
<?php if ($pluginOptions['link_a_tag_whois'] == "no") { _e('checked="checked"', "DomaintoolsPlugin"); }?>/> No</label></p>

<div class="submit">
<input type="submit" name="update_dt_settings" value="<?php _e('Update Settings', 'DomaintoolsPlugin') ?>" /></div>
</form>
 </div>

<?php
        } //end of printAdminPage() 
        
        function preg_replace_cb($matches)
        {
            //make sure tld is not all digits (basically not to detect $100.00 as a domain)
            if(preg_match($this->bad_tld_pattern, $matches[1]))
            {
                return($matches[0]);
            }
            $domain = strtolower($this->idna_converter->encode($matches[1]));
            $tooltip_url = "http://tooltips.domaintools.com/preview/v1.0/-/" . $domain . "/";

            if ($this->pluginOptions['show_tooltip'] == "yes" && $this->pluginOptions['link_domain'] == "yes")
            {
                if ( ($this->pluginOptions['link_unique_domains'] == 'no') || ( !isset($this->linkedDomains[$domain])) )
                {
                    $this->linkedDomains[$domain] = 1;
                    return('<a class="dlink" title="whois ' . $matches[1] . '" href="http://whois.domaintools.com/' . $domain . '" target="' . $this->linkTarget . '">' . $matches[1] . '</a>' . '<a class="tooltip" title="whois ' . $domain . '" data-url="' . $tooltip_url . '" rel="#tooltip_div" target="' . $this->linkTarget . '" href="http://whois.domaintools.com/' . $domain . '" target="' . $this->linkTarget . '"><img style="margin-left: 5px;" src="' . $this->bubbleIcon . '" alt="' . $matches[1] . '"/></a>');
                }
                else
                {
                    return($matches[0]);
                }
            }
            else if ($this->pluginOptions['show_tooltip'] == "yes" && $this->pluginOptions['link_domain'] == "no")
            {
                if ( ($this->pluginOptions['link_unique_domains'] == 'no') || ( !isset($this->linkedDomains[$domain])) )
                {
                    $this->linkedDomains[$domain] = 1;
                    return($matches[1] . '<a class="tooltip" title="whois ' . $matches[1] . '" data-url="' . $tooltip_url . '" rel="#tooltip_div" target="' . $this->linkTarget . '" href="http://whois.domaintools.com/' . $domain . '" target="' . $this->linkTarget . '"><img style="margin-left: 5px;" src="' . $this->bubbleIcon . '" alt="' . $matches[1] . '"/></a>');
                }
                else
                {
                    return($matches[0]);
                }

            }
            else if ($this->pluginOptions['show_tooltip'] == "no" && $this->pluginOptions['link_domain'] == "yes")
            {
                if ( ($this->pluginOptions['link_unique_domains'] == 'no') || ( !isset($this->linkedDomains[$domain])) )
                {
                    $this->linkedDomains[$domain] = 1;
                    return('<a class="dlink" title="whois ' . $matches[1] . '" href="http://whois.domaintools.com/' . $domain . '" target="' . $this->linkTarget . '">' . $matches[1] . '</a>' . '<a class="dlink" title="whois ' . $matches[1] . '" href="http://whois.domaintools.com/' . $domain . '" target="' . $this->linkTarget . '"><img style="margin-left: 5px;" src="' . $this->bubbleIcon . '" alt="' . $matches[1] . '"/></a>');
                }
                else
                {
                    return($matches[0]);
                }
            }
            else
            {
                if ( ($this->pluginOptions['link_unique_domains'] == 'no') || ( !isset($this->linkedDomains[$domain])) )
                {
                    $this->linkedDomains[$domain] = 1;
                    return($matches[1] . '<a class="dlink" title="whois '. $matches[1] . '" href="http://whois.domaintools.com/' . $domain . '" target="' . $this->linkTarget . '"><img style="margin-left: 5px;" src="' . $this->bubbleIcon . '" alt="' . $matches[1] . '"/></a>');
                }
                else
                {
                    return($matches[0]);
                }
            }
        }

        function insert_link_cb($content)
        {
            unset($this->linkedDomains);
            $this->linkedDomains = array();
            $this->pluginOptions = $this->getAdminOptions();
            $this->bubbleIcon = get_bloginfo('wpurl') . '/wp-content/plugins/domaintools-whois-plugin/images/icon.gif';
            if ($this->pluginOptions['window_type'] == 'new')
            {
                $this->linkTarget = '_blank';
            }
            else
            {
                $this->linkTarget = '_self';
            }
            $html = str_get_html($content);
            foreach($html->find('text') as $t)
            {
                $t->innertext = preg_replace_callback($this->domain_pattern, array(&$this, 'preg_replace_cb'), $t->innertext);
            }
            $ret = $html->save();
            $html->clear();
            unset($html);
            return($ret);
        }   
    }
} //End Class DomaintoolsPlugin

if (class_exists("DomaintoolsPlugin"))
{
    $DT_Plugin = new DomaintoolsPlugin();
}

//Initialize the admin panel
if (!function_exists("DomaintoolsPlugin_ap"))
{
    function DomainToolsPlugin_ap()
    {
        global $DT_Plugin;
        if (!isset($DT_Plugin))
        {
            return;
        }
        if (function_exists('add_options_page'))
        {
            add_options_page('Domaintools', 'Domaintools', 9, basename(__FILE__), array(&$DT_Plugin, 'printAdminPage'));
        }
    }
}
//Setup Action and filters
if (isset($DT_Plugin))
{
    //Actions
    add_action('activate_domaintools-whois-plugin/domaintools-whois-plugin.php', array(&$DT_Plugin, 'initPlugin'));
    add_action('admin_menu', 'DomaintoolsPlugin_ap');
    add_action('init', array(&$DT_Plugin, 'loadJSFiles'));
    add_action('loop_end', array(&$DT_Plugin, 'loadLoopEndCode'));
    add_action('wp_head', array(&$DT_Plugin, 'loadHeaderCode'));
    
    //Filters
    add_filter('the_content', array(&$DT_Plugin, 'insert_link_cb'));
    add_filter('comment_text', array(&$DT_Plugin, 'insert_link_cb'));
}
?>
