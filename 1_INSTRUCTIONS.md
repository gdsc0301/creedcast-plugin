# Creed: WordPress Code Challenge #

`Due Date:` End of the Day (5:00 PM) five days from the date you receive this test.

We recommend setting up the project in a hosted GIT repository to code share, or you can send it back in a .zip file.

`*Note:` If you have any questions or get stuck don’t hesitate to reach out.

---

The purpose of this test is to evaluate your experience with modern code languages and web/CMS/app development preferred stacks to get a better understanding on how you work with projects and tasks.

We will be evaluating your experience with custom PHP development within a WordPress website. The guidelines for what we're looking to scrutinize can be found in the README.md “Contribution guidelines” section.

## Backround ##

This simulates a situation where a client has asked us to sync data that is owned by another tool into WordPress on demand so that WordPress can statically present that data to customers on a set of web pages. While not a part of this ask, eventually there will be pieces of information that will be added to this data that will ONLY exist in WordPress and will never exist in the imported source data.

## Instructions: ##

1. Follow the `README.md` **How do I get going?** instructions to run WordPress
2. Build a WordPress plugin that reads in the JSON inside podcasts-by-genre.json
    * create a custom post type for the podcast records in this data
        * the `title` property will become the post's title
        * the `description` can go in the post body
        * the `thumbnail` property source should become the post's featured image
        * the import needs to also store: `publisher`, `image`, and `listennotes_url` so the theme will be able to utilize them within the templates.
    * store the genres as categories for the podcasts
        * ensure every podcast is related to at least one genre category
3. Ensure that your WordPress plugin is capable of reading a new JSON file containing similarly-formatted data, importing those as well. We will be providing the 2nd file during the next round so we can see how your import plugin works with new data.
4. Send an email back with your results and congratulations you’re done!