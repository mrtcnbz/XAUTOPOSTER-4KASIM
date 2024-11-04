<?php
namespace XAutoPoster\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ApiService {
    private $client;
    private $baseUrl;
    
    public function __construct() {
        $this->baseUrl = get_option('xautoposter_api_url', 'http://localhost:3000/api');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 10.0,
        ]);
    }
    
    public function getPosts($page = 1, $limit = 10, $status = 'publish') {
        try {
            $response = $this->client->get('posts', [
                'query' => [
                    'page' => $page,
                    'limit' => $limit,
                    'status' => $status
                ]
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            error_log('API Error (getPosts): ' . $e->getMessage());
            return false;
        }
    }
    
    public function getPost($postId) {
        try {
            $response = $this->client->get("posts/{$postId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            error_log('API Error (getPost): ' . $e->getMessage());
            return false;
        }
    }
    
    public function createPost($data) {
        try {
            $response = $this->client->post('posts', [
                'json' => $data
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            error_log('API Error (createPost): ' . $e->getMessage());
            return false;
        }
    }
    
    public function updatePost($postId, $data) {
        try {
            $response = $this->client->put("posts/{$postId}", [
                'json' => $data
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            error_log('API Error (updatePost): ' . $e->getMessage());
            return false;
        }
    }
    
    public function deletePost($postId) {
        try {
            $response = $this->client->delete("posts/{$postId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            error_log('API Error (deletePost): ' . $e->getMessage());
            return false;
        }
    }
}