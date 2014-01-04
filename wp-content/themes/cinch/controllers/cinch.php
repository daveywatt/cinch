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

        /* Register settings */
        \add_action('admin_init', array(CINCH_CLASS, 'adminSettings'));
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
        return ((
            (isset($developer_account) && is_user_logged_in() && $user = wp_get_current_user())
            && current_user_can(ADMIN_CAPABILITY) && $user->user_login === $developer_account) ? true : false
        );
    }

    /* Cinch admin UI functions */
    public static function menu() {

        //TODO: add font awesome? icon instead of image
        \add_menu_page('Options', 'Cinch', ADMIN_CAPABILITY, 'cinch', array(CINCH_CLASS, 'adminOptionsPage'), CINCH_URL.'/resources/cpticons/burn.png', 3);
        \add_submenu_page('cinch', 'Access Control', 'Access Control', ADMIN_CAPABILITY, 'cinch-access', array(CINCH_CLASS, 'adminAccessPage'));

    }

    /* Cinch administration - Settings */
    public static function adminSettings() {

        $sections = array(
            '__cinch_options' => array(
                'title' => 'Global Options',
                'content' => '',
                'page' => 'cinch'
            ),
            /*'cinch-wordpress-features' => array(
                'title' => 'Wordpress Features',
                'content' => null,
                'page' => 'cinch'
            )*/
        );

        /* Setup sections */
        foreach($sections as $group => $parameters) {
            \add_settings_section($group, $parameters['title'], $parameters['content'], $parameters['page']);
        }

        /* Option fields */
        $options = array(

            array(
                'title' => 'Developer account',
                'group' => '__cinch_options',
                'id' => '__cinch_options',
                'sanitize' => null,
                'html' => function() {

                        if (($adminUsers = \get_users(array('role' => 'administrator', 'orderby' => 'registered'))) && !empty($adminUsers)) {

                            echo '<select name="__cinch_options[developer_administrator_account]" id="__cinch_options[developer_administrator_account]">';

                            foreach ($adminUsers as $user) {

                                $option = get_option('__cinch_options');

                                echo '<option value="'.$user->data->ID.'" '.( $option['developer_administrator_account'] === $user->data->ID ? ' selected="selected"' : '').'>'
                                    .$user->data->user_login.' ('.$user->data->user_email.')
                                    </option>'."\n";
                            }

                            echo '</select>';

                        } else { echo '<em>No administrators were found!</em>'; }

                },
                'page' => 'cinch',
                'default' => '1'
            ),
            /*array(
                'title' => 'Disable comments',
                'group' => 'cinch-wordpress-features',
                'id' => '__cinch__wordpress_features_comments',
                'sanitize' => null,
                'html' => function() {

                    echo '<input type="checkbox" name="__cinch__wordpress_features_comments" id="__cinch__wordpress_features_comments" value="1">';

                },
                'page' => 'cinch',
                'default' => '1'
            ),
            array(
                'title' => 'Disable attachment pages',
                'group' => 'cinch-wordpress-features',
                'id' => '__cinch__wordpress_features_attachment_pages',
                'sanitize' => null,
                'html' => function() {

                    $option = get_option('__cinch__wordpress_features_attachment_pages');

                    echo $option;

                    echo '<input type="checkbox" name="__cinch__wordpress_features_attachment_pages" id="__cinch__wordpress_features_attachment_pages" value="1">';

                },
                'page' => 'cinch',
                'default' => '1'
            )*/

        );

        /* Setup fields */
        foreach($options as $option) {
            //if (get_option($option['id']) == false) add_option($option['id'], $option['default']);
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

    /* Cinch administration - Access control page */
    public static function adminAccessPage() {
        echo 'ACCESS VIEW HERE';
    }

    public static function view($fileName, $array = array()) {

        if (isset($array) && !empty($array))
            foreach ($array as $index => $value) {
                $$index = $value;
            }

        @include(CINCH_DIR.'/views/'.$fileName.'.php');
    }

}

global $cinch;
$cinch = new Cinch();