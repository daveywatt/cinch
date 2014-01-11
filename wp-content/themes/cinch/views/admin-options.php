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
                ?>
                <div id="cinch-admin-options">
                    <?php
                    settings_fields('__cinch_options');
                    do_settings_sections('cinch-options');
                    ?>
                </div>
                <?php
            break;

            case 'disable-features':
                ?>
                <div id="cinch-admin-disable-features">
                    <?php
                    settings_fields('__cinch_wordpress_features');
                    do_settings_sections('cinch-disable-features');
                    ?>
                </div>
                <?php
            break;

            case 'access-control':
                ?>
                <div id="cinch-admin-access-control">
                    <?php
                    settings_fields('__cinch_access_control');
                    do_settings_sections('cinch-access-control');
                    ?>
                </div>
                <?php
            break;
        }

        submit_button('Save Options'); ?>

    </form>

</div>