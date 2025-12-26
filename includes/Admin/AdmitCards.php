<?php

namespace BoniEdu\Admin;

class AdmitCards
{
    private $plugin_name;
    private $version;
    private $option_name = 'boniedu_admit_card_settings';
    private $option_group = 'boniedu_admit_card_group';

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
            'boniedu_admit_card_section',
            'Admit Card Template Settings',
            null, // No callback needed for section description
            'boniedu-admit-cards'
        );

        add_settings_field(
            'bg_image',
            'Background Image',
            array($this, 'render_bg_image_field'),
            'boniedu-admit-cards',
            'boniedu_admit_card_section'
        );

        add_settings_field(
            'heading_text',
            'Exam Heading',
            array($this, 'render_heading_field'),
            'boniedu-admit-cards',
            'boniedu_admit_card_section'
        );

        add_settings_field(
            'exam_details',
            'Exam Time/Details',
            array($this, 'render_exam_details_field'),
            'boniedu-admit-cards',
            'boniedu_admit_card_section'
        );

        add_settings_field(
            'body_text',
            'Instructions',
            array($this, 'render_body_field'),
            'boniedu-admit-cards',
            'boniedu_admit_card_section'
        );
    }

    public function sanitize_settings($input)
    {
        $sanitized = array();
        $sanitized['bg_image'] = sanitize_text_field($input['bg_image']);
        $sanitized['heading'] = sanitize_text_field($input['heading']);
        $sanitized['exam_details'] = sanitize_textarea_field($input['exam_details']);
        $sanitized['body'] = wp_kses_post($input['body']);
        return $sanitized;
    }

    public function render_bg_image_field()
    {
        $options = get_option($this->option_name);
        $bg_image = isset($options['bg_image']) ? $options['bg_image'] : '';
        ?>
        <input type="text" name="<?php echo $this->option_name; ?>[bg_image]" id="boniedu_admit_bg_image"
            value="<?php echo esc_attr($bg_image); ?>" class="regular-text">
        <button type="button" class="button" id="boniedu_upload_admit_bg">Upload Image</button>
        <p class="description">Select a background image for the admit card.</p>
        <?php
    }

    public function render_heading_field()
    {
        $options = get_option($this->option_name);
        $heading = isset($options['heading']) ? $options['heading'] : 'ADMIT CARD';
        ?>
        <input type="text" name="<?php echo $this->option_name; ?>[heading]" id="boniedu_admit_heading"
            value="<?php echo esc_attr($heading); ?>" class="regular-text">
        <?php
    }

    public function render_exam_details_field()
    {
        $options = get_option($this->option_name);
        $details = isset($options['exam_details']) ? $options['exam_details'] : 'Exam: Mid-Term Examination 2024\nTime: 10:00 AM - 1:00 PM';
        ?>
        <textarea name="<?php echo $this->option_name; ?>[exam_details]" id="boniedu_admit_details" rows="3"
            class="large-text"><?php echo esc_textarea($details); ?></textarea>
        <p class="description">Enter exam name and timing details.</p>
        <?php
    }

    public function render_body_field()
    {
        $options = get_option($this->option_name);
        $body = isset($options['body']) ? $options['body'] : "Important Instructions:\n1. Bring this admit card to the exam hall.\n2. Do not carry any electronic devices.";
        ?>
        <textarea name="<?php echo $this->option_name; ?>[body]" id="boniedu_admit_body" rows="5"
            class="large-text"><?php echo esc_textarea($body); ?></textarea>
        <p class="description">Enter instructions for students.</p>
        <?php
    }


    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Admit Card Settings</h1>
            <?php settings_errors($this->option_group); ?>

            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px;">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields($this->option_group);
                        do_settings_sections('boniedu-admit-cards');
                        submit_button();
                        ?>
                    </form>
                </div>

                <div style="flex: 1; min-width: 300px;">
                    <h3>Live Preview</h3>
                    <div id="admit-card-preview"
                        style="border: 1px solid #ccc; width: 100%; aspect-ratio: 1/1.414; position: relative; background-size: cover; background-position: center; padding: 20px; box-sizing: border-box; overflow: hidden; height: 600px; width: 424px; margin: 0 auto; background-color: #fff;">

                        <div style="text-align: center; margin-top: 20px;">
                            <h2 id="preview-heading" style="margin: 0; font-family: sans-serif; text-transform: uppercase;">
                            </h2>
                        </div>

                        <div style="margin-top: 30px; border: 1px solid #000; padding: 10px;">
                            <p><strong>Name:</strong> John Doe</p>
                            <p><strong>Roll No:</strong> 101</p>
                            <p><strong>Class:</strong> Class 10</p>
                        </div>

                        <div id="preview-details"
                            style="margin-top: 20px; text-align: center; white-space: pre-wrap; font-weight: bold;"></div>

                        <div style="margin-top: 30px;">
                            <h4 style="margin-bottom: 5px; text-decoration: underline;">Instructions:</h4>
                            <div id="preview-body" style="white-space: pre-wrap; font-size: 12px;"></div>
                        </div>

                    </div>
                </div>
            </div>

            <script>
                jQuery(document).ready(function ($) {
                    // Media Uploader
                    var mediaUploader;
                    $('#boniedu_upload_admit_bg').click(function (e) {
                        e.preventDefault();
                        if (mediaUploader) {
                            mediaUploader.open();
                            return;
                        }
                        mediaUploader = wp.media.frames.file_frame = wp.media({
                            title: 'Select Background Image',
                            button: { text: 'Use this image' },
                            multiple: false
                        });
                        mediaUploader.on('select', function () {
                            var attachment = mediaUploader.state().get('selection').first().toJSON();
                            $('#boniedu_admit_bg_image').val(attachment.url);
                            updatePreview();
                        });
                        mediaUploader.open();
                    });

                    // Live Preview
                    function updatePreview() {
                        var bg = $('#boniedu_admit_bg_image').val();
                        var heading = $('#boniedu_admit_heading').val();
                        var details = $('#boniedu_admit_details').val();
                        var body = $('#boniedu_admit_body').val();

                        $('#admit-card-preview').css('background-image', 'url(' + bg + ')');
                        $('#preview-heading').text(heading);
                        $('#preview-details').text(details);
                        $('#preview-body').text(body);
                    }

                    // Bind events
                    $('input, textarea').on('input change', updatePreview);

                    // Initial load
                    updatePreview();
                });
            </script>
        </div>
        <?php
    }
}
