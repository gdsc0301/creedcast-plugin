<?php
  namespace CreedCast;

  require __DIR__ . '/../vendor/autoload.php';

  class CreedCast {

    public function __construct() {
      $this->registerCPTs();

      // Registers a menu item in the admin dashboard
      add_action('admin_menu', [$this, 'registerMenuItem']);
    }

    public function registerCPTs() {
      new CPT\PodcastShows($this);
      new CPT\PodcastEpisodes($this);
    }

    public function registerMenuItem() {
      add_menu_page(
        'CreedCast', // Page title
        'CreedCast', // Menu title
        'manage_options', // Capability
        'creedcast', // Menu slug
        [$this, 'render'], // Callback function
        'dashicons-microphone', // Icon URL
        6 // Position
      );

      add_submenu_page(
        'creedcast', // Parent slug
        'Import', // Page title
        'Import', // Menu title
        'manage_options', // Capability
        'import', // Menu slug
        [$this,'renderImportPage'],'0'
      );
    }

    public function render() {
      // Redirect to the import page
      wp_redirect(admin_url('admin.php?page=import'));
    }

    public function renderImportPage() {
      new Views\Import();
    }

    public static function asset($path) {
      return plugin_dir_url(__FILE__) . '../public/' . $path;
    }
  }