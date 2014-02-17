<select name="<?=$option['id']?>" id="<?=$option['id']?>"<?=$option['attributes']?>>
<?php foreach ($option['options'] as $optionLabel => $optionValue) { ?>
    <option value="<?=$optionValue?>"<?=(($optionValue == $option['current'] || (is_array($option['current']) && in_array($optionValue, $option['current']))) ? ' selected="selected"' : '')?>>
        <?=$optionLabel?>
    </option>
<?php } ?>
</select>