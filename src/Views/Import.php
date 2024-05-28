<?php
  namespace CreedCast\Views;
  use CreedCast\CreedCast;

  require __DIR__ . '/../../vendor/autoload.php';

  class Import {
    public function __construct() {
      $reqMethod = $_SERVER['REQUEST_METHOD'];

      if ($reqMethod === 'POST') {
        try {
          header('Content-Type: application/json');
          $this->handleImport();
        } catch (\Exception $e) {
          http_response_code(500);
          echo json_encode(['error' => $e->getMessage()]);
        }
      } else {
        $this->render();
      }
    }

    public function render() { ?>
      <div id="creedApp" class="p-8">
        <link rel="stylesheet" href="<?= CreedCast::asset('style.css') ?>">
        <h2 class="mb-4 font-bold text-3xl">Import Podcast Shows</h2>
        <form
          class="w-fit p-10 grid gap-8 rounded-lg shadow bg-white"
          action="<?= admin_url('admin.php?page=import') ?>"
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
              p-4
              border-2
              border-dashed
              border-gray-400
              rounded-lg
            "
          >
            Drop a valid <code>.json</code> file in order to import it:
            <input
              type="file"
              name="podcasts"
              id="podcasts_file"
              accept=".json"
              multiple="false"
              class="absolute opacity-0 w-0 h-0 overflow-hidden pointer-events-none"
            >
          </label>
          <button type="submit" class="p-4 bg-blue-500 text-white rounded-lg shadow">
            Import
          </button>
        </form>
      </div>
      <script>
        window.addEventListener('drop', (e) => e.preventDefault());
        document.addEventListener('drop', (e) => e.preventDefault());

        const fileInput = document.getElementById('podcasts_file');
        const label = document.querySelector('label[for="podcasts_file"]');
        const form = document.querySelector('form');

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

        label.addEventListener('drop', (e) => {
          if (!e.dataTransfer.files.length) {
            label.innerHTML = 'Drop a valid <code>.json</code> file in order to import it:';
            label.classList.remove('bg-blue-200', 'border-blue-400');
            return;
          }

          const file = e.dataTransfer.files[0];
          label.innerHTML = `File selected: ${file.name}`;
          label.classList.add('bg-green-200', 'border-green-400');

          form.addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = new FormData();
            formData.append('podcasts', file);

            fetch(form.action, {
              method: 'POST',
              body: formData,
            })
              .then((res) => {
                if (res.ok) {
                  return res.json();
                } else {
                  alert('Error importing podcasts');
                }
              })
              .then((data) => {
                alert(data.message);
              })
              .catch((err) => {
                console.error(err);
              });
          });
        });

        fileInput.addEventListener('change', (e) => {
          if (!e.target.files.length) {
            label.innerHTML = 'Drop a valid <code>.json</code> file in order to import it:';
            label.classList.remove('bg-green-200', 'border-green-400');
            return;
          }

          const file = e.target.files[0];
          label.innerHTML = `File selected: ${file.name}`;
          label.classList.add('bg-green-200', 'border-green-400');
        });
      </script>
    <?php
    }

    /**
     * Handles the import of the podcast shows
     * It should read the file and create Podcast Shows and Podcast Episodes related to them
     */
    public function handleImport() {
      $file = $_FILES['podcasts'];
      if ($file['error'] !== 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Error uploading file']);
        return;
      }

      $json = file_get_contents($file['tmp_name']);
      $data = json_decode($json, true);

      if (is_array($data)) {
        foreach ($data as $show) {
          $showId = wp_insert_post([
            'post_title' => $show['name'],
            'post_type' => 'creed_podcast_shows',
            'post_status' => 'publish',
          ]);

          if (is_wp_error($showId)) {
            http_response_code(500);
            echo json_encode($showId->get_error_message());
            return;
          }

          update_post_meta($showId, 'listennotes_url', $show['listennotes_url']);

          foreach ($show['podcasts'] as $episode) {
            $episodeId = wp_insert_post([
              'post_title' => $episode['title'],
              'post_type' => 'creed_podcast_eps',
              'post_status' => 'publish',
              'post_parent' => $showId,
            ]);

            if (is_wp_error($episodeId)) {
              http_response_code(500);
              echo json_encode($episodeId->get_error_message());
              return;
            }

            // Set post featured image
            $image = media_sideload_image($episode['thumbnail'], $episodeId, null, 'id');
            set_post_thumbnail($episodeId, $image);

            update_post_meta($episodeId, 'publisher', $episode['publisher']);
            update_post_meta($episodeId, 'listennotes_url', $episode['listennotes_url']);
          }
        }
        http_response_code(201);
        echo json_encode(['message' => 'Podcasts imported successfully']);
        return;
      } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON file']);
        return;
      }
    }
  }

  /*
  // podcasts-by-genre.json
  [
    {
        "id": 127,
        "name": "Technology",
        "podcasts": [
            {
                "id": "90159e76aa8b4795869f189105fcc417",
                "title": "Smart Talks with IBM",
                "publisher": "Pushkin Industries & iHeartRadio",
                "image": "https://cdn-images-1.listennotes.com/podcasts/smart-talks-with-ibm-UYTEwShbMut-4kP5IEjRdt3.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/smart-talks-with-ibm-UYTEwShbMut-4kP5IEjRdt3.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/90159e76aa8b4795869f189105fcc417/",
                "total_episodes": 1,
                "description": "Join Malcolm Gladwell, author and host of Revisionist History, as he talks to experts in tech, communication, education, and manufacturing to uncover and break down the smart solutions they’ve pioneered that are changing the way we combat some of the world’s greatest issues.",
                "itunes_id": 1558889546,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": null,
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    67,
                    93,
                    127,
                    107
                ]
            },
            {
                "id": "b59508a9fbd842428ee60f898d85b461",
                "title": "Ladybug Podcast",
                "publisher": "Emma Bostian, Sidney Buckner, Kelly Vaughn, and Ali Spittel",
                "image": "https://cdn-images-1.listennotes.com/podcasts/ladybug-podcast-KP5bGY9fhHL-swCn6DupJQe.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/ladybug-podcast-KP5bGY9fhHL-swCn6DupJQe.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/b59508a9fbd842428ee60f898d85b461/",
                "total_episodes": 72,
                "explicit_content": false,
                "description": "We're Emma Bostian, Sidney Buckner, Kelly Vaughn, and Ali Spittel - four seasoned software developers working in different sectors. Since there's a major lack of technical podcasts out there, we've decided to start one. Just kidding -- there's already a ton! But, we wanted to add our voices to the space and share our experiences and advice. We'll have great discussions around how to start coding, the hot technologies right now, how to get your first developer job, and more!",
                "itunes_id": 1469229625,
                "rss": "https://pinecast.com/feed/ladybug-podcast",
                "language": "English",
                "country": "United States",
                "website": "https://ladybug.dev?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": true,
                "type": "episodic",
                "genre_ids": [
                    127,
                    67
                ]
            },
            {
                "id": "1882a1863070416c8d0a7a67e22861b0",
                "title": "Jovan Hutton Pulitzer",
                "publisher": "#JovanHuttonPulitzer",
                "image": "https://cdn-images-1.listennotes.com/podcasts/jovan-hutton-pulitzer-jovanhuttonpulitzer-F5JsrWZ0D36-tYKbEVv6W3k.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/jovan-hutton-pulitzer-jovanhuttonpulitzer-F5JsrWZ0D36-tYKbEVv6W3k.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/1882a1863070416c8d0a7a67e22861b0/",
                "total_episodes": 31,
                "description": "Publisher, Author and TV Personality  www.JovanHuttonPulitzer.org www.Twitter.com/JovanHPulitzer www.Instagram.com/JovanHuttonPulitzer www.Facebook.com/jovanhuttonpulitzer",
                "itunes_id": 1073191727,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "http://www.jovanhuttonpulitzer.org?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    67,
                    122,
                    125
                ]
            },
            {
                "id": "59612f5e2b7944df815121483a398d9c",
                "title": "The Science Show - Full Program Podcast",
                "publisher": "ABC Radio",
                "image": "https://cdn-images-1.listennotes.com/podcasts/the-science-show-full-program-podcast-abc-nFT4t_N8Mxh-ZwQEHgI1KDB.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/the-science-show-full-program-podcast-abc-nFT4t_N8Mxh-ZwQEHgI1KDB.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/59612f5e2b7944df815121483a398d9c/",
                "total_episodes": 341,
                "description": "\n            \n        The Science Show gives Australians unique insights into the latest scientific research and debate, from the physics of cricket to prime ministerial biorhythms.\n            \n        ",
                "itunes_id": 73330054,
                "rss": "http://www.abc.net.au/radionational/feed/2885480/podcast.xml",
                "language": "English",
                "country": "Australia",
                "website": "https://www.abc.net.au/radionational/programs/scienceshow/?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    67,
                    107,
                    110
                ]
            },
            {
                "id": "e971602fb54a4672a2f736c5154849f5",
                "title": "Tales from the Crypt: A Bitcoin Podcast",
                "publisher": "Marty Bent",
                "image": "https://cdn-images-1.listennotes.com/podcasts/tales-from-the-crypt-a-bitcoin-podcast-5XU6Kaqbsp8-YWDBMjxUdha.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/tales-from-the-crypt-a-bitcoin-podcast-5XU6Kaqbsp8-YWDBMjxUdha.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/e971602fb54a4672a2f736c5154849f5/",
                "total_episodes": 395,
                "description": "Tales from the Crypt is a podcast hosted by Marty Bent about Bitcoin. Join Marty, Editor in Chief of \"the best newsletter in Bitcoin\", as he sits down to discuss Bitcoin with interesting people.",
                "itunes_id": 1292381204,
                "rss": "https://anchor.fm/s/558f520/podcast/rss",
                "language": "English",
                "country": "United States",
                "website": "https://anchor.fm/tales-from-the-crypt?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    136,
                    67,
                    93,
                    98,
                    122,
                    126,
                    127,
                    129
                ]
            },
            {
                "id": "c1f06b514319433fa6e7bb58f6950e18",
                "title": "Hubblecast HD",
                "publisher": "ESA/Hubble",
                "image": "https://cdn-images-1.listennotes.com/podcasts/hubblecast-hd-esahubble-D6JMYh-suQN.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/hubblecast-hd-esahubble-D6JMYh-suQN.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/c1f06b514319433fa6e7bb58f6950e18/",
                "total_episodes": 25,
                "description": "The latest news about astronomy, space and the NASA/ESA Hubble Space Telescope presented in High Definition is only for devices that play High Definition video (not iPhone or iPod). To watch the Hubblecast on your iPod and/or iPhone, please download the Standard Definition version also available on iTunes.",
                "itunes_id": 258935617,
                "rss": "http://feeds.feedburner.com/hubblecast/",
                "language": "English",
                "country": "United States",
                "website": "http://www.spacetelescope.org/videos/hubblecast/?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    67,
                    107,
                    110
                ]
            },
            {
                "id": "b9ce3fdbbe3c4c08a73e20aaf565c02a",
                "title": "In Our Time: Science",
                "publisher": "BBC Radio 4",
                "image": "https://cdn-images-1.listennotes.com/podcasts/in-our-time-science-bbc-radio-4-czpwGKfCPS2.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/in-our-time-science-bbc-radio-4-czpwGKfCPS2.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/b9ce3fdbbe3c4c08a73e20aaf565c02a/",
                "total_episodes": 260,
                "description": "Scientific principles, theory, and the role of key figures in the advancement of science.",
                "itunes_id": 463701000,
                "rss": "https://podcasts.files.bbci.co.uk/p01gyd7j.rss",
                "language": "English",
                "country": "United Kingdom",
                "website": "http://www.bbc.co.uk/programmes/p01gyd7j?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    67,
                    122,
                    125
                ]
            },
            {
                "id": "86ccbc7687174c39867536f39d082cad",
                "title": "Science in Action",
                "publisher": "BBC World Service",
                "image": "https://cdn-images-1.listennotes.com/podcasts/science-in-action-bbc-world-service-FJlVPkb0cDK.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/science-in-action-bbc-world-service-FJlVPkb0cDK.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/86ccbc7687174c39867536f39d082cad/",
                "total_episodes": 99,
                "description": "The BBC brings you all the week's science news.",
                "itunes_id": 262026995,
                "rss": "https://podcasts.files.bbci.co.uk/p002vsnb.rss",
                "language": "English",
                "country": "United Kingdom",
                "website": "http://www.bbc.co.uk/programmes/p002vsnb?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    67,
                    99
                ]
            },
            {
                "id": "d93958ef159e490d8e278e7b5e89c796",
                "title": "Quirks and Quarks from CBC Radio",
                "publisher": "CBC Radio",
                "image": "https://cdn-images-1.listennotes.com/podcasts/quirks-and-quarks-from-cbc-radio-cbc-radio-Slb_sVjkQYh-t6ji9zwRUFE.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/quirks-and-quarks-from-cbc-radio-cbc-radio-Slb_sVjkQYh-t6ji9zwRUFE.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/d93958ef159e490d8e278e7b5e89c796/",
                "total_episodes": 55,
                "description": "CBC Radio's Quirks and Quarks covers the quirks of the expanding universe to the quarks within a single atom... and everything in between.",
                "itunes_id": 151485804,
                "rss": "https://www.cbc.ca/podcasting/includes/quirksaio.xml",
                "language": "English",
                "country": "Canada",
                "website": "https://www.cbc.ca/radio/quirks?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    107,
                    67
                ]
            },
            {
                "id": "ff0157d7e6aa4c0fbd869d7e37240a38",
                "title": "Science Friday",
                "publisher": "Science Friday and WNYC Studios",
                "image": "https://cdn-images-1.listennotes.com/channel/image/c4610f5eda054f7682dc71381ee81ce9.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/channel/image/c4610f5eda054f7682dc71381ee81ce9.jpg",
                "listennotes_url": "https://www.listennotes.com/c/ff0157d7e6aa4c0fbd869d7e37240a38/",
                "total_episodes": 583,
                "description": "Brain fun for curious people.",
                "itunes_id": 73329284,
                "rss": "https://feeds.feedburner.com/science-friday",
                "language": "English",
                "country": "United States",
                "website": "https://www.wnycstudios.org/podcasts/science-friday?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    107,
                    132,
                    195,
                    110,
                    116,
                    111,
                    67,
                    99,
                    131
                ]
            },
            {
                "id": "b0a209d2780b4b4c93bd8f96f8284e2b",
                "title": "The Skeptics' Guide to the Universe",
                "publisher": "Dr. Steven Novella",
                "image": "https://cdn-images-1.listennotes.com/podcasts/the-skeptics-guide-to-the-universe-dr-3g941cPEVUj-U0XePSxE-Tq.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/the-skeptics-guide-to-the-universe-dr-3g941cPEVUj-U0XePSxE-Tq.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/b0a209d2780b4b4c93bd8f96f8284e2b/",
                "total_episodes": 1545,
                "description": "The Skeptics' Guide to the Universe is a weekly science podcast discussing the latest science news, critical thinking, bad science, conspiracies and controversies. -The Skeptics' Guide to the Universe: Your escape to reality - Produced by SGU Productions, LLC: https://www.theskepticsguide.org",
                "itunes_id": 128859062,
                "rss": "https://feed.theskepticsguide.org/feed/rss.aspx?feed=sgu",
                "language": "English",
                "country": "United States",
                "website": "https://www.theskepticsguide.org/podcast/sgu?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    107,
                    111,
                    110,
                    67
                ]
            },
            {
                "id": "32e007fc40be4748bd1a30d4f77b1fcd",
                "title": "The Science Hour",
                "publisher": "BBC World Service",
                "image": "https://cdn-images-1.listennotes.com/podcasts/the-science-hour-bbc-world-service-LaEmRrYsvDk.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/the-science-hour-bbc-world-service-LaEmRrYsvDk.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/32e007fc40be4748bd1a30d4f77b1fcd/",
                "total_episodes": 83,
                "description": "Science news and highlights of the week",
                "itunes_id": 634849863,
                "rss": "https://podcasts.files.bbci.co.uk/p016tmt2.rss",
                "language": "English",
                "country": "United Kingdom",
                "website": "http://www.bbc.co.uk/programmes/p016tmt2?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    67
                ]
            },
            {
                "id": "c7b9a2a6e7a9489cae89c6e4fe1e6cc8",
                "title": "PhysicsCentral: Podcasts",
                "publisher": "American Physical Society",
                "image": "https://cdn-images-1.listennotes.com/podcasts/physicscentral-podcasts-american-physical-t_Tn6gExFAJ.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/physicscentral-podcasts-american-physical-t_Tn6gExFAJ.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/c7b9a2a6e7a9489cae89c6e4fe1e6cc8/",
                "total_episodes": 250,
                "description": "Enjoy the sounds of physics with our podcasts.  Always fun and always engaging - just like physics.",
                "itunes_id": 399065919,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "http://www.physicscentral.com?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    67,
                    107,
                    110,
                    117,
                    119
                ]
            },
            {
                "id": "bf80ac1d05da45309a0da15cbbf6653b",
                "title": "The Story Collider",
                "publisher": "Erin Barker",
                "image": "https://cdn-images-1.listennotes.com/podcasts/the-story-collider-the-story-collider-U6b9FB1zYne-AvQVEWohz-R.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/the-story-collider-the-story-collider-U6b9FB1zYne-AvQVEWohz-R.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/bf80ac1d05da45309a0da15cbbf6653b/",
                "total_episodes": 485,
                "description": "Whether we wear a lab coat or haven't seen a test tube since grade school, science is shaping all of our lives. And that means we all have science stories to tell. Every year, we host dozens of live shows all over the country, featuring all kinds of storytellers - researchers, doctors, and engineers of course, but also patients, poets, comedians, cops, and more. Some of our stories are heartbreaking, others are hilarious, but they're all true and all very personal. Welcome to The Story Collider!",
                "itunes_id": 396452781,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "http://storycollider.org?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    107,
                    110,
                    67,
                    100,
                    101,
                    122,
                    124
                ]
            },
            {
                "id": "b53125292b4e423d93dc1f16f20a7ed2",
                "title": "Stuff You Should Know",
                "publisher": "iHeartRadio",
                "image": "https://cdn-images-1.listennotes.com/podcasts/stuff-you-should-know-iheartradio-V294pb6Imn5-o3M1PutdeRk.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/stuff-you-should-know-iheartradio-V294pb6Imn5-o3M1PutdeRk.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/b53125292b4e423d93dc1f16f20a7ed2/",
                "total_episodes": 1854,
                "description": "If you've ever wanted to know about champagne, satanism, the Stonewall Uprising, chaos theory, LSD, El Nino, true crime and Rosa Parks, then look no further. Josh and Chuck have you covered.",
                "itunes_id": 278981407,
                "rss": "https://feeds.megaphone.fm/stuffyoushouldknow",
                "language": "English",
                "country": "United States",
                "website": "https://www.howstuffworks.com?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    132,
                    127,
                    111,
                    107,
                    195,
                    122,
                    67,
                    125
                ]
            },
            {
                "id": "ccdf76f866a44adcba8606315863713b",
                "title": "Practical AI: Machine Learning & Data Science",
                "publisher": "Changelog Media",
                "image": "https://cdn-images-1.listennotes.com/podcasts/practical-ai-changelog-media-179hMJkIwDu-VfEMrme9A4k.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/practical-ai-changelog-media-179hMJkIwDu-VfEMrme9A4k.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/ccdf76f866a44adcba8606315863713b/",
                "total_episodes": 126,
                "explicit_content": false,
                "description": "Making artificial intelligence practical, productive, and accessible to everyone.  Practical AI is a show in which technology professionals, business people, students, enthusiasts, and expert guests engage in lively discussions about Artificial Intelligence and related topics (Machine Learning, Deep Learning, Neural Networks, etc). The focus is on productive implementations and real-world scenarios that are accessible to everyone. If you want to keep up with the latest advances in AI, while keeping one foot in the real world, then this is the show for you!",
                "itunes_id": 1406537385,
                "rss": "https://changelog.com/practicalai/feed",
                "language": "English",
                "country": "United States",
                "website": "https://changelog.com/practicalai?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": true,
                "type": "episodic",
                "genre_ids": [
                    127,
                    131,
                    128,
                    67
                ]
            },
            {
                "id": "907466cdd8884267b9ec3dc45afc5165",
                "title": "Stuff To Blow Your Mind",
                "publisher": "iHeartRadio",
                "image": "https://cdn-images-1.listennotes.com/podcasts/stuff-to-blow-your-mind-iheartradio-zEtK3udpfaZ-fJKPYXUrITN.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/stuff-to-blow-your-mind-iheartradio-zEtK3udpfaZ-fJKPYXUrITN.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/907466cdd8884267b9ec3dc45afc5165/",
                "total_episodes": 1896,
                "description": "Deep in the back of your mind, you’ve always had the feeling that there’s something strange about reality. There is. Join Robert Lamb and Joe McCormick as they examine neurological quandaries, cosmic mysteries, evolutionary marvels and our transhuman future.",
                "itunes_id": 350359306,
                "rss": "https://feeds.megaphone.fm/stufftoblowyourmind",
                "language": "English",
                "country": "United States",
                "website": "https://www.howstuffworks.com?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    107,
                    67,
                    110,
                    122,
                    125
                ]
            },
            {
                "id": "7ec83d24602849198241dd1963aec217",
                "title": "Main Engine Cut Off",
                "publisher": "Anthony Colangelo",
                "image": "https://cdn-images-1.listennotes.com/podcasts/main-engine-cut-off-anthony-colangelo-_VgP7-XEfpY-4wA8rOz-C2j.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/main-engine-cut-off-anthony-colangelo-_VgP7-XEfpY-4wA8rOz-C2j.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/7ec83d24602849198241dd1963aec217/",
                "total_episodes": 185,
                "description": "Opinion and analysis of spaceflight, exploration, policy, and strategy, by Anthony Colangelo.",
                "itunes_id": 1105457520,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "https://mainenginecutoff.com?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    110,
                    67,
                    107
                ]
            },
            {
                "id": "6007dfbce6424beab6014ecad279831b",
                "title": "BBC Inside Science",
                "publisher": "BBC Radio 4",
                "image": "https://cdn-images-1.listennotes.com/podcasts/bbc-inside-science-bbc-radio-4-wh28NnmPH1y.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/bbc-inside-science-bbc-radio-4-wh28NnmPH1y.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/6007dfbce6424beab6014ecad279831b/",
                "total_episodes": 300,
                "description": "Dr Adam Rutherford and guests illuminate the mysteries and challenge the controversies behind the science that's changing our world.",
                "itunes_id": 670199157,
                "rss": "",
                "language": "English",
                "country": "United Kingdom",
                "website": "http://www.bbc.co.uk/programmes/b036f7w2?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    107,
                    67
                ]
            },
            {
                "id": "28e898c9c0194fa1a3131ea7fd781b93",
                "title": "Rightbrainers",
                "publisher": "Z by HP",
                "image": "https://cdn-images-1.listennotes.com/podcasts/rightbrainers-8X1F0IWr7gm-sB7MQqWLoxI.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/rightbrainers-8X1F0IWr7gm-sB7MQqWLoxI.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/28e898c9c0194fa1a3131ea7fd781b93/",
                "total_episodes": 6,
                "explicit_content": false,
                "description": "Rightbrainers is a podcast series that unpacks the latest trends in technology & creativity through real stories of creative breakthroughs. Podcast and guests brought to you by Z by HP. Visit hp.com/rightbrainers to learn more.",
                "itunes_id": 1522973036,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "http://www.hp.com/rightbrainers?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    105,
                    67,
                    127,
                    100
                ]
            }
        ],
        "total": 540,
        "has_next": true,
        "has_previous": false,
        "page_number": 1,
        "previous_page_number": 0,
        "next_page_number": 2,
        "listennotes_url": "https://www.listennotes.com/best-technology-podcasts-127/"
    },
    {
        "id": 140,
        "name": "Web Design",
        "podcasts": [{
                "id": "49b3e8357a644abfa6d45b95b57a5637",
                "title": "Alexa Stop Podcast",
                "publisher": "Robert Belgrave & Jim Bowes",
                "image": "https://cdn-images-1.listennotes.com/podcasts/alexa-stop-podcast-robert-belgrave-jim-bowes-5f2IEtTmaYv-5jn9XD-Irgu.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/alexa-stop-podcast-robert-belgrave-jim-bowes-5f2IEtTmaYv-5jn9XD-Irgu.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/49b3e8357a644abfa6d45b95b57a5637/",
                "total_episodes": 26,
                "description": "Two technology CEOs \u2014 Jim Bowes and Robert Belgrave \u2014 discuss how technology is changing our lives, joined every month by a special guest from the industry. Supported by D/SRUPTION Magazine.",
                "itunes_id": 1223829037,
                "rss": "https://anchor.fm:443/s/116d284/podcast/rss",
                "language": "English",
                "country": "United Kingdom",
                "website": "https://anchor.fm/alexa-stop-podcast?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    140,
                    67,
                    127
                ]
            },
            {
                "id": "33bd629683d6413fae3dcc6d434c63a4",
                "title": "This Week in Tech (MP3)",
                "publisher": "TWiT",
                "image": "https://cdn-images-1.listennotes.com/podcasts/this-week-in-tech-mp3-twit-FhH4pxNJ7MD-rOsjyMdh_cW.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/this-week-in-tech-mp3-twit-FhH4pxNJ7MD-rOsjyMdh_cW.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/33bd629683d6413fae3dcc6d434c63a4/",
                "total_episodes": 192,
                "description": "Your first podcast of the week is the last word in tech. Join the top tech pundits in a roundtable discussion of the latest trends in high tech.\n\nRecords live every Sunday at 5:15pm Eastern / 2:15pm Pacific / 21:15 UTC.",
                "itunes_id": 73329404,
                "rss": "https://feeds.twit.tv/twit.xml",
                "language": "English",
                "country": "United States",
                "website": "https://twit.tv/shows/this-week-in-tech?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": true,
                "type": "episodic",
                "genre_ids": [
                    149,
                    157,
                    127,
                    140,
                    67,
                    93,
                    95,
                    99,
                    130,
                    131
                ]
            },
            {
                "id": "64bfb34f7dd243a6a75123ff9158d497",
                "title": "Design Review",
                "publisher": "Design Review",
                "image": "https://cdn-images-1.listennotes.com/podcasts/design-review-design-review-WD4RmBA1os5.300x298.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/design-review-design-review-WD4RmBA1os5.300x298.jpg",
                "listennotes_url": "https://www.listennotes.com/c/64bfb34f7dd243a6a75123ff9158d497/",
                "total_episodes": 59,
                "description": "No chit-chat, just focused in-depth discussions about design topics that matter. Jonathan Shariat and Chris Liu are your hosts and bring to the table passion and  years of experience.",
                "itunes_id": 947753823,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "http://soundcloud.com/design-review?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    140,
                    67,
                    127,
                    131
                ]
            },
            {
                "id": "a5afc614d96048a2ae20bdd6136127b7",
                "title": "The Unofficial Shopify Podcast",
                "publisher": "Kurt Elster, Paul Reda",
                "image": "https://cdn-images-1.listennotes.com/podcasts/the-unofficial-shopify-podcast-kurt-elster-ncGUKkNvVRE-BqzDjoexMh1.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/the-unofficial-shopify-podcast-kurt-elster-ncGUKkNvVRE-BqzDjoexMh1.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/a5afc614d96048a2ae20bdd6136127b7/",
                "total_episodes": 299,
                "description": "\u201cHow's an entrepreneur like me supposed to grow my Shopify store?\u201d That's what The Unofficial Shopify Podcast aims to answer. Discover new opportunities to grow your store from the world\u2019s most successful Shopify entrepreneurs. Hosted by Kurt Elster, a senior ecommerce consultant and Shopify Plus Partner, The Unofficial Shopify Podcast is not authorized, endorsed, or sponsored by Shopify\u2013 It's a no holds barred discussion of ecommerce growth strategy & tactics.",
                "itunes_id": 918602360,
                "rss": "https://feeds.simplecast.com/Ewol_LzH",
                "language": "English",
                "country": "United States",
                "website": "https://unofficialshopifypodcast.com?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    140,
                    67,
                    93,
                    96,
                    97,
                    127,
                    128
                ]
            },
            {
                "id": "8c64ce9c1afa49358ca5fb931a5edf22",
                "title": "Design Details",
                "publisher": "Spec",
                "image": "https://cdn-images-1.listennotes.com/podcasts/design-details-spec-LMOK6az7Jcq-R9lQegBoP-y.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/design-details-spec-LMOK6az7Jcq-R9lQegBoP-y.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/8c64ce9c1afa49358ca5fb931a5edf22/",
                "total_episodes": 354,
                "description": "A weekly conversation about design process and culture. Hosted by Marshall Bock and Brian Lovin.",
                "itunes_id": 947191070,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "https://designdetails.fm?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    140,
                    93,
                    157,
                    97,
                    67,
                    100,
                    105,
                    127,
                    129
                ]
            },
            {
                "id": "83262c8b71f943d9b8759e0ada6d4e8b",
                "title": "UX Podcast",
                "publisher": "UX Podcast",
                "image": "https://cdn-images-1.listennotes.com/podcasts/ux-podcast-ux-podcast-ltcUrOMgR4x-31WswGXPCSk.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/ux-podcast-ux-podcast-ltcUrOMgR4x-31WswGXPCSk.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/83262c8b71f943d9b8759e0ada6d4e8b/",
                "total_episodes": 245,
                "description": "UXPodcast\u2122 is a twice-monthly digital design podcast - hosted by James Royal-Lawson and Per Axbom - sharing insights about business, technology and people since 2011. We want to push the boundaries of how user experience is perceived and boost your confidence in the work you do.",
                "itunes_id": 438896324,
                "rss": "https://anchor.fm/s/2125a62c/podcast/rss",
                "language": "English",
                "country": "United States",
                "website": "https://uxpodcast.com?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    140,
                    67,
                    93,
                    97,
                    100,
                    105,
                    111,
                    112
                ]
            },
            {
                "id": "9006239f782246c5b17c33f5f76933f6",
                "title": "seanwes podcast ",
                "publisher": "Sean McCabe and Ben Toalson",
                "image": "https://cdn-images-1.listennotes.com/podcasts/seanwes-podcast-sean-mccabe-and-ben-toalson-pmTHCb-nJF_.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/seanwes-podcast-sean-mccabe-and-ben-toalson-pmTHCb-nJF_.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/9006239f782246c5b17c33f5f76933f6/",
                "total_episodes": 494,
                "description": "Build and grow a sustainable business. From products and marketing to professionalism and clients, you\u2019ll get answers to the hard-hitting questions.  Join entrepreneurs Sean McCabe and Ben Toalson as they let you inside their discussions on the many facets of making a living online. You\u2019ll come away from every episode with something of value that you can apply to your business.",
                "itunes_id": 685421236,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "http://seanwes.com/podcast?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    140,
                    67,
                    93,
                    94,
                    97,
                    100,
                    105
                ]
            },
            {
                "id": "a706b0a930b24e269bcf177432ce5ac6",
                "title": "The Changelog: Software Dev & Open Source",
                "publisher": "Changelog Media",
                "image": "https://cdn-images-1.listennotes.com/podcasts/the-changelog-changelog-media-7o41SvyO8z_.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/the-changelog-changelog-media-7o41SvyO8z_.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/a706b0a930b24e269bcf177432ce5ac6/",
                "total_episodes": 408,
                "description": "Conversations with the hackers, leaders, and innovators of software development.  Hosts Adam Stacoviak and Jerod Santo face their imposter syndrome so you don\u2019t have to. Expect in-depth interviews with the best and brightest in software engineering, open source, and leadership. This is a polyglot podcast. All programming languages, platforms, and communities are welcome. Open source moves fast. Keep up.",
                "itunes_id": 341623264,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "https://changelog.com/podcast?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": true,
                "type": "episodic",
                "genre_ids": [
                    127,
                    157,
                    140,
                    67,
                    128,
                    131,
                    143
                ]
            },
            {
                "id": "083e27920aa049c7a4b035f3acb24272",
                "title": "Syntax - Tasty Web Development Treats",
                "publisher": "Wes Bos & Scott Tolinski - Full Stack JavaScript Web Developers",
                "image": "https://cdn-images-1.listennotes.com/podcasts/syntax-tasty-web-development-treats-wes-bos-HBLGrV82oAs.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/syntax-tasty-web-development-treats-wes-bos-HBLGrV82oAs.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/083e27920aa049c7a4b035f3acb24272/",
                "total_episodes": 262,
                "description": "Full Stack Developers Wes Bos and Scott Tolinski dive deep into web development topics, explaining how they work and talking about their own experiences. They cover from JavaScript frameworks like React, to the latest advancements in CSS to simplifying web tooling.",
                "itunes_id": 1253186678,
                "rss": "http://feed.syntax.fm/rss",
                "language": "English",
                "country": "United States",
                "website": "https://syntax.fm?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    127,
                    140,
                    67,
                    93,
                    94,
                    131
                ]
            },
            {
                "id": "a9bd8a40e25e461292ad49a903c58de4",
                "title": "Wireframe",
                "publisher": "Adobe",
                "image": "https://cdn-images-1.listennotes.com/podcasts/wireframe-adobe-Ex8uh7HATgP-oqX5WKphdUj.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/wireframe-adobe-Ex8uh7HATgP-oqX5WKphdUj.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/a9bd8a40e25e461292ad49a903c58de4/",
                "total_episodes": 17,
                "description": "Wireframe reveals the stories behind user experience design, for UX/UI designers, graphic designers, and the design-curious. Hosted by Khoi Vinh, principal designer at Adobe and one of Fast Company\u2019s 100 Most Creative People in Business, the podcast explores unexpected ways that user experience design helps technology fit into our lives.",
                "itunes_id": 1437677219,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "https://xd.adobe.com/ideas/perspectives/wireframe-podcast/?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    140,
                    127,
                    122,
                    105,
                    100,
                    67
                ]
            },
            {
                "id": "389c6ccb0e0449b9be5378f18e5bce4e",
                "title": "UIE Book Corner",
                "publisher": "Adam Churchill",
                "image": "https://cdn-images-1.listennotes.com/podcasts/uie-book-corner-adam-churchill-XCsOofQNvDj.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/uie-book-corner-adam-churchill-XCsOofQNvDj.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/389c6ccb0e0449b9be5378f18e5bce4e/",
                "total_episodes": 7,
                "description": "Here at UIE we\u2019ve amassed quite a library, and we\u2019re adding to it all the time. Adam Churchill took the opportunity to interview a few of our favorite authors to dig a little deeper into the tools and techniques you can use in your day-to-day work.",
                "itunes_id": 1228221152,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "https://uie.fm/shows/uie-book-corner?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    140,
                    67,
                    93,
                    97,
                    100,
                    105,
                    127
                ]
            },
            {
                "id": "57375bbc28cc42dfba375e1f9420add8",
                "title": "The Web Ahead",
                "publisher": "5by5",
                "image": "https://cdn-images-1.listennotes.com/podcasts/the-web-ahead-5by5-pY1MkjX_vrh.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/the-web-ahead-5by5-pY1MkjX_vrh.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/57375bbc28cc42dfba375e1f9420add8/",
                "total_episodes": 117,
                "description": "Conversations with world experts on changing technologies and future of the web. The Web Ahead is your shortcut to keeping up. Hosted by Jen Simmons.",
                "itunes_id": 464936442,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "http://5by5.tv/webahead?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    140,
                    67,
                    127,
                    128
                ]
            },
            {
                "id": "9be0417a61ce4323a65a6785676f67b3",
                "title": "O'Reilly Design Podcast - O'Reilly Media Podcast",
                "publisher": "O'Reilly Media",
                "image": "https://cdn-images-1.listennotes.com/podcasts/oreilly-design-podcast-oreilly-media-4zZB732enIt-S9Qv-0HNrFm.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/oreilly-design-podcast-oreilly-media-4zZB732enIt-S9Qv-0HNrFm.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/9be0417a61ce4323a65a6785676f67b3/",
                "total_episodes": 50,
                "description": "Experience design insight and analysis.",
                "itunes_id": 1022433707,
                "rss": "https://cdn.oreillystatic.com/radar/oreilly-design-podcast/feed.rss",
                "language": "English",
                "country": "United States",
                "website": "https://www.oreilly.com?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    140,
                    67,
                    93,
                    127,
                    131,
                    105
                ]
            },
            {
                "id": "bebf78deac8f4909a65dfd25daad989d",
                "title": "UXpod - User Experience Podcast",
                "publisher": "Gerry Gaffney",
                "image": "https://cdn-images-1.listennotes.com/podcasts/uxpod-user-experience-podcast-gerry-gaffney-kcwnJu0ghAO.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/uxpod-user-experience-podcast-gerry-gaffney-kcwnJu0ghAO.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/bebf78deac8f4909a65dfd25daad989d/",
                "total_episodes": 114,
                "description": "A free-ranging set of discussions on matters of interest to people involved in user experience design, website design, and usability in general.",
                "itunes_id": 163924332,
                "rss": "https://uxpod.libsyn.com/rss",
                "language": "English",
                "country": "United States",
                "website": "http://www.uxpod.com?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    140,
                    67,
                    127,
                    128,
                    105
                ]
            },
            {
                "id": "60d9c48eba804965814cc466b6d224b7",
                "title": "UI Breakfast: UI/UX Design and Product Strategy",
                "publisher": "Jane Portman",
                "image": "https://cdn-images-1.listennotes.com/podcasts/ui-breakfast-uiux-design-and-product-kCAtL2pBY8E-v8FO4AaYQGC.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/ui-breakfast-uiux-design-and-product-kCAtL2pBY8E-v8FO4AaYQGC.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/60d9c48eba804965814cc466b6d224b7/",
                "total_episodes": 177,
                "description": "Join us for exciting conversations about UI/UX design, SaaS products, marketing, and so much more. My awesome guests are industry experts who share actionable knowledge \u2014 so that you can apply it in your business today.",
                "itunes_id": 939175693,
                "rss": "https://uibreakfast.com/category/podcasting/feed/",
                "language": "English",
                "country": "United States",
                "website": "http://www.uibreakfast.com/podcast?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    140,
                    67,
                    93,
                    100,
                    105,
                    127
                ]
            },
            {
                "id": "6ff6656705c04e0f94166e2c086c21ec",
                "title": "The UX Blog: User Experience Design, Research & Strategy",
                "publisher": "Interviews with User Experience (UX) professionals hosted by Nicholas Tenhu",
                "image": "https://cdn-images-1.listennotes.com/podcasts/the-ux-blog-user-experience-design-research-gK-Ux0lNRt_-_L94aHGxiMc.300x300.jpg",
                "thumbnail": "https://cdn-images-1.listennotes.com/podcasts/the-ux-blog-user-experience-design-research-gK-Ux0lNRt_-_L94aHGxiMc.300x300.jpg",
                "listennotes_url": "https://www.listennotes.com/c/6ff6656705c04e0f94166e2c086c21ec/",
                "total_episodes": 18,
                "description": "Every week on The UX Blog Podcast, Nicholas Tenhue interviews user experience professionals about current trends, hot topics, and their careers. Learn about user-centered design, information architecture, user research, UX strategy, and interaction design. Nicholas interviews UXers at different stages in their career, from interns to executives. Find out more: www.theuxblog.com",
                "itunes_id": 1127946001,
                "rss": "",
                "language": "English",
                "country": "United States",
                "website": "http://theuxblog.com?utm_source=listennotes.com&utm_campaign=Listen+Notes&utm_medium=website",
                "is_claimed": false,
                "type": "episodic",
                "genre_ids": [
                    140,
                    67,
                    93,
                    94,
                    100,
                    105,
                    127,
                    129
                ]
            }
        ],
        "total": 45,
        "has_next": true,
        "has_previous": false,
        "page_number": 1,
        "previous_page_number": 0,
        "next_page_number": 2,
        "listennotes_url": "https://www.listennotes.com/best-web-design-podcasts-140/"
    }
]
*/

