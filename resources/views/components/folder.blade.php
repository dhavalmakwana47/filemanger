@if ($folder->has_access())

    <li>
        <details>
            <summary>
                <i class="fa fa-folder"></i> {{ $folder->name }}
                @if (current_user()->hasPermission('Folder', 'create') ||
                        current_user()->hasPermission('Folder', 'update') ||
                        current_user()->hasPermission('Folder', 'delete'))
                    <!-- Actions Button -->
                    <div class="dropdown d-inline">
                        <button class="btn btn-sm btn-secondary dropdown-toggle" type="button"
                            id="actionsMenu{{ $folder->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="actionsMenu{{ $folder->id }}">
                            @if (current_user()->hasPermission('Folder', 'create'))
                                <li>
                                    <button class="dropdown-item" onclick="create_folder_form({{ $folder->id }})">
                                        <i class="fa fa-plus"></i> Add
                                    </button>
                                </li>
                            @endif
                            @if (current_user()->hasPermission('Folder', 'update'))
                                <li>
                                    <a class="dropdown-item edit-folder" data-folder-id="{{ $folder->id }}"
                                        data-url="{{ route('folder.update', $folder->id) }}">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                </li>
                            @endif
                            @if (current_user()->hasPermission('Folder', 'delete'))
                                <li>
                                    <a class="dropdown-item delete-folder"
                                        data-url="{{ route('folder.destroy', $folder->id) }}">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
                                </li>
                            @endif

                        </ul>
                    </div>
                @endif

            </summary>

            @if ($folder->subfolders->isNotEmpty())
                <ul>
                    @foreach ($folder->subfolders as $subFolder)
                        <x-folder :folder="$subFolder" />
                    @endforeach
                </ul>
            @endif
        </details>
    </li>
@endif
