<?php

/*
    Plugin Name: Advanced Custom Fields: Options Page Adder
    Plugin URI: https://github.com/Hube2/acf-options-page-adder
    Description: Allows easy creation of options pages using Advanced Custom Fields Pro without needing to do any PHP coding. Requires that ACF Pro is installed.
    Author: John A. Huebner II
    Author URI: https://github.com/Hube2
    Version: 2.3.0
*/
//Depends: Advanced Custom Fields Pro, Admin Columns Pro

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$duplicator = dirname(__FILE__) . '/fieldset-duplicator.php';
if (file_exists($duplicator)) {
    include($duplicator);
}

new acfOptionsPageAdder();

class acfOptionsPageAdder {

    private $version = '2.2.0';
    private static $post_type = 'acf-options-page';
    private $parent_menus = array();
    private $exclude_locations = array('',
        'cpt_main_menu',
        'edit.php?post_type=acf-field-group',
        //'edit-comments.php',
        //'plugins.php',
        //'edit-tags.php?taxonomy=link_category',
        'edit.php?post_type=acf-options-page',
    );
    private $text_domain = 'acf-options-page-adder';

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('plugins_loaded', array($this, 'load_text_domain'));
        add_action('init', array($this, 'init'), 0);
        add_action('admin_menu', array($this, 'build_admin_menu_list'), 999);
        add_filter('acf/load_field/name=_acfop_parent', array($this, 'acf_load_parent_menu_field'));
        add_filter('acf/load_field/name=_acfop_capability', array($this, 'acf_load_capabilities_field'));
        add_filter('manage_edit-' . static::$post_type . '_columns', array($this, 'admin_columns'));
        add_action('manage_' . static::$post_type . '_posts_custom_column', array($this, 'admin_columns_content'), 10, 2);
        add_action('acf/include_fields', array($this, 'acf_include_fields'));
        add_filter('acf_options_page/post_type', array(get_called_class(), 'get_post_type'));
        add_filter('acf_options_page/text_domain', array($this, 'get_text_domain'));
    } // end public function __construct

    public function init() {
        $this->register_post_type();
        $this->acf_add_options_pages();
        do_action('acf_options_page/init');
    } // end public function init

    public static function get_post_type($value = '') {
        return static::$post_type;
    } // end public function get_post_type

    public function get_text_domain($value = '') {
        return $this->text_domain;
    } // end public function get_text_domain

    public function acf_include_fields() {
        // this function is called when ACF5 is installed
        $field_group = array(
            'key' => 'acf_options-page-details',
            'title' => __('Options Page Details', $this->text_domain),
            'fields' => array(
                array(
                    'key' => 'field_acf_key_acfop_message',
                    'label' => __('Options Page Message', $this->text_domain),
                    'name' => '',
                    'prefix' => '',
                    'type' => 'message',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'message' => __('Title above is the title that will appear on the page. Enter other details as needed.<br />For more information see the ACF documentation for <a href="http://www.advancedcustomfields.com/resources/acf_add_options_page/" target="_blank">acf_add_options_page()</a> and <a href="http://www.advancedcustomfields.com/resources/acf_add_options_sub_page/" target="_blank">acf_add_options_sub_page()</a>.', $this->text_domain)
                ),
                array(
                    'key' => 'field_acf_key_acfop_menu',
                    'label' => __('Menu Text', $this->text_domain),
                    'name' => '_acfop_menu',
                    'prefix' => '',
                    'type' => 'text',
                    'instructions' => __('Will default to title if left blank.', $this->text_domain),
                    'required' => 0,
                    'conditional_logic' => 0,
                    'default_value' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                    'maxlength' => '',
                    'readonly' => 0,
                    'disabled' => 0
                ),
                array(
                    'key' => 'field_acf_key_acfop_slug',
                    'label' => __('Slug', $this->text_domain),
                    'name' => '_acfop_slug',
                    'prefix' => '',
                    'type' => 'text',
                    'instructions' => __('Will default to sanitized title.', $this->text_domain),
                    'required' => 0,
                    'conditional_logic' => 0,
                    'default_value' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                    'maxlength' => '',
                    'readonly' => 0,
                    'disabled' => 0
                ),
                array(
                    'key' => 'field_acf_key_acfop_parent',
                    'label' => __('Menu Location (Parent)', $this->text_domain),
                    'name' => '_acfop_parent',
                    'prefix' => '',
                    'type' => 'select',
                    'instructions' => __('Select the menu this options page will appear under. Will default to None.', $this->text_domain),
                    'required' => 0,
                    'conditional_logic' => 0,
                    'choices' => array(), // dynamic populate
                    'default_value' => 'none',
                    'allow_null' => 0,
                    'multiple' => 0,
                    'ui' => 0,
                    'ajax' => 0,
                    'placeholder' => '',
                    'disabled' => 0,
                    'readonly' => 0
                ),
                array(
                    'key' => 'field_acf_key_acfop_capability',
                    'label' => __('Capability', $this->text_domain),
                    'name' => '_acfop_capability',
                    'prefix' => '',
                    'type' => 'select',
                    'instructions' => __('The user capability to view this options page. Will default to manage_options.', $this->text_domain),
                    'required' => 0,
                    'conditional_logic' => 0,
                    'choices' => array(), // dynamic populate
                    'default_value' => 'manage_options',
                    'allow_null' => 1,
                    'multiple' => 0,
                    'ui' => 0,
                    'ajax' => 0,
                    'placeholder' => '',
                    'disabled' => 0,
                    'readonly' => 0
                ),
                array(
                    'key' => 'field_acf_key_acfop_position',
                    'label' => __('Menu Position', $this->text_domain),
                    'name' => '_acfop_position',
                    'prefix' => '',
                    'type' => 'text',
                    'instructions' => __('The position in the menu order this menu should appear. WARNING: if two menu items use the same position attribute, one of the items may be overwritten so that only one item displays! Risk of conflict can be reduced by using decimal instead of integer values, e.g. 63.3 instead of 63. Defaults to bottom of utility menu items.<br /><em>Core Menu Item Positions: 2=Dashboard, 4=Separator, 5=Posts, 10=Media, 15=Links, 20=Pages, 25=Comments, 59=Separator, 60=Appearance, 65=Plugins, 70=Users, 75=Tools, 80=Settings, 99=Separator</em>', $this->text_domain),
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_acf_key_acfop_parent',
                                'operator' => '==',
                                'value' => 'none',
                            ),
                        ),
                    ),
                    'default_value' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                    'min' => '',
                    'max' => '',
                    'step' => '',
                    'readonly' => 0,
                    'disabled' => 0,
                ),
                array(
                    'key' => 'field_acf_key_acfop_icon',
                    'label' => 'Icon',
                    'name' => '_acfop_icon',
                    'prefix' => '',
                    'type' => 'text',
                    'instructions' => __('The icon url for this menu. Defaults to default WordPress gear.<br /><em>Check out <a href="http://melchoyce.github.io/dashicons/" target="_blank">http://melchoyce.github.io/dashicons/</a> for what to put in this field.</em>', $this->text_domain),
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_acf_key_acfop_parent',
                                'operator' => '==',
                                'value' => 'none',
                            ),
                        ),
                    ),
                    'default_value' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                    'maxlength' => '',
                    'readonly' => 0,
                    'disabled' => 0,
                ),
                array(
                    'key' => 'field_acf_key_acfop_redirect',
                    'label' => __('Redirect', $this->text_domain),
                    'name' => '_acfop_redirect',
                    'prefix' => '',
                    'type' => 'radio',
                    'instructions' => __('If set to true, this options page will redirect to the first child page (if a child page exists). If set to false, this parent page will appear alongside any child pages. Defaults to true.<br /><em><strong>NOTE: Changing this setting will effect the location or appearance of sub options pages currently associated with this options page.</strong></em>', $this->text_domain),
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_acf_key_acfop_parent',
                                'operator' => '==',
                                'value' => 'none',
                            ),
                        ),
                    ),
                    'choices' => array(
                        1 => 'True',
                        0 => 'False',
                    ),
                    'other_choice' => 0,
                    'save_other_choice' => 0,
                    'default_value' => 1,
                    'layout' => 'horizontal',
                ),
                array(
                    'key' => 'field_acf_key_acfop_order',
                    'label' => __('Order', $this->text_domain),
                    'name' => '_acfop_order',
                    'prefix' => '',
                    'type' => 'number',
                    'instructions' => __('The order that this child menu should appear under its parent menu.', $this->text_domain),
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_acf_key_acfop_parent',
                                'operator' => '!=',
                                'value' => 'none',
                            ),
                        ),
                    ),
                    'default_value' => 0,
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                    'maxlength' => '',
                    'readonly' => 0,
                    'disabled' => 0,
                )
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => static::$post_type
                    )
                )
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => array(
                0 => 'permalink',
                1 => 'the_content',
                2 => 'excerpt',
                3 => 'custom_fields',
                4 => 'discussion',
                5 => 'comments',
                6 => 'slug',
                7 => 'author',
                8 => 'format',
                9 => 'featured_image',
                10 => 'categories',
                11 => 'tags'
            ),
        );
        register_field_group($field_group);
    } // end public function acf_include_fields

    public function acf_load_parent_menu_field($field) {
        $field['choices'] = $this->parent_menus;
        return $field;
    } // end public function acf_load_parent_menu_field

    public function acf_load_capabilities_field($field) {
        global $wp_roles;
        if (!$wp_roles || !count($wp_roles->roles)) {
            return $field;
        }
        $sorted_caps = array();
        $caps = array();
        foreach ($wp_roles->roles as $role) {
            foreach ($role['capabilities'] as $cap => $value) {
                if (!in_array($cap, $sorted_caps)) {
                    $sorted_caps[] = $cap;
                }
            } // end foreach cap
        } // end foreach role
        sort($sorted_caps);
        foreach ($sorted_caps as $cap) {
            $caps[$cap] = $cap;
        } // end foreach sorted_caps
        $field['choices'] = $caps;
        return $field;
    } // end public function


    public function admin_columns($columns) {
        global $_REQUEST;
        if (isset($_REQUEST['import_post_id']) && isset($_REQUEST['import_post_name']) ) {

            $asrPosts = static::asrCompoundPosts_via_serFiles();
            $asrPostToImport = $asrPosts[$_REQUEST['import_post_name']];
            $post_id_to_update = $_REQUEST['import_post_id'];
            static::update_dbCompoundPost($post_id_to_update,$asrPostToImport);
//            remove_query_arg($_REQUEST['import_post_id']);
//            remove_query_arg($_REQUEST['import_post_name']);
            unset($_REQUEST['import_post_id'],$_REQUEST['import_post_name']);//<-- um, probably this whole section is the wrong place, but we'll hack it
        }

        $new_columns = array();
        foreach ($columns as $index => $column) {
            if ($index == 'title') {
                $new_columns[$index] = $column;
                $new_columns['menu_text'] = __('Menu Text', $this->text_domain);
                $new_columns['slug'] = __('Slug', $this->text_domain);
                $new_columns['location'] = __('Location (Parent)', $this->text_domain);
                $new_columns['redirect'] = __('Redirect', $this->text_domain);
                $new_columns['order'] = __('Order', $this->text_domain);
                $new_columns['capability'] = __('Capability', $this->text_domain);
                $new_columns['actions'] = __('Actions', $this->text_domain); //jjr
            } else {
                if (strtolower($column) != 'date') {
                    $new_columns[$index] = $column;
                }
            }
        }
        return $new_columns;
    } // end public function admin_columns

    public function admin_columns_content($column_name, $post_id) {
        switch ($column_name) {
            case 'menu_text':
                $value = trim(get_post_meta($post_id, '_acfop_menu', true));
                if (!$value) {
                    $value = trim(get_the_title($post_id));
                }
                echo $value;
                break;
            case 'slug':
                $value = trim(get_post_meta($post_id, '_acfop_slug', true));
                if (!$value) {
                    $value = trim(get_the_title($post_id));
                    $value = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
                }
                echo $value;
                break;
            case 'location':
                $value = get_post_meta($post_id, '_acfop_parent', true);
                if (isset($this->parent_menus[$value])) {
                    echo $this->parent_menus[$value];
                } else {
                    global $acf_options_pages;
                    if (count($acf_options_pages)) {
                        foreach ($acf_options_pages as $key => $options_page) {
                            if ($key == $value) {
                                echo $options_page['menu_title'];
                            } // end if key == value
                        } // end foreach acf_options_page
                    } // end if cout acf_options_pages
                } // end if esl
                break;
            case 'capability':
                $value = get_post_meta($post_id, '_acfop_capability', true);
                echo $value;
                //the_field('_acfop_capability', $post_id);
                break;
            case 'order':
                $value = get_post_meta($post_id, '_acfop_order', true);
                if ($value != '') {
                    echo $value;
                }
                break;
            case 'redirect':
                $value = get_post_meta($post_id, '_acfop_redirect', true);
                if ($value == 1) {
                    echo 'True';
                } elseif ($value == 0 && $value != '') {
                    echo 'False';
                }
                break;
            case 'actions':
                // Motivation: show json for this options page
                // Nice To Do: Offer 'hide' version
                global $_REQUEST;
                $extraText = '';
                global $objAcfOptionsPageAdder;
                if (!isset($_REQUEST['print_jsonified'])) {
                    #$extraText =" print_jsonified is not set";

                } else {
                    if ($_REQUEST['print_jsonified'] == $post_id) {
                        $asrPost = static::asrCompoundPost_via_id($post_id);
                        $serPost = static::serFile_asrCompoundPost_via_id($post_id);



                        $fileName = $asrPost['cpt']['post_name'].'.json';
                        $extraText = "
                <br>
                To creat a persistent version, copy the json (below) into a dir/file in your plugin or theme directly called: 'DynaDevConfig/acf_options_page/$fileName'
                <hr>
                Don't forget to add a blank 'index.php' file into your dir for safety.
                <hr>
                Register your plugin by adding these lines, as appropriate
                <br><br>
                -- something here about ClsDynaDevSyncHelper exists ---
                <br><br>
                ClsDynaDevSyncHelper::be_configured_for_acf_options_page(__FILE__);
                <hr>
                " . $serPost;
                    } else {
                        //$extraText = '---'.$_REQUEST['print_jsonified'] . " != ".$post_id;
                    }
                }

                // indicate cache status / ownership (stooopid)
                $asrPosts = acfOptionsPageAdder::get_net_asrCompountPosts();
                $html = '<br>';
                $sep = '';

                foreach ($asrPosts['asrNetPosts'] as $a_post_name => $asrPost) {
                    $objThisPost = get_post($post_id,'post_name');
                    $the_row_post_name = $objThisPost->post_name;// careful...
                    if ( $the_row_post_name == $a_post_name) {
                        $hasHtml = '';

                        $hasHtml .= "<span title='{$asrPost['source_comment']}'>$a_post_name) {$asrPost['source_heritage']}^</span>";
                        if ($asrPost['source_heritage'] == 'db < json') {
                            $url = add_query_arg(["import_post_id"=> $post_id, "import_post_name"=> $a_post_name]);
                            $hasHtml .= " <a href='$url'>Click to update db with latest json</a>";
                        }
                        if ($asrPost['source_heritage'] == 'db > json') {
                            $url = add_query_arg(["export_post_id"=> $post_id, "export_post_name"=> $a_post_name]);
                            $hasHtml .= " <a href='$url'>Click to update sync db to json (tbd)</a>";
                        }


                        $html .= " $hasHtml ";//"<span title='$title'>$innerText</span> ";


                    } else {
                        //$innerText .= $sep . $the_row_post_name;
                    }



                }

                // Add items that are json only - we'll redundantly add it to the bottom of each row.  It would be way better somewhere else on the page
                if (!empty($asrPosts['asrOnlyJson'])) {
                    $html .= "<hr>==JSON only items that shouldn't be repeated each ,==<br>";
                }
                foreach ($asrPosts['asrOnlyJson'] as $a_post_name => $asrPost) {
                    $hasHtml = "<span title=''>$a_post_name) {$asrPost['source_heritage']}^ {$asrPost['source_comment']}</span>";
                    $url = add_query_arg(["import_post_id"=> $post_id, "import_post_name"=> $a_post_name]);
                    $hasHtml .= " <a href='$url'>Click to update db with latest json</a>";
                    $html .= $hasHtml;
//
//                    $hasHtml = '';
//                    $innerText = '';
//                    $title = '';
//                    $modText = '';
//                    $name = $asrPost->cpt->post_name;
//                    $innerText = "$name (pure json^)";// -->{$asrPosts['asrJsonPosts'][$this_post_name]->plugin_basename})";
//                    $modText = $asrPost->cpt->post_modified_gmt;
//                    $title = "modifed= $modText, src file = ".$asrPosts['asrJsonPosts'][$a_post_name]->plugin_basename;
//                    $hasHtml .= "<span title='$title'>$innerText</span>";
//                    $srcText = $asrPost->source;// ouch - this is embarrassing.  Say spegehtii!
//                    $src_comment = $asrPost->source_comment;
//                    $usesText = "<-- uses $srcText";
//                    $usesHtml = " <span title='$src_comment'>$usesText^</span>";
//                    $html .= " $hasHtml $usesHtml<br>";//"<span title='$title'>$innerText</span> ";

                }

                //$html .= ']';
                $extraText .= '' . $html;


                $url = add_query_arg('print_jsonified', $post_id);
                //$url = $_SERVER['PHP_SELF']."&jsonify=$post_id";

                $showExport = "
            <a href='$url'>reveal jsonified ($post_id)</a>$extraText";
                echo $showExport;

                break;
            default:
                // do nothing
                break;
        } // end switch
    } // end public function admin_columns_content




    public function build_admin_menu_list() {
        global $menu;
        //global $submenu;
        $parent_menus = array('none' => 'None');
        if (!count($menu)) {
            // bail early
            $this->parent_menus = $parent_menus;
            return;
        }
        foreach ($menu as $item) {
            if (isset($item[0]) && $item[0] != '' &&
                isset($item[2]) && !in_array($item[2], $this->exclude_locations)
            ) {
                if ($item[2] == 'edit-comments.php') {
                    $parent_menus[$item[2]] = 'Comments';
                } elseif ($item[2] == 'plugins.php') {
                    $parent_menus[$item[2]] = 'Plugins';
                } else {
                    $key = $item[2];
                    $value = $item[0];
                    if (!preg_match('/\.php/', $key)) {
                        //$key = 'admin.php?page='.$key;
                    }
                    $parent_menus[$key] = $value;
                } // end if else
            } // end if good parent menu
        } // end foreach menu
        $this->parent_menus = $parent_menus;
    } // end public function build_admin_menu_listacf_load_capabilities_field

    public function load_text_domain() {
        load_plugin_textdomain($this->text_domain, false, dirname(plugin_basename(__FILE__)) . '/lang/');
        do_action('acf_options_page/load_text_domain');
    } // end public function load_text_domain

    public function sort_by_order($a, $b) {
        if ($a['order'] == $b['order']) {
            return 0;
        } elseif ($a['order'] < $b['order']) {
            return -1;
        } else {
            return 1;
        }
    } // end public function sort_by_order

    public function activate() {
        // just in case I want to do anything on activate
    } // end public function activate

    public function deactivate() {
        // just in case I want to do anyting on deactivate
    } // end public function deactivate

    private function register_post_type() {
        // register the post type
        $args = array('label' => __('Options Pages', $this->text_domain),
            'description' => '',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'hierarchical' => false,
            'rewrite' => array('slug' => static::$post_type, 'with_front' => false),
            'query_var' => true,
            'exclude_from_search' => true,
            'menu_position' => 100,
            'menu_icon' => 'dashicons-admin-generic',
            'supports' => array('title', 'custom-fields', 'revisions'),
            'labels' => array('name' => __('Options Pages', $this->text_domain),
                'singular_name' => __('Options Page', $this->text_domain),
                'menu_name' => __('Options Pages', $this->text_domain),
                'add_new' => __('Add Options Page', $this->text_domain),
                'add_new_item' => __('Add New Options Page', $this->text_domain),
                'edit' => __('Edit', $this->text_domain),
                'edit_item' => __('Edit Options Page', $this->text_domain),
                'new_item' => __('New Options Page', $this->text_domain),
                'view' => __('View Options Page', $this->text_domain),
                'view_item' => __('View Options Page', $this->text_domain),
                'search_items' => __('Search Options Pages', $this->text_domain),
                'not_found' => __('No Options Pages Found', $this->text_domain),
                'not_found_in_trash' => __('No Options Pages Found in Trash', $this->text_domain),
                'parent' => __('Parent Options Page', $this->text_domain)));
        register_post_type(static::$post_type, $args);
    } // end private function register_post_type


    // ============== jjr Caching Helpers -begin- ==================================================================
    // This is largely about optionally persisting what we got from the db and being able to read the persisted form.
    // for some reason, I had issues simply serializing - so we put everything into json - which isn't great, but not bad.
    //  [ ] Let json w/o db entries work.
    //  TODO: We can't see anything if there are zero rows - this a real problem
    // Nice TODO: if WP_ENV isn't defined (in the wp-config), put a warning on this page
    // TODO: If templates are installed, make all non-masters be templates ??
    // TODO: Make this a stand-alone plugin
    // TODO: Offer back to community
    // History
    // Done: Export (and Load) for individual pages.
    //    For single site plugin developers, they should comment out 'be_configured_for_acf_options_page' to return
    //    off json loading.
    //
    //    For single site version-control people (people not making a plugin, but want all configs to exit the database)
    //    Well, this is just a special case of the plugin developer.  They'll need to make at least one
    //                    plugin where all of the registration happens'
    //
    //    For the multi-user admin that is doing a hot-fix: This can't happen until we can import for file.
    //
    // WP_ENV must be set to a state of: development, staging, production
    //  Implications:
    //      development
    //          Reads either DB or JSON feature.  Mildly flags if they two aren't the same.
    //          [X] Can import JSON
    //          [ ] Can update JSON
    //          ( ) Fix: After import, the JSON is now, of course, outdate.  Fix date directly in db.
    //          ( ) Add: if has both db & json and dates match, check that all values are the same
    //      staging
    //          Reads either DB or JSON.  Sternly FLAGS if two aren't the same
    //          [X] Can import JSON
    //          ( ) Add: if has both db & json and dates match, check that all values are the same
    //      production
    //          [ ] Only reads JSON.  Sternly FLAGS something
    //
    //
    //
    //
    //
    //
    //
    //




    static public function asrCompoundPosts_via_serFiles() {
        $paths = [];
        $paths = apply_filters('acf_options_page/settings/load_json', $paths);
        $asrMocks = [];
        foreach ($paths as $path) {
            // get lists of files
            $arrFiles = scandir($path);
            foreach ($arrFiles as $tailFileName) {
                if ($tailFileName != '.' && $tailFileName != '..' && $tailFileName != 'index.php') {
                    $fullFileName = $path . '/' . $tailFileName; // nicetodo: use system separator
                    $serFromFile = file_get_contents($fullFileName);
                    $asrMock = json_decode($serFromFile,true);//true=want it back as assoc
                    $asrMock['plugin_basename'] = plugin_basename($fullFileName);
                    $asrMocks[$asrMock['cpt']['post_name']] = $asrMock;
                }
            }
        }
        return $asrMocks;
    }


    public static function asrPost_via_id($post_id) {
        // hack, 'acf-options-page' shoudl be static::$post_type, but it is not static and this object isn't available, not sure why, to ClsAcfOptionsAddedAbomination
        $args = array('post_type' => 'acf-options-page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'p' => $post_id,
            'order' => 'ASC');
        $page_query = new WP_Query($args);
        $objPost = $page_query->posts[0];
        $asrPost = [];
        foreach ($objPost as $varName=>$value) {
            $asrPost[$varName] = $value;
        }
        return $asrPost;

    }


    static function asrCompoundPost_via_id($post_id) {
        $asrPost = acfOptionsPageAdder::asrPost_via_id($post_id);
        $asrMeta = get_post_meta($post_id);
        $asrPost = [
            'cpt' => $asrPost,
            'meta' => $asrMeta,
            'source' => 'db',
            'source_comment' => 'hard from db via '.__METHOD__,
        ];
        //$asrPost = json_decode(json_encode($asrPost));// just to normalize and help with testing.  Should totally be able to unwind this.
        return $asrPost;
    }

    static function asrCompoundPosts_via_db() {
        $args = array('post_type' => static::$post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'order' => 'ASC');
        $page_query = new WP_Query($args);

        $asrCompoundPosts = [];

        foreach ($page_query->posts as $objPost) {

            $asrCompoundPosts[$objPost->post_name] = [];
            $asrCompoundPosts[$objPost->post_name]['cpt'] = static::asrPost_via_id($objPost->ID);
            $asrCompoundPosts[$objPost->post_name]['meta'] = get_post_meta($objPost->ID);
            $asrCompoundPosts[$objPost->post_name]['source'] = 'db';
            $asrCompoundPosts[$objPost->post_name]['source_comment'] = 'hard from db via '.__METHOD__;

        }
        return $asrCompoundPosts;
    }

    static function update_dbCompoundPost($post_id_to_update,$asrPostToImport) {

        $ret = wp_update_post($asrPostToImport['cpt'],true);
        if (is_wp_error($ret)) {
            $errors = $ret->get_error_messages();
            foreach ($errors as $error) {
                echo $error;
            }
        }
        foreach ($asrPostToImport['meta'] as $key=>$val) {
            update_post_meta($post_id_to_update,$key,$val[0]);
        }

        echo "<div class='updated'>
                <p>I just totally totally did importing of {$_REQUEST['import_post_name']} via json into the db - but really need to make the query a one-time thing.</p>
                </div>";
    }

    static function serFile_asrCompoundPost_via_id($post_id) {
        return json_encode(static::asrCompoundPost_via_id($post_id));
    }


    static function get_net_asrCompountPosts() {
        // posts from cached db
        $asrDbPosts = static::asrCompoundPosts_via_db();

        // posts from cached files
        $asrMocks = static::asrCompoundPosts_via_serFiles();

        $asrOnlyJson = [];

        // Missing Fields
        $arrKeysFromFiles = array_keys($asrMocks);
        $arrKeysFromDb = array_keys($asrDbPosts);
        $keysInDbButNotFiles = array_diff($arrKeysFromDb, $arrKeysFromFiles);
        $keysInFilesButNotDb = array_diff($arrKeysFromFiles,$arrKeysFromDb);
        #$keysInEither = array_merge($arrKeysFromFiles,$arrKeysFromDb);
        $keysInBoth = array_intersect($arrKeysFromFiles,$arrKeysFromDb);


        // for keys in both, figure out which to use
        $asrNetPosts =[];
        foreach ($keysInDbButNotFiles as $key) {
            $asrNetPosts[$key] = $asrDbPosts[$key];
            $asrNetPosts[$key]['source'] = 'db';
            $asrNetPosts[$key]['source_heritage'] = 'db only';
            $asrNetPosts[$key]['source_comment'] = 'there is only a db source (no json), so we really have no choice';
            //$asrNetPosts[$key]['meta'] = get_post_meta($post->ID);
        }

        foreach ($keysInFilesButNotDb as $key) {
            $asrNetPosts[$key] = $asrMocks[$key];
            $asrNetPosts[$key]['source ']= 'json';
            $asrNetPosts[$key]['source_heritage'] = 'json only';
            $asrNetPosts[$key]['source_comment'] = "only json, plugin=>{$asrMocks[$key]['plugin_basename']}";
            $asrOnlyJson[$key] = $asrNetPosts[$key];
        }

        foreach ($keysInBoth as $key) {
            $modifiedDb = $asrDbPosts[$key]['cpt']['post_modified_gmt'];
            $modifiedJson = $asrMocks[$key]['cpt']['post_modified_gmt'];

            if ($modifiedDb > $modifiedJson) {
                $asrNetPosts[$key] = $asrDbPosts[$key];
                $asrNetPosts[$key]['source'] = 'db';
                $asrNetPosts[$key]['source_heritage'] = 'db > json';

                $asrNetPosts[$key]['source_comment'] = "post_modified_gmt $modifiedDb > $modifiedJson so db!";

            } else if ($modifiedDb == $modifiedJson) {
                $asrNetPosts[$key] = $asrMocks[$key];
                $asrNetPosts[$key]['source'] = 'json';
                $asrNetPosts[$key]['source_heritage'] = 'db == json';
                $asrNetPosts[$key]['source_comment'] = "post_modified_gmt $modifiedDb == $modifiedJson so using json for safety. plugin=>{$asrMocks[$key]['plugin_basename']}";

            } else if ($modifiedDb < $modifiedJson) {
                $asrNetPosts[$key] = $asrMocks[$key];
                $asrNetPosts[$key]['source'] = "json";
                $asrNetPosts[$key]['source_heritage'] = 'db < json';

                $asrNetPosts[$key]['source_comment'] = "post_modified_gmt $modifiedDb < $modifiedJson so json!";
            } else {

                print "dates are whacked: ".__FILE__.__METHOD__.__LINE__;
            }

        }

        $asr = [
            'asrNetPosts' => $asrNetPosts,
            'asrJsonPosts' => $asrMocks,
            'asrDbPosts' => $asrDbPosts,
            'asrOnlyJson' => $asrOnlyJson,
            'arrBoth' => $keysInBoth,
            #'page_query_to_use' => $page_query
        ];

        return $asr; // CPTs with data from db used over data from json.  If data isn't in db, json is used.  j

    }
    // ============== jjr Caching Helpers -END- ====================================================================


    private function acf_add_options_pages() {
        if (!function_exists('acf_add_options_sub_page')) {
            return;
        }
        error_reporting(-1);
        ini_set('display_errors', 'On');
        // get all the options pages and add them
        $options_pages = array('top' => array(), 'sub' => array());
        $asrPosts = static::get_net_asrCompountPosts()['asrNetPosts'];

        if (count($asrPosts)) {
            foreach ($asrPosts as $key=>$asrPost) {


                $title = $asrPost['cpt']['post_title'];
                $menu_text = trim($asrPost['meta']['_acfop_menu'][0]);
                if (!$menu_text) {
                    $menu_text = $title;
                }
                $slug = trim($asrPost['meta']['_acfop_slug'][0]);
                if (!$slug) {
                    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
                }

                $parent = $asrPost['meta']['_acfop_parent'][0];
                $capability = $asrPost['meta']['_acfop_capability'][0];


                if ($parent == 'none') {
                    $options_page = array('page_title' => $title,
                        'menu_title' => $menu_text,
                        'menu_slug' => $slug,
                        'capability' => $capability);
                    $redirect = true;
                    $value = $asrPost['meta']['_acfop_redirect'][0];
                    if ($value == '0') {
                        $redirect = false;
                    }
                    $options_page['redirect'] = $redirect;

                    $icon = '';
                    $value = $asrPost['meta']['_acfop_icon'][0];
                    if ($value != '') {
                        $icon = $value;
                    }
                    if ($icon) {
                        $options_page['icon_url'] = $icon;
                    }

                    $menu_position = '';
                    $value = $asrPost['meta']['_acfop_position'][0];
                    if ($value != '') {
                        $menu_position = $value;
                    }
                    if ($menu_position) {
                        $options_page['position'] = $menu_position;
                    }

                    $options_pages['top'][] = $options_page;
                } else {
                    $order = 0;
                    $value = $asrPost['meta']['_acfop_order'][0];
                    if ($value) {
                        $order = $value;
                    }
                    $options_pages['sub'][] = array('title' => $title,
                        'menu' => $menu_text,
                        'parent' => $parent,
                        'slug' => $slug,
                        'capability' => $capability,
                        'order' => $order);
                }
            } // end foreach $post;
        } // end if have_posts
        wp_reset_query();
        if (count($options_pages['top'])) {
            foreach ($options_pages['top'] as $options_page) {
                acf_add_options_page($options_page);
            }
        }
        if (count($options_pages['sub'])) {


            usort($options_pages['sub'], array($this, 'sort_by_order'));

            foreach ($options_pages['sub'] as $options_page) {
                acf_add_options_sub_page($options_page);
            }
        }
        ini_set('display_errors', 'Off');

    } // end private function acf_add_options_pages

} // end class acfOptionsPageAdder

