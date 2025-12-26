<?php

namespace BoniEdu\Admin;

class Certificates
{
    private $plugin_name;
    private $version;
    private $option_group = 'boniedu_certificates_group';
    private $option_name = 'boniedu_certificate_settings';

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function register_settings()
    {
        register_setting(
            $this->option_group,
            $this->option_name,
            array($this, 'sanitize_settings')
        );

        add_settings_section(
            'boniedu_certificate_main',
            'Certificate Template Settings',
            null,
            'boniedu-certificates'
        );

        add_settings_field(
            'certificate_bg_image',
            'Background Image',
            array($this, 'render_image_field'),
            'boniedu-certificates',
            'boniedu_certificate_main',
            array('key' => 'bg_image', 'desc' => 'Upload a background image for the certificate.')
        );

        add_settings_field(
            'certificate_heading',
            'Heading Text',
            array($this, 'render_text_field'),
            'boniedu-certificates',
            'boniedu_certificate_main',
            array('key' => 'heading', 'default' => 'Certificate of Achievement')
        );

        add_settings_field(
            'certificate_body',
            'Body Text',
            array($this, 'render_textarea_field'),
            'boniedu-certificates',
            'boniedu_certificate_main',
            array('key' => 'body', 'desc' => 'Use placeholders: {student_name}, {class}, {roll}, {year}, {gpa}.')
        );

        add_settings_field(
            'signature_left',
            'Left Signature',
            array($this, 'render_image_field'),
            'boniedu-certificates',
            'boniedu_certificate_main',
            array('key' => 'signature_left')
        );

        add_settings_field(
            'signature_left_text',
            'Left Signature Text',
            array($this, 'render_text_field'),
            'boniedu-certificates',
            'boniedu_certificate_main',
            array('key' => 'signature_left_text', 'default' => 'Principal')
        );

        add_settings_field(
            'signature_right',
            'Right Signature',
            array($this, 'render_image_field'),
            'boniedu-certificates',
            'boniedu_certificate_main',
            array('key' => 'signature_right')
        );

        add_settings_field(
            'signature_right_text',
            'Right Signature Text',
            array($this, 'render_text_field'),
            'boniedu-certificates',
            'boniedu_certificate_main',
            array('key' => 'signature_right_text', 'default' => 'Chairman')
        );
    }

    public function sanitize_settings($input)
    {
        $new_input = array();
        $fields = ['bg_image', 'heading', 'body', 'signature_left', 'signature_left_text', 'signature_right', 'signature_right_text'];

        foreach ($fields as $field) {
            if (isset($input[$field])) {
                if ($field === 'body') {
                    $new_input[$field] = sanitize_textarea_field($input[$field]);
                } else {
                    $new_input[$field] = sanitize_text_field($input[$field]);
                }
            }
        }
        return $new_input;
    }

    public function render_text_field($args)
    {
        $options = get_option($this->option_name);
        $key = $args['key'];
        $val = isset($options[$key]) ? esc_attr($options[$key]) : (isset($args['default']) ? $args['default'] : '');
        echo "<input type='text' name='{$this->option_name}[$key]' value='$val' class='regular-text' />";
    }

    public function render_textarea_field($args)
    {
        $options = get_option($this->option_name);
        $key = $args['key'];
        $val = isset($options[$key]) ? esc_textarea($options[$key]) : '';
        echo "<textarea name='{$this->option_name}[$key]' class='large-text' rows='5'>$val</textarea>";
        if (isset($args['desc'])) {
            echo "<p class='description'>{$args['desc']}</p>";
        }
    }

    public function render_image_field($args)
    {
        $options = get_option($this->option_name);
        $key = $args['key'];
        $image_url = isset($options[$key]) ? esc_attr($options[$key]) : '';

        echo '<div class="image-uploader-wrapper">';
        echo "<input type='text' name='{$this->option_name}[$key]' id='{$key}' value='$image_url' class='regular-text' />";
        echo "<input type='button' class='button-secondary upload-image-button' data-target='{$key}' value='Upload Image' />";
        echo "<div id='preview_{$key}' style='margin-top:10px; max-width: 150px;'>";
        if ($image_url) {
            echo "<img src='$image_url' style='max-width:100%; height:auto;' />";
        }
        echo "</div>";
        echo '</div>';
    }

    public function render_page()
    {
        settings_errors($this->option_group);
        ?>
        <div class="wrap">
            <h1>Certificate Settings</h1>

            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields($this->option_group);
                        do_settings_sections('boniedu-certificates');
                        submit_button();
                        ?>
                    </form>
                </div>
                <div style="flex: 1; border: 1px solid #ccc; padding: 20px; background: #fff;">
                    <h2>Live Preview</h2>
                    <div id="certificate-preview"
                        style="position: relative; width: 100%; padding-top: 70%; background-size: cover; background-position: center; border: 1px solid #ddd;">
                        <div
                            style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px;">
                            <h2 id="preview-heading" style="font-size: 24px; margin-bottom: 20px;"></h2>
                            <p id="preview-body" style="font-size: 16px; line-height: 1.6; white-space: pre-wrap;"></p>

                            <div style="width: 100%; display: flex; justify-content: space-between; margin-top: auto;">
                                <div style="text-align: center;">
                                    <img id="preview-sig-left" src="" style="max-height: 60px; display: none;" />
                                    <p id="preview-sig-left-text"
                                        style="border-top: 1px solid #000; padding-top: 5px; margin-top: 5px; font-weight: bold;">
                                    </p>
                                </div>
                                <div style="text-align: center;">
                                    <img id="preview-sig-right" src="" style="max-height: 60px; display: none;" />
                                    <p id="preview-sig-right-text"
                                        style="border-top: 1px solid #000; padding-top: 5px; margin-top: 5px; font-weight: bold;">
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                jQuery(document).ready(function ($) {
                    // Media Uploader Logic
                    var mediaUploader;
                    $('.upload-image-button').click(function (e) {
                        e.preventDefault();
                        var targetId = $(this).data('target');
                        if (mediaUploader) {
                            mediaUploader.open();
                            return;
                        }
                        mediaUploader = wp.media.frames.file_frame = wp.media({
                            title: 'Choose Image',
                            button: { text: 'Choose Image' },
                            multiple: false
                        });
                        mediaUploader.on('select', function () {
                            var attachment = mediaUploader.state().get('selection').first().toJSON();
                            $('#' + targetId).val(attachment.url);
                            $('#preview_' + targetId).html('<img src="' + attachment.url + '" style="max-width:100%; height:auto;" />');
                            updatePreview();
                        });
                        mediaUploader.open();
                    });

                    // Live Preview Logic
                    function updatePreview() {
                        var bg = $('#bg_image').val();
                        var heading = $('input[name="<?php echo $this->option_name; ?>[heading]"]').val();
                        var body = $('textarea[name="<?php echo $this->option_name; ?>[body]"]').val();

                        var sigLeft = $('#signature_left').val();
                        var sigLeftText = $('input[name="<?php echo $this->option_name; ?>[signature_left_text]"]').val();

                        var sigRight = $('#signature_right').val();
                        var sigRightText = $('input[name="<?php echo $this->option_name; ?>[signature_right_text]"]').val();

                        if (bg) $('#certificate-preview').css('background-image', 'url(' + bg + ')');
                        $('#preview-heading').text(heading);

                        // Simple placeholder replacement for preview
                        body = body.replace(/{student_name}/g, "John Doe")
                            .replace(/{class}/g, "10")
                            .replace(/{roll}/g, "101")
                            .replace(/{year}/g, "<?php echo date('Y'); ?>")
                            .replace(/{gpa}/g, "5.00");
                        $('#preview-body').text(body);

                        if (sigLeft) { $('#preview-sig-left').attr('src', sigLeft).show(); } else { $('#preview-sig-left').hide(); }
                        $('#preview-sig-left-text').text(sigLeftText);

                        if (sigRight) { $('#preview-sig-right').attr('src', sigRight).show(); } else { $('#preview-sig-right').hide(); }
                        $('#preview-sig-right-text').text(sigRightText);
                    }

                    $('input, textarea').on('input change', updatePreview);
                    updatePreview(); // Init
                });
            </script>
        </div>
        <?php
    }
}
