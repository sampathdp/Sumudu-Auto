$(document).ready(function() {
    // Print button
    $('#printReport').on('click', () => window.print());

    // Form submit
    $('#healthCheckForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const btn = form.find('button[type="submit"]');
        const originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        // Get form data
        const formData = form.serialize();
        
        // Determine if this is an update or create
        const isUpdate = form.data('report-exists') === 'true';
        const action = isUpdate ? 'update' : 'create';

        // Log the data being sent for debugging
        console.log('Sending form data:', formData);
        console.log('Action:', action);

        $.ajax({
            url: '../../Ajax/php/health_check_report.php',
            type: 'POST',
            data: formData + '&action=' + action,
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response);
                
                if (response.status === 'success') {
                    Toastify({
                        text: 'Health check report ' + (action === 'create' ? 'created' : 'updated') + ' successfully!',
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        backgroundColor: '#28a745'
                    }).showToast();
                    
                    // Update the form's data attribute if this was a create
                    if (action === 'create') {
                        form.data('report-exists', 'true');
                        // Update button text
                        btn.html('<i class="fas fa-save me-2"></i>Update Report');
                        // Reload after a short delay to show the success message
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    Toastify({
                        text: response.message || 'Failed to save report',
                        duration: 4000,
                        backgroundColor: '#dc3545'
                    }).showToast();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response Text:', xhr.responseText);
                
                Toastify({
                    text: 'An error occurred while saving the report. Please check the console for details.',
                    duration: 5000,
                    backgroundColor: '#dc3545'
                }).showToast();
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});