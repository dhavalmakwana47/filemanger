// public/js/datatable.js
function initializeDataTable(tableId, ajaxUrl, columns, extraOptions = {}) {
    $(`#${tableId}`).DataTable({
        processing: true,
        serverSide: true,
        ajax: ajaxUrl,
        columns: columns,
        pageLength: extraOptions.pageLength || 10,
        lengthMenu: extraOptions.lengthMenu || [5, 10, 25, 50],
        responsive: extraOptions.responsive || true,
        language: extraOptions.language || {
            search: "_INPUT_",
            searchPlaceholder: extraOptions.searchPlaceholder || "Search...",
            lengthMenu: "Show _MENU_ entries",
            paginate: {
                previous: "Prev",
                next: "Next",
            },
        },
        order: extraOptions.order || [[0, "desc"]], // <--- Default ordering
        dom:
            extraOptions.dom ||
            '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
        ...extraOptions,
    });
}
