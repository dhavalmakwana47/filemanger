$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr("content");
    let searchTimeout = null; // For debouncing search input
    let currentSearchValue = ""; // Track current search value
    let lastBrowseContext = { folderId: null, folderKey: null, path: "" };
    let pendingUploadContext = null;
    
    // Internet connection monitor
    let isOnline = true;
    let connectionCheckInterval;
    
    function checkInternetConnection() {
        // Try to fetch from Google's reliable endpoint
        fetch('https://www.google.com/favicon.ico', {
            method: 'HEAD',
            mode: 'no-cors',
            cache: 'no-cache'
        })
        .then(() => {
            if (!isOnline) {
                isOnline = true;
                Swal.fire({
                    icon: 'success',
                    title: 'Back Online',
                    text: 'Internet connection restored',
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        })
        .catch(() => {
            if (isOnline) {
                isOnline = false;
                Swal.fire({
                    icon: 'error',
                    title: 'No Internet Connection',
                    text: 'Please check your internet connection and try again',
                    confirmButtonColor: '#d33',
                    allowOutsideClick: false
                });
            }
        });
    }
    
    // Check connection immediately on load
    checkInternetConnection();
    
    // Check connection every 10 seconds
    connectionCheckInterval = setInterval(checkInternetConnection, 10000);
    
    // Also listen to browser events as backup
    window.addEventListener('online', checkInternetConnection);
    window.addEventListener('offline', function() {
        isOnline = false;
        Swal.fire({
            icon: 'error',
            title: 'No Internet Connection',
            text: 'Please check your internet connection and try again',
            confirmButtonColor: '#d33',
            allowOutsideClick: false
        });
    });
    
    // Add this before the fileManager initialization
    const fileManagerItemTemplate = function (itemData, itemIndex, itemElement) {
        const $item = $("<div>").addClass("dx-filemanager-item");

        // Only add star if bookmarked and is file
        if (itemData.isBookmarked && !itemData.isDirectory) {
            $item.append(
                $('<i>')
                    .addClass('dx-icon dx-icon-favorites')
                    .css({
                        'color': 'gold',
                        'margin-right': '5px',
                        'cursor': 'pointer'
                    })
                    .on("click", function (e) {
                        e.stopPropagation();
                        $.ajax({
                            url: '/bookmarks/toggle',
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                type: 'file',
                                id: itemData.id
                            },
                            success: function (response) {
                                fetchFileManagerData();
                            }
                        });
                    })
            );
        } else {
            // Add empty space to maintain alignment
            $item.append($('<span>').css('margin-right', '24px').html('&nbsp;'));
        }

        // Add the item name
        $item.append($("<span>").text(itemData.name));

        return $item;
    };

    /**
     * Compare helper for hierarchical index (1.2 before 1.11). Used only while sorting arrays — never stored on items,
     * so the UI never shows padded strings like 00000001.00000002.
     */
    function buildIndexSortKey(raw) {
        if (raw == null || raw === "") {
            return "zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz";
        }
        var parts = String(raw).split(".");
        return parts
            .map(function (part) {
                var num = parseInt(part.trim(), 10);
                if (isNaN(num)) {
                    return "00000000";
                }
                return num.toString().padStart(8, "0");
            })
            .join(".");
    }

    function sortFileManagerItemsByIndex(nodes) {
        if (!Array.isArray(nodes) || nodes.length === 0) {
            return;
        }
        nodes.forEach(function (node) {
            if (node.items && node.items.length) {
                sortFileManagerItemsByIndex(node.items);
            }
        });
        nodes.sort(function (a, b) {
            var ka = a.name === ".." ? "" : buildIndexSortKey(a.index);
            var kb = b.name === ".." ? "" : buildIndexSortKey(b.index);
            if (ka < kb) {
                return -1;
            }
            if (ka > kb) {
                return 1;
            }
            return 0;
        });
    }

    const fileManager = $("#file-manager")
        .dxFileManager({
            name: "fileManager",
            fileProvider: [],
            height: 'auto',
            width: '100%',
            selectionMode: "multiple",
            itemView: { mode: "details" },
            permissions: {
                create: false,
                rename: true,
            },
            itemView: {
                itemTemplate: fileManagerItemTemplate,
                details: {
                    columns: [
                        {
                            dataField: "isBookmarked",
                            caption: "★",
                            width: 50,
                            alignment: "center",
                            cellTemplate: function (container, options) {
                                const isBookmarked = options.value;
                                const itemData = options.data;
                                if (isBookmarked && !itemData.isDirectory) {
                                    $('<div>')
                                        .addClass('dx-filemanager-bookmark-cell')
                                        .append(
                                            $('<i>')
                                                .addClass('dx-icon dx-icon-favorites')
                                                .css({
                                                    'color': 'gold',
                                                    'cursor': 'pointer',
                                                    'font-size': '16px'
                                                })
                                                .on('click', function (e) {
                                                    e.stopPropagation();
                                                    $.ajax({
                                                        url: '/bookmarks/toggle',
                                                        type: 'POST',
                                                        data: {
                                                            _token: $('meta[name="csrf-token"]').attr('content'),
                                                            type: 'file',
                                                            id: itemData.id
                                                        },
                                                        success: function (response) {
                                                            fetchFileManagerData();
                                                        }
                                                    });
                                                })
                                        )
                                        .appendTo(container);
                                }
                            }
                        },
                        {
                            dataField: "index",
                            caption: "Index",
                            dataType: "string",
                            width: 70,
                            cssClass: "index-column",
                            allowSorting: false,
                        },
                        {
                            dataField: "thumbnail",
                            caption: "",
                            width: 50,
                        },

                        {
                            dataField: "name",
                            caption: "File/Folder",
                            wordWrapEnabled: true,
                        },
                        {
                            dataField: "dateModified",
                            caption: "Date & Time",
                            dataType: "datetime",
                            format: "dd/MM/yyyy HH:mm:ss",
                            width: 150,
                        },
                        // "size",
                        {
                            dataField: "owner", // your custom field
                            caption: "Role", // column title
                            dataType: "string",
                        },
                    ],
                },
            },
            contextMenu: {
                items: [
                    {
                        name: "rename-folder",
                        icon: "",
                        html: "<i class='dx-icon dx-icon-rename'></i><span class='dx-button-rename'>Assign & Edit</span>",
                        visible: true,
                        onClick: function (e) {
                            renameFolder(e);
                        },
                    },
                    {
                        name: "delete-folder",
                        icon: "",
                        html: "<i class='dx-icon dx-icon-trash'></i><span class='dx-button-text'>Delete</span>",
                        visible: true,
                        onClick: function () {
                            customDeleteHandler();
                        },
                    },
                    {
                        name: "view-file",
                        icon: "",
                        html: "<i class='dx-icon dx-icon-activefolder'></i><span class='dx-button-text'>View</span>",
                        visible: false,
                        onClick: function (e) {
                            selectedItem = fileManager.getSelectedItems()[0];
                            if (
                                selectedItem &&
                                !selectedItem.isDirectory &&
                                selectedItem.dataItem &&
                                selectedItem.dataItem.id
                            ) {
                                $("<form>", {
                                    method: "POST",
                                    action: `file/view`,
                                    target: "_blank", // <--- Open in new tab
                                })
                                    .append(
                                        $("<input>", {
                                            type: "hidden",
                                            name: "_token",
                                            value: $(
                                                'meta[name="csrf-token"]'
                                            ).attr("content"),
                                        })
                                    )
                                    .append(
                                        $("<input>", {
                                            type: "hidden",
                                            name: "id",
                                            value: selectedItem.dataItem.id,
                                        })
                                    )
                                    .appendTo("body")
                                    .submit();
                            } else {
                                console.error(
                                    "View failed: Invalid item or missing ID",
                                    selectedItem
                                );
                            }
                        },
                    },
                    {
                        name: "download-file",
                        icon: "",
                        html: "<i class='dx-icon dx-icon-download'></i><span class='dx-button-text'>Download</span>",
                        visible: false,
                        onClick: function (e) {
                            selectedItem = fileManager.getSelectedItems()[0];
                            if (
                                selectedItem &&
                                !selectedItem.isDirectory &&
                                selectedItem.dataItem &&
                                selectedItem.dataItem.id
                            ) {
                                $("<form>", {
                                    method: "POST",
                                    action: `file/download`,
                                    target: "_blank", // <--- Open in new tab
                                })
                                    .append(
                                        $("<input>", {
                                            type: "hidden",
                                            name: "_token",
                                            value: $(
                                                'meta[name="csrf-token"]'
                                            ).attr("content"),
                                        })
                                    )
                                    .append(
                                        $("<input>", {
                                            type: "hidden",
                                            name: "id",
                                            value: selectedItem.dataItem.id,
                                        })
                                    )
                                    .appendTo("body")
                                    .submit();
                            } else {
                                console.error(
                                    "View failed: Invalid item or missing ID",
                                    selectedItem
                                );
                            }
                        },
                    },
                    {
                        name: "zip-folder",
                        icon: "",
                        html: "<i class='dx-icon dx-icon-download'></i><span class='dx-button-text'>Download</span>",
                        visible: false,
                        onClick: function (e) {
                            selectedItem = fileManager.getSelectedItems()[0];
                            if (
                                selectedItem &&
                                selectedItem.isDirectory &&
                                selectedItem.dataItem &&
                                selectedItem.dataItem.id
                            ) {
                                // Show loading message
                                const loadingToast = DevExpress.ui.notify({
                                    message: "Starting zip creation...",
                                    type: "info",
                                    displayTime: 3000
                                });

                                $.ajax({
                                    url: 'folder-zip',
                                    method: 'POST',
                                    data: {
                                        _token: $('meta[name="csrf-token"]').attr('content'),
                                        id: selectedItem.dataItem.id,
                                        dataItem: JSON.stringify(selectedItem.dataItem)
                                    },
                                    success: function (response) {
                                        if (response.success) {
                                            DevExpress.ui.notify({
                                                message: response.message,
                                                type: "success",
                                                displayTime: 5000
                                            });

                                            // Open downloads page in new tab
                                            if (response.redirect_url) {
                                                window.open(response.redirect_url, '_blank');
                                            }
                                console.log("Initiating zip for folder ID:", selectedItem.dataItem.id);
                                            // Start polling for status
                                            checkZipStatus(response.zip_id);
                                        } else {
                                            DevExpress.ui.notify({
                                                message: response.error || "Failed to start zip creation",
                                                type: "error",
                                                displayTime: 5000
                                            });
                                        }
                                    },
                                    error: function (xhr) {
                                        const errorMsg = xhr.responseJSON?.error || "Failed to start zip creation";
                                        DevExpress.ui.notify({
                                            message: errorMsg,
                                            type: "error",
                                            displayTime: 5000
                                        });
                                    }
                                });
                            } else {
                                console.error(
                                    "Zip failed: Invalid item or missing ID",
                                    selectedItem
                                );
                            }
                        },
                    },
                    {
                        name: "zip-extract",
                        icon: "",
                        html: "<i class='dx-icon dx-icon-file'></i><span class='dx-button-text'>Extract</span>",
                        visible: false,
                        onClick: function (e) {
                            selectedItem = fileManager.getSelectedItems()[0];
                            if (
                                selectedItem &&
                                !selectedItem.isDirectory &&
                                selectedItem.dataItem &&
                                selectedItem.dataItem.id
                            ) {
                                $("<form>", {
                                    method: "POST",
                                    action: `extract-zip`,
                                })
                                    .append(
                                        $("<input>", {
                                            type: "hidden",
                                            name: "_token",
                                            value: $(
                                                'meta[name="csrf-token"]'
                                            ).attr("content"),
                                        })
                                    )
                                    .append(
                                        $("<input>", {
                                            type: "hidden",
                                            name: "id",
                                            value: selectedItem.dataItem.id,
                                        })
                                    )
                                    .appendTo("body")
                                    .submit();
                            } else {
                                console.error(
                                    "View failed: Invalid item or missing ID",
                                    selectedItem
                                );
                            }
                        },
                    },
                    {
                        name: "move-item",
                        icon: "",
                        html: "<i class='fas fa-arrows-alt'></i><span class='dx-button-text'>Move</span>",
                        visible: true,
                        onClick: function (e) {
                            const selectedItems = fileManager.getSelectedItems();
                            if (selectedItems.length === 0) return;

                            let fileIds = selectedItems.filter(i => !i.dataItem.isDirectory).map(i => i.dataItem.id);
                            let folderIds = selectedItems.filter(i => i.dataItem.isDirectory).map(i => i.dataItem.id);

                            $("#move_file_ids").val(JSON.stringify(fileIds));
                            $("#move_folder_ids").val(JSON.stringify(folderIds));
                            $("#moveModal").modal("show");
                        },
                    },
                    {
                        name: "properties",
                        icon: "",
                        html: "<i class='dx-icon dx-icon-info'></i><span class='dx-button-text'>Properties</span>",
                        visible: true, // Controlled in onSelectionChanged
                        onClick: function (e) {
                            const selectedItem =
                                fileManager.getSelectedItems()[0];
                            if (
                                selectedItem &&
                                selectedItem.dataItem &&
                                selectedItem.dataItem.id
                            ) {
                                const isFolder =
                                    selectedItem.dataItem.isDirectory;
                                const url = getPropertiesRoute;
                                const data = {
                                    _token: $('meta[name="csrf-token"]').attr(
                                        "content"
                                    ),
                                };
                                // Send folder_id for folders, file_id for files
                                data[isFolder ? "folder_id" : "file_id"] =
                                    selectedItem.dataItem.id;

                                $.ajax({
                                    url: url,
                                    method: "POST",
                                    data: data,
                                    success: function (response) {
                                        const propertiesHtml = response;
                                        $("#propertiesModalBody").html(
                                            response
                                        );
                                        $("#propertiesModal").modal("show");
                                    },
                                    error: function (xhr) {
                                        console.error("AJAX error:", xhr); // Debug
                                        Swal.fire({
                                            title: "Error",
                                            text: "Unable to fetch properties",
                                            icon: "error",
                                        });
                                    },
                                });
                            } else {
                                Swal.fire({
                                    title: "Error",
                                    text: "No valid item selected",
                                    icon: "error",
                                });
                            }
                        },
                    },
                ],
            },
            toolbar: {
                items: [
                    {
                        name: "upload-file",
                        options: {
                            text: "Upload file",
                            icon: "upload",
                        },
                        visible: createFolderPermission,
                        onClick: function () {
                            uploadFile();
                        },
                    },
                    {
                        name: "refresh-folder",
                        options: {
                            text: "Refresh",
                            icon: "refresh",
                            position: "left",
                        },
                        visible: true,
                        onClick: function () {
                            fetchFileManagerData();
                        },
                    },
                    {
                        name: "create-folder",
                        options: {
                            text: "New Folder",
                            icon: "plus",
                        },
                        visible: createFolderPermission,
                        onClick: function () {
                            createNewFolder();
                        },
                    },
                    {
                        name: "process-selected",
                        options: {
                            text: "Assign Role",
                            icon: "key",
                            disabled: true, // start disabled
                            visible: isMultiSelect,
                            onClick: function () {
                                const selected = fileManager.getSelectedItems();

                                if (selected.length === 0) {
                                    DevExpress.ui.notify(
                                        "No items selected",
                                        "warning",
                                        2000
                                    );
                                    return;
                                }

                                let files = selected
                                    .filter((i) => !i.dataItem.isDirectory)
                                    .map((i) => i.dataItem.id);
                                let folders = selected
                                    .filter((i) => i.dataItem.isDirectory)
                                    .map((i) => i.dataItem.id);

                                $("#file_ids").val(JSON.stringify(files));
                                $("#folder_ids").val(JSON.stringify(folders));

                                // Open modal
                                $("#processModal").modal("show");
                            },
                        },
                        visible: true,
                    },

                    {
                        name: "upload-folder",
                        options: {
                            text: "Upload Folder",
                            icon: "upload",
                        },
                        visible: createFolderPermission,
                        onClick: function () {
                            uploadFolder();
                        },
                    },
                    {
                        name: "search",
                        widget: "dxTextBox",
                        options: {
                            placeholder: "Searching",
                            width: 200,
                            mode: "search",
                            valueChangeEvent: "input",
                            onValueChanged: function (e) {
                                currentSearchValue = e.value
                                    ? e.value.trim()
                                    : "";
                                // Debounce search to prevent excessive requests
                                clearTimeout(searchTimeout);
                                searchTimeout = setTimeout(() => {
                                    searchFileManagerData(currentSearchValue);
                                }, 1000);
                            },
                        },
                        visible: true,
                    }
                ],
                fileSelectionItems: [
                    {
                        name: "download-file",
                        options: {
                            text: "Download",
                            icon: "activefolder",
                        },
                        visible: false,
                        onClick: function () {
                            selectedItem = fileManager.getSelectedItems()[0];
                            if (
                                selectedItem &&
                                !selectedItem.isDirectory &&
                                selectedItem.dataItem &&
                                selectedItem.dataItem.id
                            ) {
                                $("<form>", {
                                    method: "POST",
                                    action: `file/download`,
                                    target: "_blank", // <--- Open in new tab
                                })
                                    .append(
                                        $("<input>", {
                                            type: "hidden",
                                            name: "_token",
                                            value: $(
                                                'meta[name="csrf-token"]'
                                            ).attr("content"),
                                        })
                                    )
                                    .append(
                                        $("<input>", {
                                            type: "hidden",
                                            name: "id",
                                            value: selectedItem.dataItem.id,
                                        })
                                    )
                                    .appendTo("body")
                                    .submit();
                            } else {
                                console.error(
                                    "View failed: Invalid item or missing ID",
                                    selectedItem
                                );
                            }
                        },
                    },
                    {
                        name: "download-selected",
                        options: {
                            text: "Download Selected",
                            icon: "download",
                        },
                        visible: true,
                        onClick: function () {
                            const selected = fileManager.getSelectedItems();
                            if (!selected || selected.length === 0) {
                                DevExpress.ui.notify("No items selected", "warning", 2000);
                                return;
                            }

                            const isDir = (i) => (i && ((i.dataItem && i.dataItem.isDirectory) || i.isDirectory === true));
                            const getId = (i) => (i && i.dataItem && i.dataItem.id) || i.id;
                            const folders = selected.filter((i) => isDir(i));
                            const files = selected.filter((i) => !isDir(i));

                            if (folders.length > 0) {
                                // Allow downloading exactly one folder as ZIP using existing POST route
                                folders.forEach((folder) => {
                                    const fid = getId(folder);
                                    const dataItem = folder.dataItem || {};
                                    $("<form>", {
                                        method: "POST",
                                        action: `folder-zip`,
                                        target: "_blank",
                                    })
                                        .append(
                                            $("<input>", {
                                                type: "hidden",
                                                name: "_token",
                                                value: $('meta[name="csrf-token"]').attr("content"),
                                            })
                                        )
                                        .append(
                                            $("<input>", {
                                                type: "hidden",
                                                name: "id",
                                                value: fid,
                                            })
                                        )
                                        .append(
                                            $("<input>", {
                                                type: "hidden",
                                                name: "dataItem",
                                                value: JSON.stringify(dataItem),
                                            })
                                        )
                                        .appendTo("body")
                                        .submit();
                                });
                            }

                            // Only files selected: trigger downloads for each file using existing endpoint
                            files.forEach((f) => {
                                const fid = getId(f);
                                if (fid) {
                                    $("<form>", {
                                        method: "POST",
                                        action: `file/download`,
                                        target: "_blank",
                                    })
                                        .append(
                                            $("<input>", {
                                                type: "hidden",
                                                name: "_token",
                                                value: $('meta[name="csrf-token"]').attr("content"),
                                            })
                                        )
                                        .append(
                                            $("<input>", {
                                                type: "hidden",
                                                name: "id",
                                                value: fid,
                                            })
                                        )
                                        .appendTo("body")
                                        .submit();
                                }
                            });
                        },
                    },
                    {
                        name: "process-selected",
                        options: {
                            text: "Assign Role",
                            icon: "key",
                        },
                        visible: isMultiSelect,
                        onClick: function () {
                            $(".select2").select2({
                                dropdownParent: $("#processModal"),
                                placeholder: "  Select roles",
                                allowClear: true,
                                width: "100%",
                            });
                            const selected = fileManager.getSelectedItems();

                            if (selected.length === 0) {
                                DevExpress.ui.notify(
                                    "No items selected",
                                    "warning",
                                    2000
                                );
                                return;
                            }

                            let files = selected
                                .filter((i) => !i.dataItem.isDirectory)
                                .map((i) => i.dataItem.id);
                            let folders = selected
                                .filter((i) => i.dataItem.isDirectory)
                                .map((i) => i.dataItem.id);

                            $("#file_ids").val(JSON.stringify(files));
                            $("#folder_ids").val(JSON.stringify(folders));

                            // Open modal
                            $("#processModal").modal("show");
                        },
                    },
                    {
                        name: "view-file",
                        options: {
                            text: "View",
                            icon: "activefolder",
                        },
                        visible: false,
                        onClick: function () {
                            const selectedItems =
                                fileManager.getSelectedItems();
                            if (
                                selectedItems.length === 1 &&
                                !selectedItems[0].dataItem.isDirectory &&
                                selectedItems[0].dataItem.id
                            ) {
                                window.location.href = `file/view/${selectedItems[0].dataItem.id}`;
                            } else {
                                console.error(
                                    "View failed: No single file selected or missing ID",
                                    selectedItems
                                );
                            }
                        },
                    },

                    {
                        name: "delete-folder",
                        options: {
                            text: "Delete",
                            icon: "trash",
                        },
                        visible: true,
                        onClick: function () {
                            customDeleteHandler();
                        },
                    },
                    {
                        name: "zip-folder",
                        options: {
                            text: "Download",
                            icon: "activefolder",
                        },
                        visible: false,
                        onClick: function () {
                            const selectedItems =
                                fileManager.getSelectedItems();
                            if (
                                selectedItems.length === 1 &&
                                selectedItems[0].dataItem.isDirectory &&
                                selectedItems[0].dataItem.id
                            ) {
                                window.location.href = `folder-zip/${selectedItems[0].dataItem.id}`;
                            } else {
                                console.error(
                                    "View failed: No single file selected or missing ID",
                                    selectedItems
                                );
                            }
                        },
                    },
                    {
                        name: "zip-extract",
                        options: {
                            text: "Download",
                            icon: "activefolder",
                        },
                        visible: false,
                        onClick: function () {
                            const selectedItems =
                                fileManager.getSelectedItems();
                            if (
                                selectedItems.length === 1 &&
                                !selectedItems[0].dataItem.isDirectory &&
                                selectedItems[0].dataItem.id
                            ) {
                                window.location.href = `folder-zip/${selectedItems[0].dataItem.id}`;
                            } else {
                                console.error(
                                    "View failed: No single file selected or missing ID",
                                    selectedItems
                                );
                            }
                        },
                    },
                    "clearSelection",

                    {
                        name: "move-selected",
                        options: {
                            text: "Move",
                            icon: "fas fa-arrows-alt",
                        },
                        visible: true,
                        onClick: function () {
                            const selectedItems = fileManager.getSelectedItems();
                            if (selectedItems.length === 0) {
                                DevExpress.ui.notify("No items selected", "warning", 2000);
                                return;
                            }

                            let fileIds = selectedItems.filter(i => !i.dataItem.isDirectory).map(i => i.dataItem.id);
                            let folderIds = selectedItems.filter(i => i.dataItem.isDirectory).map(i => i.dataItem.id);

                            $("#move_file_ids").val(JSON.stringify(fileIds));
                            $("#move_folder_ids").val(JSON.stringify(folderIds));
                            $("#moveModal").modal("show");
                        }
                    },
                    {
                        name: "bookmarks",
                        options: {
                            text: "Bookmarks",
                            icon: "favorites",
                        },
                        visible: true,
                        onClick: function () {
                            const selectedItems = fileManager.getSelectedItems();
                            if (selectedItems.length === 0) {
                                return;
                            }

                            // Only process files
                            const files = selectedItems.filter(item => !item.dataItem.isDirectory);
                            if (files.length === 0) {
                                DevExpress.ui.notify('Bookmarks only available for files', 'warning', 2000);
                                return;
                            }

                            files.forEach(item => {
                                const ItemName = item.dataItem.name;
                                $.ajax({
                                    url: '/bookmarks/toggle',
                                    type: 'POST',
                                    data: {
                                        _token: $('meta[name="csrf-token"]').attr('content'),
                                        type: 'file',
                                        id: item.dataItem.id
                                    },
                                    success: function (response) {
                                        const action = response.action;
                                        const message = `${ItemName} ${action} ${action === 'added' ? 'to' : 'from'} bookmarks`;

                                        DevExpress.ui.notify({
                                            message: message,
                                            type: 'success',
                                            displayTime: 2000
                                        });
                                    },
                                    error: function (xhr) {
                                        console.error('Error toggling bookmark:', xhr);
                                        DevExpress.ui.notify('Failed to update bookmark', 'error', 2000);
                                    }
                                });
                            });
                            fetchFileManagerData();
                        }
                    }
                ],
            },
            onSelectionChanged: function (e) {
                let selectedItems = e.selectedItems;

                // enable if at least 1 item is selected
                fileManager.option(
                    "toolbar.items[3].options.disabled", // index must match position of your button
                    selectedItems.length === 0
                );

                let canDelete =
                    selectedItems.length > 0 &&
                    selectedItems.every(
                        (item) =>
                            item.dataItem.permissions &&
                            item.dataItem.permissions.delete &&
                            deleteFolderPermission
                    );

                let canUpdate =
                    selectedItems.length > 0 &&
                    selectedItems.every(
                        (item) =>
                            item.dataItem.permissions &&
                            item.dataItem.permissions.update &&
                            updateFolderPermission
                    );
                let canView =
                    selectedItems.length === 1 &&
                    !selectedItems[0].dataItem.isDirectory &&
                    selectedItems[0].dataItem.permissions &&
                    selectedItems[0].dataItem.permissions.download;

                let fileView =
                    selectedItems.length > 0 &&
                    !selectedItems[0].dataItem.isDirectory &&
                    selectedItems[0].dataItem.permissions &&
                    selectedItems[0].dataItem.permissions.file_view;

                let canZip =
                    selectedItems.length > 0 &&
                    selectedItems[0].dataItem.isDirectory &&
                    selectedItems.every(
                        (item) =>
                            item.dataItem.permissions &&
                            item.dataItem.permissions.download
                    );

                let canExtract =
                    selectedItems.length > 0 &&
                    selectedItems.every(
                        (item) =>
                            item.dataItem.permissions &&
                            item.dataItem.permissions.update &&
                            updateFolderPermission &&
                            (item.name.toLowerCase().endsWith(".zip") || item.name.toLowerCase().endsWith(".rar")) // Check if it's a zip file
                    );

                fileManager.option(
                    "contextMenu.items[0].visible",
                    selectedItems.length > 1 ? true : canUpdate
                );
                fileManager.option("contextMenu.items[1].visible", canDelete);
                fileManager.option("contextMenu.items[2].visible", fileView);
                fileManager.option("contextMenu.items[3].visible", canView);
                fileManager.option("contextMenu.items[4].visible", canZip);
                fileManager.option("contextMenu.items[5].visible", canExtract);

                let canMove = selectedItems.length > 0 &&
                    selectedItems.every(item =>
                        item.dataItem.permissions &&
                        item.dataItem.permissions.update &&
                        updateFolderPermission
                    );
                fileManager.option("contextMenu.items[6].visible", canMove);

                fileManager.option(
                    "toolbar.fileSelectionItems[0].visible",
                    canView
                );

                // NEW: Check if ALL selected items (files & folders) have download permission
                let canDownloadAll = selectedItems.length > 0 &&
                    selectedItems.every(item =>
                        item.dataItem?.permissions?.download === true
                    );

                // CRITICAL: Control "Download Selected" button visibility
                const downloadSelectedIndex = fileManager.option("toolbar.fileSelectionItems").findIndex(
                    item => item.name === "download-selected"
                );
                if (downloadSelectedIndex !== -1) {
                    fileManager.option(
                        `toolbar.fileSelectionItems[${downloadSelectedIndex}].visible`,
                        canDownloadAll && selectedItems.length > 0
                    );
                }

                let canDeleteSelected = selectedItems.length > 0 &&
                    selectedItems.every(item =>
                        item.dataItem?.permissions?.delete === true
                    );

                const deleteSelectedIndex = fileManager.option("toolbar.fileSelectionItems").findIndex(
                    item => item.name === "delete-folder"
                );
                if (deleteSelectedIndex !== -1) {
                    fileManager.option(
                        `toolbar.fileSelectionItems[${deleteSelectedIndex}].visible`,
                        canDeleteSelected && selectedItems.length > 0
                    );
                }

                let canMoveSelected = selectedItems.length > 0 &&
                    selectedItems.every(item =>
                        item.dataItem?.permissions?.update === true
                    );

                const moveSelectedIndex = fileManager.option("toolbar.fileSelectionItems").findIndex(
                    item => item.name === "move-selected"
                );
                if (moveSelectedIndex !== -1) {
                    fileManager.option(
                        `toolbar.fileSelectionItems[${moveSelectedIndex}].visible`,
                        canMoveSelected && selectedItems.length > 0
                    );
                }
            },
            onItemContextMenu: function (e) {
                let selectedItems = e.selectedItems;
            },
            onCurrentDirectoryChanged: function (e) {
                createPermission = createFolderPermission;
                const dir = e.directory;
                if (dir?.dataItem !== undefined) {
                    createPermission = dir.dataItem.permissions.create;
                }
                if (dir && !dir.isRoot) {
                    const dataItem = dir.dataItem;
                    lastBrowseContext = {
                        folderId: dataItem?.isDirectory !== false ? dataItem?.id ?? null : lastBrowseContext.folderId,
                        folderKey: dir.key ?? (dataItem?.id != null ? `folder_${dataItem.id}` : lastBrowseContext.folderKey),
                        path: fileManager.option("currentPath") || dir.path || "",
                    };
                } else {
                    lastBrowseContext = { folderId: null, folderKey: null, path: "" };
                }
                fileManager.option(
                    "toolbar.items[2].visible",
                    createPermission
                );
            },
        })
        .dxFileManager("instance");

    // Manual double-click binding
    $("#file-manager").on(
        "dblclick",
        ".dx-filemanager-details-item-name",
        function () {
            const selectedItem = fileManager.getSelectedItems()[0];
            if (
                selectedItem &&
                !selectedItem.isDirectory &&
                selectedItem.dataItem &&
                selectedItem.dataItem.id &&
                selectedItem.dataItem.permissions &&
                selectedItem.dataItem.permissions.file_view
            ) {
                $("<form>", {
                    method: "POST",
                    action: `file/view`,
                    target: "_blank",
                })
                    .append(
                        $("<input>", {
                            type: "hidden",
                            name: "_token",
                            value: $('meta[name="csrf-token"]').attr("content"),
                        })
                    )
                    .append(
                        $("<input>", {
                            type: "hidden",
                            name: "id",
                            value: selectedItem.dataItem.id,
                        })
                    )
                    .appendTo("body")
                    .submit();
            }
        }
    );

    $("#processForm").on("submit", function (e) {
        e.preventDefault();
        const actionContext = captureBrowseContext();

        let formData = {
            file_ids: JSON.parse($("#file_ids").val()),
            folder_ids: JSON.parse($("#folder_ids").val()),
            roles: $("#rolesv2").val(), // selected role IDs
            send_email: $("#assign_send_email").is(':checked') ? 1 : 0,
        };

        $.ajax({
            url: multiItemAssignRolesRoute,
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify(formData),
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (resp) {
                $("#processModal").modal("hide");
                DevExpress.ui.notify(
                    resp.message || "Roles assigned successfully!",
                    "success",
                    2000
                );
                fetchFileManagerData(true, actionContext); // refresh file manager if needed
            },
            error: function (err) {
                console.error("Error:", err);
                DevExpress.ui.notify("Error assigning roles", "error", 2000);
            },
        });
    });

    function renameFolder(e) {
        let selectedItem = fileManager.getSelectedItems()[0]; // Get selected item
        let updateUrl =
            (selectedItem.dataItem.isDirectory
                ? createFolderRoute
                : createFileRoute) +
            "/" +
            selectedItem.dataItem.id +
            "/edit";

        // Show loading alert
        Swal.fire({
            title: "Loading...",
            text: "Fetching folder details...",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: updateUrl,
            type: "GET",
            success: function (response) {
                Swal.close(); // Close loader

                $("#folderForm").html(response);
                $("#folderModal").modal("show");
            },
            error: function (xhr) {
                Swal.close();

                Swal.fire({
                    icon: "error",
                    title: "Failed to Load",
                    text:
                        (xhr.responseJSON && xhr.responseJSON.message) ||
                        "An unexpected error occurred.",
                });
            },
        });
    }

    $("#folderForm").on("submit", function (e) {
        e.preventDefault();
        const actionContext = captureBrowseContext();

        let selectedItem = fileManager.getSelectedItems()[0]; // Get selected item
        let editUrl =
            (selectedItem.dataItem.isDirectory
                ? createFolderRoute
                : createFileRoute) +
            "/" +
            selectedItem.dataItem.id;

        // Show loading alert
        Swal.fire({
            title: "Updating...",
            text: "Please wait while the folder is being renamed.",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: editUrl,
            type: "PUT",
            data: $(this).serialize(),
            headers: {
                "X-CSRF-TOKEN": csrfToken,
            },
            success: function (response) {
                Swal.close();

                if (response.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Item Updated!",
                        text: "The file or folder was updated successfully.",
                        timer: 2000,
                        showConfirmButton: false,
                    });

                    $("#folderModal").modal("hide");
                    fetchFileManagerData(true, actionContext);
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Failed",
                        text: response.message || "Unknown error occurred.",
                    });
                }
            },
            error: function (xhr) {
                Swal.close();

                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text:
                        (xhr.responseJSON && xhr.responseJSON.message) ||
                        "An unexpected error occurred.",
                });
            },
        });
    });

    //delete handle
    function customDeleteHandler() {
        let selectedItems = fileManager.getSelectedItems();
        const actionContext = captureBrowseContext();

        if (selectedItems.length === 0) {
            Swal.fire({
                icon: "warning",
                title: "No items selected",
                text: "Please select at least one item to delete.",
            });
            return;
        }

        // Separate file IDs and folder IDs
        let folderIds = selectedItems
            .filter((item) => item.dataItem.isDirectory) // assuming fileManager provides isDirectory flag
            .map((item) => item.dataItem.id);

        let fileIds = selectedItems
            .filter((item) => !item.dataItem.isDirectory)
            .map((item) => item.dataItem.id);

        // Show confirmation dialog
        Swal.fire({
            title: "Are you sure?",
            text: "This action will permanently delete the selected items.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete them!",
            cancelButtonText: "Cancel",
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading alert
                Swal.fire({
                    title: "Deleting...",
                    text: "Please wait while the items are being deleted.",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                $.ajax({
                    url: deleteFolderRoute,
                    type: "POST", // Or "DELETE" if your backend expects it
                    data: JSON.stringify({
                        folder_ids: folderIds,
                        file_ids: fileIds,
                    }),
                    headers: {
                        "X-CSRF-TOKEN": $("meta[name='csrf-token']").attr(
                            "content"
                        ),
                        "Content-Type": "application/json",
                    },
                    success: function (response) {
                        Swal.close();

                        if (response.success) {
                            Swal.fire({
                                icon: "success",
                                title: "Deleted!",
                                text:
                                    response.message ||
                                    "Selected items have been deleted.",
                                timer: 2000,
                                showConfirmButton: false,
                            });

                            fetchFileManagerData(true, actionContext);
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Failed",
                                text:
                                    response.message ||
                                    "Failed to delete the items.",
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        Swal.close();

                        console.error("Error:", xhr.responseText);

                        Swal.fire({
                            icon: "error",
                            title: "Unexpected Error",
                            text: "An unexpected error occurred. Please try again.",
                        });
                    },
                });
            }
        });
    }

    $("#fileModal").on("show.bs.modal", function () {
        pendingUploadContext = captureBrowseContext();
    });

    $("#folderUploadModalModal").on("show.bs.modal", function () {
        pendingUploadContext = captureBrowseContext();
    });

    async function searchFileManagerData(query) {
        try {
            // Construct URL with query parameter for getFileMangerRoute
            const url = query
                ? `${getFileMangerRoute}?query=${encodeURIComponent(query)}`
                : getFileMangerRoute;
            const response = await fetch(url, {
                method: "GET",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Content-Type": "application/json",
                }
            });
            const data = await response.json();
            sortFileManagerItemsByIndex(data);
            fileManager.option("fileSystemProvider", data);

            // Keep the search term in the input but don't clear it
            fileManager.option("toolbar.items[5].options.value", query);
        } catch (error) {
            console.error("Error searching data:", error);
            DevExpress.ui.notify(
                "Failed to search files and folders",
                "error",
                2000
            );
        }
    }

    function getCurrentPathSafe() {
        const fromOption = fileManager.option("currentPath");
        if (fromOption) {
            return fromOption;
        }
        const currentDir = fileManager.getCurrentDirectory();
        return currentDir?.path || lastBrowseContext.path || "";
    }

    function captureBrowseContext() {
        const dir = fileManager.getCurrentDirectory();
        const dataItem = dir?.dataItem;
        const folderId =
            dataItem?.isDirectory !== false && dataItem?.id != null
                ? dataItem.id
                : lastBrowseContext.folderId;

        return {
            folderId: folderId ?? null,
            folderKey:
                dir?.key ??
                (folderId != null ? `folder_${folderId}` : null) ??
                lastBrowseContext.folderKey,
            path: fileManager.option("currentPath") || dir?.path || lastBrowseContext.path || "",
        };
    }

    function findPathByFolderKey(items, targetKey, parentPath = "") {
        if (!Array.isArray(items) || !targetKey) {
            return null;
        }

        for (const item of items) {
            if (!item?.isDirectory || !item.name) {
                continue;
            }

            const itemPath = parentPath ? `${parentPath}/${item.name}` : item.name;
            const itemKey = item.key || (item.id != null ? `folder_${item.id}` : null);

            if (itemKey === targetKey) {
                return itemPath;
            }

            if (Array.isArray(item.items) && item.items.length > 0) {
                const nestedPath = findPathByFolderKey(item.items, targetKey, itemPath);
                if (nestedPath) {
                    return nestedPath;
                }
            }
        }

        return null;
    }

    function findPathByDirectoryId(items, targetId, parentPath = "") {
        if (!Array.isArray(items) || targetId == null) {
            return null;
        }

        for (const item of items) {
            if (!item?.isDirectory || !item.name) {
                continue;
            }

            const itemPath = parentPath ? `${parentPath}/${item.name}` : item.name;
            if (String(item.id) === String(targetId)) {
                return itemPath;
            }

            if (Array.isArray(item.items) && item.items.length > 0) {
                const nestedPath = findPathByDirectoryId(item.items, targetId, itemPath);
                if (nestedPath) {
                    return nestedPath;
                }
            }
        }

        return null;
    }

    function restoreFolderLocation(data, context) {
        if (!context) {
            return;
        }

        const { folderId, folderKey, path } = context;
        let resolvedPath = null;

        if (folderKey) {
            resolvedPath = findPathByFolderKey(data, folderKey);
        }
        if (!resolvedPath && folderId != null) {
            resolvedPath = findPathByDirectoryId(data, folderId);
        }
        if (!resolvedPath && path) {
            resolvedPath = path;
        }

        if (!folderId && !folderKey && !path) {
            fileManager.option("currentPath", "");
            return;
        }

        if (!resolvedPath) {
            return;
        }

        const applyPath = () => fileManager.option("currentPath", resolvedPath);
        applyPath();
        [50, 200, 500, 1000].forEach((delay) => setTimeout(applyPath, delay));
    }

    // ✅ Fetch File Manager Data
    async function fetchFileManagerData(keepCurrentPath = true, browseContext = null) {
        $('#fm-loader').show();
        const context = keepCurrentPath ? (browseContext || captureBrowseContext()) : null;
        try {
            const response = await fetch(getFileMangerRoute);
            const data = await response.json();
            sortFileManagerItemsByIndex(data);
            fileManager.option("fileSystemProvider", data);

            if (context) {
                restoreFolderLocation(data, context);
            }
            fileManager.option("toolbar.items[4].options.value", "");
        } catch (error) {
            console.error("Error fetching data:", error);
            DevExpress.ui.notify(
                "Failed to fetch file manager data",
                "error",
                2000
            );
        } finally {
            $('#fm-loader').hide();
        }
    }

    // ✅ Custom Function to Create a New Folder
    function createNewFolder() {
        $("#createFolderForm")[0].reset();
        $("#createFolderModal").modal("show");
    }

    // ✅ Custom Function to Upload a file
    function uploadFile() {
        scrollLocked = false;
        pendingUploadContext = captureBrowseContext();
        $("#fileForm")[0].reset();
        $("#fileModal").modal("show");
    }

    function uploadFolder() {
        scrollLocked = false;
        pendingUploadContext = captureBrowseContext();
        $("#folderForm")[0].reset();
        $("#folderUploadModalModal").modal("show");
    }

    FilePond.registerPlugin(FilePondPluginFileValidateType);
    let invalidFiles = [];
    let skippedFiles = [];
    let validationTimeout = null;
    let validationLoader = null;
    let allSkippedFiles = [];
    let allInvalidFiles = [];
    let scrollLocked = false;
    let targetScrollPosition = 0;

    /**
     * Skip OS / app lock & temp files (e.g. Office ~$Document.docx when file is open).
     */
    function shouldSkipFolderUploadFile(fileName, filePath) {
        const baseName = (fileName || "").split(/[/\\]/).pop() || fileName || "";
        const lowerBase = baseName.toLowerCase();
        const lowerPath = (filePath || fileName || "").toLowerCase();

        // Microsoft Office lock files while document is open: ~$Report.docx
        if (baseName.startsWith("~$") || lowerBase.startsWith("~$")) {
            return true;
        }

        // Other temp/lock files starting with ~
        if (baseName.startsWith("~") || lowerBase.startsWith("~")) {
            return true;
        }

        // LibreOffice lock files: .~lock.document.docx#
        if (lowerPath.includes(".~lock.") || lowerBase.startsWith(".~lock.")) {
            return true;
        }

        // macOS metadata
        if (lowerBase === ".ds_store" || lowerBase.startsWith("._")) {
            return true;
        }

        const skipPatterns = [
            ".gitignore",
            ".git/",
            "node_modules/",
            "thumbs.db",
            "desktop.ini",
            ".vscode/",
            ".idea/",
        ];

        if (
            skipPatterns.some((pattern) => {
                if (pattern.endsWith("/")) {
                    return lowerPath.includes(pattern);
                }
                return lowerBase === pattern || lowerPath.endsWith("/" + pattern);
            })
        ) {
            return true;
        }

        const skipExtensions = [".tmp", ".temp", ".log", ".cache", ".swp", ".swo"];
        return skipExtensions.some((ext) => lowerBase.endsWith(ext));
    }

    const folderPond = FilePond.create(
        document.querySelector("#folder-upload"),
        {
            allowMultiple: true,
            allowDirectoryDrop: true,
            allowDrop: true,
            labelIdle: 'Select Folder <span class="filepond--label-action">Browse</span>',
            allowFileTypeValidation: false,
            server: {
                process: null,
            },
            onaddfilestart: (file) => {
                console.log('File processing started:', file.file.name);
                // Show loader immediately when files start being added
                if (!validationLoader) {
                    validationLoader = Swal.fire({
                        title: "Processing Files...",
                        text: "Please wait while we process your folder.",
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                }
            },
            onprocessfiles: () => {
                console.log('All files processed');
                if (validationLoader) {
                    Swal.close();
                    validationLoader = null;
                }
                if (invalidFiles.length > 0) {
                    const fileList = invalidFiles.map(path => `• ${path}`).join('<br>');
                    Swal.fire({
                        icon: "warning",
                        title: "Invalid File Types",
                        html: `<div style="text-align: left;"><p>The following <strong>${invalidFiles.length}</strong> file(s) have invalid types and will be skipped:</p><div style="max-height: 400px; overflow-y: auto; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; font-family: monospace; font-size: 12px;">${fileList}</div></div>`,
                        confirmButtonText: "OK",
                        confirmButtonColor: "#3085d6",
                        width: '700px'
                    });
                    invalidFiles = [];
                }
            },
            onaddfile: (error, file) => {
                console.log('File added:', file.file.name, error ? 'with error' : 'successfully');
                // Close loader after all files are processed
                setTimeout(() => {
                    if (validationLoader) {
                        console.log('Closing validation loader after file processing');
                        Swal.close();
                        validationLoader = null;
                    }
                }, 500);
                // Scroll to Assign Permissions by Role section in modal - only once
                if (!scrollLocked) {
                    scrollLocked = true;
                    setTimeout(() => {
                        const modalBody = document.querySelector('#folderUploadModalModal .modal-body');
                        const rolesSection = document.querySelector('#folder-upload-permissions-section');
                        if (modalBody && rolesSection) {
                            targetScrollPosition = rolesSection.offsetTop - modalBody.offsetTop;
                            modalBody.scrollTop = targetScrollPosition;
                            
                            // Lock scroll position for a moment
                            const maintainScroll = () => {
                                if (modalBody.scrollTop < targetScrollPosition - 50) {
                                    modalBody.scrollTop = targetScrollPosition;
                                }
                            };
                            
                            // Maintain scroll for 2 seconds
                            const scrollInterval = setInterval(maintainScroll, 100);
                            setTimeout(() => {
                                clearInterval(scrollInterval);
                            }, 2000);
                        }
                    }, 800);
                }
            },
            beforeAddFile: (item) => {
                const fileName = item.file.name;
                const filePath = item.file.webkitRelativePath || item.file.name;
                console.log('Processing file:', fileName, 'Path:', filePath);

                if (shouldSkipFolderUploadFile(fileName, filePath)) {
                    console.log('Skipping system/temp file:', fileName);
                    skippedFiles.push(filePath);
                    allSkippedFiles.push(filePath);
                    // Close loader if all files are being skipped
                    setTimeout(() => {
                        if (validationLoader && folderPond.getFiles().length === 0) {
                            console.log('All files skipped, closing loader');
                            Swal.close();
                            validationLoader = null;
                            
                            // Show skipped files alert
                            if (skippedFiles.length > 0) {
                                const fileList = skippedFiles.map(path => `• ${path}`).join('<br>');
                                Swal.fire({
                                    icon: "info",
                                    title: "Files Skipped",
                                    html: `<div style="text-align: left;"><p>The following <strong>${skippedFiles.length}</strong> file(s) were skipped (system files):</p><div style="max-height: 400px; overflow-y: auto; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; font-family: monospace; font-size: 12px;">${fileList}</div></div>`,
                                    confirmButtonText: "OK",
                                    confirmButtonColor: "#3085d6",
                                    width: '700px'
                                });
                                skippedFiles = [];
                            }
                        }
                    }, 1000);
                    return false; // Skip silently
                }

                const allowedTypes = [
                    "image/png", "image/jpeg", "image/gif", "application/pdf", "application/msword",
                    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                    "application/zip", "application/x-zip-compressed", "text/csv",
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    "text/plain", "application/x-rar-compressed", "application/vnd.rar",
                    "image/tiff", "image/tif", "application/rtf", "application/vnd.ms-excel",
                    "application/vnd.ms-powerpoint", "application/vnd.openxmlformats-officedocument.presentationml.presentation",
                    "video/mp4", "video/quicktime", "video/x-ms-wmv", "video/x-matroska", "video/mpeg",
                    "audio/mpeg", "audio/wav", "audio/aac", "audio/mp4", "audio/x-m4a",
                    "application/acad", "application/x-acad", "application/autocad_dwg", "application/dwg",
                    "application/x-dwg", "application/x-autocad", "drawing/dwg", "image/vnd.dwg",
                    "image/x-dwg", "application/x-7z-compressed"
                ];
                
                // Allow special config files
                const allowedSpecialFiles = [];
                
                const isSpecialFile = allowedSpecialFiles.some(special => 
                    fileName === special || fileName.endsWith(special)
                );
                
                if (!allowedTypes.includes(item.file.type) && !isSpecialFile) {
                    console.log('Invalid file type:', fileName, 'Type:', item.file.type);
                    invalidFiles.push(filePath);
                    allInvalidFiles.push(filePath);
                    
                    clearTimeout(validationTimeout);
                    validationTimeout = setTimeout(() => {
                        if (invalidFiles.length > 0) {
                            // Close validation loader
                            if (validationLoader) {
                                Swal.close();
                                validationLoader = null;
                            }
                            
                            const fileList = invalidFiles.map(path => `• ${path}`).join('<br>');
                            
                            Swal.fire({
                                icon: "warning",
                                title: "Invalid File Types",
                                html: `<div style="text-align: left;"><p>The following <strong>${invalidFiles.length}</strong> file(s) have invalid types and will be skipped:</p><div style="max-height: 400px; overflow-y: auto; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; font-family: monospace; font-size: 12px;">${fileList}</div></div>`,
                                confirmButtonText: "OK",
                                confirmButtonColor: "#3085d6",
                                width: '700px'
                            });
                            invalidFiles = [];
                        }
                    }, 500);
                    
                    return false;
                }
                
                console.log('Valid file accepted:', fileName);
                return true;
            },
        }
    );
    // Handle form submission with batch upload and live progress
    $("#folderuploadForm").on("submit", function (e) {
        e.preventDefault();
        const files = folderPond.getFiles();
        const uploadContext = pendingUploadContext || captureBrowseContext();
        
        if (files.length === 0) {
            Swal.fire({
                icon: "warning",
                title: "No Valid Files",
                text: "Please add files of allowed types.",
                confirmButtonColor: "#3085d6",
            });
            return false;
        }

        const BATCH_SIZE = 15;
        const batches = [];
        const skippedFilesSnapshot = [...allSkippedFiles];
        const invalidFilesSnapshot = [...allInvalidFiles];
        console.log('Submit - skippedFilesSnapshot:', skippedFilesSnapshot);
        console.log('Submit - invalidFilesSnapshot:', invalidFilesSnapshot);
        allSkippedFiles = [];
        allInvalidFiles = [];
        
        for (let i = 0; i < files.length; i += BATCH_SIZE) {
            batches.push(files.slice(i, i + BATCH_SIZE));
        }

        Swal.fire({
            title: "Uploading Folder...",
            html: `
                <p>Uploading ${files.length} files in ${batches.length} batch(es)...</p>
                <div class="progress mt-3">
                    <div class="progress-bar" role="progressbar" style="width: 0%" id="folderUploadProgress">0%</div>
                </div>
                <small class="mt-2 d-block" id="folderUploadStatus">Preparing upload...</small>
            `,
            allowOutsideClick: false,
            showConfirmButton: false
        });

        let completedBatches = 0;
        const folderUploadItemIndex = (
            $("#folderUploadModalModal #folderIndex").val() || ""
        ).trim();
        const uploadBatch = (batchIndex) => {
            const batch = batches[batchIndex];
            const formData = new FormData();
            
            batch.forEach((fileItem, index) => {
                formData.append(`files[${index}]`, fileItem.file);
                formData.append(`file_paths[${index}]`, fileItem.file.webkitRelativePath || fileItem.filename);
            });
            
            formData.append("folder_id", uploadContext.folderId || "");
            if (folderUploadItemIndex) {
                formData.append("item_index", folderUploadItemIndex);
            }
            formData.append("batch_index", batchIndex);
            formData.append("total_batches", batches.length);
            formData.append("send_email", $("#folder_send_email").is(":checked") ? 1 : 0);
            $("#folderUploadModalModal #upload_roles").val()?.forEach(role => formData.append("roles[]", role));
            if (batchIndex === 0) {
                formData.append("skipped_files", JSON.stringify(skippedFilesSnapshot));
                formData.append("invalid_files", JSON.stringify(invalidFilesSnapshot));
            }

            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const batchProgress = Math.round((e.loaded / e.total) * 100);
                    const overallProgress = Math.round(((completedBatches + (e.loaded / e.total)) / batches.length) * 100);
                    const progressBar = document.getElementById('folderUploadProgress');
                    const statusText = document.getElementById('folderUploadStatus');
                    
                    if (progressBar) {
                        progressBar.style.width = overallProgress + '%';
                        progressBar.textContent = overallProgress + '%';
                    }
                    if (statusText) {
                        statusText.textContent = `Batch ${batchIndex + 1}/${batches.length}: ${batchProgress}%`;
                    }
                }
            });
            
            xhr.onload = function() {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success || xhr.status === 200) {
                    completedBatches++;
                    const progress = Math.round((completedBatches / batches.length) * 100);
                    const progressBar = document.getElementById('folderUploadProgress');
                    const statusText = document.getElementById('folderUploadStatus');
                    
                    if (progressBar) {
                        progressBar.style.width = progress + '%';
                        progressBar.textContent = progress + '%';
                    }
                    if (statusText) {
                        statusText.textContent = `Completed batch ${completedBatches}/${batches.length}`;
                    }
                    
                    if (completedBatches < batches.length) {
                        uploadBatch(completedBatches);
                    } else {
                        Swal.fire({
                            icon: "success",
                            title: "Upload Successful!",
                            text: "Folder uploaded successfully!",
                            confirmButtonColor: "#3085d6",
                        });
                        folderPond.removeFiles();
                        $("#folderUploadModalModal").modal("hide");
                        fetchFileManagerData(true, uploadContext);
                        pendingUploadContext = null;
                    }
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Upload Failed",
                        text: "Upload failed at batch " + (batchIndex + 1) + ". Please try again.",
                        confirmButtonColor: "#d33",
                    });
                }
            };
            
            xhr.onerror = function() {
                Swal.fire({
                    icon: "error",
                    title: "Upload Failed",
                    text: "Upload failed at batch " + (batchIndex + 1) + ". Please try again.",
                    confirmButtonColor: "#d33",
                });
            };

            xhr.open('POST', $("#folderuploadForm").attr("action"));
            xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr("content"));
            xhr.send(formData);
        };
        
        uploadBatch(0);
    });

    const fileAllowedExtensions = [
        ".png", ".jpg", ".jpeg", ".gif", ".tiff", ".tif",
        ".pdf", ".doc", ".docx", ".rtf",
        ".xls", ".xlsx", ".csv",
        ".ppt", ".pptx", ".txt",
        ".mp4", ".mov", ".wmv", ".mkv", ".mpeg", ".mpg",
        ".mp3", ".wav", ".aac", ".m4a",
        ".dwg", ".zip", ".rar", ".7z"
    ];
    let fileInvalidFiles = [];
    let fileInvalidTimeout = null;

    // Create FilePond instance
    const pond = FilePond.create(document.querySelector("#file-upload"), {
        allowMultiple: true,
        instantUpload: false,
        allowFileTypeValidation: false,
        labelIdle: 'Drag & Drop your files or <span class="filepond--label-action">Browse</span>',
        beforeAddFile: (fileItem) => {
            const fileName = fileItem.file.name.toLowerCase();
            const fileExtension = fileName.substring(fileName.lastIndexOf('.'));
            if (!fileAllowedExtensions.includes(fileExtension)) {
                fileInvalidFiles.push(fileName);
                allInvalidFiles.push(fileName);
                clearTimeout(fileInvalidTimeout);
                fileInvalidTimeout = setTimeout(() => {
                    if (fileInvalidFiles.length > 0) {
                        const fileList = fileInvalidFiles.map(f => `• ${f}`).join('<br>');
                        Swal.fire({
                            icon: "warning",
                            title: "Invalid File Types",
                            html: `<div style="text-align: left;"><p>The following <strong>${fileInvalidFiles.length}</strong> file(s) have invalid types and will be skipped:</p><div style="max-height: 400px; overflow-y: auto; margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; font-family: monospace; font-size: 12px;">${fileList}</div></div>`,
                            confirmButtonText: "OK",
                            confirmButtonColor: "#3085d6",
                            width: '700px'
                        });
                        fileInvalidFiles = [];
                    }
                }, 500);
                return false;
            }
            return true;
        },
        onaddfile: (error, file) => {
            if (!scrollLocked) {
                scrollLocked = true;
                setTimeout(() => {
                    const modal = document.querySelector('#fileModal');
                    const modalBody = modal?.querySelector('.modal-body');
                    const rolesSection = document.querySelector('#roles, [name="roles[]"], #rolesv2');
                    if (modalBody && rolesSection) {
                        targetScrollPosition = rolesSection.offsetTop - modalBody.offsetTop;
                        modalBody.scrollTop = targetScrollPosition;
                        
                        // Maintain scroll position
                        const maintainScroll = () => {
                            if (modalBody.scrollTop < targetScrollPosition - 50) {
                                modalBody.scrollTop = targetScrollPosition;
                            }
                        };
                        
                        const scrollInterval = setInterval(maintainScroll, 100);
                        setTimeout(() => {
                            clearInterval(scrollInterval);
                        }, 2000);
                    }
                }, 300);
            }
        }
    });

    // Handle form submission with batch upload for files
    document
        .querySelector("#fileForm")
        .addEventListener("submit", function (e) {
            e.preventDefault();

            const form = e.target;
            const files = pond.getFiles();
            const uploadContext = pendingUploadContext || captureBrowseContext();
            
            if (files.length === 0) {
                Swal.fire({
                    icon: "warning",
                    title: "No Files Selected",
                    text: "Please select at least one file to upload.",
                });
                return;
            }

            // Validate files before upload
            const invalidFilesList = files.filter((fileItem) => {
                const fileName = fileItem.file.name.toLowerCase();
                return !fileAllowedExtensions.some((ext) => fileName.endsWith(ext));
            });

            if (invalidFilesList.length > 0) {
                Swal.fire({
                    icon: "error",
                    title: "Invalid Files Detected",
                    text: "One or more files are not allowed.",
                });
                return;
            }

            const BATCH_SIZE = 15; // Safe limit under PHP's default max_file_uploads=20
            const batches = [];
            
            // Split files into batches
            for (let i = 0; i < files.length; i += BATCH_SIZE) {
                batches.push(files.slice(i, i + BATCH_SIZE));
            }

            Swal.fire({
                title: "Uploading Files...",
                html: `
                    <p>Uploading ${files.length} files in ${batches.length} batch(es)...</p>
                    <div class="progress mt-3">
                        <div class="progress-bar" role="progressbar" style="width: 0%" id="fileUploadProgress">0%</div>
                    </div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false
            });

            let completedBatches = 0;
            const uploadBatch = (batchIndex) => {
                const batch = batches[batchIndex];
                const formData = new FormData();
                
                // Append files
                batch.forEach((fileItem, index) => {
                    formData.append(`file[${index}]`, fileItem.file);
                });
                
                // Append other form fields
                formData.append("folder_id", uploadContext.folderId || "");
                formData.append("_token", document.querySelector('input[name="_token"]').value);
                
                const otherFields = form.querySelectorAll("input, select, textarea");
                otherFields.forEach((field) => {
                    if (field.name !== "file[]" && field.name !== "_token") {
                        formData.append(field.name, field.value);
                    }
                });
                
                if ($("#send_email").is(":checked")) {
                    formData.append("send_email", 1);
                }
                
                formData.append("batch_index", batchIndex);
                formData.append("total_batches", batches.length);
                if (batchIndex === 0) {
                    formData.append("invalid_files", JSON.stringify(allInvalidFiles));
                }

                const xhr = new XMLHttpRequest();
                
                xhr.onload = function () {
                    const data = JSON.parse(xhr.responseText);
                    
                    if (data.success) {
                        completedBatches++;
                        const progress = Math.round((completedBatches / batches.length) * 100);
                        const progressBar = document.getElementById('fileUploadProgress');
                        if (progressBar) {
                            progressBar.style.width = progress + '%';
                            progressBar.textContent = progress + '%';
                        }
                        
                        if (completedBatches < batches.length) {
                            uploadBatch(completedBatches);
                        } else {
                            Swal.fire({
                                icon: "success",
                                title: "Upload Successful!",
                                text: "Files uploaded successfully!",
                                confirmButtonColor: "#3085d6",
                            });
                            $("#fileModal").modal("hide");
                            fetchFileManagerData(true, uploadContext);
                            pendingUploadContext = null;
                            pond.removeFiles();
                        }
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Upload Failed",
                            text: data.message || "Upload failed at batch " + (batchIndex + 1),
                        });
                    }
                };
                
                xhr.onerror = function () {
                    Swal.fire({
                        icon: "error",
                        title: "Upload Failed",
                        text: "Upload failed at batch " + (batchIndex + 1) + ". Please try again.",
                    });
                };

                xhr.open('POST', form.action);
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('input[name="_token"]').value);
                xhr.send(formData);
            };
            
            uploadBatch(0);
        });
    $("#createFolderForm").on("submit", function (e) {
        e.preventDefault();
        const actionContext = captureBrowseContext();

        const currentDir = fileManager.getCurrentDirectory();
        const parentId = currentDir.dataItem?.id || "";

        let formData = new FormData(this);
        formData.append("parent_id", parentId);

        // Show loading alert
        Swal.fire({
            title: "Creating Folder...",
            text: "Please wait while the folder is being created.",
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: createFolderRoute,
            type: "POST",
            data: formData,
            headers: {
                "X-CSRF-TOKEN": csrfToken,
            },
            processData: false,
            contentType: false,
            success: function (response) {
                Swal.close(); // Close loader

                if (response.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Folder Created!",
                        text: "Your folder has been created successfully.",
                        timer: 2000,
                        showConfirmButton: false,
                    });

                    $("#createFolderModal").modal("hide");
                    fetchFileManagerData(true, actionContext);
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Failed to Create Folder",
                        text: response.message || "Unknown error occurred.",
                    });
                }
            },
            error: function (xhr, status, error) {
                Swal.close();
                console.error("Error:", error);

                // Handle Laravel validation errors (422)
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = "";

                    // Concatenate all validation error messages
                    for (const field in errors) {
                        if (errors.hasOwnProperty(field)) {
                            errorMessage += errors[field].join("<br>") + "<br>";
                        }
                    }

                    Swal.fire({
                        icon: "error",
                        title: "Validation Error",
                        html: errorMessage,
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Unexpected Error",
                        text: xhr.responseJSON?.message || "An unexpected error occurred. Please try again.",
                    });
                }
            },
        });
    });
    // Move form submission
    $("#moveForm").on("submit", function (e) {
        e.preventDefault();
        const actionContext = captureBrowseContext();

        let formData = {
            file_ids: JSON.parse($("#move_file_ids").val()),
            folder_ids: JSON.parse($("#move_folder_ids").val()),
            destination_folder_id: $("#destination_folder").val() || null,
        };

        $.ajax({
            url: moveItemsRoute,
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify(formData),
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (resp) {
                $("#moveModal").modal("hide");
                DevExpress.ui.notify(
                    resp.message || "Items moved successfully!",
                    "success",
                    2000
                );
                fetchFileManagerData(true, actionContext);
            },
            error: function (err) {
                console.error("Error:", err);
                DevExpress.ui.notify("Error moving items", "error", 2000);
            },
        });
    });

    // Initialize File Manager Data
    fetchFileManagerData(false);
});

// Function to check zip status and handle download
function checkZipStatus(zipId) {
    const statusInterval = setInterval(function () {
        $.ajax({
            url: `zip-status/${zipId}`,
            method: 'GET',
            success: function (response) {
                if (response.status === 'completed') {
                    clearInterval(statusInterval);
                    DevExpress.ui.notify({
                        message: "Zip file is ready! Starting download...",
                        type: "success",
                        displayTime: 3000
                    });

                    // Start download
                    window.location.href = `download-zip/${zipId}`;

                } else if (response.status === 'failed') {
                    clearInterval(statusInterval);
                    DevExpress.ui.notify({
                        message: `Zip creation failed: ${response.error_message || 'Unknown error'}`,
                        type: "error",
                        displayTime: 5000
                    });
                } else if (response.status === 'processing') {
                    DevExpress.ui.notify({
                        message: "Zip file is being processed...",
                        type: "info",
                        displayTime: 2000
                    });
                }
            },
            error: function (xhr) {
                clearInterval(statusInterval);
                DevExpress.ui.notify({
                    message: "Failed to check zip status",
                    type: "error",
                    displayTime: 3000
                });
            }
        });
    }, 3000); // Check every 3 seconds

    // Stop checking after 5 minutes to prevent infinite polling
    setTimeout(function () {
        clearInterval(statusInterval);
    }, 300000);
}