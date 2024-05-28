<?php
  namespace CreedCast\CPT;
  use CreedCast\CreedCast;

  require __DIR__ . '/../../vendor/autoload.php';

  class PodcastShows {
    private CreedCast $plugin;

    public function __construct(CreedCast $plugin) {
      $this->plugin = $plugin;
      add_action('init', [$this, 'createCPT']);
    }

    public function createCPT() {
      $err = register_post_type('creed_podcast_shows', [
        'labels' => [
          'name' => __('Podcast Shows'),
          'singular_name' => __('Podcast Show'),
        ],
        'rewrite' => [
          'slug' => 'podcast-shows',
        ],
        'show_in_menu' => 'creedcast',
        'menu_icon'=> 'dashicons-format-video',
        'public' => true,
        'has_archive' => true,
      ]);

      if (is_wp_error($err)) {
        echo $err->get_error_message();
      }
    }
  }

