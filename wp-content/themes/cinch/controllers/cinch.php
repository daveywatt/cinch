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
        $autoload = array_merge(array(
            'controllers/AdminControls'
        ), $autoload);

        foreach($autoload as $class) {
            if (!class_exists($class)) include_once(CINCH_DIR.'/'.$class.'.php');
        }

        /* Create admin UI */
        \add_action('admin_menu', array(CINCH_CLASS, 'menu'), 9999);

        /* Register admin CSS styles */
        \add_action('admin_enqueue_scripts', array(CINCH_CLASS, 'adminStyles'));

        /* Register settings */
        \add_action('admin_init', array(CINCH_CLASS, 'adminSettings'));

        /* Access control over-rides */
        \add_action('admin_init', array(CINCH_CLASS, 'accessControl'));
    }

    /* Check for developer administrator account */
    public static function isDeveloperAdmin() {

        $option = get_option('__cinch_options');
        $developer_account = (isset($option['developer_administrator_account']) ? $option['developer_administrator_account'] : '1');

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

            /*$optionsPage = \add_menu_page('Options', 'Cinch', ADMIN_CAPABILITY, 'cinch', array(CINCH_CLASS, 'adminOptionsPage'), null, 3);
            \add_submenu_page('cinch', 'Add-ons', 'Add-ons', ADMIN_CAPABILITY, 'cinch-addons', array(CINCH_CLASS, 'adminAddonsPage'));*/

            $adminPages = array(

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

            \AdminControls::menuPages($adminPages);
        }

        if (!Cinch::isDeveloperAdmin()) {

            /* Filter menu items in access control restrictions */
            global $menu, $submenu, $menuOrder;
            $accessControl = get_option('__cinch_access_control');

            /*foreach ($menu as $menuKey => $menuItem) {
                echo $menuKey;
                //if ($menuItem[2] === $accessControl['pointer'])
                   // unset($menu[$menuKey]);
            }*/

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

            /*foreach ($submenu as $subMenuKey => $subMenuItem) {
                foreach ($subMenuItem as $subMenuChild) {
                    if (Cinch::array_key_exists_r($subMenuChild[2], $accessControl))
                        unset($submenu[$subMenuKey]);
                }
            }*/
        }
    }

    /* Enqueue admin CSS */
    public static function adminStyles($hook) {
        if (strstr($hook, 'page_cinch')) wp_enqueue_style('cinch-css', CINCH_URL.'/css/cinch-admin.css', array(), CINCH_VERSION);
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
                ?>

                <div id="access-control-instructions">
                    <p><?=__('Select a menu page to disable client access to the relevant section.')?>
                    <br />Use the <strong>CTRL</strong> key (Windows) or <strong>CMD &#8984;</strong> key (Mac) to deselect or select multiple items.</p>
                </div>

                <?php

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

                        $optionID = '__cinch_access_control';
                        $option = \get_option($optionID);

                        // echo '<pre>'; print_r($option); echo '</pre>';

                        /* Sort menu array as per option order, but only if menu is not set */
                        $menuOperator = $menu;
                        if (Cinch::checkValidOperator($option)) {
                            $menuOperator = array();
                            foreach($option as $position => $menuItem) {
                                /* Check for removed menu items */
                                if (!isset($menu[$position]) || ($menu[$position][2] !== $menuItem['pointer']
                                        && 'admin.php?page='.$menu[$position][2] !== $menuItem['pointer'])) continue;
                                $menuOperator[$position] = $menu[$position];
                            }
                        }

                        $menuCounter = 0;
                        ?>

                        <p>
                            <button id="access-control-clear" class="button button-secondary">Re-enable all</button>
                        </p>

                        <div id="cinch-access-control">

                            <?php foreach ($menuOperator as $menuPosition => $menuItem) { if ($menuItem[0] === 'Cinch') continue; ?>

                                <?php
                                $menuCounter++;
                                $topItemPointer = (strstr($menuItem[2], '.php') !== false ? $menuItem[2] : 'admin.php?page='.$menuItem[2]);
                                $isRestricted = ($option[$menuPosition]['restricted'] == 'true' ? true : false);
                                $topLabel = ($option[$menuPosition]['label'] != null ?
                                    $option[$menuPosition]['label'] : trim(preg_replace('/[0-9]+/', '', strip_tags($menuItem[0]))));
                                ?>

                                <?php if ($menuItem[4] === 'wp-menu-separator') { ?>

                                    <ul class="cinch-access-control seperator">
                                        <span class="item-position-badge">
                                            <span><?=$menuCounter?></span>
                                        </span>
                                        <li class="ui-widget-content top-item" data-position="<?=$menuPosition?>" data-pointer="<?=$menuItem[2]?>">&nbsp;</li>
                                        <input type="hidden" name="<?=$optionID?>[<?=$menuPosition?>][pointer]" class="item-is-active" value="<?=$menuItem[2]?>" />
                                        <!-- <input type="hidden" name="<?=$optionID?>[<?=$menuPosition?>][label]" value="seperator" /> -->
                                        <!-- <input type="hidden" name="<?=$optionID?>[<?=$menuPosition?>][position]" class="item-position" value="<?=$menuPosition?>" /> -->
                                    </ul>

                                <?php } else {  ?>

                                    <ul class="cinch-access-control">
                                        <li class="ui-widget-content item top-item<?=($isRestricted ? ' ui-selected' : '')?>" data-position="<?=$menuPosition?>" data-pointer="<?=$topItemPointer?>" data-label="<?=$topLabel?>">
                                            <span class="item-label-rename">
                                                <?=$topLabel?>
                                            </span>
                                            <span class="item-position-badge">
                                                <span><?=$menuCounter?></span>
                                            </span>
                                            <?php //($isActive ? '<input type="hidden" name="'.$optionID.'[]['.$topItemPointer.']" id="'.$optionID.'[]['.$topItemPointer.']" value="true" />' : '')?>
                                            <input type="hidden" name="<?=$optionID?>[<?=$menuPosition?>][restricted]" class="item-is-restricted" value="<?=($isRestricted ? 'true' : 'false')?>" />
                                            <input type="hidden" name="<?=$optionID?>[<?=$menuPosition?>][pointer]" value="<?=$topItemPointer?>" />
                                            <input type="hidden" name="<?=$optionID?>[<?=$menuPosition?>][label]" class="item-label" value="<?=$topLabel?>" />
                                            <!-- <input type="hidden" name="<?=$optionID?>[<?=$menuPosition?>][position]" class="item-position" value="<?=$menuPosition?>" /> -->
                                        </li>

                                        <?php if (isset($submenu[$menuItem[2]])) { ?>

                                            <ul class="cinch-access-control-subs">

                                            <?php foreach($submenu[$menuItem[2]] as $subMenuPosition => $subMenuItem) { ?>

                                                <?php
                                                $subItemPointer = (strstr($subMenuItem[2], '.php') !== false ? $subMenuItem[2] : 'admin.php?page='.$subMenuItem[2]);
                                                $isRestricted = ($option[$menuPosition]['submenu'][$subMenuPosition]['restricted'] == 'true' ? true : false);
                                                $subLabel = ($option[$menuPosition]['submenu'][$subMenuPosition]['label'] != null ?
                                                    $option[$menuPosition]['submenu'][$subMenuPosition]['label'] : trim(preg_replace('/[0-9]+/', '', strip_tags($subMenuItem[0]))));
                                                ?>

                                                <li class="ui-widget-content item sub-item<?=($isRestricted ? ' ui-selected' : '')?>" data-position="<?=$subMenuPosition?>" data-pointer="<?=$subItemPointer?>" data-label="<?=$subLabel?>">
                                                    <span class="item-label-rename">
                                                        <?=$subLabel?>
                                                    </span>
                                                    <?php //($isActive ? '<input type="hidden" name="'.$optionID.'[]['.$subItemPointer.']" id="'.$optionID.'[]['.$subItemPointer.']" value="true" />' : '')?>
                                                    <input type="hidden" name="<?=$optionID?>[<?=$menuPosition?>][submenu][<?=$subMenuPosition?>][restricted]" class="item-is-restricted" value="<?=($isRestricted ? 'true' : 'false')?>" />
                                                    <input type="hidden" name="<?=$optionID?>[<?=$menuPosition?>][submenu][<?=$subMenuPosition?>][pointer]" value="<?=$subItemPointer?>" />
                                                    <input type="hidden" name="<?=$optionID?>[<?=$menuPosition?>][submenu][<?=$subMenuPosition?>][label]" class="item-label" value="<?=$subLabel?>" />
                                                   <!-- <input type="hidden" name="<?=$optionID?>[<?=$menuPosition?>][submenu][<?=$subMenuPosition?>][position]" class="item-position" value="<?=$subMenuPosition?>" /> -->
                                                </li>

                                            <?php } ?>

                                            </ul>

                                        <?php } ?>

                                    </ul>

                            <?php
                                }
                            }
                            ?>

                        </div>

                        <script>
                            jQuery(function($) {

                                $('#cinch-access-control').sortable({
                                    handle: '.item-position-badge',
                                    connectWith: '.cinch-column',
                                    stop: function(event, ui) {
                                        $.each($(this).find('.cinch-access-control'), function() {
                                            $(this).find('li.top-item').attr('data-position', $(this).index());
                                            $(this).find('input.item-position').val($(this).index());
                                            $(this).find('.item-position-badge span').html(($(this).index()+1));
                                        });
                                    }
                                }).disableSelection();

                                /*$('.cinch-access-control-subs').sortable({
                                    handle: '.sub-handle',
                                    stop: function(event, ui) {
                                        $.each($(this).find(''), function() {
                                            $(this).find('li').attr('data-position', $(this).index());
                                            $(this).find('input.item-position').val($(this).index());
                                        });
                                    }
                                }).disableSelection();*/

                                $('.cinch-access-control:not(.seperator)').mousedown(function(e){e.metaKey = true}).selectable({
                                        filter: 'li',
                                        cancel: '.item-position-badge',
                                        selected: function(event, ui) {
                                            $(ui.selected).find('input.item-is-restricted').val('true');
                                            $('.cinch-access-control li').find('input').blur();
                                        },
                                        unselected: function(event, ui) {
                                            //$(ui.unselected).find('input').remove();
                                            $(ui.unselected).find('input.item-is-restricted').val('false');
                                            $('.cinch-access-control li').find('input').blur();
                                        }
                                    }
                                );

                                var rename = function() {

                                    $('.cinch-access-control li span').dblclick(function(e) {

                                        e.preventDefault();

                                        var currentName = $(this).html(),
                                            fillWidth = $(this).parent().width();

                                        if (!$(this).hasClass('renaming')) {

                                            var parentItem = $(this).parent();

                                            parentItem.removeClass('ui-selected');
                                            $(this).unbind('dblclick').addClass('renaming').html('').append('<input type="text" value="'+currentName+'" />')
                                                .find('input').css({width:fillWidth+'px'}).focus().select().bind('blur', function() {

                                                var newVal = (typeof $(this).val() !== 'undefined' && $(this).val().length > 0 ? $(this).val() : currentName);
                                                $(this).parent('span.item-label-rename').removeClass('renaming').html(newVal);
                                                parentItem.find('input.item-label').val(newVal);
                                                $(this).remove();
                                                rename();
                                            });
                                        }
                                    });
                                };
                                rename();

                                $('#access-control-clear').click(function(e) {
                                    e.preventDefault();
                                    $('.item-is-restricted').val('false');
                                    $('.ui-widget-content').removeClass('ui-selected');

                                })
                            });
                        </script>

                        <?php //echo '<pre>'; print_r($option); echo '</pre>'; ?>

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

    public static function accessControlCheck() {
        $currentRequest = explode('/', \add_query_arg(null, null));
        $currentRequest = end($currentRequest);

        if (!Cinch::isDeveloperAdmin()) {

            /* Get required options */
            $cinchOptions = get_option('__cinch_options');
            $accessControl = get_option('__cinch_access_control');

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

    /* Utility functions */
    public static function array_key_exists_r($needle, $haystack) {
        foreach ($haystack as $item) if (array_key_exists($needle, $item)) return true;
        return false;
    }

    public static function in_array_r($needle, $haystack) {
        foreach ($haystack as $item) if (in_array($needle, $item)) return true;
        return false;
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

global $cinch;
$cinch = new Cinch();