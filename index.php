<?php
/**
 * Plugin Name: CreedCast Plugin
 * Plugin URI: https://github.com/gdsc0301/creedcast-plugin
 * Description: A WordPress plugin that make it a podcast blog.
 * Version: 1.0
 * Author: Guilherme Carvalho
 * Author URI: https://github.com/gdsc0301
 * License: GPL2
 */

if (!defined('ABSPATH')) {
  exit;
}

// Show errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

new CreedCast\CreedCast();
