<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    
    <div class="properties-content">
    <p><strong>Name:</strong> {{ $name ?? 'N/A' }}</p>
    {{-- <p><strong>Type:</strong> Folder</p>
    <p><strong>Modified:</strong> {{ $dateModified ?? 'N/A' }}</p>
    <p><strong>Owner:</strong> {{ $owner ?? 'N/A' }}</p>
    <p><strong>Permissions:</strong> {{ !empty($permissions) ? implode(', ', $permissions) : 'None' }}</p>
    <p><strong>Contains:</strong> {{ $itemCount ?? '0' }} items</p> --}}
</div>
</body>
</html>