# Auto-Numbering Index System

## Overview
Implemented hierarchical auto-numbering for folders and files with **manual control for parent folders** and **automatic assignment for children**.

## How It Works

### Index Format
- **Root Level**: 1, 2, 3, ... (can be manually set)
- **First Level**: 1.1, 1.2, 1.3, ... (auto-assigned based on parent)
- **Second Level**: 1.1.1, 1.1.2, 1.1.3, ... (auto-assigned based on parent)
- **And so on...**

### Example Structure
```
Legal (1) ← User enters "1"
├── MOA (1.1) ← Auto-assigned
├── AOA (1.2) ← Auto-assigned
└── MOM (1.3) ← Auto-assigned
    └── MOM1 (1.3.1) ← Auto-assigned

Financials (2) ← User enters "2"
└── Balance Sheet (2.1) ← Auto-assigned
```

## Key Features

### 1. Manual Index for Parent Folders
- Users can **enter custom index** when creating/editing folders
- If left empty, system auto-assigns next available number
- Example: Enter "1" for Legal folder

### 2. Auto-Assignment for Children
- All child folders and files automatically follow parent's index
- Child folders: 1.1, 1.2, 1.3...
- Child files: Follow same pattern within parent folder

### 3. Cascading Updates
- When you change a parent folder's index, **all children update automatically**
- Example: Change Legal from "1" to "5"
  - MOA changes from 1.1 → 5.1
  - AOA changes from 1.2 → 5.2
  - MOM changes from 1.3 → 5.3
  - MOM1 changes from 1.3.1 → 5.3.1

### 4. Smart Auto-Assignment
- System calculates next available index automatically
- Considers both folders and files in same level
- Maintains sequential order

## Implementation Details

### 1. IndexNumberingService
Created `app/Services/IndexNumberingService.php` with the following methods:

- **generateNextIndex($parentId, $type)**: Generates the next index number for a folder or file
  - For root level: Returns next sequential number (1, 2, 3...)
  - For child items: Returns parent index + next sequential number (1.1, 1.2, 1.3...)

- **reindexFolder($parentId)**: Reindexes all items in a folder (useful after move or delete operations)

- **reindexChildren($parentId, $parentIndex)**: Private method to recursively reindex children

### 2. Controller Updates

#### FolderController
- **store()**: Auto-generates index when creating folders
- **uploadFolderStructure()**: Auto-generates index for bulk folder uploads
- Removed manual `item_index` input from requests

#### FileController
- **store()**: Auto-generates index when uploading files
- **update()**: Removed manual `item_index` update
- Removed manual `item_index` input from requests

### 3. View Updates
Removed manual index input fields from:
- `resources/views/app/folder/index.blade.php` (file and folder upload modals)
- `resources/views/app/folder/update.blade.php` (folder edit form)
- `resources/views/app/folder/updatefile.blade.php` (file edit form)

## Usage

### Creating a Folder
```php
// Index is automatically generated based on parent
$folder = Folder::create([
    'name' => 'Legal',
    'parent_id' => null, // Root level
    'company_id' => $company_id,
    'item_index' => IndexNumberingService::generateNextIndex(null, 'folder'), // Returns "1"
    'created_by' => $user_id
]);
```

### Creating a File
```php
// Index is automatically generated based on folder
$file = File::create([
    'name' => 'document.pdf',
    'folder_id' => $folder_id,
    'company_id' => $company_id,
    'item_index' => IndexNumberingService::generateNextIndex($folder_id, 'file'), // Returns "1.1"
    'created_by' => $user_id
]);
```

### Reindexing After Move/Delete
```php
// Reindex root level
IndexNumberingService::reindexFolder(null);

// Reindex specific folder
IndexNumberingService::reindexFolder($folderId);
```

## Benefits
1. **Automatic**: No manual input required from users
2. **Hierarchical**: Clear parent-child relationships
3. **Sequential**: Maintains order within each level
4. **Consistent**: Same logic for folders and files
5. **Flexible**: Can be reindexed after structural changes

## Future Enhancements
- Add reindexing after move operations
- Add reindexing after delete operations
- Add custom index prefix/suffix options
- Add index display in file manager UI
