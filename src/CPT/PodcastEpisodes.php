<?php
  namespace CreedCast\CPT;
  use CreedCast\CreedCast;

  require __DIR__ . '/../../vendor/autoload.php';

  class PodcastEpisodes {
    private CreedCast $plugin;

    public function __construct(CreedCast $plugin) {
      $this->plugin = $plugin;
      add_action('init', [$this, 'createCPT']);
    }

    public function createCPT() {
      $err = register_post_type('creed_podcast_eps', [
        'labels' => [
          'name' => __('Podcast Episodes'),
          'singular_name' => __('Podcast Episode'),
        ],
        'rewrite' => [
          'slug' => 'podcast-episodes',
        ],
        'show_in_menu' => 'creedcast',
        'menu_icon'=> 'dashicons-microphone',
        'public' => true,
        'has_archive' => true,
      ]);

      if (is_wp_error($err)) {
        echo $err->get_error_message();
      }
    }
  }

