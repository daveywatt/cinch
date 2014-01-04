<?php
/**
 * cinch
 * Cinch Administration Panel - Global options
 */
?>
<div class="wrap">

    <h2>Cinch</h2>

    <?php settings_errors(); ?>

    <form method="post" action="<?=$action?>">

        <?php settings_fields('__cinch_options'); ?>
        <?php do_settings_sections('cinch'); ?>
        <?php submit_button('Save Options'); ?>

    </form>

</div>