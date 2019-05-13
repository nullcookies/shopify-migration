<?php

if ( !class_exists( 'Shopify_Migration' ) ) {

    class Shopify_Migration
    {
        private $api_key;
        private $password;
        private $url;
        private $limit;
        private $author;

        public function __construct( $data ) {
            $this->api_key = $data['api_key'];
            $this->password = $data['password'];
            $this->url = $data['url'];
            $this->blog = $data['blog'];
            $this->limit = $data['limit'];
            $this->author = $data['author'];
        }

        /**
         * Exports blog posts from Shopify
         * 
         * @return array blog
         */
        public function export_posts_from_shopify() {
            $config = array(
                'ApiKey' => $this->api_key,
                'Password' => $this->password,
                'ShopUrl' => $this->url
            );
        
            $shopify = new PHPShopify\ShopifySDK($config);        
            $params = array(
                'limit' => $this->limit,
                'published_at_max' => '2018-08-01' // need to set option field for date
            );
            $blog = $shopify->Blog( $this->blog )->Article->get($params);
            return $blog;
        }

        /**
         * Imports blog posts into WordPress
         * 
         * @param array blog
         * @return bool
         */
        function import_posts_to_wordpress($blog) {
            global $wpdb;
        
            $cat_id = 1; // need to import categories
            $author_id = $this->author;
        
            foreach($blog as $b) {

                $dateTime = iso8601_to_datetime( $b['published_at'] );
                $title = sanitize_text_field( $b['title'] );
                $content = $b['body_html'];
                $exerpt = sanitize_text_field( $b['summary_html'] );
                $tags = sanitize_text_field( $b['tags'] );
                
                $args = array(
                    'post_title' => $title,
                    'post_content' => $content,
                    'post_status' => 'publish',
                    'post_date' => $dateTime,
                    'post_excerpt' => $exerpt,
                    'post_author' => $author_id,
                    'tags_input' => $tags
                );

                $post_id = wp_insert_post($args);
                $cat = wp_set_post_categories($post_id, $cat_id);
                $media = media_sideload_image($b['image']['src'], $post_id);
                if (!empty($media) && !is_wp_error($media)) {
                    $args = array(
                        'post_type' => 'attachment',
                        'posts_per_page' => -1,
                        'post_status' => 'any',
                        'post_parent' => $post_id
                    );
        
                    $attachments = get_posts($args);
        
                    if (isset($attachments) && is_array($attachments)) {
                        foreach ($attachments as $attachment) {
                            $image = wp_get_attachment_image_src($attachment->ID, 'full');
                            if (strpos($media, $image[0]) !== false) {
                                set_post_thumbnail($post_id, $attachment->ID);
                                break;
                            }
                        }
                    }
                }
            }

            return true;
        }
    }
}