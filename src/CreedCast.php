<?php
  namespace CreedCast;
  use CreedCast\Views;

  require __DIR__ . '/../vendor/autoload.php';

  class CreedCast {
    public static string $menuSlug = 'creed-cast';

    public function __construct() {
      add_action('admin_menu', [$this, 'registerMenu']);
      
      new CPT\PodcastShows();
      new Views\Import();
    }

    public function registerMenu() {
      add_menu_page(
        'CreedCast', // Page title
        'CreedCast', // Menu title
        'manage_options', // Capability
        $this::$menuSlug, // Menu slug
        '', // Callback function
        'dashicons-microphone', // Icon URL
        6 // Position
      );
    }

    public static function asset($path) {
      return plugin_dir_url(__FILE__) . '../public/' . $path;
    }
  }