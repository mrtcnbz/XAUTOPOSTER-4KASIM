<?php
namespace XAutoPoster;

class Plugin {
    private static $instance = null;
    private $settings;
    private $twitter;
    private $queue;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->initHooks();
    }

    private function initHooks() {
        add_action('init', [$this, 'init']);
        register_activation_hook(XAUTOPOSTER_FILE, [$this, 'activate']);
        register_deactivation_hook(XAUTOPOSTER_FILE, [$this, 'deactivate']);
    }

    public function init() {
        if (!class_exists('Abraham\\TwitterOAuth\\TwitterOAuth')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . 
                     __('XAutoPoster requires TwitterOAuth library. Please run composer install.', 'xautoposter') . 
                     '</p></div>';
            });
            return;
        }

        $this->loadTextdomain();
        $this->initComponents();
        $this->registerHooks();
    }

    private function loadTextdomain() {
        load_plugin_textdomain(
            'xautoposter',
            false,
            dirname(plugin_basename(XAUTOPOSTER_FILE)) . '/languages'
        );
    }

    private function initComponents() {
        if (!class_exists('XAutoPoster\\Admin\\Settings')) {
            require_once XAUTOPOSTER_PATH . 'src/Admin/Settings.php';
        }
        $this->settings = new Admin\Settings();
        
        $options = get_option('xautoposter_options', []);
        
        if (!empty($options['api_key']) && !empty($options['api_secret']) && 
            !empty($options['access_token']) && !empty($options['access_token_secret'])) {
            if (!class_exists('XAutoPoster\\Services\\TwitterService')) {
                require_once XAUTOPOSTER_PATH . 'src/Services/TwitterService.php';
            }
            $this->twitter = new Services\TwitterService(
                $options['api_key'],
                $options['api_secret'],
                $options['access_token'],
                $options['access_token_secret']
            );
            
            // Verify credentials
            if (!$this->twitter->verifyCredentials()) {
                add_action('admin_notices', function() {
                    echo '<div class="error"><p>' . 
                         __('XAutoPoster: Twitter API credentials are invalid. Please check your settings.', 'xautoposter') . 
                         '</p></div>';
                });
            }
        }
        
        if (!class_exists('XAutoPoster\\Models\\Queue')) {
            require_once XAUTOPOSTER_PATH . 'src/Models/Queue.php';
        }
        $this->queue = new Models\Queue();
    }

    private function registerHooks() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this->settings, 'registerSettings']);
        add_action('save_post', [$this, 'handlePostSave'], 10, 3);
        add_action('wp_ajax_xautoposter_share_posts', [$this, 'handleManualShare']);
        add_action('xautoposter_cron_hook', [$this, 'processQueue']);
        add_filter('cron_schedules', [$this, 'addCronInterval']);
    }

    public function addAdminMenu() {
        add_menu_page(
            __('XAutoPoster', 'xautoposter'),
            __('XAutoPoster', 'xautoposter'),
            'manage_options',
            'xautoposter',
            [$this, 'renderAdminPage'],
            'dashicons-twitter',
            30
        );

        add_submenu_page(
            'xautoposter',
            __('Settings', 'xautoposter'),
            __('Settings', 'xautoposter'),
            'manage_options',
            'xautoposter'
        );
    }

    public function renderAdminPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz bulunmuyor.', 'xautoposter'));
        }
        
        if (!file_exists(XAUTOPOSTER_PATH . 'templates/admin-page.php')) {
            wp_die(__('Admin template file not found.', 'xautoposter'));
        }
        
        require_once XAUTOPOSTER_PATH . 'templates/admin-page.php';
    }

    public function handlePostSave($postId, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if ($post->post_status !== 'publish') return;
        if ($update && get_post_meta($postId, '_xautoposter_shared', true)) return;
        
        $this->queue->addToQueue($postId);
    }

    public function handleManualShare() {
        check_ajax_referer('xautoposter_share_posts', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Yetkiniz yok.', 'xautoposter')]);
        }
        
        if (!$this->twitter) {
            wp_send_json_error(['message' => __('Twitter API bilgileri eksik veya hatalı.', 'xautoposter')]);
            return;
        }
        
        $postIds = isset($_POST['posts']) ? array_map('intval', $_POST['posts']) : [];
        
        if (empty($postIds)) {
            wp_send_json_error(['message' => __('Gönderi seçilmedi.', 'xautoposter')]);
        }
        
        $sharedPosts = [];
        $errors = [];
        
        foreach ($postIds as $postId) {
            $result = $this->sharePost($postId);
            if ($result === true) {
                $sharedPosts[] = $postId;
            } else {
                $errors[] = sprintf(__('Gönderi #%d: %s', 'xautoposter'), $postId, $result);
            }
        }
        
        if (!empty($sharedPosts)) {
            wp_send_json_success([
                'message' => sprintf(__('%d gönderi başarıyla paylaşıldı.', 'xautoposter'), count($sharedPosts)),
                'shared_posts' => $sharedPosts,
                'errors' => $errors
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Gönderiler paylaşılırken hata oluştu:', 'xautoposter') . ' ' . implode(', ', $errors)
            ]);
        }
    }

    public function processQueue() {
        $pendingPosts = $this->queue->getPendingPosts();
        
        foreach ($pendingPosts as $post) {
            $this->sharePost($post->post_id);
        }
    }

    private function sharePost($postId) {
        if (!$this->twitter) {
            return __('Twitter API bilgileri eksik.', 'xautoposter');
        }
        
        $post = get_post($postId);
        if (!$post) {
            return __('Gönderi bulunamadı.', 'xautoposter');
        }
        
        $content = $this->formatPostContent($post);
        $mediaIds = $this->uploadFeaturedImage($postId);
        
        $result = $this->twitter->sharePost($content, $mediaIds);
        
        if ($result) {
            update_post_meta($postId, '_xautoposter_shared', '1');
            update_post_meta($postId, '_xautoposter_share_time', current_time('mysql'));
            $this->queue->markAsShared($postId);
            return true;
        }
        
        return __('Twitter API hatası.', 'xautoposter');
    }

    private function formatPostContent($post) {
        $options = get_option('xautoposter_options', []);
        $template = isset($options['post_template']) ? $options['post_template'] : '%title% %link%';
        $permalink = get_permalink($post->ID);
        
        $content = str_replace(
            ['%title%', '%excerpt%', '%link%'],
            [$post->post_title, wp_trim_words(get_the_excerpt($post), 10), $permalink],
            $template
        );
        
        return mb_substr($content, 0, 280);
    }

    private function uploadFeaturedImage($postId) {
        if (!has_post_thumbnail($postId)) {
            return [];
        }
        
        $imageId = get_post_thumbnail_id($postId);
        $imagePath = get_attached_file($imageId);
        
        if ($mediaId = $this->twitter->uploadMedia($imagePath)) {
            return [$mediaId];
        }
        
        return [];
    }

    public function addCronInterval($schedules) {
        $schedules['five_minutes'] = [
            'interval' => 300,
            'display' => __('Her 5 dakikada', 'xautoposter')
        ];
        return $schedules;
    }

    public function activate() {
        if (!class_exists('XAutoPoster\\Models\\Queue')) {
            require_once XAUTOPOSTER_PATH . 'src/Models/Queue.php';
        }
        $queue = new Models\Queue();
        $queue->createTable();
        
        if (!wp_next_scheduled('xautoposter_cron_hook')) {
            wp_schedule_event(time(), 'five_minutes', 'xautoposter_cron_hook');
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook('xautoposter_cron_hook');
    }
}