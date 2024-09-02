jQuery(document).ready(function($) {
    // Handle adding new lot sizes
    $("#add-lot-size").click(function() {
        var container = $("#available-lot-sizes-container");
        var index = container.children().length + 1;
        var html = `
            <div class="lot-size-field">
                <div class="label_wrapper"><h3>Entry ${index}</h3><a class="remove-lot-size">Remove</a></div>
                <div class="input_wrapper">
                <label for="my_idx_options_filters[available_lot_sizes][${index}][size]">Lot Size Label</label>
                <input type="text" name="my_idx_options_filters[available_lot_sizes][${index}][size]" placeholder="Size">
                </div>
                <div class="input_wrapper">
                <label for="my_idx_options_filters[available_lot_sizes][${index}][description]">Lot Size Value</label>
                <input type="text" name="my_idx_options_filters[available_lot_sizes][${index}][description]" placeholder="Description">
                </div>
                <div class="input_wrapper">
                <label for="my_idx_options_filters[available_lot_sizes][${index}][range]">Actual Lot Size Range</label>
                <input type="text" name="my_idx_options_filters[available_lot_sizes][${index}][range]" placeholder="Actual Lot Size Range">
                </div>
                <div class="categories-container input_wrapper">
                    <label for="my_idx_options_filters[categories]">Lot Categories in RETS</label>
                    <div class="categories">
                        <div class="category-field">
                            <input type="text" name="my_idx_options_filters[available_lot_sizes][${index}][categories][0]" placeholder="Category">
                            <a class="remove-category"><i class="fa fa-times-circle"></i></a>
                        </div>
                    </div
                </div>
            </div>
            <button type="button" class="add-category">Add Category</button>
        `;
        container.append(html);
    })

    // Handle adding new categories and removing lot sizes
    $("#available-lot-sizes-container").on("click", ".add-category", function() {
        var $lotSizeField = $(this).closest(".lot-size-field");
        var $categoriesContainer = $lotSizeField.find(".categories-container").find(".categories");
        var index = $categoriesContainer.children().length;
        var lotSizeIndex = $lotSizeField.find('input[name^="my_idx_options_filters[available_lot_sizes]"]').attr('name').match(/\d+/)[0];
        var newInputName = `my_idx_options_filters[available_lot_sizes][${lotSizeIndex}][categories][${index}]`;

        var newInput = `
            <div class="category-field">
                <input type="text" name="${newInputName}" placeholder="Category">
                <a class="remove-category"><i class="fa fa-times-circle"></i></a>
            </div>
        `;
        $categoriesContainer.append(newInput);
    }).on("click", ".remove-category", function() {
        $(this).closest(".category-field").remove();
    }).on("click", ".remove-lot-size", function() {
        $(this).closest(".lot-size-field").remove();
    });

    $("#add-status-option").click(function() {
        var container = $("#available-status-options-container");
        var index = container.children().length + 1;
        var html = `
            <div class="status-option-field">
                <div class="label_wrapper"><h3>Entry ${index}</h3><a class="remove-status-option">Remove</a></div>
                <div class="input_wrapper">
                <label for="my_idx_options_filters[available_status_options][${index}][size]">Status Label</label>
                <input type="text" name="my_idx_options_filters[available_status_options][${index}][size]" placeholder="Size">
                </div>
                <div class="input_wrapper">
                <label for="my_idx_options_filters[available_status_options][${index}][description]">Status Value</label>
                <input type="text" name="my_idx_options_filters[available_status_options][${index}][description]" placeholder="Description">
                </div>
                <div class="categories-container input_wrapper">
                    <label for="my_idx_options_filters[categories]">Status Categories in RETS</label>
                    <div class="categories">
                        <div class="category-field">
                            <input type="text" name="my_idx_options_filters[available_status_options][${index}][categories][0]" placeholder="Category">
                            <a class="remove-category"><i class="fa fa-times-circle"></i></a>
                        </div>
                    </div>
                </div>
                <button type="button" class="add-category">Add Category</button>
            </div>
        `;
        container.append(html);
    })

    // Handle adding new categories and removing lot sizes
    $("#available-status-options-container").on("click", ".add-category", function() {
        var $lotSizeField = $(this).closest(".status-option-field");
        var $categoriesContainer = $lotSizeField.find(".categories-container").find(".categories");
        var index = $categoriesContainer.children().length;
        var lotSizeIndex = $lotSizeField.find('input[name^="my_idx_options_filters[available_status_options]"]').attr('name').match(/\d+/)[0];
        var newInputName = `my_idx_options_filters[available_status_options][${lotSizeIndex}][categories][${index}]`;

        var newInput = `
            <div class="category-field">
                <input type="text" name="${newInputName}" placeholder="Category">
                <a class="remove-category"><i class="fa fa-times-circle"></i></a>
            </div>
        `;
        $categoriesContainer.append(newInput);
    }).on("click", ".remove-category", function() {
        $(this).closest(".category-field").remove();
    }).on("click", ".remove-status-option", function() {
        $(this).closest(".status-option-field").remove();
    });
});