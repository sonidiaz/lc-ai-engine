<?php
/*
Plugin Name: La Cabrera AI Engine: 
Plugin URI: https://wordpress.org/plugins/ai-engine/
Description: Api to get wordpress info
Version: 1.1.1
Author: Nacho Díaz
Author URI: https://nachodiaz.me
Text Domain: ai-engine

Dual licensed under the MIT and GPL licenses:
http://www.opensource.org/licenses/mit-license.php
http://www.gnu.org/licenses/gpl.html
*/

require_once plugin_dir_path(__FILE__) . 'includes/dataset.php';

add_action('init', function() {
  $dataSet = new LC_DataSet();
  $dataSet->lc_endpoint_pages();
  
  });


?>
