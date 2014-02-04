<?php
/**
 * Cinch Admin Controls
 * admin-controls.php
 * This class streamlines wordpress admin interface API functions
 */

class AdminControls extends cinch\Cinch {

    private static $scripts;

    public function __construct() {

    }

    public static function menuPages($menus) {

        /*
         *  $adminPages = array(

                'Cinch' => array(
                    'title' => 'Options',
                    'top_title' => 'Options',
                    'capability' => ADMIN_CAPABILITY,
                    'slug' => 'cinch',
                    'function' => array(CINCH_CLASS, 'adminOptionsPage'),
                    'icon' => array('css3', 'core'),
                    'position' => 3,
                    'scripts' => array('jquery-ui-core', 'jquery-ui-selectable', 'jquery-ui-sortable'),
                    'submenus' => array(
                        'cinch' => array(
                            'page_title' => 'Add-ons',
                            'menu_title' => 'Add-ons',
                            'capability' => ADMIN_CAPABILITY,
                            'slug' => 'cinch-addons',
                            'function' => array(CINCH_CLASS, 'adminAddonsPage')
                        )
                    )
                )
            );

         */

        if (!parent::checkValidArray($menus)) return new WP_Error('empty_or_incorrect_datatype', __('Incorrect arguments for admin menu page.'));

        foreach ($menus as $label => $menu) {

            //TODO: configure css icons properly (simply add class to menu maybe?)
            $icon = ((parent::checkValidArray($menu['icon']) && $menu['icon'][0] == 'css3') ? null : $menu['icon']);

            $menuPage = add_menu_page($menu['title'], $label, $menu['capability'], $menu['slug'], $menu['function'], $icon, $menu['position']);

            if (parent::checkValidArray($menu['scripts'])) {

                self::$scripts = $menu['scripts'];
                //TODO: get all parameters for enqueuing scripts/styles
                add_action('load-'.$menuPage, array('AdminControls', 'enqueueScripts'), 10);

            }

            if (parent::checkValidArray($menu['submenus'])) {

                foreach($menu['submenus'] as $parentSlug => $submenu) {
                    //TODO: attach script/styles enqueuing to submenus
                    $subMenuPage = add_submenu_page($parentSlug, $submenu['page_title'], $submenu['menu_title'], $submenu['capability'], $submenu['slug'], $submenu['function']);
                }

            }

            if (parent::checkValidString($menu['top_title'])) {
                global $submenu;
                $submenu[$menu['slug']][0][0] = $menu['top_title'];
            }

        }

    }

    public static function enqueueScripts() {

        //TODO: full enqueue script paramaters
        foreach (self::$scripts as $script) wp_enqueue_script($script);
    }



}