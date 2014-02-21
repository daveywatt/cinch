<?php
/**
 * options View
 * Cinch administration base page template
 */
?>

<div class="wrap">

    <h2>Cinch</h2>

    <?php $options->tabs(); ?>

    <div id="cinch-options-container" class="<?php $options->optionClass(); ?>">
        <?php $options->options(); ?>
    </div>

</div>