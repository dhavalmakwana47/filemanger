$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr("content");
    let searchTimeout = null; // For debouncing search input
    let currentSearchValue = ""; // Track current search value
    const fileManager = $("#file-manager")
        .dxFileManager({
            name: "fileManager",
            fileProvider: [],
            height: 450,
            width: 800,
            selectionMode: isMultiSelect ? "multiple" : "single",
            itemView: { mode: "details" },
            permissions: {
                create: false,
                rename: true,
            },
            itemView: {
                details: {
                    columns: [
                        {
                            dataField: "index", // your custom field
                            caption: "Index", // column title
                            dataType: "number",
                            width: 70,
                            cssClass: "index-column",
                            sortOrder: "asc", // 👈 default sort order
                            sortIndex: 0, // 👈 first sort priority
                        },
                        {
                            dataField: "thumbnail",
                            caption: "",
                            width: 50,
                        },

                        {
                            dataField: "name",
                            caption: "File/Folder",
                            with: 300,
                        },
                        // {
                        //     dataField: "dateModified",
                        //     caption: "Date of Creation",
                        //     dataType: "datetime",
                        //     format: "dd/MM/yyyy HH:mm:ss",
                        // },
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
                        html: "<i class='dx-icon dx-icon-rename'></i><span class='dx-button-rename'>Assign</span>",
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
                                $("<form>", {
                                    method: "POST",
                                    action: `folder-zip`,
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
                                    .append(
                                        $("<input>", {
                                            type: "hidden",
                                            name: "dataItem",
                                            value: JSON.stringify(
                                                selectedItem.dataItem
                                            ), // Pass entire dataItem as JSON string
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
                    },
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
                        visible: isMultiSelect,
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
                        visible: isMultiSelect,
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
                    selectedItems.length > 1 ? false : canUpdate
                );
                fileManager.option("contextMenu.items[1].visible", canDelete);
                fileManager.option("contextMenu.items[2].visible", fileView);
                fileManager.option("contextMenu.items[3].visible", canView);
                fileManager.option("contextMenu.items[4].visible", canZip);
                fileManager.option("contextMenu.items[5].visible", canExtract);

                fileManager.option(
                    "toolbar.fileSelectionItems[0].visible",
                    canView
                );
            },
            onItemContextMenu: function (e) {
                let selectedItems = e.selectedItems;
            },
            onCurrentDirectoryChanged: function (e) {
                createPermission = createFolderPermission;
                if (e.directory.dataItem !== undefined) {
                    createPermission = e.directory.dataItem.permissions.create;
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

        let formData = {
            file_ids: JSON.parse($("#file_ids").val()),
            folder_ids: JSON.parse($("#folder_ids").val()),
            roles: $("#rolesv2").val(), // selected role IDs
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
                fetchFileManagerData(); // refresh file manager if needed
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
                    fetchFileManagerData();
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

                            fetchFileManagerData();
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
                },
            });
            const data = await response.json();
            fileManager.option("fileSystemProvider", data);

            // Keep the search term in the input but don't clear it
            if (query) {
                fileManager.option("toolbar.items[5].options.value", query);
            }
        } catch (error) {
            console.error("Error searching data:", error);
            DevExpress.ui.notify(
                "Failed to search files and folders",
                "error",
                2000
            );
        }
    }

    // ✅ Fetch File Manager Data
    async function fetchFileManagerData() {
        try {
            const response = await fetch(getFileMangerRoute);
            const data = await response.json();
            fileManager.option("fileSystemProvider", data);
            // Clear search input when refreshing data
            fileManager.option("toolbar.items[4].options.value", "");
        } catch (error) {
            console.error("Error fetching data:", error);
            DevExpress.ui.notify(
                "Failed to fetch file manager data",
                "error",
                2000
            );
        }
    }

    // ✅ Custom Function to Create a New Folder
    function createNewFolder() {
        $("#createFolderForm")[0].reset();
        $("#createFolderModal").modal("show");
    }

    // ✅ Custom Function to Upload a file
    function uploadFile() {
        $("#fileForm")[0].reset();
        $("#fileModal").modal("show");
    }

    function uploadFolder() {
        $("#folderForm")[0].reset();
        $("#folderUploadModalModal").modal("show");
    }

    FilePond.registerPlugin(FilePondPluginFileValidateType);
 const folderPond = FilePond.create(
    document.querySelector("#folder-upload"),
    {
        allowMultiple: true,
        allowDirectoryDrop: true,
        allowDrop: false, // Disable drag-and-drop
        labelIdle: 'Select Folder <span class="filepond--label-action">Browse</span>',
        fileValidateTypeDetectType: (source, type) => new Promise((resolve, reject) => {
            if (source.size === 0) {
                reject(new Error('File is empty (0 KB)'));
                return;
            }
            resolve(type);
        }),
        acceptedFileTypes: [
            "image/png",
            "image/jpeg",
            "image/gif",
            "application/pdf",
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "application/zip",
            "application/x-zip-compressed",
            "text/csv",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "text/plain",
            "application/x-rar-compressed",
            "application/vnd.rar",
            "image/tiff",
            "image/tif",
            "application/rtf",
            "application/vnd.ms-excel",
            "application/vnd.ms-powerpoint",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation",
            "video/mp4",
            "video/quicktime",
            "video/x-ms-wmv",
            "video/x-matroska",
            "video/mpeg",
            "audio/mpeg",
            "audio/wav",
            "audio/aac",
            "audio/mp4",
            "audio/x-m4a",
            "application/acad",
            "application/x-acad",
            "application/autocad_dwg",
            "application/dwg",
            "application/x-dwg",
            "application/x-autocad",
            "drawing/dwg",
            "image/vnd.dwg",
            "image/x-dwg",
            "application/x-7z-compressed"
        ],
        server: {
            process: null,
        },
        onaddfile: (error, file) => {
            if (error) {
                console.warn("Skipped file:", file.filename, "Reason:", error.message);
                Swal.fire({
                    icon: "warning",
                    title: "Upload Error",
                    text: `Skipped ${file.filename}: ${error.message || 'File type not allowed or empty file'}`,
                    confirmButtonText: "OK",
                    confirmButtonColor: "#3085d6",
                });
                return;
            }
        },
    }
);
    // Handle form submission
    $("#folderuploadForm").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);
        const formData = new FormData($form[0]);

        // Append FilePond files and paths
        const files = folderPond.getFiles();
        if (files.length === 0) {
            Swal.fire({
                icon: "warning",
                title: "No Valid Files",
                text: "Please add files of allowed types.",
                confirmButtonColor: "#3085d6",
            });
            return;
        }
        $.each(files, function (index, fileItem) {
            formData.append(`files[${index}]`, fileItem.file);
            formData.append(
                `file_paths[${index}]`,
                fileItem.file.webkitRelativePath || fileItem.filename
            );
        });

        // Append folder_id
        formData.append(
            "folder_id",
            fileManager.getCurrentDirectory().dataItem?.id || ""
        );
        // Show loading alert
        Swal.fire({
            title: "Uploading...",
            html: `
                <p>Please wait while your folder are being uploaded.</p>
                <small>This may take a few minutes depending on your folder size and internet speed.</small>
            `,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });
        // AJAX request
        $.ajax({
            url: $form.attr("action"),
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                Swal.fire({
                    icon: "success",
                    title: "Upload Successful!",
                    text: response.message,
                    confirmButtonColor: "#3085d6",
                });
                folderPond.removeFiles();
                $("#folderUploadModalModal").modal("hide");
                fetchFileManagerData();
            },
            error: function (xhr) {
                console.error("Error uploading files:", xhr.responseText);
                let errorMsg = "Upload failed. Please try again.";
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).join(
                        "\n"
                    );
                }
                Swal.fire({
                    icon: "error",
                    title: "Upload Failed",
                    text: errorMsg,
                    confirmButtonColor: "#d33",
                });
            },
        });
    });

    // Create FilePond instance
    const pond = FilePond.create(document.querySelector("#file-upload"), {
        allowMultiple: true,
        instantUpload: false,
        allowFileTypeValidation: false, // disable MIME check
        labelIdle:
            'Drag & Drop your files or <span class="filepond--label-action">Browse</span>',
        onaddfilestart: (fileItem) => {
            const allowedExtensions = [
                // Images
                ".png", ".jpg", ".jpeg", ".gif", ".tiff", ".tif",
                // Documents
                ".pdf", ".doc", ".docx", ".rtf",
                ".xls", ".xlsx", ".csv",
                ".ppt", ".pptx", ".txt",
                // Video
                ".mp4", ".mov", ".wmv", ".mkv", ".mpeg", ".mpg",
                // Audio
                ".mp3", ".wav", ".aac", ".m4a",
                // CAD
                ".dwg",
                // Archives
                ".zip", ".rar", ".7z"
            ];

            const fileName = fileItem.file.name.toLowerCase();
            const fileExtension = fileName.substring(fileName.lastIndexOf('.'));
            const isValidExtension = allowedExtensions.includes(fileExtension);

            if (!isValidExtension) {
                const allowedTypesList = [
                    'Images: PNG, JPG, JPEG, GIF, TIFF, TIF',
                    'Documents: PDF, DOC, DOCX, RTF, XLS, XLSX, CSV, PPT, PPTX, TXT',
                    'Video: MP4, MOV, WMV, MKV, MPEG, MPG',
                    'Audio: MP3, WAV, AAC, M4A',
                    'CAD: DWG',
                    'Archives: ZIP, RAR, 7Z'
                ].join('\n');

                Swal.fire({
                    icon: "error",
                    title: "Invalid File Type",
                    html: `The file type <strong>${fileExtension}</strong> is not allowed.<br><br>Allowed file types are:<br>${allowedTypesList.replace(/\n/g, '<br>')}`,
                    confirmButtonText: 'OK'
                });
                fileItem.remove(); // remove invalid file
            }
        },
    });

    // Handle form submission with pre-upload validation
    document
        .querySelector("#fileForm")
        .addEventListener("submit", function (e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData();

            // Append folder_id manually (if applicable)
            formData.append(
                "folder_id",
                fileManager.getCurrentDirectory().dataItem?.id || ""
            );

            // Append CSRF token
            formData.append(
                "_token",
                document.querySelector('input[name="_token"]').value
            );

            // Append other form fields (e.g., permissions from filepermissions partial)
            const otherFields = form.querySelectorAll(
                "input, select, textarea"
            );
            otherFields.forEach((field) => {
                if (field.name !== "file[]") {
                    formData.append(field.name, field.value);
                }
            });

            // Pre-upload check: Ensure at least one valid file is selected
            const files = pond.getFiles();
            if (files.length === 0) {
                Swal.fire({
                    icon: "warning",
                    title: "No Files Selected",
                    text: "Please select at least one file to upload.",
                });
                return;
            }

            // Validate files before appending to formData
            const allowedExtensions = [
                // Images
                ".png", ".jpg", ".jpeg", ".gif", ".tiff", ".tif",
                // Documents
                ".pdf", ".doc", ".docx", ".rtf",
                ".xls", ".xlsx", ".csv",
                ".ppt", ".pptx", ".txt",
                // Video
                ".mp4", ".mov", ".wmv", ".mkv", ".mpeg", ".mpg",
                // Audio
                ".mp3", ".wav", ".aac", ".m4a",
                // CAD
                ".dwg",
                // Archives
                ".zip", ".rar", ".7z"
            ];
            const invalidFiles = files.filter((fileItem) => {
                const fileName = fileItem.file.name.toLowerCase();
                return !allowedExtensions.some((ext) => fileName.endsWith(ext));
            });

            if (invalidFiles.length > 0) {
                Swal.fire({
                    icon: "error",
                    title: "Invalid Files Detected",
                    text: "One or more files are not allowed. Only PNG, JPEG, GIF, PDF, DOC, DOCX, ZIP, CSV, and XLSX files are permitted.",
                });
                return;
            }

            // Append valid files to formData
            files.forEach((fileItem) => {
                formData.append("file[]", fileItem.file);
            });

            // Show loading alert
            Swal.fire({
                title: "Uploading...",
                html: `
                <p>Please wait while your files are being uploaded.</p>
                <small>This may take a few minutes depending on your file size and internet speed.</small>
            `,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            // Submit via AJAX
            fetch(form.action, {
                method: "POST",
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    Swal.close(); // Close loader

                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Uploaded!",
                            text: "Files uploaded successfully.",
                            timer: 2000,
                            showConfirmButton: false,
                        });

                        $("#fileModal").modal("hide");
                        fetchFileManagerData(); // Assuming this refreshes your file manager
                        pond.removeFiles();
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Upload Failed",
                            text: data.message || "Unknown error occurred.",
                        });
                    }
                })
                .catch((error) => {
                    Swal.close();
                    console.error("Error:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Unexpected Error",
                        text: "An unexpected error occurred. Please try again.",
                    });
                });
        });
    $("#createFolderForm").on("submit", function (e) {
        e.preventDefault();

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
                    fetchFileManagerData();
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

    // Initialize File Manager Data
    fetchFileManagerData();
});
