# Creed: WordPress Code Challenge #

## Running locally
The following command will run a brand new WordPress, MySQL, and PHPMyAdmin instance on your local machine. 
```bash
docker compose up -d
```

## Access and setup WordPress
Once the containers have spun all the way up and you see the WordPress core files in the __wordpress__ folder.

Now you should be able to visit http://localhost to see the website and proceed through the WordPress install steps.

## Enable the plugin
This plugin requires Advanced Custom Fields to be installed and activated. 

At the plugins page you will see a notice to install the plugin. Click the link to install and activate the plugin.

## Run the plugin
Once the plugin is activated, you will see a new menu item in the WordPress admin called "CreedCast".

There you can upload the .json file with the Podcasts data.

Drop the file in the field or click on it to select the file.

Click on the "Import" button to import the data.

## Extra
After the process is done, you can see the errors that happened during the import process below the import button.