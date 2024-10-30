<?php

@ini_set('log_errors','On'); // enable or disable php error logging (use 'On' or 'Off')
@ini_set('display_errors','On'); // enable or disable public display of errors (use 'On' or 'Off')
@ini_set('error_log',MARKETEER__PLUGIN_DIR.'errors.log'); 

/** Step 2 (from text above). */
add_action('admin_menu', 'marketeer_plugin_menu');

/** Step 1. */
function marketeer_plugin_menu() {
    /* Menu Name        , Page Name  , Capabilities   , Unique Slug for plugin ,  Function name  */
    //add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position ); 
    // add_option_page
    add_menu_page('Marketeer Setting', 'Marketeer', 'manage_options', 'wp-marketeer', 'marketeer_plugin_option', plugins_url( 'logo_icon.png', __FILE__ ) );

    //call register settings function
    add_action('admin_init', 'register_marketeer_plugin_settings');
}

function register_marketeer_plugin_settings() {
    register_setting('marketeer-plugin-settings-group', 'txt_VerifyKey');
}

/* @ Plugin body code START */

function marketeer_plugin_option() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $page_args = array(
        'sort_order' => 'asc',
        'sort_column' => 'post_title',
        'hierarchical' => 1,
        'exclude' => '',
        'include' => '',
        'meta_key' => '',
        'meta_value' => '',
        'authors' => '',
        'child_of' => 0,
        'parent' => -1,
        'exclude_tree' => '',
        'number' => '',
        'offset' => 0,
        'post_type' => 'page',
        'post_status' => 'publish'
    );
    $pages = get_pages($page_args);

    global $context_id, $json_response;

    $marketeer_api_key = get_option('marketeer_api_key');

    $json_response = get_marketeer_api_details($marketeer_api_key);
    $result = json_decode($json_response['body'], TRUE);

    $context_id = $result['code'];

    if (!get_option('marketeer_context_id')):
        update_option('marketeer_context_id', $context_id);
    else:
        add_option('marketeer_context_id', $context_id);
    endif;
    ?>
    <div class="wrap">
        <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
        <h1>Marketeer For WordPress</h1>
        <style type="text/css">
            li{
                list-style-type:none;
            }
            body{font-family: 'Open Sans', sans-serif;}
        </style>
        <form method="post" action="" autocomplete="off">
            <?php settings_fields('marketeer-plugin-settings-group'); ?>
            <?php do_settings_sections('marketeer-plugin-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row" colspan="2"><img src="<?php echo plugins_url( 'logo.png', __FILE__ ); ?>" height="100px"/></th>

                </tr>

                <tr valign="top">
                    <th scope="row" colspan="2"><span>Please add Your API Key.</span><br/>
                    <span>To find your API key, please access to your Marketeer account at <a href="https://my.marketeer.co">https://my.marketeer.co</a> and go to Settings</span>
                    </th>
                </tr>

                <tr valign="top">
                    <th scope="row">Verify Your API Key
                    </th>
                    <td colspan="2"><input type="password" name="txt_VerifyKey" value="<?php echo esc_attr(get_option('marketeer_api_key')); ?>" size="100" placeholder="Your API Key here.."/></td>
                </tr>

                <?php
                if (!empty($result) && !empty($marketeer_api_key)):
                    $get_post_categories = get_option('marketeer_categories');
                    ?>

                    <tr>
                        <th scope="row">Select your Default folder</th>
                        <td>
                            <?php
                            $get_folders = get_option('marketeer_folders'); 
                            echo '<select name="folder_name" id="folder_name">';
                            echo '<option value="">-- Default --</option>';
                            for ($m = 0; $m < count($result['folders']); $m++):
                                $folder_name = (!empty($result['folders'][$m][1])) ? $result['folders'][$m][1] : '';
                                $folder_id = $result['folders'][$m][0];
                                $checked = '';
                                if (!empty($get_folders)):
                                    $checked = ($get_folders == $folder_id) ? "selected=''" : "";
                                endif;

                                echo '<option value="'.$folder_id.'" '.$checked.'>'.$folder_name.'</option>';
                            endfor;
                            echo '</select>';
                            ?>
                        </td>
                    </tr>
                    <?php
                    $terms = get_terms( 'category', array(
                                'hide_empty' => false,
                            ) );
                    foreach( $terms as $term ) {
                    ?>
                    <tr>
                        <th scope="row">
                            <?php echo $term->name;?>
                            <input type="hidden" value="<?php echo $term->term_id;?>" name="categories_name[]">
                        </th>
                        <td>
                            <?php
                                $selected = get_term_meta($term->term_id,'marketeer_folder_name',true);

                                echo '<select name="categories_folder[]" id="folder_name">';
                                echo '<option value="">-- Default --</option>';
                                for ($m = 0; $m < count($result['folders']); $m++):
                                    $folder_name = (!empty($result['folders'][$m][1])) ? $result['folders'][$m][1] : '';
                                    $folder_id = $result['folders'][$m][0];
                                    $checked = '';
                                    if (!empty($selected)):
                                        $checked = ($selected == $folder_id) ? "selected=''" : "";
                                    endif;
                                    echo '<option value="'.$folder_id.'" '.$checked.'>'.$folder_name.'</option>';
                                endfor;
                                echo '</select>';
                            ?>
                        </td>
                    </tr>
                    <?php } 
                else:
                    echo '<tr><td></td><td style="color:red;"><b><span class="dashicons dashicons-info"></span> Wrong API KEY</b></td></tr>';
                endif; ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/* @ Plugin body code ENDS */

/* @ Save API key in database START */
if (isset($_POST['submit']) && !empty($_POST['submit']) && $_SERVER['REQUEST_METHOD'] === 'POST'):

    $is_existing_api_key = get_option('marketeer_api_key');
    if ($is_existing_api_key !== false):

        if (isset($_POST['txt_VerifyKey'])):
            $api_key = esc_sql($_POST['txt_VerifyKey']);
            update_option('marketeer_api_key', $api_key);
        endif;

        for($ct=0; $ct<count($_POST['categories_name']); $ct++){

            if( !empty($_POST['categories_folder'][$ct]) ){
                add_term_meta ($_POST['categories_name'][$ct], 'marketeer_folder_name', $_POST['categories_folder'][$ct], true );
            }
        }

        if (isset($_POST['folder_name'])):
             update_option('marketeer_folders', $_POST['folder_name']);
        endif;
    
        if (isset($_POST['drp_page'])):
            update_option('marketeer_pages', $_POST['drp_page']);
        endif;
    else:
        if (!empty($_POST['txt_VerifyKey']) && isset($_POST['txt_VerifyKey'])):
            $api_key = esc_sql($_POST['txt_VerifyKey']);
            add_option('marketeer_api_key', $api_key);
        endif;

        if (isset($_POST['folder_name']) && !empty($_POST['folder_name'])):
            add_option('marketeer_folders', $_POST['folder_name']);
        endif;

        if (isset($_POST['drp_page']) && !empty($_POST['drp_page'])):
            add_option('marketeer_pages', $_POST['drp_page']);
        endif;

        
    endif;
endif;
/* @ Save API key in database ENDS */

/* @ Add script into the head tag start */
if (!function_exists('add_marketeer_script_in_head')):
    global $get_api_key;

     $get_api_key = get_option('marketeer_api_key');
   

    if (!empty($get_api_key)):

        function add_marketeer_script_in_head() {
           global $get_api_key;

            $queried = is_queried_object();

            $get_marketeer_folder = get_option('marketeer_folders');
            $get_marketeer_page_id = get_option('marketeer_pages');
            $get_contect_id = get_option('marketeer_context_id');

            if(!empty($queried)):
                $page_category_id = $queried->ID;
                 $get_current_category_id = get_the_category($page_category_id);

                 if(!empty($get_current_category_id)):
                    $current_category_id = $get_current_category_id;
                    $current_folder_name = get_term_meta($current_category_id[0]->term_id,'marketeer_folder_name',true );
                endif;
            endif;
           
            if( !empty($current_category_id) && !empty($current_folder_name) ) {

                $get_folders = get_option('marketeer_folders');
                echo '<script src="https://my.marketeer.co/interface/?k=' . $get_contect_id . '&context_id=' . $current_folder_name . '"></script>';
            } else if( !empty($get_marketeer_folder) ) {
                 echo '<script src="//my.marketeer.co/interface/?k='.$get_contect_id.'&context_id=' . $get_marketeer_folder . '"></script>';
            } else {
                echo '<script src="//my.marketeer.co/interface/?k='.$get_contect_id.'"></script>';
            }

        }

        add_action('wp_head', 'add_marketeer_script_in_head');

    endif;
     
endif;
/* @ Add script in head Ends */


/* @ API Calling Start */
if (!function_exists('get_marketeer_api_details')):

    function get_marketeer_api_details($api_key) {
        if (!empty($api_key)) {

            $opts = array(
                'http' => array(
                    'method' => "GET",
                    'header' => "Content-Type: text/html; charset=utf-8"
                )
            );
            $data = wp_remote_get('https://my.marketeer.co/api_wordpress/' . $api_key);
            return $data;
        }
    }
endif;
/*@ API Calling Ends */


function is_queried_object(){
    return $queried = get_queried_object(); 
}