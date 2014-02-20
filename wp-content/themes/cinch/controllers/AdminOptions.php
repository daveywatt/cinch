<?php
/**
 * Class AdminOptions
 *
 * Streamlines the process of adding admin options to WordPress
 *
 * @package Cinch
 *
 */

class AdminOptions extends Cinch
{

    protected static $scripts;
    protected static $styles;

    function __construct($pages, $subpages = array(), $template = false, $options = array())
    {

        /* Add default initializer on theme activate */
        //TODO: do we need to load defaults into database?

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

    protected function pages($arguments)
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

    protected function subpages($arguments)
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

    protected function tabs()
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

    protected function options()
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

            <?php submit_button('Save Options'); //TODO: allow relabel of submit button in option constructor ?>

        </form>
        <?php
    }

    public function template()
    {
        $adminOptions = $this;
        require($this->template);
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

        /* Extract serialised values from current option if required */
        if (isset($option['current']) && (isset($option['serialized']) && $option['serialized']))
            $option['current'] = (isset($option['current'][$option['sub_id']]) ? $option['current'][$option['sub_id']] : '');

        /* Add enhanced data attribute for select2 elements */
        if ($option['type'] == 'select' && isset($option['enhanced']) && $option['enhanced'] == true)
            $option['attributes']['data-enhanced'] = 'true';

        /* Format attributes */
        if (isset($option['attributes']) && parent::checkValidArray($option['attributes'])) { //TODO: attributes is now set by loadOptions, validate in top

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

        /* Populate field value for view output */
        $option['populate'] = $this->populateField($option);

        /* Field callbacks can be over-ridden by defining a function using the cinch_adminOptions_field_[TYPE] prefix */
        if (function_exists('cinch_options_field_'.$option['type']))
            return call_user_func_array('cinch_options_field_'.$option['type'], $option); //TODO: add more over-ride possibilities here?

        $viewFile = (($option['type'] == 'custom' && isset($option['view'])) ? 'views/'.$option['view'].'.php' : 'views/fields/'.$option['type'].'.php');
        $optionView = parent::view($viewFile, array('option' => $option));
        if (!$optionView) {
            parent::$notices[] = array(
                'message' => 'View file does not exist',
                'error' => true
            );
        }


        /* Custom view requested? If so, we will call the view else use a field type */
        /*if ($option['type'] == 'custom' && isset($option['view'])) {

            /* Check for child template file and over-ride if exists  //TODO: should we use locate_template here?
            $childViewPath = '/cinch/'.$option['view'].'.php';
            $cinchViewPath = '/views/'.$option['view'].'.php';

        } else {

            /* Check for child template file and over-ride if exists  //TODO: should we use locate_template here?
            $childViewPath = '/cinch/fields/'.$option['type'].'.php';
            $cinchViewPath = '/views/fields/'.$option['type'].'.php';
        }*/

        //TODO: capture view error here if it does not exist etc.

        //$viewSource = ((CHILD_DIR && is_file(CHILD_DIR.$childViewPath)) ? CHILD_DIR.$childViewPath : CINCH_DIR.$cinchViewPath);

        //TODO: tooltips on [help]
        //TODO: descriptions on fields
        //TODO: custom validation on inputs
        //TODO: sanitation on fields

        if (isset($viewSource) && is_file($viewSource)) {

            require($viewSource);

            /* Add help tooltips to view */
            if (isset($option['help']) && $option['help'] != null)
                echo '<a class="cinch-dashicon cinch-icon-help cinch-tooltip" data-toggle="tooltip" data-placement="top" title="'.$option['help'].'"><br /></a>';

            return true;
        }

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

            /* Format option id for view */
            $option['view_id'] = ((isset($option['sub_id']) && $option['sub_id'] != null) ? $option['id'].'['.$option['sub_id'].'][]' : $option['id']);

            /* Label output */
            $option['label'] = '<label class="cinch-adminoptions-label" for="'.$option['view_id'].'">'.$option['label'].'</label>'."\n";

            /* Construct attributes and merge view selector fields */
            $option['attributes'] = ((isset($option['attributes']) && parent::checkValidArray($option['attributes']) ? $option['attributes'] : array()));
            $option['attributes'] = array_merge(array('id' => $option['view_id'], 'name' => $option['view_id']), $option['attributes']);

            /* Register field and parse option array data to the field() function */
            add_settings_field($option['sub_id'], $option['label'], $callback, $option['page'], $option['group'], $option);
            register_setting($option['group'], $option['id'], null);

            /* Check for enhanced field option(s), include and configure as required */
            if (isset($option['enhanced']) && $option['enhanced']) {
                if (!wp_script_is('select2')) wp_enqueue_script('select2', CINCH_URL.'/vendor/select2/select2.min.js', array('jquery'));
                if (!wp_style_is('select2')) wp_enqueue_style('select2', CINCH_URL.'/vendor/select2/select2.css');
            }

            /* Check for tooltip / popover field option(s), include and initialise as required */
            if (isset($option['help']) && $option['help']) {
                if (!wp_script_is('cinch_bootstrap')) wp_enqueue_script('cinch_bootstrap', CINCH_URL.'/vendor/bootstrap/bootstrap.min.js', array('jquery'));
                if (!wp_style_is('cinch_bootstrap')) wp_enqueue_style('cinch_bootstrap', CINCH_URL.'/vendor/bootstrap/bootstrap.min.css');
            }

        }

    }

    public function populateField($option) {

        $population = array();

        /* Field specific operations */
        switch($option['type']) {
            case 'select':
                $population['attribute'] = ' selected';
                break;
            case 'checkbox':
                $population['attribute'] = ' checked';
                break;

            default: $population['attribute'] = false;
        }

        /* Construct and inject current field value into option array */
        if (isset($option['current']) && $option['current'] != null) { //TODO: there has to be a better way

            /* Option is a serialized array, populate each value dependant on type */
            if (is_array($option['current']) && (isset($option['options']) && is_array($option['options']))) {

                foreach($option['options'] as $value) {
                    $population[$value] = ((in_array($value, $option['current']) && $population['attribute']) ? $population['attribute'] : (in_array($value, $option['current']) ? $option['current'] : null));
                }

            } else {
                $population = (($option['current'] && $population['attribute']) ? $population['attribute'] : $option['current']);
            }

        } else { //option has not yet been saved

            if (isset($option['options']) && is_array($option['options'])) {

                foreach($option['options'] as $value) {
                    $population[$value] = (($option['default'] == $value) ? $population['attribute'] : null);
                }

            } else {
                $population = (($population['attribute'] && (isset($option['default']) && $option['default'])) ? $population['attribute'] : (isset($option['default']) ? $option['default'] : null));
            }
        }
        return $population;

    }

    public function registerSection($group, $title, $page)
    {
        add_settings_section($group, $title, null, $page); //TODO: possibly add content description output for callback?
    }

    public static function validateArguments($arguments)
    {

        //TODO: validate arguments parsed on each construct

    }

    public function scripts()
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

    public function styles($hook)
    {
        foreach (self::$styles as $style) {
            if (strstr($hook, $style['hook'])) wp_enqueue_style($style['slug'], $style['source'], $style['dependencies'], $style['version']);
        }
    }
}