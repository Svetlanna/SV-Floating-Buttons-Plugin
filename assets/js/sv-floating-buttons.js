jQuery(document).ready(function ($) {
    let currentRow; // Track the current row for icon updating

    // Initialize the icon picker for the "Ajouter un Nouveau Bouton" icon input
    $('#sv-button-icon').iconpicker({
        hideOnSelect: true,
        placement: 'bottom'
    });

    // Open icon picker for the new button when "Choisir une Icône" button is clicked
    $('#sv-upload-icon').on('click', function () {
        $('#sv-button-icon').iconpicker('toggle'); // Opens the icon picker for new button
    });

    // Update icon preview when an icon is selected in the new button icon picker
    $('#sv-button-icon').on('iconpickerSelected', function (event) {
        $('#sv-button-icon').val(event.iconpickerValue); // Set icon class in input
        $('#icon-preview').html('<i class="' + event.iconpickerValue + '" style="font-size: 24px;"></i>'); // Show preview
    });

    // Open row-specific icon picker modal when "Choisir une Icône" button is clicked in existing rows
    $(document).on('click', '.choisirIconeButton', function () {
        currentRow = $(this).closest('tr'); // Set the row being edited
        $('#icon-selection-modal').show(); // Show the modal
    });

    // Initialize the icon picker inside the modal for row-specific icons
    $('#icon-picker').iconpicker({
        align: 'center',
        placement: 'bottom',
    });

    // Handle icon selection in the row-specific modal
    $('#icon-picker').on('iconpickerSelected', function (event) {
        const selectedIconClass = event.iconpickerValue; // Get the selected icon class
        currentRow.find('.button-icon i').attr('class', selectedIconClass); // Update icon preview in row
        currentRow.find('.icon-value').val(selectedIconClass); // Store icon class for saving
        $('#icon-selection-modal').hide(); // Hide modal
    });

    // Close the modal when the close button is clicked
    $('#close-icon-modal').on('click', function () {
        $('#icon-selection-modal').hide();
    });

    // Add a new button on "Ajouter le Bouton" click
    $('#sv-add-button').on('click', function () {
        const buttonData = {
            action: 'sv_add_button',
            nonce: svFloatingButtons.nonce,
            text: $('#sv-button-text').val(),
            bg_color: $('#sv-button-bg-color').val(),
            text_color: $('#sv-button-text-color').val(),
            icon: $('#sv-button-icon').val(),
            position: $('#sv-button-position').val(),
        };


        $.post(svFloatingButtons.ajax_url, buttonData, function (response) {
            if (response.success) {
                alert('Bouton ajouté avec succès!'); // Display success message

                // Add the new row to the table
                const newButton = response.data[response.data.length - 1];
                const newIndex = response.data.length - 1;

                const newRow = `
                    <tr>
                        <td>${newIndex}</td>
                        <td><input type="text" class="button-text" value="${newButton.text}"></td>
                        <td><input type="color" class="button-bg-color" value="${newButton.bg_color}"></td>
                        <td><input type="color" class="button-text-color" value="${newButton.text_color}"></td>
                        <td>
                            <span class="button-icon"><i class="${newButton.icon}"></i></span>
                            <button class="choisirIconeButton button button-secondary">Choisir une Icône</button>
                            <input type="hidden" class="icon-value" value="${newButton.icon}">
                        </td>
                        <td>
                            <select class="button-position">
                                <option value="top-left" ${newButton.position === 'top-left' ? 'selected' : ''}>Top Left</option>
                                <option value="top-right" ${newButton.position === 'top-right' ? 'selected' : ''}>Top Right</option>
                                <option value="bottom-left" ${newButton.position === 'bottom-left' ? 'selected' : ''}>Bottom Left</option>
                                <option value="bottom-right" ${newButton.position === 'bottom-right' ? 'selected' : ''}>Bottom Right</option>
                            </select>
                        </td>
                        <td>
                            <button style="background-color: ${newButton.bg_color};padding:5px">
                                <i class="${newButton.icon}" style="color: ${newButton.text_color};"></i>
                                <span>${newButton.text}</span>
                            </button>
                        </td>
                        <td>[sv_floating_button id="${newIndex}"]</td>
                        <td>
                            <button class="sv-edit-button button button-primary" data-index="${newIndex}">Enregistrer</button>
                            <button class="sv-delete-button button button-secondary" data-index="${newIndex}">Supprimer</button>
                        </td>
                    </tr>
                `;
                $('#sv-buttons-table-body').append(newRow);

                // Clear input fields
                $('#sv-add-button-form').trigger("reset");
            } else {
                alert('Erreur lors de l\'ajout du bouton.');
            }
        }).fail(function () {
            alert('Erreur réseau. Veuillez réessayer.');
        });
    });




    // Handle Edit Button Click
    $(document).on('click', '.sv-edit-button', function () {
        currentRow = $(this).closest('tr');
        const index = $(this).data('index');

        const buttonData = {
            action: 'sv_edit_button',
            nonce: svFloatingButtons.nonce,
            index: index,
            text: currentRow.find('.button-text').val(),
            bg_color: currentRow.find('.button-bg-color').val(),
            text_color: currentRow.find('.button-text-color').val(),
            icon: currentRow.find('.icon-value').val(),
            position: currentRow.find('.button-position').val()
        };

        $.post(svFloatingButtons.ajax_url, buttonData, function (response) {
            if (response.success) {
                alert('Bouton mis à jour avec succès!');
                updateRow(currentRow, response.data[index]); // Update row dynamically
            } else {
                alert(response.data.message || 'Erreur lors de la mise à jour du bouton.');
            }
        }).fail(function () {
            alert('Erreur réseau. Veuillez réessayer.');
        });
    });


    function updateRow(row, buttonData) {
        // Update text and colors
        row.find('.button-text').val(buttonData.text);
        row.find('.button-bg-color').val(buttonData.bg_color);
        row.find('.button-text-color').val(buttonData.text_color);

        // Update icon
        row.find('.button-icon i').attr('class', buttonData.icon);
        row.find('.icon-value').val(buttonData.icon);

        // Update preview
        row.find('.button-preview').html(`
            <div style="background-color: ${buttonData.bg_color}; color: ${buttonData.text_color}; padding: 5px; border-radius: 4px;">
                <i class="${buttonData.icon}" style="font-size: 24px;"></i> ${buttonData.text}
            </div>
        `);

        // Update position
        row.find('.button-position').val(buttonData.position);
        location.reload();

    }


// Delete a button on "Supprimer" button click
$(document).on('click', '.sv-delete-button', function () {
    const row = $(this).closest('tr');
    const index = $(this).data('index');

    if (confirm('Êtes-vous sûr de vouloir supprimer ce bouton ?')) {
        $.post(svFloatingButtons.ajax_url, {
            action: 'sv_delete_button',
            nonce: svFloatingButtons.nonce,
            index: index
        }, function (response) {
            if (response.success) {
                alert('Bouton supprimé avec succès!');
                row.remove(); // Remove the row from the table
            } else {
                alert('Erreur lors de la suppression du bouton.');
            }
        }).fail(function () {
            alert('Erreur réseau. Veuillez réessayer.');
        });
    }
});

// Open the icon picker on "Choisir une Icône" button click
$('#sv-upload-icon').on('click', function () {
    $('#sv-button-icon').iconpicker('toggle'); // Open icon picker
});

// Display selected icon in the preview area
$('#sv-button-icon').on('iconpickerSelected', function (event) {
    $('#sv-button-icon').val(event.iconpickerValue); // Set icon class in input
    $('#icon-preview').html('<i class="' + event.iconpickerValue + '" style="font-size: 24px;"></i>'); // Show preview
});
});
