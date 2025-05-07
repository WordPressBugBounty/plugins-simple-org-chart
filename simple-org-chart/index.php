<?php
/*
Plugin Name: Simple Org Chart
Version: 2.3.5
Plugin URI: https://wordpress.org/plugins/simple-org-chart/
Description: Build Org chart by dragging users in required order.
Author: G Matta
Author URI: http://webtechforce.com/
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'org_chart_init');
add_action('admin_menu', 'org_chart_add_page');

add_action('admin_init', 'orgchart_scripts');
add_action('admin_enqueue_scripts', 'orgchart_enqueue');

add_action("init", "set_org_cookie", 1);
add_action('admin_notices', 'general_admin_notice', 10);
add_action('current_screen', 'this_screen');

function this_screen()
{

    $current_screen = get_current_screen();
    if ($current_screen->id === "settings_page_org_chart") {
        remove_all_actions('admin_notices');
    }

}

function orgchart_scripts()
{
    wp_enqueue_style('orgchart-style1', plugin_dir_url(__FILE__) . 'css/jquery.jOrgChart.css');
    wp_enqueue_style('orgchart-style2', plugin_dir_url(__FILE__) . 'css/custom.css');
    wp_register_style('select2css', '//cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css', false, '1.0', 'all');
    wp_enqueue_style('select2css');

}

function orgchart_enqueue()
{

    // Use `get_stylesheet_directory_uri() if your script is inside your theme or child theme.

    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-draggable');
    wp_enqueue_script('jquery-ui-droppable');
    wp_enqueue_media();
    wp_register_script('select2', '//cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js', array('jquery'), '1.0', true);
    wp_enqueue_script('select2');
    wp_enqueue_script('org_cha', plugin_dir_url(__FILE__) . 'js/jquery.jOrgChart.js');
    //wp_enqueue_script('org_cha1', plugin_dir_url(__FILE__) . 'js/custom.js');

}

// Init plugin options to white list our options
function org_chart_init()
{
    register_setting('org_chart_options', 'org_chart_sample', 'org_chart_validate');
}

// Add menu page
function org_chart_add_page()
{
    add_options_page('Org Chart Builder', 'Org Chart', 'manage_options', 'org_chart', 'org_chart_do_page');
}

// Draw the menu page itself
function org_chart_do_page()
{

    // Enqueue admin scripts and localize data
    wp_enqueue_script('soc-custom-js', plugin_dir_url(__FILE__) . 'js/custom.js', array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'select2', 'soc-jorgchart-js'), '2.3.5', true); // Ensure dependencies are correct
    wp_localize_script('soc-custom-js', 'orgChartAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('org_chart_ajax_nonce') // Create and pass the nonce
    ));

    // Also ensure select2 is enqueued if not already handled by WordPress or another plugin
    wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css'); // Example CDN link
    wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), null, true); // Example CDN link

    // Enqueue jOrgChart if not already done
    wp_enqueue_style('soc-jorgchart-css', plugin_dir_url(__FILE__) . 'css/jquery.jOrgChart.css');
    wp_enqueue_script('soc-jorgchart-js', plugin_dir_url(__FILE__) . 'js/jquery.jOrgChart.js', array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-draggable', 'jquery-ui-droppable'), '1.0', true); // Add dependencies

    // Enqueue custom admin CSS
    wp_enqueue_style('soc-custom-admin-css', plugin_dir_url(__FILE__) . 'css/custom.css');

    // Enqueue WP media scripts for the uploader
    wp_enqueue_media();

    ?>
    <div class="wrap">

        <?php

        echo '<h2>ORG CHART Builder - Lite version <small>(Try <a target="_blank" href="https://wporgchart.com">WP Org Chart Pro</a>)</small></h2>';

        echo '<div class="wrap orgchart">';

        // Handle form submission with nonce check
        if (isset($_POST['osubmit']) && isset($_POST['_wpnonce_org_chart_settings']) && wp_verify_nonce($_POST['_wpnonce_org_chart_settings'], 'org_chart_settings_action')) {
            orgchart_remove_meta();
            // Sanitize input
            $top_user_id = isset($_POST['user_dropdown']) ? absint($_POST['user_dropdown']) : 0;
            if ($top_user_id > 0) {
                update_user_meta($top_user_id, "top_org_level", 1);
            }
            // Clear the saved chart when resetting
            delete_option('org_array');
            echo '<div class="notice notice-success is-dismissible"><p>Top level user reset and chart cleared.</p></div>';
        } elseif (isset($_POST['osubmit'])) {
            // Nonce verification failed
            echo '<div class="notice notice-error is-dismissible"><p>Security check failed. Please try again.</p></div>';
        }


        ?>
        <span class="oblock">Drag and Drop users in order to set levels and Save Changes. Use shortcode
        <b>[orgchart]</b> to display on any page or post.
        </span>
        <span class="oinline"> <?php esc_html_e('Select Top Level:');?> </span>

        <span class="oinline">
        <form action="<?php echo esc_url(admin_url('options-general.php?page=org_chart')); ?>"
              name="select_top_level"
              method="post">
            <?php wp_nonce_field('org_chart_settings_action', '_wpnonce_org_chart_settings'); // Add nonce field ?>

<?php

$user_query0 = new WP_User_Query(array('meta_key' => 'top_org_level', 'meta_value' => 1));

if (!empty($user_query0->results)) {

    foreach ($user_query0->results as $user) {
        $top_level_id = $user->ID;
        $top_level = $user->display_name;

    }
}

// now make users dropdown

$users = get_users();
if ($users) { ?>

    <select id="user_dropdown" name="user_dropdown">

<?php
        foreach ($users as $userz) {
        $top_user = '';
             if ($userz->ID == $top_level_id) {
               $top_user = "selected";
             }
        echo '<option ' . $top_user . ' value="' . esc_attr($userz->ID) . '">' . esc_html($userz->display_name) . '</option>';
         }
 ?>
    </select>

<?php
}

// now get selected user id from $_POST to use in your next function
if (isset($_POST['user_dropdown'])) {
    // Sanitize input
    $userz_id = absint($_POST['user_dropdown']);
    $user_data = get_user_by('id', $userz_id);
} else {
    // Use the currently saved top level ID if not submitting
    $userz_id = $top_level_id;
    $user_data = !empty($top_level_id) ? get_user_by('id', $top_level_id) : null;
}

?>

    <input type="submit" name="osubmit" id="oreset" class="button" value="Reset"/>
    </form>
    </span>

<?php

        if (!empty($top_level_id) && $user_data) { // Check if $user_data is valid

            $options = get_option('org_chart_sample'); // This option seems unused, consider removing register_setting if so.

            // Display initial structure only if form was just submitted (resetting)
            // Otherwise, rely on the saved structure from get_option('org_array')
            if (isset($_POST['osubmit']) && isset($_POST['_wpnonce_org_chart_settings']) && wp_verify_nonce($_POST['_wpnonce_org_chart_settings'], 'org_chart_settings_action')) {
                $otree = '';

                $uimg = get_user_meta($top_level_id, 'shr_pic', true);
                $image_data = !empty($uimg) ? wp_get_attachment_image_src(absint($uimg), 'thumbnail') : false;
                $image_url = $image_data ? $image_data[0] : '';
                $org_role = get_user_meta($top_level_id, 'org_job_title', true);
                $user_description = get_the_author_meta('description', $top_level_id);

                $user_b_content = !empty($user_description) ? '<div id="" data-id="bio' . esc_attr($top_level_id) . '" class="overlay1">
                    <div class="popup1">
                    <a class="close1" href="#">&times;</a>
                    <div class="content1">' . esc_textarea($user_description) . '</div>
                    </div>
                    </div>
                    <a href="#bio' . esc_attr($top_level_id) . '" class="bio' . esc_attr($top_level_id) . '">' : '';

                echo '<ul id="org" style="display:none">';

                $node_content = '';
                if (!empty($image_url)) {
                    $node_content .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($user_data->display_name) . '">';
                } else {
                    $node_content .= get_avatar($top_level_id);
                }
                $node_content .= esc_html($user_data->display_name) . '<small> ' . esc_html($org_role) . ' </small>';

                $otree .= '<li id="' . esc_attr($top_level_id) . '"> ' . $user_b_content . $node_content . (!empty($user_b_content) ? '</a>' : '') . '<ul>';


                $user_query1 = new WP_User_Query(array('exclude' => array($top_level_id)));

                if (!empty($user_query1->results)) {

                    foreach ($user_query1->results as $user) {
                        $org_job_title = get_user_meta($user->ID, 'org_job_title', true);
                        $uimg = get_user_meta($user->ID, 'shr_pic', true);
                        $image_data = !empty($uimg) ? wp_get_attachment_image_src(absint($uimg), 'thumbnail') : false;
                        $image_url = $image_data ? $image_data[0] : '';
                        $user_description = get_the_author_meta('description', $user->ID);
                        $user_data_loop = get_user_by('id', $user->ID); // Get user data for display name

                        $user_b_content = !empty($user_description) ? '<div id="" data-id="bio' . esc_attr($user->ID) . '" class="overlay1">
                                        <div class="popup1">
                                        <a class="close1" href="#">&times;</a>
                                                <div class="content1"> ' . esc_textarea($user_description) . '
                                         </div></div>
                                        </div><a href="#bio' . esc_attr($user->ID) . '" class="bio' . esc_attr($user->ID) . '">' : '';


                        $node_content = '';
                        if (!empty($image_url)) {
                            $node_content .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($user_data_loop->display_name) . '">';
                        } else {
                            $node_content .= get_avatar($user->ID);
                        }
                        $node_content .= esc_html($user_data_loop->display_name) . '<small> ' . esc_html($org_job_title) . ' </small>';


                        $otree .= '<li id="' . esc_attr($user->ID) . '">  ' . $user_b_content . $node_content . (!empty($user_b_content) ? '</a>' : '') . '<a class="rmv-nd close" href="javascript:void(0);">Delete</a><span class="name_c" id="' . esc_attr($user->ID) . '"></span></li>';


                    }
                }

                $otree .= '</ul> </li></ul>';
                echo $otree; // Output the generated tree structure

            } elseif (get_option('org_array') != '') {
                // Load from saved JSON data
                $org_json = get_option('org_array');
                // Decode JSON into an associative array
                $tree = json_decode($org_json, true);
                // Check if decoding was successful and it's an array
                if (is_array($tree)) {
                    $result = parseTree($tree); // Use the existing parseTree function
                    printTree($result); // Use the existing printTree function
                } else {
                     echo '<div class="notice notice-warning"><p>Could not load saved chart structure (invalid format).</p></div>';
                     // Optionally, display the initial structure based on top-level user here as a fallback
                }

            } else {
                 // No saved data and not resetting, maybe show a message or the initial structure?
                 // For now, let's show the initial structure if top_level_id is set
                 // This duplicates the logic from the 'if (isset($_POST['osubmit']))' block, consider refactoring
                 $otree = '';
                 $uimg = get_user_meta($top_level_id, 'shr_pic', true);
                 $image_data = !empty($uimg) ? wp_get_attachment_image_src(absint($uimg), 'thumbnail') : false;
                 $image_url = $image_data ? $image_data[0] : '';
                 $org_role = get_user_meta($top_level_id, 'org_job_title', true);
                 $user_description = get_the_author_meta('description', $top_level_id);

                 $user_b_content = !empty($user_description) ? '<div id="" data-id="bio' . esc_attr($top_level_id) . '" class="overlay1">
                     <div class="popup1">
                     <a class="close1" href="#">&times;</a>
                     <div class="content1">' . esc_textarea($user_description) . '</div>
                     </div>
                     </div>
                     <a href="#bio' . esc_attr($top_level_id) . '" class="bio' . esc_attr($top_level_id) . '">' : '';

                 echo '<ul id="org" style="display:none">';

                 $node_content = '';
                 if (!empty($image_url)) {
                     $node_content .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($user_data->display_name) . '">';
                 } else {
                     $node_content .= get_avatar($top_level_id);
                 }
                 $node_content .= esc_html($user_data->display_name) . '<small> ' . esc_html($org_role) . ' </small>';

                 $otree .= '<li id="' . esc_attr($top_level_id) . '"> ' . $user_b_content . $node_content . (!empty($user_b_content) ? '</a>' : '') . '<ul>';
                 // Add empty UL for JS to potentially populate later if needed, or query users again
                 $otree .= '</ul></li></ul>';
                 echo $otree;
            }
        ?>

            <div id="chart" class="orgChart"></div>

            <?php

        } else {
            echo '<p>' . esc_html__('Select Top Level User and click Reset to start building the chart.', 'simple-org-chart') . '</p>';
        }

        ?>
        <div class="org_bottom">
        <span class="submit">
         <input type="button" onClick="makeArrays();" class="button-primary"
                   value="<?php esc_attr_e('Save Changes')?>"/>
        <div class="chart_saved" style="display: none"><span><?php esc_html_e('Changes Saved!', 'simple-org-chart'); ?></span></div>
        </span>

            <form class="pending_user" name="opending" action="">
                <?php
                // Load saved data
                $org_json = get_option('org_array');
                $org_array = json_decode($org_json, true); // Decode JSON
                $rest = array();
                $all_user_ids = get_users(array('fields' => 'ID')); // Get all user IDs efficiently

                if (!empty($org_array) && is_array($org_array)) {
                    // Find users not in the saved chart structure
                    $users_in_chart = array_keys($org_array);
                    // Ensure values are integers for comparison
                    $users_in_chart = array_map('intval', $users_in_chart);
                    $rest = array_diff($all_user_ids, $users_in_chart);

                    // Also include the top-level user if they somehow got excluded from org_array keys
                    if (!empty($top_level_id) && !in_array($top_level_id, $users_in_chart)) {
                         // Check if $top_level_id is not already in $rest before adding
                         if (!in_array($top_level_id, $rest)) {
                            $rest[] = $top_level_id;
                         }
                    }

                } else {
                    // If no chart saved, all users except the top level are potentially available
                    $rest = array_diff($all_user_ids, array($top_level_id));
                }
                ?>

                <select id="comboBox"><option value=""><?php esc_html_e('Select User', 'simple-org-chart'); ?></option>

                    <?php
                    $hiden_val_array = array(); // Use an array to build data for hidden field
                    $html ='';

                    // Populate dropdown with users not in the chart
                    foreach ($rest as $rid) {
                        $ud = get_userdata($rid);
                        if (!$ud) continue; // Skip if user data couldn't be retrieved

                        $uimg_id = get_user_meta($rid, 'shr_pic', true);
                        $org_role = get_user_meta($rid, 'org_job_title', true);
                        $img_url = get_avatar_url($rid); // Default to avatar URL

                        if (!empty($uimg_id)) {
                            $image_data = wp_get_attachment_image_src(absint($uimg_id), 'thumbnail');
                            if ($image_data) {
                                $img_url = $image_data[0];
                            }
                        }

                        // Add user to dropdown
                        $html .= '<option value="' . esc_attr($rid) . '*' . esc_attr($img_url) . '*' . esc_attr($org_role) . '*' . esc_attr($ud->display_name) . '">' . esc_html($ud->display_name) . '</option>';

                    }

                    // Prepare data for all users for the hidden field (used by JS)
                    foreach ($all_user_ids as $user_id) {
                         $ud = get_userdata($user_id);
                         if (!$ud) continue;

                         $uimg_id = get_user_meta($user_id, 'shr_pic', true);
                         $org_role = get_user_meta($user_id, 'org_job_title', true);
                         $img_url = get_avatar_url($user_id);

                         if (!empty($uimg_id)) {
                             $image_data = wp_get_attachment_image_src(absint($uimg_id), 'thumbnail');
                             if ($image_data) {
                                 $img_url = $image_data[0];
                             }
                         }
                         // Store data pieces for JS
                         $hiden_val_array[] = implode('*', [
                             esc_attr($user_id),
                             esc_attr($img_url),
                             esc_attr($org_role),
                             esc_attr($ud->display_name)
                         ]);
                    }

                    echo $html; // Output dropdown options
                    ?>

                </select>
                <?php // Combine the user data array into a string for the hidden field, separated by '$' ?>
                <input type="hidden" id="hidden_val" name="hidden_val" value="<?php echo esc_attr(implode('$', $hiden_val_array)); ?>" />
                <button id="btnAddOrg" type="button" class="button"><?php esc_html_e('Add', 'simple-org-chart'); ?></button>
            </form>
        </div>
        <div id="mja"></div> <?php // Consider a more descriptive ID ?>
    </div>
    <p> Like Simple Org Chart? <a target="_blank" href="https://wordpress.org/support/plugin/simple-org-chart/reviews/#new-post">Leave a Review</a>.
    <?php
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
// This function seems unused based on register_setting usage. Consider removing if not needed.
function org_chart_validate($input)
{
    // Our first value is either 0 or 1
    // $input['option1'] = (isset($input['option1']) && $input['option1'] == 1 ? 1 : 0); // Example sanitization

    // Sanitize other options as needed
    return $input;
}

function parseTree($tree, $root = null)
{
    $return = array();
    # Traverse the tree and search for direct children of the root
    foreach ($tree as $child => $parent) {
        # A direct child is found
        if ($parent == $root) {
            # Remove item from tree (we don't need to traverse this again)
            unset($tree[$child]);
            # Append the child into result array and parse its children
            $return[] = array(
                'name' => $child,
                'children' => parseTree($tree, $child),
            );
        }
    }
    return empty($return) ? null : $return;
}

function printTree($tree, $count = 0)
{

    if (!is_null($tree) && count($tree) > 0) {

        if ($count == 0) {
            // Use wp_json_encode to pass data safely to JavaScript if needed later
            // For now, just output the list structure
            echo '<ul id="org" style="display:none">';
        } else {
            echo '<ul>';
        }

        foreach ($tree as $node) {
            // Ensure 'name' exists and is numeric before casting
            if (!isset($node['name']) || !is_numeric($node['name'])) {
                continue; // Skip invalid nodes
            }
            $userid = (int) $node['name'];
            $user_info = get_userdata($userid);

            // Skip if user doesn't exist
            if (!$user_info) {
                continue;
            }

            $org_role = get_user_meta($userid, 'org_job_title', true);
            $user_description = get_the_author_meta('description', $userid);

            // Prepare bio link content safely
            $user_b_content = '';
            if (!empty($user_description)) {
                 $user_b_content = '<div id="" data-id="bio' . esc_attr($userid) . '" class="overlay1">
                    <div class="popup1">
                    <a class="close1" href="#">&times;</a>
                    <div class="content1">' . esc_textarea($user_description) . '</div></div>
                    </div><a href="#bio' . esc_attr($userid) . '" class="bio' . esc_attr($userid) . '">';
            }


            $uimg_id = get_user_meta($userid, 'shr_pic', true);
            $image_data = !empty($uimg_id) ? wp_get_attachment_image_src(absint($uimg_id), 'thumbnail') : false;
            $image_url = $image_data ? $image_data[0] : '';

            // Start list item
            echo '<li id="' . esc_attr($userid) . '"> ' . $user_b_content; // Output bio link start

            // Output image or avatar
            if (!empty($image_url)) {
                echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($user_info->display_name) . '">';
            } else {
                echo get_avatar($userid);
            }

            // Output user name and role
            echo esc_html($user_info->display_name) . '<small> ' . esc_html($org_role) . ' </small>';

            // Close bio link if it was opened
            if (!empty($user_b_content)) {
                echo '</a>';
            }

            // Add admin controls (Delete button, span)
            if ($count != 0 && is_admin()) {
                echo '<span class="name_c" id="' . esc_attr($userid) . '"></span><a class="rmv-nd close" href="javascript:void(0);">' . esc_html__('Delete', 'simple-org-chart') . '</a>';
            }

            // Recursively print children if they exist and are an array
            if (isset($node['children']) && is_array($node['children'])) {
                 printTree($node['children'], 1);
            }

            echo '</li>'; // Close list item
        }
        echo '</ul>';
    }
}

function orgchart_remove_meta()
{

    $users = get_users();

    foreach ($users as $user) {

        delete_user_meta($user->ID, 'top_org_level');

    }

}

add_action('init', 'orgchart_scripts');

function orgchart_display()
{
    // Styles and scripts are enqueued conditionally via shortcode presence is better practice,
    // but keeping existing enqueue logic for now. Consider refactoring later.
    wp_enqueue_style('orgchart-style1', plugin_dir_url(__FILE__) . 'css/jquery.jOrgChart.css');
    wp_enqueue_style('orgchart-style2', plugin_dir_url(__FILE__) . 'css/custom.css');
    wp_enqueue_script('jquery'); // Already a dependency for others
    wp_enqueue_script('jquery-ui-core'); // Already enqueued? Check dependencies
    wp_enqueue_script('orgchart-script', plugin_dir_url(__FILE__) . 'js/jquery.jOrgChart.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('orgchart-script1', plugin_dir_url(__FILE__) . 'js/custom1.js', array(), '1.0.0', true);


    $org_json = get_option('org_array');
    $tree = json_decode($org_json, true); // Decode JSON

    // Check if decoding was successful and it's an array
    if (!is_array($tree)) {
        return '<p>' . esc_html__('Org chart data is missing or invalid.', 'simple-org-chart') . '</p>';
    }

    $result = parseTree($tree);

    // Use output buffering to capture the HTML from printTree
    ob_start();
    printTree($result);
    $tree_html = ob_get_clean();

    // Check if printTree actually produced output
    if (empty($tree_html)) {
         return '<p>' . esc_html__('No organization chart structure found.', 'simple-org-chart') . '</p>';
    }

    // Assemble the final output
    $out = $tree_html; // Contains the <ul id="org"...> structure
    $out .= '<div id="chart" class="orgChart"></div>'; // The container for the JS chart

    // Pass necessary data to the frontend script (custom1.js)
    // This assumes custom1.js is correctly enqueued and uses wp_localize_script
    // Example (needs to be added where scripts are enqueued, e.g., in orgchart_enqueue or a shortcode-specific function):
    
    wp_localize_script('orgchart-script1', 'orgChartData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        // Add any other data needed by custom1.js
    ));
    

    return $out;
}

add_shortcode('orgchart', 'orgchart_display');

function shr_extra_profile_fields($user)
{
    // Ensure $user is a WP_User object or 'add-new-user' string
    $user_id = 0;
    if (is_object($user) && isset($user->ID)) {
        $user_id = $user->ID;
    } elseif (is_numeric($user)) { // Handle case where user ID is passed directly
        $user_id = (int) $user;
    } elseif ($user === 'add-new-user') {
        // No user ID available yet
    } else {
        return; // Invalid user parameter
    }


    $profile_pic_id = ($user_id > 0) ? get_user_meta($user_id, 'shr_pic', true) : false;
    $image_url = '';
    if (!empty($profile_pic_id)) {
        // Ensure it's an integer before using
        $image_data = wp_get_attachment_image_src(absint($profile_pic_id), 'thumbnail');
        if ($image_data) {
            $image_url = $image_data[0];
        }
    }
    ?>

    <table class="form-table fh-profile-upload-options">
    <tr>
        <th>
            <label for="shr-image"><?php esc_html_e('Main Profile Image', 'shr')?></label>
        </th>

        <td>
            <?php // Security: It's better to use the WP media uploader launched by JS than direct input fields for IDs ?>
            <button type="button" data-id="shr_image_id" data-src="shr-img" class="button shr-image" name="shr_image"
                   id="shr-image"><?php esc_html_e('Upload Image', 'simple-org-chart'); ?></button>
            <input type="hidden" class="button" name="shr_image_id" id="shr_image_id"
                   value="<?php echo !empty($profile_pic_id) ? esc_attr(absint($profile_pic_id)) : ''; ?>"/>
            <img id="shr-img" src="<?php echo !empty($image_url) ? esc_url($image_url) : ''; ?>"
                 style="<?php echo empty($image_url) ? 'display:none;' : '' ?> max-width: 100px; max-height: 100px; vertical-align: middle; margin-left: 10px;"/>
             <?php if (!empty($image_url)): ?>
                 <button type="button" class="button button-small shr-remove-image" style="margin-left: 5px;"><?php esc_html_e('Remove', 'simple-org-chart'); ?></button>
             <?php endif; ?>
             <p class="description"><?php esc_html_e('Upload or select an image for the profile.', 'simple-org-chart'); ?></p>
             <?php // Add JS to handle the upload button and removal ?>
             <script type="text/javascript">
                jQuery(document).ready(function($){
                    var frame;
                    $('#shr-image').on('click', function(e){
                        e.preventDefault();
                        if (frame) { frame.open(); return; }
                        frame = wp.media({
                            title: '<?php esc_attr_e( "Select or Upload Profile Image", "simple-org-chart" ); ?>',
                            button: { text: '<?php esc_attr_e( "Use this image", "simple-org-chart" ); ?>' },
                            multiple: false
                        });
                        frame.on('select', function(){
                            var attachment = frame.state().get('selection').first().toJSON();
                            $('#shr_image_id').val(attachment.id);
                            $('#shr-img').attr('src', attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url).show();
                            $('.shr-remove-image').show(); // Show remove button
                        });
                        frame.open();
                    });
                     $('.shr-remove-image').on('click', function(e){
                         e.preventDefault();
                         $('#shr_image_id').val('');
                         $('#shr-img').attr('src', '').hide();
                         $(this).hide(); // Hide remove button itself
                     });
                     // Hide remove button initially if no image
                     if (!$('#shr_image_id').val()) {
                         $('.shr-remove-image').hide();
                     }
                });
             </script>
        </td>
    </tr>
    </table><?php

}

add_action('show_user_profile', 'shr_extra_profile_fields');
add_action('edit_user_profile', 'shr_extra_profile_fields');
add_action('user_new_form', 'shr_extra_profile_fields');

function shr_profile_update($user_id)
{
    // Check if the current user has permission to edit this user profile
    // 'edit_user' capability check is crucial here.
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    // Verify nonce if this is triggered from a form submission context (e.g., profile.php, user-edit.php)
    // Note: 'profile_update' and 'user_register' hooks might not have a specific nonce set by this plugin.
    // Relying on WordPress's own nonce verification for user profile updates is generally sufficient.

    // Sanitize the input before saving
    $profile_pic_id = (isset($_POST['shr_image_id']) && !empty($_POST['shr_image_id'])) ? absint($_POST['shr_image_id']) : '';

    if (!empty($profile_pic_id)) {
        // Optional: Check if the ID corresponds to a valid attachment
        if (get_post_type($profile_pic_id) === 'attachment') {
            update_user_meta($user_id, 'shr_pic', $profile_pic_id);
        } else {
            // If ID is invalid, remove the meta
            delete_user_meta($user_id, 'shr_pic');
        }
    } else {
        // If input is empty, remove the meta
        delete_user_meta($user_id, 'shr_pic');
    }
}

add_action('profile_update', 'shr_profile_update');
add_action('user_register', 'shr_profile_update');

// add anything else
function my_new_contactmethods($contactmethods)
{

    //add Phone
    $contactmethods['phone'] = 'Phone (SOC)';
    // Add Twitter
    $contactmethods['twitter'] = 'Twitter (SOC)';
//add Facebook
    $contactmethods['facebook'] = 'Facebook (SOC)';

    return $contactmethods;
}

add_filter('user_contactmethods', 'my_new_contactmethods', 10, 1);

function user_interests_fields($user)
{
    // Similar user object/ID handling as in shr_extra_profile_fields
    $user_id = 0;
    if (is_object($user) && isset($user->ID)) {
        $user_id = $user->ID;
    } elseif (is_numeric($user)) {
        $user_id = (int) $user;
    } elseif ($user !== 'add-new-user') {
         return; // Invalid user
    }

    $org_job_title = ($user_id > 0) ? get_user_meta($user_id, 'org_job_title', true) : '';
    ?>
    <table class="form-table">
        <tr>
            <th><label for="org_job_title"><?php esc_html_e('Job Title (Org Chart)', 'simple-org-chart'); ?></label></th>
            <td>
                <input id="org_job_title" name="org_job_title" type="text" class="regular-text"
                       value="<?php echo esc_attr($org_job_title); ?>"/>
                 <p class="description"><?php esc_html_e('Job title displayed in the organization chart.', 'simple-org-chart'); ?></p>
            </td>
        </tr>

    </table>
    <?php
}

add_action('show_user_profile', 'user_interests_fields');
add_action('edit_user_profile', 'user_interests_fields');
add_action('user_new_form', 'user_interests_fields');

// store interests
// store interests
function user_interests_fields_save($user_id)
{
    // Capability check
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    // Nonce check (similar consideration as shr_profile_update)

    // Sanitize and save/delete job title
    if (isset($_POST['org_job_title'])) {
        $job_title = sanitize_text_field(trim($_POST['org_job_title']));
        if (!empty($job_title)) {
            update_user_meta($user_id, 'org_job_title', $job_title);
        } else {
            delete_user_meta($user_id, 'org_job_title');
        }
    }
}

add_action('personal_options_update', 'user_interests_fields_save');
add_action('edit_user_profile_update', 'user_interests_fields_save');
add_action('user_register', 'user_interests_fields_save');

function myajax()
{
    
    // 1. Verify Nonce (Nonce should be passed from JS)
    check_ajax_referer('org_chart_ajax_nonce', 'security'); // Dies if nonce is invalid
  
    // 2. Check Capabilities
    if (!current_user_can('manage_options')) { // Or a more specific capability if defined
   
        wp_send_json_error(['message' => 'Permission denied.'], 403);
        die();
    }

    // 3. Sanitize Input Data (Assuming $_POST['tree'] is the array structure)
    if (!isset($_POST['tree']) || !is_array($_POST['tree'])) {
         wp_send_json_error(['message' => 'Invalid data format.'], 400);
         die();
    }

    $sanitized_tree = array();
    foreach ($_POST['tree'] as $item) {
        // Expecting items like ['child_id' => 'parent_id']
        if (is_array($item) && count($item) === 1) {
            $child_id = key($item);
            $parent_id = current($item);

            // Sanitize IDs (assuming they are user IDs, hence integers)
            // Parent can be empty string '' for the root node
            $clean_child_id = absint($child_id);
            $clean_parent_id = ($parent_id === '' || $parent_id === null || $parent_id === 'null') ? '' : absint($parent_id); // Allow empty string for root

            if ($clean_child_id > 0) { // Ensure child ID is valid
                 // Optional: Check if user IDs actually exist? Might be overkill.
                 $sanitized_tree[$clean_child_id] = $clean_parent_id;
            }
        }
    }

    // 4. Encode data as JSON
    $json_tree = wp_json_encode($sanitized_tree); // Use wp_json_encode for better handling

    // 5. Save to options table
    // Use update_option which handles both adding and updating
    $updated = update_option('org_array', $json_tree, 'no'); // 'no' for autoload

    // 6. Send JSON Response
    if ($updated) {
        wp_send_json_success(['message' => 'Chart saved successfully.']);
    } else {
        // Check if the value was the same as before (update_option returns false if value unchanged)
        $current_value = get_option('org_array');
        if ($current_value === $json_tree) {
             wp_send_json_success(['message' => 'Chart data unchanged.']);
        } else {
             wp_send_json_error(['message' => 'Failed to save chart.'], 500);
        }
    }


    // Remove the var_dump
    // var_dump($org_array); // Removed debug output

    die(); // Required for WP AJAX handlers
}

// Note: AJAX actions need nonces passed from JS. The JS file (custom.js) needs modification.
// Example nonce generation in PHP (e.g., in org_chart_do_page or via wp_localize_script):
// $ajax_nonce = wp_create_nonce('org_chart_ajax_nonce');
// Pass this nonce to the JS and include it in the AJAX data object as 'security': ajax_nonce

// Hook for logged-in users
add_action('wp_ajax_org_chart', 'myajax');
// Remove wp_ajax_nopriv_org_chart - Saving should require login and capabilities


// JSON endpoint
add_action('rest_api_init', 'my_register_route');
function my_register_route()
{
    register_rest_route('org_chart/v1', '/structure', array( // Use versioning in namespace
            'methods' => WP_REST_Server::READABLE, // Use constant for GET method
            'callback' => 'custom_json',
            // Add permission callback for security
            'permission_callback' => function () {
                // Allow public access (if intended), or check capabilities
                // return true; // Publicly accessible
                return current_user_can('read'); // Example: Only logged-in users can access
                // return current_user_can('manage_options'); // Example: Only admins
            }
        )
    );
}

function custom_json()
{
    $org_json = get_option('org_array');
    $tree = json_decode($org_json, true); // Decode JSON

    // Check if decoding was successful and it's an array
    if (!is_array($tree)) {
        // Return an error or empty response
        return new WP_Error('no_data', 'Organization chart data not found or invalid.', array('status' => 404));
        // return rest_ensure_response(null); // Or just return null/empty
    }

    // Use parseJSON (which should be adapted for the JSON structure if different from parseTree)
    // Assuming parseJSON works correctly with the array structure from json_decode
    $result = parseJSON($tree);

    // Ensure the response is correctly formatted for the REST API
    return rest_ensure_response($result);
}


// Ensure parseJSON handles the array structure from json_decode correctly
if ( !function_exists('parseJSON') ) :
    function parseJSON($tree, $root = null) // $root is parent_id here
    {
        // Ensure $tree is an array
        if (!is_array($tree)) {
            return null;
        }

        $return = array();
        $processed_children = array(); // Keep track of processed children to avoid duplicates if structure is odd

        // Find direct children of the current root
        foreach ($tree as $child_id => $parent_id) {
            // Normalize root and parent_id for comparison (e.g., null vs '')
            $current_parent_id = ($parent_id === '' || $parent_id === null) ? null : (int)$parent_id;
            $current_root = ($root === '' || $root === null) ? null : (int)$root;

            if ($current_parent_id === $current_root && !isset($processed_children[$child_id])) {
                $user_info = get_userdata($child_id);
                if (!$user_info) continue; // Skip if user data not found

                // Mark as processed before recursion
                $processed_children[$child_id] = true;

                // Recursively find children of this child
                // Pass the original tree structure, but the child_id as the new root
                $children = parseJSON($tree, $child_id);

                $return[] = array(
                    'id' => $child_id,
                    'role' => get_user_meta($child_id, 'org_job_title', true),
                    'name' => $user_info->display_name,
                    // Add image URL if needed by the consumer
                    // 'image' => get_avatar_url($child_id), // Example
                    'children' => $children, // Result of recursive call
                );
            }
        }
        // Return null if no children found, otherwise the array of children
        return empty($return) ? null : $return;
    }
endif;

function set_org_cookie() {
    $set_time = time() + 60*60*24;
    if(isset($_GET['dismiss-org-nag'])){
    if (!isset($_COOKIE['org_nag']) && ($_GET['dismiss-org-nag'] == 1)) {
        setcookie('org_nag', 'yes', $set_time, "/");
    }
  }
}

function general_admin_notice(){
    global $pagenow;

    if( empty($_COOKIE['org_nag'])) {
        if($pagenow != 'post-new.php') {
            echo '<div class="notice notice-warning">
             <p> Like Simple Org Chart? <a target="_blank" href="https://wordpress.org/support/plugin/simple-org-chart/reviews/#new-post">Leave a Review</a>. Also checkout Pro Version with features like Multiple Charts, Responsive chart. <a target="_blank" href="https://wporgchart.com">WP Org Chart Pro</a>. <a style="float:right" href="?dismiss-org-nag=1">Dismiss</a></p>
         </div>';
        }
    }
}
