<div id="access-control-instructions" class="cinch-option-instruction-box">
    <p><?php _e('Select a menu page to disable client access to the relevant section.'); ?>
        <br />Use the <strong>CTRL</strong> key (Windows) or <strong>CMD &#8984;</strong> key (Mac) to deselect or select multiple items.</p>
</div>

<div id="cinch-access-options" class="cinch-options-box-wrapper">

    <div class="cinch-options-box-header">
        <h3><?php _e('Client Access Control'); ?></h3>
        <?php submit_button('Save access settings'); ?>
    </div>

    <div class="cinch-options-box-body">

        <ul class="cinch-access-item">

            <li class="nav-menus-php menu-item">
                <dl class="menu-item-bar">
                    <dt class="menu-item-handle cinch-access-control-item-handle">
                        <span class="item-title">
                            <span class="menu-item-title">Dashboard</span>
                        </span>
                        <span class="item-controls">
                            <span class="cinch-item-lock cinch-state-locked">
                                <a class="cinch-dashicon cinch-icon-lock cinch-lock-button" title="<?php _e('Lock this page from client access'); ?>"><br /></a>
                            </span>
                            <a class="item-edit cinch-access-item-toggle-parent" id="" title="Toggle Settings" href="#">Toggle Settings</a>
                        </span>
                    </dt>
                </dl>

                <div class="menu-item-settings cinch-parent-settings" id="">

                    <p class="description description-wide">
                        <label for="">Menu Label</label>
                        <input type="text" id="" name="" class="widefat edit-menu-item-title"  value="Dashboard">
                    </p>

                    <p class="description description-wide">

                        <label for="edit-menu-item-title-10">Sub Items</label>

                        <ul class="cinch-access-sub-item">
                            <li class="nav-menus-php menu-item">
                                <dl class="menu-item-bar">
                                    <dt class="menu-item-handle cinch-access-control-item-handle">
                                        <span class="item-title">
                                            <span class="menu-item-title">Sub menu item</span>
                                        </span>
                                        <span class="item-controls">
                                            <span class="cinch-item-lock cinch-state-locked">
                                                <a class="cinch-dashicon cinch-icon-lock cinch-lock-button" title="<?php _e('Lock this page from client access'); ?>"><br /></a>
                                            </span>
                                            <a class="item-edit cinch-access-item-toggle-child" id="" title="Toggle Settings" href="#">Toggle Settings</a>
                                        </span>
                                    </dt>
                                </dl>

                                <div class="menu-item-settings cinch-child-settings" id="">
                                    <p class="description description-wide">
                                        <label for="">Menu Label</label>
                                        <input type="text" id="" name="" class="widefat edit-menu-item-title"  value="Sub menu item">
                                    </p>
                                </div>
                            </li>
                        </ul>

                    </p>

                </div>
            </li>
        </ul>

    </div>

    <div class="cinch-options-box-footer">
        <span>Administration pages restricted 3/10</span>
        <?php submit_button('Save access settings'); ?>
    </div>

</div>





<?php

global $menu;
global $submenu;

/* Sort menu array as per option order, but only if menu is not set */
$menuOperator = $menu;
if (Cinch::checkValidOperator($option['current'])) {
    $menuOperator = array();
    foreach($option['current'] as $position => $menuItem) {
        /* Check for removed menu items */
        if (!isset($menu[$position]) || ($menu[$position][2] !== $menuItem['pointer']
        && 'admin.php?page='.$menu[$position][2] !== $menuItem['pointer'])) continue;
        $menuOperator[$position] = $menu[$position];
    }
}

$menuCounter = 0;
?>
<!--
<p><button id="access-control-clear" class="button button-secondary">Re-enable all</button></p>

<div id="cinch-access-control">

    <?php foreach ($menuOperator as $menuPosition => $menuItem) { if ($menuItem[0] === 'Cinch') continue; ?>

        <?php
        $menuCounter++;
        $topItemPointer = (strstr($menuItem[2], '.php') !== false ? $menuItem[2] : 'admin.php?page='.$menuItem[2]);
        $isRestricted = ((isset($option['current'][$menuPosition]['restricted']) && $option['current'][$menuPosition]['restricted'] == 'true') ? true : false);
        $topLabel = ((isset($option['current'][$menuPosition]['label']) && $option['current'][$menuPosition]['label'] != null) ?
            $option['current'][$menuPosition]['label'] : trim(preg_replace('/[0-9]+/', '', strip_tags($menuItem[0]))));
        ?>

        <?php if ($menuItem[4] === 'wp-menu-separator') { ?>

            <ul class="cinch-access-control seperator">
                <span class="item-position-badge">
                    <span><?=$menuCounter?></span>
                </span>
                <li class="ui-widget-content top-item" data-position="<?=$menuPosition?>" data-pointer="<?=$menuItem[2]?>">&nbsp;</li>
                <input type="hidden" name="<?=$option['id']?>[<?=$menuPosition?>][pointer]" class="item-is-active" value="<?=$menuItem[2]?>" />
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
                    <?php //($isActive ? '<input type="hidden" name="'.$option['current'].'[]['.$topItemPointer.']" id="'.$option['current'].'[]['.$topItemPointer.']" value="true" />' : '')?>
                    <input type="hidden" name="<?=$option['id']?>[<?=$menuPosition?>][restricted]" class="item-is-restricted" value="<?=($isRestricted ? 'true' : 'false')?>" />
                    <input type="hidden" name="<?=$option['id']?>[<?=$menuPosition?>][pointer]" value="<?=$topItemPointer?>" />
                    <input type="hidden" name="<?=$option['id']?>[<?=$menuPosition?>][label]" class="item-label" value="<?=$topLabel?>" />
                </li>

                <?php if (isset($submenu[$menuItem[2]])) { ?>

                    <ul class="cinch-access-control-subs">

                        <?php foreach($submenu[$menuItem[2]] as $subMenuPosition => $subMenuItem) { ?>

                            <?php
                            $subItemPointer = (strstr($subMenuItem[2], '.php') !== false ? $subMenuItem[2] : 'admin.php?page='.$subMenuItem[2]);
                            $isRestricted = ((isset($option['current'][$menuPosition]['submenu'][$subMenuPosition]['restricted']) && $option['current'][$menuPosition]['submenu'][$subMenuPosition]['restricted']) == 'true' ? true : false);
                            $subLabel = ((isset($option['current'][$menuPosition]['submenu'][$subMenuPosition]['label']) && $option['current'][$menuPosition]['submenu'][$subMenuPosition]['label'] != null) ?
                                $option['current'][$menuPosition]['submenu'][$subMenuPosition]['label'] : trim(preg_replace('/[0-9]+/', '', strip_tags($subMenuItem[0]))));
                            ?>

                            <li class="ui-widget-content item sub-item<?=($isRestricted ? ' ui-selected' : '')?>" data-position="<?=$subMenuPosition?>" data-pointer="<?=$subItemPointer?>" data-label="<?=$subLabel?>">
                                <span class="item-label-rename">
                                    <?=$subLabel?>
                                </span>
                                <?php //($isActive ? '<input type="hidden" name="'.$option['current'].'[]['.$subItemPointer.']" id="'.$option['current'].'[]['.$subItemPointer.']" value="true" />' : '')?>
                                <input type="hidden" name="<?=$option['id']?>[<?=$menuPosition?>][submenu][<?=$subMenuPosition?>][restricted]" class="item-is-restricted" value="<?=($isRestricted ? 'true' : 'false')?>" />
                                <input type="hidden" name="<?=$option['id']?>[<?=$menuPosition?>][submenu][<?=$subMenuPosition?>][pointer]" value="<?=$subItemPointer?>" />
                                <input type="hidden" name="<?=$option['id']?>[<?=$menuPosition?>][submenu][<?=$subMenuPosition?>][label]" class="item-label" value="<?=$subLabel?>" />
                                <!-- <input type="hidden" name="<?=$option['id']?>[<?=$menuPosition?>][submenu][<?=$subMenuPosition?>][position]" class="item-position" value="<?=$subMenuPosition?>" />
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

-->

<script>
jQuery(function ($) {

    $('#cinch-access-control').sortable({
        handle: '.item-position-badge',
        connectWith: '.cinch-column',
        stop: function (event, ui) {
            $.each($(this).find('.cinch-access-control'), function () {
                $(this).find('li.top-item').attr('data-position', $(this).index());
                $(this).find('input.item-position').val($(this).index());
                $(this).find('.item-position-badge span').html(($(this).index() + 1));
            });
        }
    }).disableSelection();

    $('.cinch-access-control-subs').sortable({
        handle: '.sub-handle',
        stop: function (event, ui) {
            $.each($(this).find(''), function () {
                $(this).find('li').attr('data-position', $(this).index());
                $(this).find('input.item-position').val($(this).index());
            });
        }
    }).disableSelection();

    $('.cinch-access-control:not(.seperator)').mousedown(function (e) {
        e.metaKey = true
    }).selectable({
            filter: 'li',
            cancel: '.item-position-badge',
            selected: function (event, ui) {
                $(ui.selected).find('input.item-is-restricted').val('true');
                $('.cinch-access-control li').find('input').blur();
            },
            unselected: function (event, ui) {
                //$(ui.unselected).find('input').remove();
                $(ui.unselected).find('input.item-is-restricted').val('false');
                $('.cinch-access-control li').find('input').blur();
            }
        }
    );

    var rename = function () {

        $('.cinch-access-control li span').dblclick(function (e) {

            e.preventDefault();

            var currentName = $(this).html(),
                fillWidth = $(this).parent().width();

            if (!$(this).hasClass('renaming')) {

                var parentItem = $(this).parent();

                parentItem.removeClass('ui-selected');
                $(this).unbind('dblclick').addClass('renaming').html('').append('<input type="text" value="' + currentName + '" />')
                    .find('input').css({width: fillWidth + 'px'}).focus().select().bind('blur', function () {

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

    $('#access-control-clear').click(function (e) {
        e.preventDefault();
        $('.item-is-restricted').val('false');
        $('.ui-widget-content').removeClass('ui-selected');

    })
});
</script>