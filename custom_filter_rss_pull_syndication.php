<?php
/*
    Description: This file contains filters to add custom fields (post meta datas) into new posts pulled from other sites by Push Syndication

    Author: Lucas Fonseca
    URL: http://devlucasmendes.com
    Version: 1.2
*/


//This filter acts in each post iteration, when Push Syndication gets them to insert or update
add_filter('syn_rss_pull_filter_post', 'my_function_rss_pull_save_meta', 10, 3);
/**
 * Filter $post on ingenstion and update from RSS Pull
 * @param $post -- the new post array
 * @param $args -- an array of args
 * @param $item -- an object of class SimplePie_Item representing the feed item see http://simplepie.org/api/class-SimplePie_Item.html
 *
 **/
function my_function_rss_pull_save_meta($post, $args, $item)
{

    // $post is the post that push syndication will insert
    // $item is a SimplePie object with the values caught from a RSS for example.

    ### Examples

    //Thumbnail - getting an img src and saving into a post
    $featured_image_url = $item->get_item_tags('', 'featured_image_url');
    if (isset($featured_image_url[0]['data'])) {
        $post['postmeta']['my_featured_image_url'] = $featured_image_url[0]['data'];
    }

    //URL - getting an custom url and inserting into the post metadata
    $url = $item->get_item_tags('', 'link');
    if (isset($url[0]['data'])) {
        $post['postmeta']['my_url_post_from_another_site'] = $url[0]['data'];
    }

    //Tax - getting a custom value from RSS and add in a custom tax
    //Ps.: WP will not insert nothin into my_brand taxonomy. We will have to use the filters documented below
    $brand = $item->feed->data['child']['']['rss'][0]['child']['']['channel'][0]['child']['']['brand_slug'];
    if (!empty($brand)) {
        $post['my_brand_values'] = $brand[0]['data'];
    }

    // PS will add those values into the new (or updated) post
    return $post;
}

// Filters to get the id of the post after it's inserted or updated by PS(push syndication)
add_action('syn_post_pull_new_post', 'get_post_inserted_and_use_infs', 10, 5);
add_action('syn_post_pull_edit_post', 'get_post_inserted_and_use_infs', 10, 5);
/**
 * With this filter we can insert values into custom taxonomies for example
 *
 * @param $result the post id that ps has inserted/updated
 * @param $post the $post variable returned by my_function_rss_pull_save_meta in filter syn_rss_pull_filter_post
 * @param $site
 * @param $transport_type
 * @param $client
 * @return bool
 */
function get_post_inserted_and_use_infs($result, $post, $site, $transport_type, $client)
{
    //Example: geting values (terms) and inserting them into a taxonomy
    if (isset($post['my_brand_values'])) {
        wp_set_object_terms($result, $post['my_brand_values'], 'my_brand', true);
    }
}


/**
 * Push Syndication/SimplePie has a cache that can be a bother.
 * Ex.: Push Syndication doesn' syndicates new posts because of its cache
 *
 * Function to retrieve url from push syndication sites and add an arg to prevent cache
 * when PS gets the site url, it'll now came with a timestamp
 * */
add_filter("get_post_metadata", 'my_filter_url_meta_post_in_ps', 10, 4);
function my_filter_url_meta_post_in_ps($a, $object_id, $meta_key, $single)
{
    if ($meta_key === 'syn_feed_url') {

        //preventing a infinite looping
        remove_filter('get_post_metadata', 'my_filter_url_meta_post_in_ps');

        //adds a timestamp to url on retrieving value
        $url = get_post_meta($object_id, 'syn_feed_url', true) . '?' . time();

        //adds this filter again
        add_filter("get_post_metadata", 'my_filter_url_meta_post_in_ps', 10, 4);

        return $url;
    }
}