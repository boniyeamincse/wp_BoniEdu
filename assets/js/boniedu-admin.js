jQuery(document).ready(function ($) {
    // UI Enhancements
    console.log('BoniEdu Admin Assets Loaded');

    // Add fade-in effect to cards
    $('.boniedu-card').hide().fadeIn(400);

    // Optional: Add close button to notices if missing
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut();
    });
});
