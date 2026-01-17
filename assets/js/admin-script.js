document.addEventListener('DOMContentLoaded', function() {
    
    // --- START: MODAL LOGIC (WITH MAP INTEGRATION) ---
    document.body.addEventListener('click', function(event) {
        // For opening the modal
        if (event.target.classList.contains('open-smart-data-modal')) {
            const modalId = event.target.getAttribute('data-modal-id');
            const modal = document.getElementById(modalId);
            
            if (modal) {
                // Change display from 'none' to 'flex' for CSS centering
                modal.style.display = 'flex';

                // --- START: NEW MAP CODE ---
                const logId = modalId.split('-').pop();
                const mapId = 'iplogger-map-' + logId;
                const mapContainer = document.getElementById(mapId);

                if (mapContainer) {
                    const lat = mapContainer.dataset.lat;
                    const lon = mapContainer.dataset.lon;

                    // Check if lat/lon are valid numbers
                    if (lat && lon && lat !== 'N/A' && lon !== 'N/A') {
                        
                        // Check if map is already initialized
                        if (mapContainer._leaflet_map) {
                            // If yes, just invalidate its size to fix rendering
                            mapContainer._leaflet_map.invalidateSize();
                        } else {
                            // If no, create the map
                            try {
                                const map = L.map(mapId).setView([lat, lon], 15);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                }).addTo(map);
                                
                                L.marker([lat, lon]).addTo(map);
                                
                                // Store the map instance on the element
                                mapContainer._leaflet_map = map;

                                // Leaflet sometimes renders grey tiles if the container
                                // was 'display: none'. We force a resize after it's visible.
                                setTimeout(() => {
                                    map.invalidateSize();
                                }, 10);

                            } catch (e) {
                                console.error('Leaflet Error:', e);
                                mapContainer.innerHTML = '<p style="text-align:center; padding: 20px;">Error loading map.</p>';
                            }
                        }
                    } else {
                        // If no lat/lon, show a message
                        mapContainer.innerHTML = '<p style="text-align:center; padding: 20px;">No GPS coordinates available for this entry.</p>';
                    }
                }
                // --- END: NEW MAP CODE ---
            }
        }
        
        // For the 'x' close button
        if (event.target.classList.contains('iplogger-close')) {
            // Find the closest parent modal and hide it
            const modal = event.target.closest('.iplogger-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
    });

    // For closing the modal by clicking on the background
    document.body.addEventListener('click', function(event) {
        if (event.target.classList.contains('iplogger-modal')) {
            event.target.style.display = 'none';
        }
    });
    
    // For closing the modal with the Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            document.querySelectorAll('.iplogger-modal').forEach(function(modal) {
                if (modal.style.display === "flex") {
                    modal.style.display = "none";
                }
            });
        }
    });
    // --- END: MODAL LOGIC ---


    // --- START: INLINE NOTE EDITING LOGIC ---
    const table = document.querySelector('.wp-list-table');
    if (!table) return;

    // When clicking on the note display area to start editing
    table.addEventListener('click', function(e) {
        if (e.target.closest('.note-display')) {
            const container = e.target.closest('.iplogger-note-container');
            if (container) {
                container.querySelector('.note-display').style.display = 'none';
                container.querySelector('.note-edit').style.display = 'block';
                container.querySelector('textarea').focus();
            }
        }
    });

    // When clicking the "Save" button for a note
    table.addEventListener('click', function(e) {
        if (e.target.classList.contains('note-save')) {
            e.preventDefault();
            const container = e.target.closest('.iplogger-note-container');
            const spinner = container.querySelector('.spinner');
            const noteText = container.querySelector('textarea').value;
            const logId = container.dataset.logId;
            const nonceInput = document.getElementById('iplogger_save_note_nonce');
            
            // Check if the nonce field exists
            if (!nonceInput) {
                alert('Security field missing. Please refresh the page.');
                return;
            }
            const nonce = nonceInput.value;

            spinner.style.visibility = 'visible';

            // Prepare data for AJAX request
            const formData = new URLSearchParams();
            formData.append('action', 'iplogger_save_note');
            formData.append('log_id', logId);
            formData.append('note_text', noteText);
            formData.append('nonce', nonce);

            // ✅ FIX: Use the reliable localized object for the AJAX URL.
            fetch(iplogger_ajax_object.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                spinner.style.visibility = 'hidden';
                if (data.success) {
                    const displayDiv = container.querySelector('.note-display');
                    // Use the HTML-formatted note returned from the server
                    displayDiv.innerHTML = data.data.display_note || 'Click to add a note';
                    
                    // Switch back to display view
                    container.querySelector('.note-edit').style.display = 'none';
                    container.querySelector('.note-display').style.display = 'block';
                } else {
                    alert('Error: ' + data.data.message);
                }
            })
            .catch(error => {
                spinner.style.visibility = 'hidden';
                alert('An unexpected error occurred.');
                console.error('Error:', error);
            });
        }
    });

    // When clicking the "Cancel" button for a note
    table.addEventListener('click', function(e) {
        if (e.target.classList.contains('note-cancel')) {
            e.preventDefault();
            const container = e.target.closest('.iplogger-note-container');
            container.querySelector('.note-edit').style.display = 'none';
            container.querySelector('.note-display').style.display = 'block';
        }
    });
    // --- END: INLINE NOTE EDITING LOGIC ---
});