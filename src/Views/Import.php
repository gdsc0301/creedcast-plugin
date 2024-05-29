<?php
  namespace CreedCast\Views;
  use CreedCast\CPT\PodcastShows;
  use CreedCast\CreedCast;
  use CreedCast\Taxonomy\Genre;
  use WP_Query;
  use WP_REST_Response;

  require __DIR__ . '/../../vendor/autoload.php';
  require_once(ABSPATH . 'wp-admin/includes/media.php');
  require_once(ABSPATH . 'wp-admin/includes/file.php');
  require_once(ABSPATH . 'wp-admin/includes/image.php');

  class Import {
    public function __construct() {
      add_action('admin_menu', [$this, 'registerSubMenu']);

      add_action('rest_api_init', function() {
        register_rest_route(CreedCast::$menuSlug . '/v1', '/import-json', [
          'methods' => 'POST',
          'callback' => [$this, 'handleImport'],
        ]);
      });
    }

    public function registerSubMenu() {
      add_submenu_page(
        CreedCast::$menuSlug,
        'Import Podcast Shows',
        'Import Podcast Shows',
        'manage_options',
        CreedCast::$menuSlug,
        [$this, 'render']
      );
    }

    public function render() { ?>
      <div id="creedApp" class="p-8 max-w-4xl">
        <link rel="stylesheet" href="<?= CreedCast::asset('style.css') ?>">
        <h2 class="mb-4 font-bold text-3xl">Import Podcast Shows</h2>

        <form
          class="relative w-full p-10 grid gap-8 rounded-lg shadow bg-white overflow-hidden"
          action="/wp-json/<?= CreedCast::$menuSlug ?>/v1/import-json"
          method="POST"
        >
          <label
            for="podcasts_file"
            dropzone="move"
            class="
              text-gray-700
              font-semibold
              text-lg
              flex
              items-center
              justify-center
              p-16 px-6
              border-2
              border-dashed
              border-gray-400
              rounded-lg
            "
          >
            Drop a valid <code>.json</code> file in order to import it
          </label>
          <input
            type="file"
            accept=".json"
            id="podcasts_file"
            name="podcasts"
            class="hidden"
            required
          />
          
          <button
            type="submit"
            class="
              p-4 py-2
              bg-blue-500
              text-white
              text-lg
              font-bold
              rounded-lg
              shadow
              duration-300
              hover:bg-blue-600
              disabled:bg-blue-300
              disabled:cursor-not-allowed
              disabled:pointer-events-none
            "
            disabled
          >
            Select a file to proceed ⬆️
          </button>

          <div
            id="loading-overlay"
            class="
              absolute
              top-0 left-0
              w-full h-full
              flex items-center justify-center
              bg-white bg-opacity-75
              opacity-0
              pointer-events-none
              duration-300
              data-[visible]:opacity-100
              data-[visible]:pointer-events-auto
              backdrop-blur-sm
            "
          >
            <img src="<?= CreedCast::asset('/img/loading.svg') ?>" alt="loading" class="w-16">
          </div>
        </form>

        <h3 class="mt-16 font-bold text-2xl">Errors: </h3>
        <div class="errorsList mt-4 p-4 grid gap-4 rounded-lg bg-white">
          <span class="text-red-500 font-semibold">No errors</span>
        </div>
      </div>
      <script>
        window.addEventListener('drop', (e) => e.preventDefault());
        document.addEventListener('drop', (e) => e.preventDefault());

        const fileInput = document.getElementById('podcasts_file');
        const label = document.querySelector('label[for="podcasts_file"]');
        const form = document.querySelector('form');
        const errorsList = document.querySelector('.errorsList');
        const button = document.querySelector('button[type="submit"]');

        const loadingOverlay = document.getElementById('loading-overlay');
        const toggleLoadingOverlay = (visible = undefined) => {
          loadingOverlay.toggleAttribute('data-visible', visible);
        };

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
          label.addEventListener(eventName, (e) => e.preventDefault());
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
          label.addEventListener(eventName, () => {
            label.classList.add('bg-blue-200', 'border-blue-400');
          });
        });

        ['dragleave', 'dragend'].forEach((eventName) => {
          label.addEventListener(eventName, () => {
            label.classList.remove('bg-blue-200', 'border-blue-400');
          });
        });

        form.addEventListener('submit', e => e.preventDefault());

        const setSubmitListener = (file) => {
          label.innerHTML = `File selected: ${file.name}`;
          label.classList.add('bg-green-200', 'border-green-400');

          button.removeAttribute('disabled');
          button.innerHTML = 'Import';
          
          form.addEventListener('submit', async (e) => {
            e.preventDefault();
            toggleLoadingOverlay(true);

            const formData = new FormData();
            formData.append('podcasts', file);

            fetch(form.action, {
              method: 'POST',
              body: formData
            })
              .then((res) => {
                if (res.ok) {
                  return res.json();
                } else {
                }
              })
              .then((data) => {
                console.log('Data:', data);
                alert(data.message);
                label.innerHTML = 'Drop a valid <code>.json</code> file in order to import it';

                if (Array.isArray(data["0"])) {
                  errorsList.innerHTML = data["0"].map((err) => `<code>${JSON.stringify(err)}</code>`).join('');
                }

                fileInput.value = '';
                fileInput.files = null;

                label.classList.remove('bg-green-200', 'border-green-400');
                button.setAttribute('disabled', '');
                button.innerHTML = 'Select a file to proceed ⬆️';
              })
              .catch((err) => {
                console.error(err);
                errorsList.innerHTML = err
                alert('Error importing podcasts');
              })
              .finally(() => {
                toggleLoadingOverlay(false);
              });
          });
        };

        label.addEventListener('drop', (e) => {
          if (!e.dataTransfer.files.length) {
            label.innerHTML = 'Drop a valid <code>.json</code> file in order to import it:';
            label.classList.remove('bg-blue-200', 'border-blue-400');
            return;
          }

          setSubmitListener(e.dataTransfer.files[0]);
        });

        fileInput.addEventListener('change', (e) => {
          if (!e.target.files.length) {
            label.innerHTML = 'Drop a valid <code>.json</code> file in order to import it:';
            label.classList.remove('bg-green-200', 'border-green-400');
            return;
          }

          setSubmitListener(e.target.files[0]);
        });
      </script>
      <?php
    }

    public function handleImport() {
      header('Content-Type: application/json');

      $errors = [];

      try {
        $file = $_FILES['podcasts'];

        if ($file['error'] !== 0) {
          $errors[] = $file['error'];
          return new WP_REST_Response(['error' => 'Error uploading file', $errors], 400);
        }

        $json = file_get_contents($file['tmp_name']);

        // Podcasts by genre
        $data = json_decode($json, true);
        if (is_array($data)) {
          foreach ($data as $genre) {
            // Create the genre taxonomy term
            $genreTerm = Genre::createGenre($genre['name']);
            if (is_wp_error($genreTerm)) {
              array_push($errors, $genreTerm->get_error_message());
              return new WP_REST_Response(['error' => 'Error importing podcasts', 'details' => $genreTerm, $errors], 400);
            }

            // Check the existance of the genre
            $podcasts = $genre['podcasts'] ?? [];

            foreach ($podcasts as $podcast) {
              // Prevent empty podcast titles
              if (!isset($podcast['title']) || empty(trim($podcast['title']))) {
                array_push($errors, $podcast['id'] .' has no title');
                continue;
              }

              $post = [
                'post_title' => $podcast['title'],
                'post_content' => $podcast['description'] ?? '',
                'post_status' => 'publish',
                'post_type' => PodcastShows::$slug
              ];

              // Look for podcasts with the same title
              $query = new WP_Query([
                'post_type' => PodcastShows::$slug,
                'title' => $podcast['title'],
              ]);
              
              // If the podcast already exists, skip it
              if ($query->have_posts()) {
                array_push($errors, 'Podcast ' . $podcast['title'] . ' already exists');
                continue;
              }

              $postId = wp_insert_post($post);

              if (is_wp_error($postId)) {
                array_push($errors, $postId->get_error_message());
                continue;
              }
              
              // Download the podcast thumbnail and set it as the featured image
              if (isset($podcast['thumbnail']) && !empty($podcast['thumbnail'])) {
                $image = media_sideload_image($podcast['thumbnail'], $postId, $podcast['title'], 'id');
                if (!is_wp_error($image)) {
                  set_post_thumbnail($postId, $image);
                } else {
                  array_push($errors, $image);
                }
              }
              
              // Attribute the podcast to the genre
              wp_set_post_terms($postId, $genreTerm->term_id, Genre::$slug);

              // Update the post ACF custom fields
              if ($postId) {
                update_field('publisher', $podcast['publisher'] ?? '', $postId);
                update_field('image_url', $podcast['image'] ?? '', $postId);
                update_field('listennotes_url', $podcast['listennotes_url'] ?? '', $postId);
                update_field('itunes_id', $podcast['itunes_id'] ?? '', $postId);
                update_field('language', $podcast['language'] ?? '', $postId);
                update_field('rss_feed', $podcast['rss_feed'] ?? '', $postId);
                update_field('country', $podcast['country'] ?? '', $postId);
                update_field('website', $podcast['website'] ?? '', $postId);
                update_field('is_claimed', $podcast['is_claimed'] ?? '', $postId);
                update_field('type', $podcast['type'] ?? '', $postId);
              }else {
                array_push($errors, 'Error importing podcast ' . $podcast['title']);
              }
            }
          }
          
          return new WP_REST_Response(['message' => 'Podcasts imported successfully', $errors], 201);
        } else {
          return new WP_REST_Response(['error' => 'Invalid JSON file', $errors], 400);
        }
      } catch (\Exception $e) {
        return new WP_REST_Response(['error' => $e->getMessage(), $errors], 500);
      }
    }
  }