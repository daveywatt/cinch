<?php
/**
 * cinch
 * Cinch Administration Panel - Global options
 */
?>

<div class="wrap">

    <h2>Cinch</h2>

    <?php $adminOptions->tabs(); ?>

    <div id="cinch-options-container">
        <?php $adminOptions->options(); ?>
    </div>

    <?php

    /*switch($activeTab) {

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

    submit_button('Save Options'); */
    ?>

</div>