<?php
/**
 * @name options
 * Cinch Administration Page Template
 */
?>

<div class="wrap">

    <h2>Cinch</h2>

    <?php $adminOptions->tabs(); ?>

    <div id="cinch-options-container">
        <?php $adminOptions->options(); ?>
    </div>

</div>