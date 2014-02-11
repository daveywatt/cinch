<?php
/**
 * @name AdminOptions
 *
 * Streamlines the process of adding admin panel options to WordPress
 *
 * @package Cinch
 *
 */

class AdminOptions extends cinch\Cinch {

    private static $scripts;
    private static $styles;

    public function __construct($pages, $subpages = array(), $template = false, $options = array())
    {

        /* Add default initializer on theme activate */

        /* Hook settings errors into admin notices */
        add_action('admin_notices', array(&$this, 'notices'));

        /* Construct class globals */
        $this->pages = $pages;
        $this->subpages = $subpages;

        $this->tabs = array();
        $this->activeTab = false;

        $this->template = $template;
        $this->options = $options;

        $this->sections = array();

        /* Initialise pages */
        $this->pages($pages);

    }

    public function pages($arguments)
    {

        foreach ($arguments as $page)
        {

            //TODO: configure css icons properly (simply add class to menu maybe?)
            $icon = ((parent::checkValidArray($page['icon']) && $page['icon']['type'] == 'css') ? null : $page['icon']['reference']);
            $callback = (($this->template && is_file($this->template)) ? array(&$this, 'template') : '');

            $menuPage = add_menu_page($page['label'], $page['label'], $page['capability'], sanitize_title($page['slug'], $page['label']), $callback, $icon, $page['position']);

            if (parent::checkValidArray($page['scripts']))
            {
                self::$scripts = $page['scripts'];
                add_action('load-'.$menuPage, array(&$this, 'scripts'), 10);
            }

            if (isset($page['styles']) && parent::checkValidArray($page['styles']))
            {
                self::$styles = $page['styles'];
                add_action('admin_enqueue_scripts', array(&$this, 'styles'));
            }

            /*if (parent::checkValidArray($page['submenus'])) {
                foreach($menu['submenus'] as $parentSlug => $submenu) {
                    //TODO: attach script/styles enqueuing to submenus
                    add_submenu_page($parentSlug, $submenu['page_title'], $submenu['menu_title'], $submenu['capability'], $submenu['slug'], $submenu['callback']);
                }
            }*/

            /*if (isset($page['top_label']) && parent::checkValidString($page['top_label'])) {
                global $submenu;
                //$submenu[$slug][0][0] = $page['top_label'];
            }*/

        }

        $this->subpages($this->subpages);
        $this->loadOptions();
    }

    public function subpages($arguments)
    {

        foreach ($arguments as $subpage)
        {
            /* Sanitize arguments */
            $subpage['slug'] = sanitize_title($subpage['slug'], $subpage['label']);
            $subpage['page'] = sanitize_title($subpage['page'], $subpage['label']);

            switch($subpage['type'])
            {
                case 'tab':
                    $this->tabs[$subpage['slug']] = $subpage;
                break;
            }
            $this->registerSection($subpage['id'], $subpage['label'], $subpage['slug']);

        }

    }

    public function tabs()
    {

        /**
         * @function tabs
         *
         * This function MUST be called in a options view or callback before the options() output if any sub pages have been defined as tabs
         * All tabs will be constructed into sections for use in the options() output before outputting the tab structure
         */

        if (!empty($this->tabs))
        {
            $this->activeTab = (isset($_GET['tab']) ? $_GET['tab'] : $this->tabs[0]['slug']);
            ?>

            <h2 class="nav-tab-wrapper">

                <?php
                foreach($this->tabs as $tab)
                {
                    echo '<a href="?page='.$tab['page'].'&tab='.$tab['slug'].'" class="nav-tab'.($this->activeTab == $tab['slug'] ? ' nav-tab-active' : '').'">'.$tab['label'].'</a>'."\n";
                }
                ?>

            </h2>

            <?php
        }

    }

    public function options()
    {
        ?>
        <form method="post" action="options.php">

            <?php

            /* Are we using tabs? */
            if ((isset($this->tabs) && !empty($this->tabs)) && isset($this->activeTab)) {
                settings_fields($this->tabs[$this->activeTab]['id']);
                do_settings_sections($this->tabs[$this->activeTab]['slug']);
            }

            /*foreach($this->tabs as $tab) {

                if (isset($this->activeTab) && );
                settings_fields($section['group']);
                do_settings_sections($section['section']);
            }*/

            ?>

            <?php submit_button('Save Options'); ?>

        </form>
        <?php
    }

    public function template()
    {
        $adminOptions = $this;
        require_once($this->template);
    }

    public static function notices()
    {
        settings_errors();
    }

    public function field($type) {
        echo '[field]';
    }

    public function loadOptions() {

        foreach($this->options as $option) {

            add_settings_field($option['id'].sanitize_title($option['label']), $option['label'], array(&$this, 'field'), $option['page'], $option['id'], array('type' => 'test')); //TODO: use callback functions on master option generator function!
            register_setting($option['id'], $option['label'], ''); //TODO: add sanitize on callback
        }
    }

    public function registerSection($group, $title, $page) {
        add_settings_section($group, $title, null, $page); //TODO: possibly add content output for callback?
    }

    public static function validateArguments($arguments)
    {

        //TODO: validate arguments parsed on each construct

    }

    public static function scripts()
    {

        foreach (self::$scripts as $script) {
            if (parent::checkValidString($script['bundled']))  {
                wp_enqueue_script($script);
                continue;
            } else {
                wp_enqueue_script($script['slug'], $script['source'], $script['dependencies'], $script['version']);
            }
        }
    }

    public static function styles($hook)
    {

        foreach (self::$styles as $style) {
            if (strstr($hook, $style['hook'])) wp_enqueue_style($style['slug'], $style['source'], $style['dependencies'], $style['version']);
        }
    }
}