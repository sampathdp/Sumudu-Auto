<?php
// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $baseUrl = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
    define('BASE_URL', $baseUrl);
}
?>

<!-- Webfont Loader -->
<script src="<?php echo asset('js/plugin/webfont/webfont.min.js'); ?>"></script>
<script>
    WebFont.load({
        google: {
            families: ["Public Sans:300,400,500,600,700"]
        },
        custom: {
            families: [
                "Font Awesome 5 Solid",
                "Font Awesome 5 Regular",
                "Font Awesome 5 Brands",
                "simple-line-icons",
            ],
            urls: ["<?php echo asset('css/fonts.min.css'); ?>"],
        },
        active: function() {
            sessionStorage.fonts = true;
        },
    });
</script>

<!-- CSS Files -->
<link rel="stylesheet" href="<?php echo asset('css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?php echo asset('css/plugins.min.css'); ?>">
<link rel="stylesheet" href="<?php echo asset('css/admin.min.css'); ?>">