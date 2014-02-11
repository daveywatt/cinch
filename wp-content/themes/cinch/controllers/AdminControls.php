<?php
/**
 * Cinch Admin Controls
 * admin-controls.php
 * This class streamlines the WordPress admin interface API functions
 */

class AdminControls extends cinch\Cinch {

    private static $scripts;
    private static $styles;
    private static $sections;

    public function __construct() {

        add_action('admin_notices', array(&$this, 'settingsNotices'));
    }

    public static function pages($menus) {

        if (!parent::checkValidArray($menus)) return new WP_Error('cinch_empty_or_incorrect_datatype', __('Cinch error: incorrect arguments for admin menu page.')); //TODO: own error handler

        foreach ($menus as $label => $menu) {

            //TODO: configure css icons properly (simply add class to menu maybe?)
            $icon = ((parent::checkValidArray($menu['icon']) && $menu['icon'][0] == 'css3') ? null : $menu['icon']);

            $menuPage = add_menu_page($menu['title'], $label, $menu['capability'], $menu['slug'], $menu['callback'], $icon, $menu['position']);

            if (parent::checkValidArray($menu['scripts'])) {
                self::$scripts = $menu['scripts'];
                add_action('load-'.$menuPage, array('AdminControls', 'enqueueScripts'), 10);
            }

            if (parent::checkValidArray($menu['styles'])) {
                self::$styles = $menu['styles'];
                add_action('admin_enqueue_scripts', array('AdminControls', 'enqueueStyles'));
            }

            if (parent::checkValidArray($menu['submenus'])) {
                foreach($menu['submenus'] as $parentSlug => $submenu) {
                    //TODO: attach script/styles enqueuing to submenus
                    add_submenu_page($parentSlug, $submenu['page_title'], $submenu['menu_title'], $submenu['capability'], $submenu['slug'], $submenu['callback']);
                }
            }

            if (parent::checkValidString($menu['top_title'])) {
                global $submenu;
                $submenu[$menu['slug']][0][0] = $menu['top_title'];
            }

        }
    }

    public static function sections($sections) {
        self::$sections = $sections;
        add_action('admin_init', array('AdminControls', 'adminSectionsCallback'));
    }

    public static function adminSectionsCallback() {

        foreach(self::$sections as $section) {

            add_settings_section($section['group'], (parent::checkValidString($section['title']) ? $section['title'] : null), $section['callback'], $section['page']);

            if ((isset($section['fields']) && parent::checkValidArray($section['fields']))) {

                foreach($section['fields'] as $field) {

                    //TODO: add options with defaults on theme activate?
                    if (get_option($section['group']) == false) {
                        $default = (is_array($field['default']) ? serialize($field['default']) : $field['default']);
                        add_option($section['group'], $default);
                    }

                    add_settings_field($field['id'], __($field['title']), $field['callback'], $section['page'], $section['group'], array('label_for' => $section['group']));
                    register_setting($section['group'], $section['group'], $field['sanitize']);
                }
            }
        }
    }

    /*public static function fields($fields) {

        foreach($fields as $field) {
            //TODO: add options with defaults on theme activate?
            if (get_option($field['id']) == false) {
                $default = (is_array($field['default']) ? serialize($field['default']) : $field['default']);
                add_option($field['id'], $default);
            }
            \add_settings_field($field['id'], __($field['title']), $field['html'], $field['page'], $field['group'], array('label_for' => $field['id']));
            \register_setting($field['group'], $field['id'], $field['sanitize']);
        }
    }*/

    public static function enqueueScripts() {

        foreach (self::$scripts as $script) {
            if (parent::checkValidString($script['bundled']))  {
                wp_enqueue_script($script);
                continue;
            } else {
                wp_enqueue_script($script['slug'], $script['source'], $script['dependencies'], $script['version']);
            }
        }
    }

    public static function enqueueStyles($hook) {

        foreach (self::$styles as $style) {
            if (strstr($hook, $style['hook'])) wp_enqueue_style($style['slug'], $style['source'], $style['dependencies'], $style['version']);
        }
    }

}