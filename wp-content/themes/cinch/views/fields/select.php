<select <?=$option['attributes']?>>
<?php foreach ($option['options'] as $label => $value) { ?>
    <option value="<?=$value?>"<?=$option['populate'][$value]?>>
        <?=$label?>
    </option>
<?php } ?>
</select>