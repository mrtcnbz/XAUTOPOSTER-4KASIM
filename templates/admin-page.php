<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="nav-tab-wrapper">
        <a href="?page=xautoposter" class="nav-tab nav-tab-active"><?php _e('Settings', 'xautoposter'); ?></a>
    </div>
    
    <form method="post" action="options.php">
        <?php
            settings_fields('xautoposter_options');
            do_settings_sections('xautoposter-settings');
            submit_button();
        ?>
    </form>
    
    <?php if (get_option('xautoposter_options')): ?>
    <div class="card">
        <h2><?php _e('Manual Share', 'xautoposter'); ?></h2>
        <p><?php _e('Select posts to share them manually on X (Twitter).', 'xautoposter'); ?></p>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all-posts"></th>
                    <th><?php _e('Title', 'xautoposter'); ?></th>
                    <th><?php _e('Date', 'xautoposter'); ?></th>
                    <th><?php _e('Status', 'xautoposter'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $posts = get_posts([
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'posts_per_page' => 10,
                    'orderby' => 'date',
                    'order' => 'DESC'
                ]);
                
                foreach ($posts as $post):
                    $is_shared = get_post_meta($post->ID, '_xautoposter_shared', true);
                    $share_time = get_post_meta($post->ID, '_xautoposter_share_time', true);
                ?>
                <tr>
                    <td><input type="checkbox" name="posts[]" value="<?php echo $post->ID; ?>" <?php echo $is_shared ? 'disabled' : ''; ?>></td>
                    <td><?php echo esc_html($post->post_title); ?></td>
                    <td><?php echo get_the_date('', $post); ?></td>
                    <td>
                        <?php if ($is_shared): ?>
                            <span class="dashicons dashicons-yes-alt"></span> 
                            <?php echo sprintf(__('Shared on %s', 'xautoposter'), $share_time); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-minus"></span>
                            <?php _e('Not shared', 'xautoposter'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p>
            <button type="button" id="share-selected" class="button button-primary">
                <?php _e('Share Selected Posts', 'xautoposter'); ?>
            </button>
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#select-all-posts').on('change', function() {
            $('input[name="posts[]"]:not(:disabled)').prop('checked', $(this).prop('checked'));
        });
        
        $('#share-selected').on('click', function() {
            var posts = $('input[name="posts[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (posts.length === 0) {
                alert('<?php _e('Please select posts to share.', 'xautoposter'); ?>');
                return;
            }
            
            $(this).prop('disabled', true).text('<?php _e('Sharing...', 'xautoposter'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'xautoposter_share_posts',
                    posts: posts,
                    nonce: '<?php echo wp_create_nonce('xautoposter_share_posts'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('<?php _e('An error occurred while sharing posts.', 'xautoposter'); ?>');
                },
                complete: function() {
                    $('#share-selected').prop('disabled', false)
                        .text('<?php _e('Share Selected Posts', 'xautoposter'); ?>');
                }
            });
        });
    });
    </script>
    <?php endif; ?>
</div>

<style>
.wrap .card {
    max-width: none;
    margin-top: 20px;
    padding: 20px;
}
.wrap .widefat {
    margin: 15px 0;
}
.wrap .dashicons-yes-alt {
    color: #46b450;
}
.wrap .dashicons-minus {
    color: #dc3232;
}
</style>