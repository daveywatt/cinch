<?php
/**
 * Cinch Admin Controls
 * admin-controls.php
 * This class streamlines wordpress admin interface API functions
 */

class AdminControls extends cinch\Cinch {

    public function __construct() {

    }

    public static function menuPages($arguments) {

        if (empty($arguments) || !is_array($arguments))
            return new WP_Error('empty_or_incorrect_datatype', __('Incorrect arguments for admin menu page.'));

        print_r($arguments);

    }



}