<!-- Core JS Files -->
<script src="<?php echo asset('js/core/jquery-3.7.1.min.js'); ?>"></script>
<script src="<?php echo asset('js/core/popper.min.js'); ?>"></script>
<script src="<?php echo asset('js/core/bootstrap.min.js'); ?>"></script>

<!-- jQuery Scrollbar -->
<script src="<?php echo asset('js/plugin/jquery-scrollbar/jquery.scrollbar.min.js'); ?>"></script>

<!-- Chart JS -->
<script src="<?php echo asset('js/plugin/chart.js/chart.min.js'); ?>"></script>

<!-- jQuery Sparkline -->
<script src="<?php echo asset('js/plugin/jquery.sparkline/jquery.sparkline.min.js'); ?>"></script>

<!-- Chart Circle -->
<script src="<?php echo asset('js/plugin/chart-circle/circles.min.js'); ?>"></script>

<!-- Datatables -->
<script src="<?php echo asset('js/plugin/datatables/datatables.min.js'); ?>"></script>

<!-- Bootstrap Notify -->
<script src="<?php echo asset('js/plugin/bootstrap-notify/bootstrap-notify.min.js'); ?>"></script>

<!-- jQuery Vector Maps -->
<script src="<?php echo asset('js/plugin/jsvectormap/jsvectormap.min.js'); ?>"></script>
<script src="<?php echo asset('js/plugin/jsvectormap/world.js'); ?>"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Admin JS -->
<script src="<?php echo asset('js/admin.min.js'); ?>"></script>

<!-- Initialize components -->
<script>
    // Define BASE_URL for JavaScript
    const BASE_URL = '<?php echo BASE_URL; ?>';
    
    // Make Swal globally available
    window.Swal = Swal;

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap collapse
        var collapseElements = document.querySelectorAll('[data-bs-toggle="collapse"]');
        collapseElements.forEach(function(element) {
            new bootstrap.Collapse(element, {
                toggle: false
            });
        });

        // Initialize sparklines if elements exist
        if (typeof jQuery !== 'undefined' && jQuery.fn.sparkline) {
            $("#lineChart").sparkline([102, 109, 120, 99, 110, 105, 115], {
                type: "line",
                height: "70",
                width: "100%",
                lineWidth: "2",
                lineColor: "#177dff",
                fillColor: "rgba(23, 125, 255, 0.14)",
            });

            $("#lineChart2").sparkline([99, 125, 122, 105, 110, 124, 115], {
                type: "line",
                height: "70",
                width: "100%",
                lineWidth: "2",
                lineColor: "#f3545d",
                fillColor: "rgba(243, 84, 93, .14)",
            });

            $("#lineChart3").sparkline([105, 103, 123, 100, 95, 105, 115], {
                type: "line",
                height: "70",
                width: "100%",
                lineWidth: "2",
                lineColor: "#ffa534",
                fillColor: "rgba(255, 165, 52, .14)",
            });
        }
    });
</script>