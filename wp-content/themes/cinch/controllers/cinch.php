<?php
/**
 * Cinch
 * cinch.php
 * This is the core cinch controller
 */

namespace cinch;

class Cinch {

    public function __construct() {

        /* Define constants */
        define('CINCH_VERSION', '0.0.1');
        define('CINCH_URL', get_stylesheet_directory_uri());
        define('CINCH_DIR', get_stylesheet_directory());
        define('ADMIN_CAPABILITY', 'manage_options');
        define(__NAMESPACE__.'\NS', __NAMESPACE__.'\\');
        define('CINCH_CLASS', NS.'Cinch');

        /* Include config file */
        @require_once(CINCH_DIR.'/config.php');

        /* Autoload classes */
        Cinch::autoLoader($autoload);

        /* Create admin UI */
        \add_action('admin_menu', array(CINCH_CLASS, 'menu'));

        /* Register admin CSS styles */
        \add_action('admin_enqueue_scripts', array(CINCH_CLASS, 'adminStyles'));

        /* Register settings */
        \add_action('admin_init', array(CINCH_CLASS, 'adminSettings'));

        /* Access control over-rides */
        \add_action('admin_init', array(CINCH_CLASS, 'accessControl'));
    }

    /* Autoloader */
    static public function autoLoader($classes) {

        foreach($classes as $className => $classPath) {
            if (!class_exists($className) && @include_once(CINCH_DIR.'/'.$classPath.'.php')) {
                global $cinch;
                @$cinch->$className = new $className;
            }
        }
    }

    /* Check for developer administrator account */
    public function isDeveloperAdmin() {

        $option = get_option('__cinch_options');
        $developer_account = (isset($option['developer_administrator_account']) ? $option['developer_administrator_account'] : '1');

        return ((
            (isset($developer_account) && is_user_logged_in() && $user = wp_get_current_user())
            && $user->data->ID === $developer_account) ? true : false
        );
    }

    /* Cinch admin UI functions */
    public static function menu() {

        //TODO: add font awesome? icon instead of image

        if ((Cinch::isDeveloperAdmin())
            || ((!Cinch::isDeveloperAdmin() && $cinchOptions = get_option('__cinch_options')) && $cinchOptions['allow_client_cinch'] === '1')) {

            $optionsPage = \add_menu_page('Options', 'Cinch', ADMIN_CAPABILITY, 'cinch', array(CINCH_CLASS, 'adminOptionsPage'), CINCH_URL.'/resources/cpticons/drill.png', 3);
            \add_submenu_page('cinch', 'Add-ons', 'Add-ons', ADMIN_CAPABILITY, 'cinch-addons', array(CINCH_CLASS, 'adminAddonsPage'));

            /* Rename top level sub menu */
            global $submenu;
            $submenu['cinch'][0][0] = 'Options';

            /* Add jQuery UI functions to admin head */
            \add_action('load-'.$optionsPage, function() {
                    \wp_enqueue_script('jquery-ui-core');
                    \wp_enqueue_script('jquery-ui-selectable');
            });
        }
    }

    /* Enqueue admin CSS */
    public static function adminStyles($hook) {
        if (strstr($hook,'page_cinch')) wp_enqueue_style('cinch-css', CINCH_URL.'/css/cinch-admin.css', array(), CINCH_VERSION);
    }

    /* Cinch administration - Settings */
    public static function adminSettings() {

        $sections = array(
            '__cinch_options' => array(
                'title' => '',
                'content' => '',
                'page' => 'cinch-options'
            ),
            '__cinch_wordpress_features' => array(
                'title' => '',
                'content' => '',
                'page' => 'cinch-disable-features'
            ),
            '__cinch_access_control' => array(
                'title' => 'Client Access Control',
                'content' => function() {
                        echo '<p>'.__('Select a menu page to disable client access to the relevant section.')."\n";
                        echo '<br />Use the <strong>CTRL</strong> key (Windows) or <strong>CMD &#8984;</strong> key (Mac) to deselect or select multiple items.</p>';

                    },
                'page' => 'cinch-access-control'
            )
        );

        /* Setup sections */
        foreach($sections as $group => $parameters) {
            \add_settings_section($group, $parameters['title'], $parameters['content'], $parameters['page']);
        }

        /* Option fields */
        $options = array(

            /* Cinch global options */
            array(
                'title' => 'Developer account',
                'group' => '__cinch_options',
                'id' => '__cinch_options',
                'sanitize' => '',
                'html' => function() {

                        $option = \get_option('__cinch_options');

                        /* Developer administrator account select */
                        $optionId = 'developer_administrator_account';

                        if (($adminUsers = \get_users(array('role' => 'administrator', 'orderby' => 'registered'))) && !empty($adminUsers)) {

                            ?>

                            <select name="__cinch_options[<?=$optionId?>]" id="__cinch_options[<?=$optionId?>]">

                                <?php foreach ($adminUsers as $user) { ?>
                                    <option value="<?=$user->data->ID?>"<?=($option[$optionId] === $user->data->ID ? ' selected="selected"' : '')?>>
                                        <?=$user->data->user_login?> (<?=$user->data->user_email?>)
                                    </option>
                                <?php } ?>

                            </select>

                            <?php } else { ?>
                                <em><?=__('No administrators were found!')?></em>
                            <?php
                        }

                        /* Allow client access to cinch */
                        $optionId = 'allow_client_cinch';
                        ?>

                        </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">
                                <label for="__cinch_options[<?=$optionId?>]"><?=__('Allow client access to Cinch')?></label>
                            </th>
                            <td>
                                <input type="checkbox" name="__cinch_options[<?=$optionId?>]" id="__cinch_options[<?=$optionId?>]" value="1"<?=($option[$optionId] == '1' ? ' checked="checked"' : '')?>>
                            </td>
                        </tr>

                        <?php

                },
                'page' => 'cinch-options',
                'default' => array(
                    'developer_administrator_account' => '1',
                    'allow_client_cinch' => '0'
                )
            ),

            /* Disable Wordpress features */
            array(
                'title' => 'Disable comments',
                'group' => '__cinch_wordpress_features',
                'id' => '__cinch_wordpress_features',
                'sanitize' => 'intval',
                'html' => function() {

                    $option = \get_option('__cinch_wordpress_features');

                    /* Disable comments */
                    $optionId = 'comments';
                    ?>

                    <input type="checkbox" name="__cinch_wordpress_features[<?=$optionId?>]" id="__cinch_wordpress_features[<?=$optionId?>]" value="1"<?=($option[$optionId] == '1' ? ' checked="checked"' : '')?>>

                    </td>
                    </tr>

                    <?php $optionId = 'attachment_pages'; ?>

                    <tr valign="top">
                        <th scope="row">
                            <label for="__cinch_options[<?=$optionId?>]"><?=__('Disable attachment pages')?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="__cinch_options[<?=$optionId?>]" id="__cinch_options[<?=$optionId?>]" value="1"<?=($option[$optionId] == '1' ? ' checked="checked"' : '')?>>
                        </td>
                    </tr>

                    <?php

                },
                'page' => 'cinch-disable-features',
                'default' => '1'
            ),

            /* Access Control */
            array(
                'title' => 'Disable client access',
                'group' => '__cinch_access_control',
                'id' => '__cinch_access_control',
                'sanitize' => '',
                'html' => function() {

                        global $menu;
                        global $submenu;

                        $option = \get_option('__cinch_access_control');
                        ?>

                        <div id="cinch-access-control">

                            <?php foreach ($menu as $menuItem) { if ($menuItem[0] == '' || $menuItem[0] === 'Cinch') continue; ?>

                                <ul class="cinch-access-control">

                                    <li class="ui-widget-content top-item" data-pointer="<?=(strstr($menuItem[2], '.php') !== false ? $menuItem[2] : 'admin.php?page='.$menuItem[2])?>">
                                        <?=preg_replace('/[0-9]+/', '', $menuItem[0])?>
                                    </li>

                                    <?php if (isset($submenu[$menuItem[2]])) foreach($submenu[$menuItem[2]] as $subMenuItem) { ?>

                                        <li class="ui-widget-content sub-item" data-pointer="<?=(strstr($subMenuItem[2], '.php') !== false ? $subMenuItem[2] : 'admin.php?page='.$subMenuItem[2])?>">
                                            <?=preg_replace('/[0-9]+/', '', $subMenuItem[0])?>
                                        </li>

                                    <?php } ?>

                                </ul>

                            <?php } ?>

                        </div>

                        <script>jQuery(function($) { $('.cinch-access-control').selectable(); });</script>

                    <?php
                },
                'page' => 'cinch-access-control',
                'default' => '1'
            )
        );

        /* Setup fields */
        foreach($options as $option) {
            //TODO: add options on theme activate?
            /*if (get_option($option['id']) == false) {
                $default = (is_array($option['default']) ? serialize($option['default']) : $option['default']);
                add_option($option['id'], $default);
            }*/
            \add_settings_field($option['id'], __($option['title']), $option['html'], $option['page'], $option['group'], array('label_for' => $option['id']));
            \register_setting($option['group'], $option['id'], $option['sanitize']);
        }
    }

    /* Cinch administration - Global options page */
    public static function adminOptionsPage() {

        $data = array (
            'action' => 'options.php'
        );
        Cinch::view('admin-options', $data);
    }

    /* Cinch administration - Addons page */
    public static function adminAddonsPage() {
        echo 'ADDONS VIEW HERE';
    }

    public static function view($fileName, $array = array()) {

        if (isset($array) && !empty($array))
            foreach ($array as $index => $value) {
                $$index = $value;
            }

        @include(CINCH_DIR.'/views/'.$fileName.'.php');
    }

    public static function accessControl() {
        if (Cinch::accessControlCheck()) return true;

        $data = array (
            'message' => 'You do not have sufficient permissions to access this page.'
        );

        Cinch::view('admin-error', $data);
        return false;
    }

    public function accessControlCheck() {
        $currentRequest = end(explode('/', add_query_arg(null, null)));

        //echo $currentRequest;

        if (!Cinch::isDeveloperAdmin()) {

            /* Check for Cinch request */
            $cinchOptions = get_option('__cinch_options');
            if (strstr($currentRequest, 'admin.php?page=cinch') && $cinchOptions['allow_client_cinch'] !== '1') return false;

            if ($currentRequest === 'edit.php') {
                return false;
            }

            return true;
        }
        return true;
    }

}

global $cinch;
$cinch = new Cinch();