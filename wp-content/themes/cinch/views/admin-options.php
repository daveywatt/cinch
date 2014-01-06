<?php
/**
 * cinch
 * Cinch Administration Panel - Global options
 */
$activeTab = (isset($_GET['tab']) ? $_GET['tab'] : 'options');
?>

<div class="wrap">

    <h2>Cinch</h2>

    <?php settings_errors(); ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=cinch&tab=options" class="nav-tab<?=($activeTab == 'options' ? ' nav-tab-active' : '')?>">Cinch Options</a>
        <a href="?page=cinch&tab=disable-features" class="nav-tab<?=($activeTab == 'disable-features' ? ' nav-tab-active' : '')?>">Disable Features</a>
        <a href="?page=cinch&tab=access-control" class="nav-tab<?=($activeTab == 'access-control' ? ' nav-tab-active' : '')?>">Access Control</a>
    </h2>

    <form method="post" action="options.php">

        <?php

        switch($activeTab) {
            case 'options':
                settings_fields('__cinch_options');
                do_settings_sections('cinch-options');
            break;

            case 'disable-features':
                settings_fields('__cinch_wordpress_features');
                do_settings_sections('cinch-disable-features');
            break;

            case 'access-control':
                settings_fields('__cinch_access_control');
                do_settings_sections('cinch-access-control');
                break;
        }

        submit_button('Save Options'); ?>

    </form>

</div>