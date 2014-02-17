<select name="<?=$option['id']?>" id="<?=$option['id']?>"<?=$option['attributes']?>>

<?php foreach ($option['options'] as $optionLabel => $optionValue) { ?>
    <option value="<?=$optionValue?>"<?=($optionValue == $option['current'] ? ' selected="selected"' : ($optionValue == $option['default'] ? ' selected="selected"' : ''))?>>
        <?=$optionLabel?>
    </option>
<?php } ?>

</select>