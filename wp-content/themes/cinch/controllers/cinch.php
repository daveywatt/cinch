<?php
/**
 * @name Cinch
 *
 * This is the core cinch controller
 *
 * @package Cinch
 * @version 0.2
 */

namespace cinch;

define('CINCH_VERSION', '0.0.1');
define('CINCH_URL', get_template_directory_uri());
define('CINCH_DIR', get_template_directory());
define('CHILD_URL', (is_child_theme() ? get_stylesheet_directory_uri() : false));
define('CHILD_DIR', (is_child_theme() ? get_stylesheet_directory() : false));
define('ADMIN_CAPABILITY', 'manage_options');
define(__NAMESPACE__.'\NS', __NAMESPACE__.'\\');
define('CINCH_I18N', 'cinch');
define('CINCH_CLASS', NS.'Cinch');

class Cinch {

    public function __construct() {

        /* Include config file */
        require_once(CINCH_DIR.'/config.php');

        /* Autoload classes */
        $autoload = array_merge(array(
            'controllers/AdminControls',
            'controllers/AdminOptions'
        ), $autoload);

        foreach($autoload as $class) {
            if (!class_exists($class)) include_once(CINCH_DIR.'/'.$class.'.php');
        }

        /* Create admin UI */
        \add_action('admin_menu', array(CINCH_CLASS, 'menu'), 9999);

        /* Register admin global CSS styles */
        add_action('admin_enqueue_scripts', array(CINCH_CLASS, 'globalStyles'));

        /* Register admin global JS */
        add_action('admin_enqueue_scripts', array(CINCH_CLASS, 'globalScripts'));

        /* Register settings */
        //\add_action('admin_init', array(CINCH_CLASS, 'adminSettings'));

        /* Access control over-rides */
        \add_action('admin_init', array(CINCH_CLASS, 'accessControl'));
    }

    /* Check for developer administrator account */
    public static function isDeveloperAdmin() {

        $option = get_option('_cinch_options');
        $developer_account = (isset($option['developer_administrator_account']) ? $option['developer_administrator_account'] : '1');

        //return true;

        return ((
            (isset($developer_account) && is_user_logged_in() && $user = wp_get_current_user())
            && $user->data->ID === $developer_account) ? true : false
        );
    }

    /* Cinch admin UI functions */
    public static function menu() {

        if ((Cinch::isDeveloperAdmin())
            || ((!Cinch::isDeveloperAdmin() && $cinchOptions = get_option('__cinch_options'))
            && Cinch::checkValidOperator($cinchOptions)
            && (isset($cinchOptions['allow_client_cinch']) && $cinchOptions['allow_client_cinch'] === '1'))) {

            $pages[] = array(

                'label' => __('Cinch', CINCH_I18N), //menu label
                'sub_label' => __('Options', CINCH_I18N), //if sub menu pages are defined, will rename the top label
                //'id' => '_cinch_options', //if id is provided, will assign section to this page
                'capability' => ADMIN_CAPABILITY, //capability required for access
                'slug' => 'cinch', //menu page slug, if not provided will default to label
                'group' => 'cinch', //if this exists, will assign to this group, otherwise will default to slug
                'icon' => [ //define icon, TODO: add support for types, images, css, custom
                    'type' => 'css',
                    'reference' => 'core'
                ],
                'position' => 3, //menu position
                'scripts' => [ //define scripts only for these pages
                    'jquery-ui' => [
                        'bundled' => 'jquery-ui-core'
                    ],
                    'jquery-ui-selectable' => [
                        'bundled' => 'jquery-ui-selectable'
                    ],
                    'jquery-ui-sortable' => [
                        'bundled' => 'jquery-ui-sortable'
                    ]
                ]
                /*'styles' => array( //define styles only for these pages
                    'main' => array(
                        'hook' => 'page_cinch',
                        'slug' => 'cinch-css',
                        'source' => CINCH_URL.'/css/cinch-admin.css',
                        'dependencies' => array(),
                        'version' => CINCH_VERSION
                    )
                )*/
            );

            $subpages = array(

                array(
                    'page' => 'Cinch',
                    'type' => 'tab',
                    'label' => 'Cinch Options',
                    'slug' => 'options',
                    'id' => '_cinch_options'
                ),
                array(
                    'page' => 'cinch',
                    'type' => 'tab',
                    'label' => 'Disable Features',
                    'slug' => 'disable-features',
                    'id' => '_cinch_wordpress_features'
                ),
                array(
                    'page' => 'Cinch',
                    'type' => 'tab',
                    'label' => 'Access Control',
                    'slug' => 'access-control',
                    'id' => '_cinch_access_control',
                    'labels' => false
                )
            );

            $options = array(

                array(
                    'page' => 'options', //attach to this admin page (must match the slug of a predefined page or sub-page)
                    'label' => __('Developer accounts', CINCH_I18N), //label for field option
                    'description' => '', //will include this description in HTML if provided
                    'group' => '_cinch_options', //this must match the options group as defined in the associated page or sub-page
                    'id' => array('_cinch_options', 'developer_accounts'), //if id is an array, will parse second parameter into a serialised array
                    'multiple' => true, //if true, will register array field for multiple values
                    'type' => 'select', //this will result in relevant field include
                    'attributes' => array('multiple', 'placeholder' => 'Please select developer accounts'), //must be array, any attributes here will be added to field tag
                    'enhanced' => true, //if true, uses select2 for advanced input field controls, can also be array of select2 options
                    'sanitize' => null, //if exists will parse this function as sanitize parameter
                    'callback' => '', //if callback is present, use custom callback function (ARRAY WITH EXTRA ARGUMENTS) type must be defined as custom!
                    'options' => self::getAdminUserOptions(), //if select field will accept options as array key => values (label => value)
                    'default' => '1', //will use this default if option not set
                    'help' => _('Choose developer accounts from the list of administrators.')
                ),
                array(
                    'page' => 'options',
                    'label' => __('Allow client access', CINCH_I18N),
                    'group' => '_cinch_options',
                    'id' => array('_cinch_options', 'allow_client_access'),
                    'type' => 'checkbox',
                    'default' => 0,
                    'description' => 'Check this option to allow clients to access the cinch control panel.'
                ),
                array(
                    'page' => 'disable-features',
                    'label' => __('Disable comments', CINCH_I18N),
                    'group' => '_cinch_wordpress_features',
                    'id' => '_cinch_wordpress_features',
                    'sanitize' => 'intval',
                    'type' => 'checkbox',
                    'default' => false,
                    'help' => 'Checking this option will disable all comments.'
                ),
                array(
                    'page' => 'access-control',
                    'label' => null,
                    'group' => '_cinch_access_control',
                    'id' => '_cinch_access_control',
                    'type' => 'custom',
                    'view' => 'access-control'
                )

            );

            new \AdminOptions($pages, $subpages, CINCH_DIR.'/views/admin-options.php', $options);
        }

        if (!Cinch::isDeveloperAdmin()) {

            /* Filter menu items in access control restrictions */
            global $menu, $submenu, $menuOrder;
            $accessControl = get_option('__cinch_access_control');

            /* Remove menu pages if restricted, rename if label differs */
            if (Cinch::checkValidOperator($accessControl)) {
                foreach ($accessControl as $position => $access) {

                    if (isset($access['restricted']) && $access['restricted'] === 'true') {
                        \remove_menu_page(str_replace('admin.php?page=', '', $access['pointer']));
                    }

                    /* Rename menu item to option label */
                    if (isset($access['label']) && isset($menu[$position][0])) $menu[$position][0] = $access['label'];

                    if (!empty($access['submenu'])) foreach($access['submenu'] as $subPosition => $sub) {

                        if (isset($sub['label']) && isset($submenu[$access['pointer']][$subPosition]) && $sub['label'] !== $submenu[$access['pointer']][$subPosition][0])
                            $submenu[$access['pointer']][$subPosition][0] = $sub['label'];

                        if ($sub['restricted'] === 'true') {
                            $formattedSubPointer = str_replace('&', '&amp;', str_replace('admin.php?page=', '', $sub['pointer']));
                            \remove_submenu_page($access['pointer'], $formattedSubPointer);
                        }
                    }
                    $menuOrder[] = $access['pointer'];
                }
            }

            /* Reorder menu items */
            if (Cinch::checkValidOperator($menuOrder))  {
                \add_filter('custom_menu_order', '__return_true');
                \add_filter('menu_order', function() { global $menuOrder; return $menuOrder; });
            }
        }
    }

    public static function view($fileName, $array = array()) {

        if (isset($array) && !empty($array)) {
            foreach ($array as $index => $value) {
                $$index = $value;
                if ($index === 'group') {
                    $optionObject = \get_option('group');
                }
            }
        }
        include(CINCH_DIR.'/views/'.$fileName.'.php');
    }

    public static function globalStyles() {
        \wp_enqueue_style('cinch', CINCH_URL.'/css/cinch.css', array(), CINCH_VERSION);
    }

    public static function globalScripts() {
        \wp_enqueue_script('cinch', CINCH_URL.'/js/cinch.js', array('jquery'), CINCH_VERSION);
    }

    public static function accessControl() {

        if (Cinch::accessControlCheck()) return true;
        $data = array (
            'message' => 'You do not have sufficient permissions to access this page.'
        );
        Cinch::view('admin-error', $data);
        return false;
    }

    public static function accessControlCheck() {

        $currentRequest = explode('/', \add_query_arg(null, null));
        $currentRequest = end($currentRequest);

        if (!Cinch::isDeveloperAdmin()) {

            /* Get required options */
            $cinchOptions = get_option('_cinch_options');
            $accessControl = get_option('_cinch_access_control');

            /* Check for Cinch request */
            if (Cinch::checkValidOperator($cinchOptions)
                && strstr($currentRequest, 'admin.php?page=cinch') && $cinchOptions['allow_client_cinch'] !== '1') return false;

            /* Check for access control blocked pages */
            if (Cinch::checkValidOperator($accessControl)) {
                foreach ($accessControl as $access) {
                    if ($access['pointer'] === $currentRequest && $access['restricted'] === 'true') return false;
                    if (!empty($access['submenu'])) foreach($access['submenu'] as $sub) {
                        if (in_array($currentRequest, $sub) && $sub['restricted'] === 'true') return false;
                    }
                }
            }
            return true;
        }
        return true;
    }

    /**
     * @function getAdminUserOptions
     * @params none
     * @returns Array((string) user's name, (id) user's id)
     *
     * Fetches all admin users from WordPress and returns array ready for an adminOptions select field
     */

    public static function getAdminUserOptions() {
        $adminUsers = array();
        foreach(\get_users(array('role' => 'administrator', 'orderby' => 'registered')) as $user) {
            $adminUsers[$user->data->user_nicename.' ('.$user->data->user_email.')'] = $user->data->ID;
        }
        return $adminUsers;
    }

    /* Utility functions */
    public static function arrayKeyExistsRecursive($needle, $haystack) {
        foreach ($haystack as $item) if (array_key_exists($needle, $item)) return true;
        return false;
    }

    public static function inArrayRecursive($needle, $haystack, $return = false) {
        foreach ($haystack as $item) if (in_array($needle, $item))
            return ($return ? $item : true);
        return false;
    }

    public static function viewExists($fileName) {
        return (is_file(CINCH_DIR.'/views/'.$fileName.'.php'));
    }

    public static function checkValidOperator($option) {
        return ((isset($option) && $option != null && !empty($option)) ? true : false);
    }

    public static function checkValidString($string) {
        return ((isset($string) && $string != null) ? true : false);
    }

    public static function checkValidArray($array) {
        return ((isset($array) && $array != null && !empty($array) && count($array) > 0) ? true : false);
    }

}

//TODO: review this, do we need to globalise the class?
global $cinch;
$cinch = new Cinch();