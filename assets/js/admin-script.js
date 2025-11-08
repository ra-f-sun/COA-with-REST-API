jQuery(document).ready(function($) {
    // Add new category row
    $('#coa-add-category').on('click', function() {
        var rowIndex = $('.coa-categories-table tbody tr').length;
        var newRow = '<tr>' +
            '<td><input type="text" name="coa_category_inputs[' + rowIndex + '][value]" placeholder="e.g., raw-materials" required style="width: 100%;"></td>' +
            '<td><input type="text" name="coa_category_inputs[' + rowIndex + '][label]" placeholder="e.g., Raw Materials" required style="width: 100%;"></td>' +
            '<td><button type="button" class="button coa-remove-row">Remove</button></td>' +
            '</tr>';
        $('.coa-categories-table tbody').append(newRow);
    });
    
    // Remove category row
    $(document).on('click', '.coa-remove-row', function() {
        if (confirm('Remove this category?')) {
            $(this).closest('tr').remove();
        }
    });
});
