<?php
/**
 * Cinch Admin Controls
 * admin-controls.php
 * This class streamlines the WordPress admin interface API functions
 */

class AdminControls extends cinch\Cinch {

    private static $scripts;
    private static $styles;

    public function __construct() {
    }

    public static function pages($menus) {

        if (!parent::checkValidArray($menus)) return new WP_Error('empty_or_incorrect_datatype', __('Incorrect arguments for admin menu page.'));

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
                    add_submenu_page($parentSlug, $submenu['page_title'], $submenu['menu_title'], $submenu['capability'], $submenu['slug'], $submenu['function']);
                }
            }

            if (parent::checkValidString($menu['top_title'])) {
                global $submenu;
                $submenu[$menu['slug']][0][0] = $menu['top_title'];
            }

        }
    }

    public static function sections($sections) {
        foreach($sections as $section) {
            add_settings_section($section['group'], (parent::checkValidString($section['title']) ? $section['title'] : null), $section['view'], $section['page']);
        }
    }

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