<?php if (!empty($adminUsers)) { ?>

<select name="<?=$group?>[<?=$option?>]" id="<?=$group?>[<?=$option?>]">

    <?php foreach ($adminUsers as $user) { ?>
        <option value="<?=$user->data->ID?>"<?=($optionObject[$option] === $user->data->ID ? ' selected="selected"' : '')?>>
            <?=$user->data->user_login?> (<?=$user->data->user_email?>)
        </option>
    <?php } ?>

</select>

<?php } else { ?>
    <em><?=__('No administrators were found!')?></em>
<?php
}