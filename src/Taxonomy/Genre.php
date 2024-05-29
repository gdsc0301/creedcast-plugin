<?php
  namespace CreedCast\Taxonomy;
  use CreedCast\CPT\PodcastShows;
  use CreedCast\CreedCast;
  require __DIR__ . '/../../vendor/autoload.php';

  class Genre {
    public static $slug = 'genre';
    public function __construct() {
      add_action('init', [$this, 'createGenreTaxonomy']);
    }

    public function createGenreTaxonomy() {
      $err = register_taxonomy($this::$slug, PodcastShows::$slug, [
        'labels' => [
          'name' => __('Genre'),
          'singular_name' => __('Genre'),
        ],
        'rewrite' => [
          'slug' => $this::$slug,
        ],
        'hierarchical' => true,
        'public' => true
      ]);

      if (is_wp_error($err)) {
        echo $err->get_error_message();
      }
    }

    public static function createGenre($name) {
      $genre = wp_insert_term($name, self::$slug);
      if (is_wp_error($genre)) {
        // Check if the genre already exists
        if ($genre->get_error_code() === 'term_exists') {
          $genre = get_term_by('name', $name, self::$slug);
          return $genre;
        } else {
          throw new \Exception('Error creating genre, error: ' . $genre->get_error_message());
        }
      }

      return $genre;
    }
  }