<?php
namespace XAutoPoster\Admin;

class Settings {
    public function registerSettings() {
        // ... mevcut ayarlar aynı ...

        // Post template ayarı ekleniyor
        add_settings_field(
            'post_template',
            __('Post Template', 'xautoposter'),
            [$this, 'renderTemplateField'],
            'xautoposter-settings',
            'xautoposter_twitter_settings'
        );
    }

    // ... diğer metodlar aynı ...

    public function renderTemplateField() {
        $options = get_option('xautoposter_options', []);
        $value = isset($options['post_template']) ? $options['post_template'] : '%title% %link%';
        
        echo '<input type="text" name="xautoposter_options[post_template]" value="' . 
             esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">' . 
             __('Available placeholders: %title%, %link%', 'xautoposter') . '</p>';
    }
}