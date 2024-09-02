jQuery(document).ready(function($) {
    // Handle adding new lot sizes
    $("#add-lot-size").click(function() {
        var container = $("#available-lot-sizes-container");
        var index = container.children().length + 1;
        var html = `
            <div class="lot-size-field">
                <h3>Entry ${index}</h3><a class="remove-lot-size">Remove</a>
                <label for="my_idx_options_filters[available_lot_sizes][${index}][size]">Lot Size Label</label>
                <input type="text" name="my_idx_options_filters[available_lot_sizes][${index}][size]" placeholder="Size">
                <label for="my_idx_options_filters[available_lot_sizes][${index}][description]">Lot Size Value</label>
                <input type="text" name="my_idx_options_filters[available_lot_sizes][${index}][description]" placeholder="Description">
                <div class="categories-container">
                    <input type="text" name="my_idx_options_filters[available_lot_sizes][${index}][categories][0]" placeholder="Category">
                </div>
                <button type="button" class="add-category">Add Category</button>
            </div>
        `;
        container.append(html);
    })

    // Handle adding new categories and removing lot sizes
    $("#available-lot-sizes-container").on("click", ".add-category", function() {
        var $lotSizeField = $(this).closest(".lot-size-field");
        var $categoriesContainer = $lotSizeField.find(".categories-container");
        var index = $categoriesContainer.children().length;
        var lotSizeIndex = $lotSizeField.find('input[name^="my_idx_options_filters[available_lot_sizes]"]').attr('name').match(/\d+/)[0];
        var newInputName = `my_idx_options_filters[available_lot_sizes][${lotSizeIndex}][categories][${index}]`;

        var newInput = `
            <div class="category-field">
                <input type="text" name="${newInputName}" placeholder="Category">
                <button type="button" class="remove-category">Remove Category</button>
            </div>
        `;
        $categoriesContainer.append(newInput);
    }).on("click", ".remove-category", function() {
        $(this).closest(".category-field").remove();
    }).on("click", ".remove-lot-size", function() {
        $(this).closest(".lot-size-field").remove();
    });
});