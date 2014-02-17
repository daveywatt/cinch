<?php
/**
 * @name AdminOptions
 *
 * Streamlines the process of adding admin options to WordPress
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
         * @params none
         * @echoes tabs HTML
         *
         * This function MUST be called in a options view or callback before the options() output if any sub pages have been defined as tabs
         * All tabs will be constructed into sections for use in the options() output before outputting the tab structure
         */

        if (!empty($this->tabs))
        {
            $this->activeTab = (isset($_GET['tab']) ? $_GET['tab'] : $this->tabs[key($this->tabs)]['slug']);
            ?>

            <h2 class="nav-tab-wrapper">

                <?php
                foreach($this->tabs as $tab)
                    echo '<a href="?page='.$tab['page'].'&tab='.$tab['slug'].'" class="nav-tab'.($this->activeTab == $tab['slug'] ? ' nav-tab-active' : '').'">'.$tab['label'].'</a>'."\n";
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

    public function field($option)
    {

        //TODO: check for option validity to capture PHP errors

        /* Get current option data, merge into in existing option array - will also check if sub option exists */
        $option['current'] = get_option($option['id']);

        if (isset($option['current']) && (isset($option['serialized']) && $option['serialized'])) {
            $option['current'] = (isset($option['current'][$option['sub_id']]) ? $option['current'][$option['sub_id']] : '');
        }

        /* Field callbacks can be over-ridden by defining a function using the cinch_adminOptions_field_[TYPE] prefix */
        if (function_exists('cinch_adminOptions_field_'.$option['type'])) return call_user_func_array('cinch_adminOptions_field_'.$option['type'], $option);

        /* Custom view requested? If so, we will call the view else use a field type */
        if ($option['type'] == 'custom' && isset($option['view'])) {

            /* Check for child template file and over-ride if exists */ //TODO: should we use locate_template here?
            $childViewPath = '/cinch/'.$option['view'].'.php';
            $cinchViewPath = '/views/'.$option['view'].'.php';

        } else {

            /* Check for child template file and over-ride if exists */ //TODO: should we use locate_template here?
            $childViewPath = '/cinch/fields/'.$option['type'].'.php';
            $cinchViewPath = '/views/fields/'.$option['type'].'.php';
        }

        //TODO: capture view error here if it does not exist etc.

        $viewSource = ((CHILD_DIR && is_file(CHILD_DIR.$childViewPath)) ? CHILD_DIR.$childViewPath : CINCH_DIR.$cinchViewPath);

        /* Format option id for view */
        if (isset($option['sub_id']) && $option['sub_id'] != null)
            $option['id'] = ((isset($option['multiple']) && $option['multiple']) ? $option['id'].'['.$option['sub_id'].'][]' : $option['id'].'['.$option['sub_id'].']');

        /* Format attributes */
        if (isset($option['attributes']) && parent::checkValidArray($option['attributes'])) {

            $attributes = '';
            foreach ($option['attributes'] as $index => $value) {
                if (!is_numeric($index)) {
                    $attributes .= ' '.$index.'="'.$value.'"';
                } else {
                    $attributes .= ' '.$value;
                }
            }
        }

        $option['attributes'] = (isset($attributes) ? $attributes : null);

        /* Add enhanced data attribute for select2 elements */
        if ($option['type'] == 'select' && isset($option['enhanced']) && $option['enhanced'] == true) $option['attributes'] .= ' data-enhanced="true"';

        /* Check for defaults */
        //if (!isset($option['current']) || $option['current'] == null)
            //$option['current'] = (isset($option['default']) ? $option['default'] : (($option['type'] == 'select' || $option['type'] == 'checkbox') ? false : ''));

        //TODO: tooltips on [help]
        //TODO: descriptions on fields
        //TODO: custom validation on inputs

        //TODO: reduce amount of logic in views?

        if (isset($viewSource) && is_file($viewSource))
            return require($viewSource);

        return false;
    }

    public function loadOptions()
    {

        foreach($this->options as $option) {

            /* Determine callback, user defined or default */
            $customCallback = (($option['type'] == 'custom' && isset($option['callback']) && function_exists($option['callback'])) ? true : false);
            $callback = ($customCallback ? $option['callback'] : array(&$this, 'field'));

            /* Format option slug based on id */
            if (is_array($option['id']) && count($option['id']) == 2) {
                $option['sub_id'] = $option['id'][1];
                $option['id'] = $option['id'][0];
                $option['serialized'] = true;
            } else {
                $option['sub_id'] = $option['id'];
            }

            /*$option['sub_id'] = (isset($option['id'][1]) ? $option['id'][1] : $option['id']);
            $option['id'] = (isset($option['id'][0]) ? $option['id'][0] : $option['id']);*/

            //$option['slug'] = (isset($option['id'][1]) ? $option['id'][1] : $option['id']);

            /* Validate sanitation */
            //$option['sanitize'] = ((isset($option['sanitize']) && function_exists($option['sanitize'])) ? $option['sanitize'] : '');

            //TODO: work out how to handle sanitize here, we cant use on register_setting as it could be a serialized array

            /* Register field and parse option array data to the field() function */
            add_settings_field($option['sub_id'], $option['label'], $callback, $option['page'], $option['group'], $option);
            register_setting($option['group'], $option['id'], null);

            /* Check for enhanced field option(s), include and configure as required */
            if (isset($option['enhanced']) && $option['enhanced']) {
                if (!wp_script_is('select2')) wp_enqueue_script('select2', CINCH_URL.'/vendor/select2/select2.min.js', array('jquery'));
                if (!wp_style_is('select2')) wp_enqueue_style('select2', CINCH_URL.'/vendor/select2/select2.css');
            }

        }

    }

    public function registerSection($group, $title, $page)
    {
        add_settings_section($group, $title, null, $page); //TODO: possibly add content description output for callback?
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