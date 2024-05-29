<?php
  namespace CreedCast\CPT;
  use CreedCast\CreedCast;
  use CreedCast\Taxonomy\Genre;

  require __DIR__ . '/../../vendor/autoload.php';

  class PodcastShows {
    public static string $slug = 'creed_podcast_shows';

    public function __construct() {
      add_action('init', [$this, 'createCPT']);
      register_activation_hook( __FILE__, [$this,'flushRewriteRules']);
      add_action('init', [$this,'createACFFieldGroup']);
      new Genre();
    }

    public function flushRewriteRules() {
      $this->createCPT();
      flush_rewrite_rules();
    }
    public function createCPT() {
      $err = register_post_type($this::$slug, [
        'labels' => [
          'name' => __('Podcast Shows'),
          'singular_name' => __('Podcast Show'),
        ],
        'rewrite' => [
          'slug' => 'podcast-shows',
        ],
        'menu_icon'=> 'dashicons-format-video',
        'supports' => ['title', 'editor', 'thumbnail'],
        'public' => true,
        'has_archive' => true,
        'taxonomies' => [Genre::$slug],
        'menu_position' => 7,
      ]);

      if (is_wp_error($err)) {
        echo $err->get_error_message();
      }
    }

    public function createACFFieldGroup() {
      if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group([
          'key' => "group_{$this::$slug}_details",
          'title' => 'Podcast Show Details',
          'fields' => [
            [
              'key' => "field_{$this::$slug}_publisher",
              'label' => 'Publisher',
              'name' => 'publisher',
              'type' => 'text',
            ],
            [
              'key' => "field_{$this::$slug}_image_url",
              'label' => 'Image URL',
              'name' => 'image_url',
              'type' => 'url'
            ],
            [
              'key' => "field_{$this::$slug}_listen_notes_url",
              'label' => 'Listen Notes URL',
              'name' => 'listen_notes_url',
              'type' => 'url',
            ],
            [
              'key' => "field_{$this::$slug}_itunes_id",
              'label' => 'iTunes ID',
              'name' => 'itunes_id',
              'type' => 'text',
            ],
            [
              'key' => "field_{$this::$slug}_language",
              'label' => 'Language',
              'name' => 'language',
              'type' => 'text',
            ],
            [
              'key' => "field_{$this::$slug}_rss_feed",
              'label' => 'RSS Feed',
              'name' => 'rss_feed',
              'type' => 'text',
            ],
            [
              'key' => "field_{$this::$slug}_country",
              'label' => 'Country',
              'name' => 'country',
              'type' => 'text',
            ],
            [
              'key' => "field_{$this::$slug}_website",
              'label' => 'Website',
              'name' => 'website',
              'type' => 'url',
            ],
            [
              'key' => "field_{$this::$slug}_is_claimed",
              'label' => 'Is Claimed?',
              'name' => 'is_claimed',
              'type' => 'true_false'
            ],
            [
              'key' => "field_{$this::$slug}_type",
              'label' => 'Type',
              'name' => 'type',
              'type' => 'text',
            ],
          ],
          'location' => [
            [
              [
                'param' => 'post_type',
                'operator' => '==',
                'value' => $this::$slug,
              ],
            ],
          ],
        ]);
      }
    }
  }

