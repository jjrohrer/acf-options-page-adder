<?php
/*

*/
ini_set("display_errors","2"); ERROR_REPORTING(E_ALL);


class ClsAcfOptionsAddedAbomination  {
    function __construct() {
        ini_set("display_errors","2"); ERROR_REPORTING(E_ALL);
        //add_filter('cac/column/actions/action_links',[get_called_class(),'_admin_actions_filter'],10,4);
        #add_action('manage_acf-options-page_post_columns', [get_called_class(),'_admin_header_filter'], 10, 2);
        add_action('manage_acf-options-page_post_custom_column', [get_called_class(),'_admin_row_filter'], 10, 2);
    }


    // =========================== Modify 'Actions' column -BEGIN- =========================================================
//    // Motivation: show json for this options page
//    static function _admin_header_filter($defaults) {
//        $defaults['actions'] = 'Actions';
//        return $defaults;
//    }

    //static function _admin_row_filter($asrActions, $column_instance, $post_id) {
    function _admin_row_filter($column_name, $post_ID) {
        if ($column_name == 'actions') {
            echo "actions here.";
            return;
        }
        ini_set("display_errors","2"); ERROR_REPORTING(E_ALL);
        //https://www.admincolumns.com/documentation/developer-docs/filter-reference/cac-column_actions-action_links/

        if ($column_instance->storage_model->key == acfOptionsPageAdder::get_post_type()) {
//            print "<pre>";
//            print_r(array_keys($asrActions));
//            print "</pre>";
            global $_REQUEST;
            $extraText = '';
            global $objAcfOptionsPageAdder;
            if (!isset($_REQUEST['print_jsonified'])) {
                #$extraText =" print_jsonified is not set";
            } else if ( $_REQUEST['print_jsonified'] == $post_id) {
                $objPost = acfOptionsPageAdder::post_id_to_jsonified($post_id);
                $asrMeta = get_post_meta($post_id);
                $asrMock = ['cpt'=>$objPost, 'meta'=>$asrMeta];

                $jsonMock = json_encode($asrMock);

                $fileName = "{$post_id}.json";
                $extraText ="
                <br>
                Copy this into a dir/file in your plugin called: 'DynaDevConfig_acf_options_page/$fileName'
                <hr>
                Don't forget to add a blank 'index.php' file into your dir for safety.
                <hr>
                Register your plugin by adding these lines, as appropriate
                <br><br>
                -- something here about ClsDynaDevSyncHelper exists ---
                <br><br>
                ClsDynaDevSyncHelper::be_configured_for_acf_options_page(__FILE__);
                <hr>
                ".$jsonMock;
            } else {
                #$extraText = '---'.$_REQUEST['print_jsonified'] . " != ".$post_id;
            }

            // indicate cache status / ownership (stooopid)
            $asrPosts = acfOptionsPageAdder::get_asr_posts();
            $html = '[';
            $sep = '';
            foreach ($asrPosts['asrNetPosts'] as $this_post_id=>$asrPost) {
//                print "<pre>";
//                print_r($asrPost);
//                print "</pre>";
                // if you had time, you would
                if ($this_post_id == $post_id) {
                    $html .= "$sep<span style='background-color:yellow;'>{$asrPost->post_title}</span>";
                } else {
                    $html .= $sep.$asrPost->post_title  ;
                }
                if (in_array($this_post_id,array_keys($asrPosts['asrDbPosts']))) {
                    $html .= " <-- db";
                }
                if (in_array($this_post_id,array_keys($asrPosts['asrJsonPosts']))) {
                    $html .= " <-- json ({$asrPosts['asrJsonPosts'][$this_post_id]->plugin_basename})";
                }
                $sep='<br>&nbsp;';
            }
            $html .= ']';
            $extraText .= '<hr>'.$html;





            unset($asrActions['edit']);
            unset($asrActions['inline hide-if-no-js']);
            unset($asrActions['trash']);
            $url = add_query_arg('print_jsonified',$post_id);
            //$url = $_SERVER['PHP_SELF']."&jsonify=$post_id";
            $asrActions['showExport'] = "
            <a href='$url'>reveal jsonified ($post_id)</a>$extraText";



        } else {
            #print $column_instance->storage_model->key;
        }
        return $asrActions;
    }
    // =========================== Modify 'Actions' column -END- ===========================================================
}

// =========================== Plugin Activation Stuff -BEGIN- =========================================================
// Either method works - pick on.
$objThis = new ClsAcfOptionsAddedAbomination();

