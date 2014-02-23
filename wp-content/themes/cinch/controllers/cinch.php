<?php
/**
 * Cinch
 *
 * This is the core cinch controller
 *
 * IMPORTANT: All code must be supported by WordPress's minimum PHP version 5.2.4
 *
 * @package Cinch
 * @version 0.0.1
 */

define('CINCH_URL', get_template_directory_uri());
define('CINCH_DIR', get_template_directory());
define('CHILD_URL', (is_child_theme() ? get_stylesheet_directory_uri() : false));
define('CHILD_DIR', (is_child_theme() ? get_stylesheet_directory() : false));

class Cinch
{
    public static $notices;

    const VERSION = '0.0.1';
    const VERSION_KEY = 'cinch_alpha_development';
    const ADMIN_CAPABILITY = 'manage_options';
    const I18N = 'cinch';
    const CINCH = 'Cinch';

    function __construct()
    {
        /* Include config file */
        require_once CINCH_DIR.'/config.php';

        /* Class auto loading */
        self::autoload(array_merge(array('controllers/AdminOptions'), $autoload));

        /* Add notices to admin section */
        add_action('admin_notices', array(&$this, 'notices'), 10);

        /* Create admin UI */
        add_action('admin_menu', array(&$this, 'menu'), 8);

        /* Register admin global CSS styles */
        add_action('admin_enqueue_scripts', array(&$this, 'styles'));

        /* Register admin global JS */
        add_action('admin_enqueue_scripts', array(&$this, 'scripts'));

        /* Access control over-rides */
        add_action('admin_init', array(&$this, 'access'));

    }

    /* Auto loader */
    private static function autoload($classes)
    {
        foreach($classes as $class)
        if (!class_exists($class)) include_once CINCH_DIR.'/'.$class.'.php';
    }

    /* Framework version control */
    public function cinchVersion()
    {
        if (!defined('CINCH_VERSION_KEY')) define('CINCH_VERSION_KEY', self::VERSION_KEY);
        if (!defined('CINCH_VERSION_NUM')) define('CINCH_VERSION_NUM', self::VERSION);
        update_option(self::VERSION_KEY, self::VERSION);
    }

    /* Framework update check */
    public function cinchUpdate()
    {
        if (get_option(CINCH_VERSION_KEY) != self::VERSION)
        {
            //TODO: all update logic goes here
            update_option(CINCH_VERSION_KEY, self::VERSION);
        }
    }

    /* Check for developer administrator account */
    public static function isDeveloperAdmin()
    {

        $option = get_option('_cinch_options');
        $accounts = (isset($option['developer_administrator_account']) ? $option['developer_administrator_account'] : '1');

        foreach((array)$accounts as $developer)
        if ((is_user_logged_in() && $user = wp_get_current_user()) && $user->data->ID === $developer) return true;
        return false;

        /*return ((
            (isset($developer_account) && is_user_logged_in() && $user = wp_get_current_user())
            && $user->data->ID === $developer_account) ? true : false
        );*/
    }

    /* Cinch admin user interface method */
    public function menu()
    {

        //TODO: add more seed pages and test

        if ((Cinch::isDeveloperAdmin()) || ((!Cinch::isDeveloperAdmin()
                && $cinchOptions = get_option('__cinch_options'))
                && Cinch::checkValidOperator($cinchOptions)
                && (isset($cinchOptions['allow_client_cinch'])
                && $cinchOptions['allow_client_cinch'] === '1')))
        {

            $pages[] = array(

                'label' => __('Cinch', self::I18N), //menu label
                'sub_label' => __('Options', self::I18N), //if sub menu pages are defined, will rename the top label
                //'id' => '_cinch_options', //if id is provided, will assign section to this page
                'capability' => self::ADMIN_CAPABILITY, //capability required for access
                'slug' => 'cinch', //menu page slug, if not provided will default to label
                'group' => 'cinch', //if this exists, will assign to this group, otherwise will default to slug
                'icon' => array( //define icon, TODO: add support for types, images, css, custom
                    'type' => 'css',
                    'reference' => 'core'
                ),
                'position' => 3, //menu position
                'scripts' => array( //define scripts only for these pages

                    'jquery-ui' => array('bundled' => 'jquery-ui-core'),
                    'jquery-ui-selectable' => array('bundled' => 'jquery-ui-selectable'),
                    'jquery-ui-sortable' => array('bundled' => 'jquery-ui-sortable')
                )
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

            $subPages = array(

                array(
                    'page' => 'Cinch',
                    'type' => 'tab',
                    'label' => 'Cinch Options',
                    'slug' => 'options',
                    'id' => '_cinch_options',
                    'save' => 'Save Options'
                ),
                array(
                    'page' => 'cinch',
                    'type' => 'tab',
                    'label' => 'Disable Features',
                    'slug' => 'disable-features',
                    'id' => '_cinch_wordpress_features',
                    'save' => 'Disable Features'
                ),
                array(
                    'page' => 'Cinch',
                    'type' => 'tab',
                    'label' => 'Access Control',
                    'page_label' => false,
                    'slug' => 'access-control',
                    'id' => '_cinch_access_control',
                    'labels' => 0,
                    'save' => false
                )
            );

            $options = array(

                array(
                    'page' => 'options', //attach to this admin page (must match the slug of a predefined page or sub-page)
                    'label' => __('Developer accounts', self::I18N), //label for field option
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
                    'label' => __('Allow client access', self::I18N),
                    'group' => '_cinch_options',
                    'id' => array('_cinch_options', 'allow_client_access'),
                    'type' => 'checkbox',
                    'default' => 0,
                    'description' => 'Check this option to allow clients to access the cinch control panel.'
                ),
                array(
                    'page' => 'disable-features',
                    'label' => __('Disable comments', self::I18N),
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
                    'view' => 'access'
                )

            );

            new AdminOptions($pages, $subPages, 'views/options.php', $options);
        }

        /* Filter menu */
        //TODO: move this to independent function?
        //TODO: add CSS classes to menu items?

        if (!Cinch::isDeveloperAdmin())
        {
            /* Filter menu items in access control restrictions */
            global $menu, $submenu, $menuOrder;
            $accessControl = get_option('__cinch_access_control');

            /* Remove menu pages if restricted, rename if label differs */
            if (Cinch::checkValidOperator($accessControl))
            {
                foreach ($accessControl as $position => $access)
                {

                    if (isset($access['restricted']) && $access['restricted'] === 'true')
                    {
                        remove_menu_page(str_replace('admin.php?page=', null, $access['pointer']));
                    }

                    /* Rename menu item to option label */
                    if (isset($access['label']) && isset($menu[$position][0])) $menu[$position][0] = $access['label'];

                    if (!empty($access['submenu'])) foreach($access['submenu'] as $subPosition => $sub)
                    {

                        if (isset($sub['label']) && isset($submenu[$access['pointer']][$subPosition]) && $sub['label'] !== $submenu[$access['pointer']][$subPosition][0])
                            $submenu[$access['pointer']][$subPosition][0] = $sub['label'];

                        if ($sub['restricted'] === 'true')
                        {
                            $formattedSubPointer = str_replace('&', '&amp;', str_replace('admin.php?page=', '', $sub['pointer']));
                            remove_submenu_page($access['pointer'], $formattedSubPointer);
                        }
                    }
                    $menuOrder[] = $access['pointer'];
                }
            }

            /* Reorder menu items */
            if (Cinch::checkValidOperator($menuOrder))
            {
                add_filter('custom_menu_order', '__return_true');
                add_filter('menu_order', array(&$this, 'menuOrder'));
            }
        }
    }

    public function menuOrder()
    {
        global $menuOrder; return $menuOrder;
    }

    /*public static function view($reference, $array = array())
    {

        /* If an array of key => value pairs is parsed to the view, it will be assigned to variables to be made available in the view
        if (isset($array) && !empty($array))
        {
            foreach ($array as $index => $value)
            {
                $$index = $value;
                //TODO: allow developers to dynamically get wp_options etc to include?
            }
        }

        /* Load views using locateView (custom locate_template) which in turn calls load_template
        self::loadView($reference);

    }*/

    public static function view($reference, $array = array())
    {
        global $wp_query;

        /* If an array of key => value pairs is parsed to the view, they will be extracted into variables for use in templates */
        if (is_array($array) && !empty($array)) extract($array, EXTR_OVERWRITE);

        $located = false;
        foreach ((array) $reference as $file)
        {

            if (!$file) continue;
            if (file_exists(STYLESHEETPATH.'/cinch/'.$file))
            {
                $located = STYLESHEETPATH.'/cinch/'.$file;
                break;
            }
            else if (file_exists(TEMPLATEPATH.'/'.$file))
            {
                $located = TEMPLATEPATH.'/'.$file;
                break;
            }
        }

        if ($located) {

            if (is_array($wp_query->query_vars)) extract($wp_query->query_vars, EXTR_SKIP);
            require_once $located;
            return $located;
        }

        return false;
    }

    public function styles()
    {
        wp_enqueue_style('cinch', CINCH_URL.'/css/cinch.css', array(), self::VERSION);
    }

    public function scripts()
    {
        wp_enqueue_script('cinch', CINCH_URL.'/js/cinch.js', array('jquery'), self::VERSION);
    }

    public static function access()
    {
        if (Cinch::accessCheck()) return true;
        Cinch::view('error', array('message' => 'You do not have sufficient permissions to access this page.'));
        return false;
    }

    protected function accessCheck()
    {

        $currentRequest = explode('/', add_query_arg(null, null));
        $currentRequest = end($currentRequest);

        if (!Cinch::isDeveloperAdmin())
        {
            /* Get required options */
            $cinchOptions = get_option('_cinch_options');
            $accessControl = get_option('_cinch_access_control');

            /* Check for Cinch request */
            if (Cinch::checkValidOperator($cinchOptions)
                && strstr($currentRequest, 'admin.php?page=cinch') && $cinchOptions['allow_client_cinch'] !== '1') return false;

            /* Check for access control blocked pages */
            if (Cinch::checkValidOperator($accessControl))
            {
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
     *
     * getAdminUserOptions
     * Fetches all admin users from WordPress and returns array ready for an adminOptions select field
     *
     * @params none
     * @returns Array((string) user's name, (id) user's id)
     *
     */

    protected function getAdminUserOptions()
    {
        $adminUsers = array();
        foreach(get_users(array('role' => 'administrator', 'orderby' => 'registered')) as $user) {
            $adminUsers[$user->data->user_nicename.' ('.$user->data->user_email.')'] = $user->data->ID;
        }
        return $adminUsers;
    }

    /**
     *
     * notices
     * Inserts notices into the admin panel (callback)
     *
     * @return null
     *
     */

    public static function notices()
    {
        if ((isset(self::$notices) && self::checkValidArray(self::$notices) && count(self::$notices[0]) > 0)) {

            foreach(self::$notices as $notice) {
                $class = ((isset($notice['error']) && $notice['error']) ? 'error' : 'updated');
                ?>
                <div class="<?=$class?>">
                    <p><?php _e($notice['message'], self::I18N); ?></p>
                </div>
            <?php
            }
        }
    }

    public static function debugNotice($message)
    {
        if (WP_DEBUG === true) {
            if (is_array($message) || is_object($message)) {
                error_log(print_r($message, true));
            } else {
                error_log($message);
            }
        }
    }

    /* Internal utility functions */
    //TODO: do we really need these? can they do a better job here?
    public static function arrayKeyExistsRecursive($needle, $haystack)
    {
        foreach ($haystack as $item) if (array_key_exists($needle, $item)) return true;
        return false;
    }

    public static function inArrayRecursive($needle, $haystack, $return = false)
    {
        foreach ($haystack as $item) if (in_array($needle, $item))
            return ($return ? $item : true);
        return false;
    }

    public static function checkValidOperator($option)
    {
        return ((isset($option) && $option != null && !empty($option)) ? true : false);
    }

    public static function checkValidString($string)
    {
        return ((isset($string) && $string != null) ? true : false);
    }

    public static function checkValidArray($array)
    {
        return ((isset($array) && $array != null && !empty($array) && count($array) > 0) ? true : false);
    }

}

//TODO: review this, do we need to globalise the class what are the pros/cons? performance issues?
global $cinch;
$cinch = new Cinch();