<?php
namespace XAutoPoster\Services;

use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterService {
    private $connection;
    private $debug = false;
    
    public function __construct($apiKey, $apiSecret, $accessToken, $accessTokenSecret) {
        try {
            $this->connection = new TwitterOAuth(
                $apiKey,
                $apiSecret,
                $accessToken,
                $accessTokenSecret
            );
            $this->connection->setApiVersion('2');
            $this->connection->setTimeouts(10, 15);
        } catch (\Exception $e) {
            error_log('Twitter Connection Error: ' . $e->getMessage());
            throw new \Exception('Twitter bağlantısı kurulamadı: ' . $e->getMessage());
        }
    }
    
    public function verifyCredentials() {
        try {
            $result = $this->connection->get('users/me');
            return isset($result->data->id) ? $result : false;
        } catch (\Exception $e) {
            error_log('Twitter API Error (Verify): ' . $e->getMessage());
            return false;
        }
    }
    
    public function sharePost($postId) {
        try {
            // Get post data
            $post = get_post($postId);
            if (!$post) {
                throw new \Exception('Post bulunamadı');
            }

            // Get post title and permalink
            $title = html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8');
            $permalink = get_permalink($post);
            
            // Format the content
            $content = $this->formatPostContent($title, $permalink);
            
            // Get featured image if exists
            $mediaIds = [];
            if (has_post_thumbnail($post)) {
                $imageId = get_post_thumbnail_id($post);
                $imagePath = get_attached_file($imageId);
                if ($imagePath && file_exists($imagePath)) {
                    $mediaId = $this->uploadMedia($imagePath);
                    if ($mediaId) {
                        $mediaIds[] = $mediaId;
                    }
                }
            }

            // Prepare tweet parameters
            $params = ['text' => $content];
            
            if (!empty($mediaIds)) {
                $params['media'] = ['media_ids' => $mediaIds];
            }
            
            // Post tweet
            $result = $this->connection->post('tweets', $params, true);
            
            if ($this->debug) {
                error_log('Twitter API Response: ' . print_r($result, true));
            }
            
            if (!isset($result->data->id)) {
                throw new \Exception(isset($result->errors[0]->message) ? 
                    $result->errors[0]->message : 'Bilinmeyen Twitter API hatası');
            }
            
            return true;
            
        } catch (\Exception $e) {
            error_log('Twitter API Error (Share): ' . $e->getMessage());
            if ($this->debug) {
                error_log('Post ID: ' . $postId);
                error_log('Parameters: ' . print_r($params ?? [], true));
            }
            throw new \Exception('Twitter paylaşım hatası: ' . $e->getMessage());
        }
    }
    
    private function formatPostContent($title, $permalink) {
        $options = get_option('xautoposter_options', []);
        $template = isset($options['post_template']) ? 
            $options['post_template'] : '%title% %link%';
            
        // Replace placeholders
        $content = str_replace(
            ['%title%', '%link%'],
            [$title, $permalink],
            $template
        );
        
        // Ensure content doesn't exceed Twitter's limit
        return mb_substr($content, 0, 280);
    }
    
    private function uploadMedia($imagePath) {
        try {
            if (!file_exists($imagePath)) {
                throw new \Exception('Görsel dosyası bulunamadı: ' . $imagePath);
            }
            
            // Check file size
            $fileSize = filesize($imagePath);
            if ($fileSize > 5242880) { // 5MB limit
                throw new \Exception('Görsel dosyası çok büyük (max: 5MB)');
            }
            
            // Upload media
            $media = $this->connection->upload('media/upload', ['media' => $imagePath]);
            
            if ($this->debug) {
                error_log('Media Upload Response: ' . print_r($media, true));
            }
            
            if (!isset($media->media_id_string)) {
                throw new \Exception('Medya yüklenemedi');
            }
            
            return $media->media_id_string;
            
        } catch (\Exception $e) {
            error_log('Twitter Media Upload Error: ' . $e->getMessage());
            return false;
        }
    }
}