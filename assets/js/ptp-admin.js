/**
 * Admin JavaScript for Plugin Tracking Personalise
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Add event button handler
        $('#ptp-add-event-btn').on('click', function() {
            var shipmentId = $('#post_ID').val();
            var eventDate = $('#ptp_event_date').val();
            var status = $('#ptp_event_status').val();
            var location = $('#ptp_event_location').val();
            var description = $('#ptp_event_description').val();

            if (!shipmentId || !eventDate || !status) {
                alert('Veuillez remplir tous les champs obligatoires.');
                return;
            }

            var button = $(this);
            button.prop('disabled', true).text('Ajout en cours...');

            $.ajax({
                url: ptpAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ptp_add_event',
                    nonce: ptpAdmin.nonce,
                    shipment_id: shipmentId,
                    event_date: eventDate,
                    status: status,
                    location: location,
                    description: description
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || 'Événement ajouté avec succès');
                        // Reload page to show new event
                        location.reload();
                    } else {
                        alert(response.data || 'Erreur lors de l\'ajout de l\'événement');
                    }
                },
                error: function() {
                    alert('Erreur de connexion');
                },
                complete: function() {
                    button.prop('disabled', false).text('Ajouter l\'événement');
                }
            });
        });

        // Delete event button handler
        $(document).on('click', '.ptp-delete-event', function() {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
                return;
            }

            var eventId = $(this).data('event-id');
            var row = $(this).closest('tr');
            var button = $(this);

            button.prop('disabled', true).text('Suppression...');

            $.ajax({
                url: ptpAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ptp_delete_event',
                    nonce: ptpAdmin.nonce,
                    event_id: eventId
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(300, function() {
                            row.remove();
                            // Check if there are no more events
                            if ($('#ptp-events-list tr').length === 0) {
                                $('#ptp-events-list').append(
                                    '<tr class="ptp-no-events"><td colspan="5">Aucun événement</td></tr>'
                                );
                            }
                        });
                    } else {
                        alert(response.data || 'Erreur lors de la suppression');
                        button.prop('disabled', false).text('Supprimer');
                    }
                },
                error: function() {
                    alert('Erreur de connexion');
                    button.prop('disabled', false).text('Supprimer');
                }
            });
        });

        // Set default datetime to now
        if ($('#ptp_event_date').length && !$('#ptp_event_date').val()) {
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            $('#ptp_event_date').val(now.toISOString().slice(0, 16));
        }
    });

})(jQuery);
