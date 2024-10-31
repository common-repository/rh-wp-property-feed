<?php
/*
Plugin Name: WP Property Feed Real Homes Theme Connector
 Plugin URI: http://www.wppropertyfeed.co.uk/
 Description: This plugin will take your property feed using WP Property Feed Plugin and map the feed to the Real Homes / Real Places properties.
 Version: 1.53
 Author: Ultimateweb Ltd
 Author URI: http://www.ultimateweb.co.uk
 Text Domain: wppf
 License: GPL2
*/

/*  Copyright 2021  Ian Scotland, Ultimateweb Ltd  (email : info@ultimateweb.co.uk)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

error_reporting(E_ALL);
ini_set('display_errors', '1');
*/

defined('ABSPATH') or die("No script kiddies please!");
$wppf_rh_version = '1.53';

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
register_activation_hook( __FILE__, 'wppf_rh_install');
register_deactivation_hook( __FILE__, 'wppf_rh_uninstall' );

add_action('init', 'wppf_rh_init',1);
add_action('admin_init', 'wppf_rh_admin_init');
add_action('wppf_after_admin_init', 'wppf_rh_admin_init');
add_action('wppf_settings_tabs','wppf_rh_tab');
add_action('wppf_settings_sections','wppf_rh_section');
add_action('admin_notices', 'wppf_rh_notice_realhomes');
add_action('wppf_after_settings_updated', 'wppf_rh_settings_updated', 101, 1);
add_action('wppf_rh_incremental','wppf_rh_populate');
add_action('wppf_register_scripts', 'wppf_rh_register_scripts');

function wppf_rh_install() {
    wp_clear_scheduled_hook('wppf_rh_incremental');
    wp_schedule_event(time() + 60, 'quarterhourly', 'wppf_rh_incremental');
}

function wppf_rh_uninstall() {
    delete_option("wppf_theme");
}

function wppf_rh_init() {
    //are the theme and plugin active
    if (is_plugin_active('wp-property-feed/wppf_properties.php')) {
        add_action('admin_menu', 'wppf_rh_admin_menu');
        add_filter('wppf_filter_slug', function($title) { return "wppf-property";});
        update_option("wppf_theme", "RealHomes");
        if (!wp_get_schedule('wppf_rh_incremental')) wp_schedule_event(time() + 60, 'quarterhourly', 'wppf_rh_incremental');  //re-instate the realhomes schedule in case it drops away!
        
        //additional fields
        $fields = get_option('inspiry_property_additional_fields');
        $createfields = array('Tenure','Council Tax Band');
        if (is_array($fields) && array_key_exists('inspiry_additional_fields_list',$fields)) {
            foreach($fields['inspiry_additional_fields_list'] as $field) {
                $key = array_search($field['field_name'],$createfields);
                if ($key !== FALSE) unset($createfields[$key]);
            }
        }
        foreach($createfields as $field) {
            $fields['inspiry_additional_fields_list'][] = array('field_name' => $field, 'field_type' => 'text', 'field_display' => array('single'));
        } 
        update_option('inspiry_property_additional_fields',$fields);
    } else {
        delete_option("wppf_theme");
    }
}

function wppf_rh_notice_realhomes() {
    if (!is_plugin_active('wp-property-feed/wppf_properties.php')) {
        echo "<div class='notice notice-error'><p><img align='left' src='".plugins_url('wppf.png', __FILE__)."' alt='WP Property Feed' style='margin: -10px 10px -1px -10px' /> <strong>WPPF Realhomes/Realplaces</strong><br>The Real Homes & Real Places WP Property Theme plugin is currently inactive as it requires the WP Property Feed plugin to be active.</p><div style='clear:both'></div></div>";
    }
}

function wppf_rh_register_scripts() {
    wp_dequeue_script('google-maps');
}

function wppf_rh_admin_menu() {
    //check for theme and plugin and show notice it they do not exist and are not active
    if (is_plugin_active('wp-property-feed/wppf_properties.php')) remove_menu_page('edit.php?post_type=wppf_property');
}

function wppf_rh_tab() {
    echo "<a href='#realhomes' class='nav-tab' style='width: 80%'>Realhomes/Realplaces</a>";
}

function wppf_rh_section() {
    echo "<div class='wppf_tab_section' id='realhomes' style='display:none'>";
    do_settings_sections('wppf_rh_feed');
    $wppf_rh_propertycount = get_option("wppf_rh_propertycount");
    if (!empty($wppf_rh_propertycount)) {
        echo 'There are currently ' . $wppf_rh_propertycount.' feed properties listed in the theme.<br /><br />';
        echo '<strong>Last 10 Updates:</strong><br /><table border="0">';
        if ($logs = get_option('wppf_rh_log')) {
            foreach($logs as $log) {?>
                <tr>
                    <td><?php echo $log['logdate']; ?></td>
                    <td>&nbsp;&nbsp;&nbsp;updated: <?php echo $log['updated']; ?></td>
                    <td>&nbsp;&nbsp;&nbsp;deleted: <?php echo $log['deleted']; ?></td>
                    <td>&nbsp;&nbsp;&nbsp;total: <?php echo $log['totalproperties']; ?></td>
                </tr>
            <?php
            }
        }
        echo '</table><br /><br />';
    }
    echo '</div>';
}

function wppf_rh_admin_init(){
    add_settings_section('wppf_main', 'Realhomes / RealPlaces Settings', 'wppf_rh_section_text', 'wppf_rh_feed');
    add_settings_field('wppf_rh_filter', 'Properties Filtering', 'wppf_rh_setting_filter', 'wppf_rh_feed', 'wppf_main');
    add_settings_field('wppf_fh_webstatus', 'Property Status (Category) Option', 'wppf_rh_setting_status', 'wppf_rh_feed', 'wppf_main');
    add_settings_field('wppf_fh_epc_floorplan', 'Show EPCs and Floorplans in gallery', 'wppf_rh_setting_epc_floorplan', 'wppf_rh_feed', 'wppf_main');
    add_settings_field('wppf_fh_lettype', 'Hide Let type', 'wppf_rh_setting_lettype', 'wppf_rh_feed', 'wppf_main');
    add_settings_field('wppf_fh_refresh', 'Update Realhomes / RealPlaces properties now', 'wppf_rh_setting_refresh', 'wppf_rh_feed', 'wppf_main');
}

function wppf_rh_section_text() {
    echo '<p>Congratulations you and now set up up and the property feed plugin will automatically feed properties into the RealHomes / RealPlaces theme every hour.</p>';
}

function wppf_rh_setting_refresh() {
    echo "<input type='checkbox' name='wppf_options[realhomes_refresh]' value='1' /> tick to refresh the property data on save (please be patient it can take a while!)";
}

function wppf_rh_setting_lettype() {
    $options = get_option('wppf_options');
    if (empty($options['hide_lettype']))
        echo "<input type='checkbox' name='wppf_options[hide_lettype]' value='1' />";
    else
        echo "<input type='checkbox' name='wppf_options[hide_lettype]' value='1' checked='checked' />";
}

function wppf_rh_setting_epc_floorplan() {
    $options = get_option('wppf_options');
    if (empty($options['epc_floorplan'])) $options['epc_floorplan']='none';
    echo "<select name='wppf_options[epc_floorplan]'><option value='none'>Do not show EPCs and Floorplans in gallery</option>";
    if ($options['epc_floorplan'] == 'epcs')
        echo "<option value='epcs' selected='selected'>Show EPCs in gallery</option>";
    else
        echo "<option value='epcs'>Show EPCs in gallery</option>";
    if ($options['epc_floorplan'] == 'floorplans')
        echo "<option value='floorplans' selected='selected'>Show Floorplans in gallery</option>";
    else
        echo "<option value='floorplans'>Show Floorplans in gallery</option>";
    if ($options['epc_floorplan'] == 'both')
        echo "<option value='both' selected='selected'>Show EPCs and Floorplans in gallery</option>";
    else
        echo "<option value='both'>Show EPCs and Floorplans in gallery</option>";
    echo "</select> (Note: EPCs and Floorplans will normally show as attachments in the property details in this theme)";
}

function wppf_rh_setting_filter() {
    $options = get_option('wppf_options');
    if (empty($options['feed_filter'])) $options['feed_filter']='none';
    echo "<select name='wppf_options[feed_filter]'><option value='none'>No filter include all properties</option>";
    if ($options['feed_filter'] == 'unsold')
        echo "<option value='unsold' selected='selected'>Only advertised and SSTC/Let Agreed properties</option>";
    else
        echo "<option value='unsold'>Only advertised and SSTC/Let Agreed properties</option>";
    if ($options['feed_filter'] == 'advertised')
        echo "<option value='adverised' selected='selected'>Only advertised properties (exclude SSTC/Let Agreed/Sold/Let)</option>";
    else
        echo "<option value='advertised'>Only advertised properties (exclude SSTC/Let Agreed/Sold/Let)</option>";
    echo "</select>";
}

function wppf_rh_setting_status() {
    $options = get_option('wppf_options');
    if (empty($options['status_display'])) $options['status_display']='category';
    echo "<select name='wppf_options[status_display]'><option value='category'>Use the main property category Only (i.e. To Rent, To Buy, Commercial)</option>";
    if ($options['status_display'] == 'status')
        echo "<option value='status' selected='selected'>Use the property status only (i.e. For Sale, Sold, To Let, Let Agreed)</option>";
    else
        echo "<option value='status'>Use the property status only (i.e. For Sale, Sold, To Let, Let Agreed)</option>";
    if ($options['status_display'] == 'both')
        echo "<option value='both' selected='selected'>Use both property cateogry and status (e.g. For Sale - SSTC)</option>";
    else
        echo "<option value='both'>Use both property cateogry and status (e.g. For Sale - SSTC)</option>";

    echo "</select>";
}

function wppf_rh_settings_updated() {
    $options = get_option('wppf_options');
    if (isset($options['realhomes_refresh'])) {
        wppf_rh_populate();
    }
}

function wppf_rh_purge() {
    $rhposts = get_posts(array('post_type' => 'property', 'numberposts' => -1, 'meta_key' => 'wppf', 'meta_value' => '1'));
    foreach ($rhposts as $post) {
        wp_delete_post($post->ID, true);
    }
}
add_action("wppf_purge","wppf_rh_purge");

function wppf_rh_populate() {
    //set_time_limit(300);
    //ini_set('max_execution_time', 300);
    $options = get_option('wppf_options');
    $options['epc_floorplan'] = (isset($options['epc_floorplan'])) ? $options['epc_floorplan'] : "none";


    //fix for older version of the wppf plugin
    $tax_prefix=(floatval(explode(".",get_option('wppf_version'))[1]) > 50) ? "wppf_": "";

    //clear the image log
    update_option("wppf_rh_imagelog", null);

    //check to see if this has timed out from a previous attempt
    $check = get_posts(array('post_type' => 'property', 'numberposts' => -1, 'meta_query' => array('relation' => 'AND', array('key' => 'wppf', 'meta_value' => '1'),array('key' => 'remove', 'value' => true))));
    if (count($check)==0) {
        //mark all properties to delete (we will un-mark any that are in the feed) - only do this if we have successfully connected to the API, hence it is here
        $rhposts = get_posts(array('post_type' => 'property', 'numberposts' => -1, 'meta_key' => 'wppf', 'meta_value' => '1'));
        foreach ($rhposts as $post) {
            update_post_meta($post->ID,'remove',true);
        }
    }

    //Update agents
    $terms = get_terms(array('taxonomy' => $tax_prefix.'branch'));
    foreach ($terms as $branch) {
        $postid = 0;
        $rhposts = get_posts(array('post_type' => 'agent', 'post_status' => array('publish', 'private'), 'numberposts' => -1, 'meta_key' => 'wppf_slug', 'meta_value' => $branch->slug));
        if (empty($rhposts)) {
            $agent = array('ID' => $postid,
                'post_title' => $branch->name,
                'post_status' => 'publish',
                'post_type' => 'agent');
            $postid = wp_insert_post($agent);
            $phone = get_term_meta($branch->ID, 'phone');
            $email = get_term_meta($branch->ID, 'email');
            if (!empty($phone)) add_post_meta($postid,'REAL_HOMES_office_number',$phone,true);
            if (!empty($email)) add_post_meta($postid,'REAL_HOMES_agent_email',$email,true);
            add_post_meta($postid,'wppf_slug',$branch->slug,true);
        }
    }

    //Main update loop
    $updated = 0;
    $wposts = get_posts(array('post_type' => 'wppf_property', 'numberposts' => -1));
    foreach ($wposts as $property) {

        $process_property = true;
        $web_status = get_post_meta($property->ID,'web_status',true);
        if ($options['feed_filter']=="unsold" && ($web_status=='Let' || $web_status=='Sold')) $process_property = false;
        if ($options['feed_filter']=="advertised" && ($web_status=='Let' || $web_status=='Sold' || $web_status=='Let Agreed' || $web_status=='SSTC')) $process_property = false;
        if ($process_property) {

            $postid = 0; $isNew = false; $changed=true; $nothumb = true;
            $wppfid = get_post_meta($property->ID,'wppf_id',true);
            $rhposts = get_posts(array('post_type' => 'property', 'numberposts' => -1, 'meta_key' => 'wppf_id', 'meta_value' => $wppfid));
            if (!empty($rhposts)) {
                $postid = $rhposts[0]->ID;
                $plast = get_post_meta($property->ID,'updated',true);  //date the feed property was last updated
                $tlast = get_post_meta($postid,'updated',true); //date the realhomes property was last updated
                if (!empty($plast) && !empty($tlast)) {
                    if (strtotime($plast) < strtotime($tlast)) {
                        $changed = false;
                        update_post_meta($postid,'remove',false);
                    }
                }
                if (!get_post_meta($postid,'remove',true)) $changed = false;
            }

            if ($postid == 0) $isNew = true;

            if ($changed || $isNew) {
                $postcontent = $property->post_content;
                $paragraphs = wppf_get_paragraphs($property->ID);
                if (!empty($paragraphs) && get_post_meta($property->ID,'feed',true)!="letmc") {
                    foreach($paragraphs as $paragraph) {
                        if ($paragraph['name']!='') $postcontent .= "<h4>".$paragraph['name']."</h4>";
                        $postcontent.="<p>";
                        if ($paragraph['dimensions']!="") $postcontent .= "<em>".$paragraph['dimensions']."</em><br />";
                        $postcontent.=$paragraph['description']."</p>";
                    }
                }
                if (get_post_meta($property->ID,'tenure',true)!='') {
                    update_post_meta($postid,'inspiry_tenure',get_post_meta($property->ID,'tenure',true));
                    update_post_meta($postid,'tenure',get_post_meta($property->ID,'tenure',true));
                    $postcontent .= "<p>Tenure: ".get_post_meta($property->ID,'tenure',true)."</p>";
                } 
                if (get_post_meta($property->ID,'counciltaxband',true)!='') {
                    update_post_meta($postid,'inspiry_council_tax_band',get_post_meta($property->ID,'counciltaxband',true));
                    $postcontent .= "<p>Council Tax Band: ".get_post_meta($property->ID,'counciltaxband',true)."</p>";
                } 

                //set up key datafields
                $status_display = $options['status_display'];
                if (empty($status_display)) $status_display = "category";
                $proptitle = $property->post_title;
                if ($status_display != "status") {
                    $cstat = get_post_meta($property->ID,'web_status',true);
                    if ($cstat == "For Sale" || $cstat == "To Let") {
                        //do nothing
                    } else {
                        $proptitle .= " - ".$cstat;
                    }
                }

                $prop = array('ID' => $postid,
                    'post_title' => $proptitle,
                    'post_excerpt' => $property->post_content,
                    'post_content' => $postcontent,
                    'post_status' => 'publish',
                    'post_type' => 'property',
                    'post_date' => date('Y-m-d',strtotime(get_post_meta($property->ID,'uploaded',true))),
                    'post_modified' => date('Y-m-d',strtotime(get_post_meta($property->ID,'updated',true))));
                $postid = wp_insert_post($prop);
                update_post_meta($postid,'wppf','1');
                update_post_meta($postid,'wppf_id',$wppfid);
                update_post_meta($postid,'updated',get_post_meta($property->ID,'updated',true));
                update_post_meta($postid,'remove',false);
                if (get_post_meta($property->ID,'price_display',true)=='0' || get_post_meta($property->ID,'price_qualifier',true)=='POA') {
                    update_post_meta($postid,'REAL_HOMES_property_price','');
                    update_post_meta($postid,'REAL_HOMES_property_price_postfix',get_post_meta($property->ID,'price_qualifier',true));
                } else {
                    update_post_meta($postid,'REAL_HOMES_property_price_prefix',get_post_meta($property->ID,'price_qualifier',true));
                    update_post_meta($postid,'REAL_HOMES_property_price',get_post_meta($property->ID,'price',true));
                    update_post_meta($postid,'REAL_HOMES_property_price_postfix',get_post_meta($property->ID,'price_postfix',true));
                }
                $propertyarea_sqft = get_post_meta($property->ID,'propertyarea_sqft',true);
                if (!empty($propertyarea_sqft)) {
                    update_post_meta($postid,'REAL_HOMES_property_size',$propertyarea_sqft);
                    update_post_meta($postid,'REAL_HOMES_property_size_postfix','sqft');
                }
                update_post_meta($postid,'REAL_HOMES_property_bedrooms',get_post_meta($property->ID,'bedrooms',true));
                update_post_meta($postid,'REAL_HOMES_property_receptions',get_post_meta($property->ID,'receptions',true));
                update_post_meta($postid,'REAL_HOMES_property_bathrooms',get_post_meta($property->ID,'bathrooms',true));
                update_post_meta($postid,'REAL_HOMES_property_id',get_post_meta($property->ID,'agentref',true));
                update_post_meta($postid,'REAL_HOMES_property_map','0');
                update_post_meta($postid,'REAL_HOMES_property_address',wppf_get_address($property->ID, ","));
                update_post_meta($postid,'REAL_HOMES_property_location',get_post_meta($property->ID,'latitude',true).",".get_post_meta($property->ID,'longitude',true).",14");
                update_post_meta($postid,'REAL_HOMES_gallery_slider_type','thumb-on-bottom');
                update_post_meta($postid,'REAL_HOMES_agent_display_option','agent_info');
                $feed = get_post_meta($property->ID,'feed',true);
                if ($feed=='vebra' || $feed=='jupix' || $feed=='dezrez') update_post_meta($postid,'REAL_HOMES_featured',get_post_meta($property->ID,'featured',true));
                update_post_meta($postid,'REAL_HOMES_terms_conditions','0');
                update_post_meta($postid,'inspiry_is_published','yes');

                delete_post_meta($postid,'REAL_HOMES_agents'); //clear current agents
                $branches = wp_get_post_terms($property->ID, $tax_prefix."branch");
                foreach ($branches as $branch) {
                    $tbs = get_posts(array('post_type' => 'agent', 'numberposts' => -1, 'meta_key' => 'wppf_slug', 'meta_value' => $branch->slug));
                    foreach($tbs as $tb) {
                        update_post_meta($postid,'REAL_HOMES_agents',$tb->ID);
                    }
                }

                delete_post_meta($postid,'REAL_HOMES_property_images'); //clear property images
                delete_post_meta($postid,'REAL_HOMES_attachments'); //clear property images

                $files = get_post_meta($property->ID,'files');
                $outfiles = array();
                $floorplans = array();
                $videos = array();

                //property thumbnail linking
                $hasthumb = false;
                if (has_post_thumbnail($property->ID)) {
                    $sourcethumbid = get_post_thumbnail_id($property->ID);
                    if (is_numeric($sourcethumbid)) {
                        set_post_thumbnail($postid, $sourcethumbid);
                        $hasthumb = true;
                    }
                }

                if (isset($files[0])) {
                    $ifiles = $files[0];
                    $sfiles = array(); $tfiles = array();
                    foreach ($ifiles as $key => $row)
                    {
                        $tfiles[$key] = (int)$row['filetype'];
                        $sfiles[$key] = (int)$row['sortorder'];
                    }
                    array_multisort($tfiles, SORT_ASC, $sfiles, SORT_ASC, $ifiles);

                    foreach($ifiles as $file) {
                        switch($file['filetype']) {
                            case "0": //picture
                            case "1": //picture map
                                if (!wppf_rh_check_attachment($file['name'], $file['attachmentid'],$postid,$file['sortorder'])) {
                                    if ($aid = wppf_rh_upload_file($file['name'],$file['url'],$postid,$file['sortorder'])) {
                                        $file['attachmentid'] = $aid;
                                    }
                                }
                                add_post_meta($postid,'REAL_HOMES_property_images',$file['attachmentid']);
                                if (!$hasthumb) {
                                    set_post_thumbnail($postid, $file['attachmentid']);
                                    $hasthumb = true;
                                }
                                break;
                            case "2": //floorplans
                                $floorplans[] = array("inspiry_floor_plan_name"=>(($file['name']=="") ? "Floorplan ".(count($floorplans)+1):$file['name']), "inspiry_floor_plan_image"=> $file['url']);
                                if ($options['epc_floorplan']=="both" || $options['epc_floorplan']=="floorplans") {
                                    if (!wppf_rh_check_attachment($file['name'], $file['attachmentid'],$postid,$file['sortorder'])) {
                                        if ($aid = wppf_rh_upload_file($file['name'],$file['url'],$postid,$file['sortorder'])) {
                                            $file['attachmentid'] = $aid;
                                        }
                                    }
                                    add_post_meta($postid,'REAL_HOMES_property_images',$file['attachmentid']);
                                }
                                break;
                            case "3": //360 tour
                                update_post_meta($postid,'REAL_HOMES_360_virtual_tour',$file['url']);
                                break;
                            case "7": //Documents
                            case "9": //EPCs
                                if (!wppf_rh_check_attachment($file['name'], $file['attachmentid'],$postid,$file['sortorder'])) {
                                    if ($aid = wppf_rh_upload_file($file['name'],$file['url'],$postid,$file['sortorder'])) {
                                        $file['attachmentid'] = $aid;
                                    }
                                }
                                add_post_meta($postid,'REAL_HOMES_attachments',$file['attachmentid']);
                                if (($options['epc_floorplan']=="both" || $options['epc_floorplan']=="epcs") && $file['filetype']=="9") add_post_meta($postid,'REAL_HOMES_property_images',$file['attachmentid']);
                                break;
                            case "11": //Video tour
                                $vurl = (strpos($file['url'],"http")===FALSE) ? 'https:'.$file['url'] : $file['url'];
                                $parts = parse_url($vurl);
                                if (strpos($parts['host'],"youtube.com")!==false || strpos($parts['host'],"youtu.be")!==false || strpos($parts['host'],"vimeo")!==false) {
                                    $videos[] = array("inspiry_video_group_title"=>(($file['name']=="") ? "Virtual Tour ".(count($videos)+1) : $file['name']), "inspiry_video_group_url"=> $vurl);
                                } else {
                                    //iframe it
                                    update_post_meta($postid,'REAL_HOMES_360_virtual_tour','<iframe src="'.$file['url'].'" width="600" height="366" frameborder="0"></iframe>');
                                }
                                //update_post_meta($postid,'REAL_HOMES_tour_video_image', get_post_thumbnail_id($postid));
                                break;
                        }
                        $outfiles[] = $file;
                    }
                    update_post_meta($property->ID,'files',$outfiles);
                    if (!empty($videos)) update_post_meta($postid,'inspiry_video_group', $videos);
                    if (!empty($floorplans)) update_post_meta($postid,'inspiry_floor_plans', $floorplans);
                }

                //get property sales areas (For Sale, To Let, etc) and add to property status
                wp_delete_object_term_relationships($postid, "property-status");
                $areas = wp_get_post_terms($property->ID, $tax_prefix."property_area");
                foreach ($areas as $area) {
                    switch($status_display) {
                        case "both":
                            switch(get_post_meta($property->ID,'web_status',true)) {
                                case "SSTC":
                                case "Let Agreed":
                                case "Sold":
                                case "Let":
                                    wp_set_object_terms($postid,$area->name." - ".get_post_meta($property->ID,'web_status',true), "property-status");
                                    break;
                                default:
                                    wp_set_object_terms($postid,$area->name, "property-status");
                                    break;
                            }
                            break;
                        case "status":
                            wp_set_object_terms($postid,get_post_meta($property->ID,'web_status',true), "property-status");
                            break;
                        default:
                            wp_set_object_terms($postid,$area->name, "property-status");
                            break;
                    }
                }

                //get property type (detached, semi, etc) and add to property types
                wp_delete_object_term_relationships($postid, "property-type");
                $ptypes = wp_get_post_terms($property->ID, $tax_prefix."property_type");
                foreach ($ptypes as $ptype) {
                    wp_set_object_terms($postid,$ptype->name,"property-type");
                }

                //get City/locality locations
                wp_delete_object_term_relationships($postid, "property-city");
                //wp_set_object_terms($postid,get_post_meta($property->ID,'address_locality',true),"property-city");
                wp_set_object_terms($postid,get_post_meta($property->ID,'address_town',true),"property-city");
                //wp_set_object_terms($postid,get_post_meta($property->ID,'address_county',true),"property-city");

                //turn bullets into property features
                wp_delete_object_term_relationships($postid, "property-feature");
                $bullets = get_post_meta($property->ID,'bullets',true);
                if (!empty($bullets)) {
                    libxml_use_internal_errors(true);
                    $dom = new DOMDocument;
                    $dom->loadHTML($bullets);
                    $lis = $dom->getElementsByTagName('li');
                    foreach ($lis as $li) {
                        wp_set_object_terms($postid,$li->textContent,"property-feature",true);
                    }
                }

                //add let types
                if (empty($options['hide_lettype'])) {
                    $let_type = get_post_meta($property->ID,'let_type',true);
                    if (!empty($let_type))
                        if ($let_type!="Not Specified") wp_set_object_terms($postid,"Let Type: ".$let_type,"property-feature",true);
                }
                $furnished = get_post_meta($property->ID,'furnished',true);
                if (!empty($furnished))
                    if ($furnished!="Not Specified") wp_set_object_terms($postid,$furnished,"property-feature",true);

                do_action('wppf_rh_property_updated', $postid, $property);
                $updated++;
            }
        }
    }

    //Now remove any properties that were not in the feed
    $deleted = 0;
    $rhposts = get_posts(array('post_type' => 'property', 'numberposts' => -1, 'meta_query' => array('relation' => 'AND', array('key' => 'wppf', 'value' => '1', 'compare' => '='),array('key' => 'remove', 'value' => true, 'compare' => '='))));
    foreach ($rhposts as $post) {
        wppf_purge_post($post->ID);
        $deleted++;
    }


    $property_count = 0;
    //update total count
    $rhposts = get_posts(array('post_type' => 'property', 'numberposts' => -1, 'meta_key'=>'wppf', 'meta_value'=>'1'));
    if (is_array($rhposts)) {
        $property_count = count($rhposts);
        update_option("wppf_rh_propertycount", $property_count);
    }

    //update logs keeping just the last 10
    $date = new DateTime();
    $log = get_option("wppf_rh_log");
    if (is_array($log)) {
        array_unshift($log,array('logdate' => $date->format('d/m/Y H:i:s'),'updated' => $updated, 'deleted' => $deleted, 'totalproperties' => $property_count));
        $log = array_slice($log,0,10);
    } else {
        $log = array(array('logdate' => $date->format('d/m/Y H:i:s'), 'updated' => $updated, 'deleted' => $deleted, 'totalproperties' => $property_count));
    }
    update_option("wppf_rh_log", $log);
}

function wppf_rh_check_attachment($name, $attachment_id,$post_id,$sortorder) {
    if (!empty($attachment_id)) {
        $image = wp_get_attachment_image_src($attachment_id);
        if (empty($name))
            wp_update_post( array('ID' => $attachment_id, 'post_parent' => $post_id, 'menu_order' => $sortorder));
        else
            wp_update_post( array('ID' => $attachment_id, 'post_title' => $name, 'post_parent' => $post_id, 'menu_order' => $sortorder));
        if ($image) return true;
    }
    return false;
}

function wppf_rh_upload_file($name, $url, $postid, $sortorder) {
    $mimetype = "image/jpeg";
    $parts = pathinfo($url);
    switch ($parts['extension']) {
        case "gif":
            $mimetype = "image/gif";
            break;
        case "png":
            $mimetype = "image/png";
            break;
        case "pdf":
            $mimetype = "application/pdf";
            break;
    }

    $posttitle = (empty($name)) ? preg_replace( '/\.[^.]+$/', '', basename($url)) : $name;
    $attachment = array(
            'guid'           => $url,
            'post_mime_type' => $mimetype,
            'post_title'     => $posttitle,
            'post_content'   => 'cdn',
            'post_status'    => 'inherit',
            'post_parent'    => $postid,
            'menu_order'     => $sortorder
        );
    $attach_id = wp_insert_attachment($attachment, basename($url), $postid);
    //fake the attachment data so it will display in the gallery
    $imagemeta = array("aperture" => "0", "credit" => "", "camera" => "", "caption" => "", "created_timestamp" => "0", "copyright" => "", "focal_length" => "0", "iso" => "0", "shutter_speed" => "0", "title" => "", "orientation" => "1", "keywords" => array());
    $meta = array("width" => "300", "height" => "200", "file" => preg_replace( '/\.[^.]+$/', '', basename($url)), "image_meta" => $imagemeta);
    wp_update_attachment_metadata($attach_id, $meta);
    return $attach_id;
}

if (!function_exists("get_image_sizes")) {
    function get_image_sizes() {
	    global $_wp_additional_image_sizes;
	    $sizes = array();
	    foreach ( get_intermediate_image_sizes() as $_size ) {
		    if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
			    $sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
			    $sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
			    $sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
		    } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			    $sizes[ $_size ] = array(
				    'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
				    'height' => $_wp_additional_image_sizes[ $_size ]['height'],
				    'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
			    );
		    }
	    }
	    return $sizes;
    }
}

if (!function_exists("wppf_image_downsize")) {
    function wppf_image_downsize($downsize, $id, $size) {
        //if this is a cdn image then serve that
        $attachment = get_post($id);
        if (!empty($attachment) && $attachment->post_content == 'cdn') {
            $sizes = get_image_sizes();
            if (is_array($size)) $size='thumbnail';
            if (isset($sizes[$size])) {
                return array($attachment->guid, $sizes[$size]['width'], $sizes[$size]['height'], $sizes[$size]['crop']);
            } else {
                return array($attachment->guid, '100', '75', true);
            }
        }
        return false;
    }
    add_filter('image_downsize', 'wppf_image_downsize', 10, 3);
}

if (!function_exists("wppf_attachment_cdn_url")) {
    function wppf_attachment_cdn_url($url, $id) {
        $attachment = get_post($id);
        if ($attachment->post_content == 'cdn') {
		    $url = str_replace("http://","//",(string)$attachment->guid);
	    }
	    return $url;
    }
    add_filter('attachment_link', 'wppf_attachment_cdn_url', 10, 2 );
    add_filter('wp_get_attachment_url', 'wppf_attachment_cdn_url', 10, 2);
    add_filter('wp_get_attachment_thumb_url', 'wppf_attachment_cdn_url', 10, 2);
}

function wppf_rh_published($the_date, $overide, $post) {
    if ($post->post_type == 'property') {
        $wppf_slug = get_post_meta($post->ID,'wppf_slug',true);
        if (!empty($wppf_slug)) {
            $updated = get_post_meta($post->ID,'updated',true);
            $the_date = mysql2date(get_option( 'date_format' ), $updated);
        }
    }
    return $the_date;
}
add_filter('get_the_date', 'wppf_rh_published', 10, 3);