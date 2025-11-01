<!DOCTYPE html>
<html>

<head>
    <title>View File</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .pdf-container {
            max-width: 1000px;
            margin: 0 auto;
        }
         body {
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
    }
    </style>
</head>

<body oncontextmenu="return false;">
    <div class="container pdf-container">

        @if (str_contains($mimeType, 'image'))
            @include('partials.image', ['id' => $id, 'fileName' => $fileName, 'routeName' => $routeName])
        @elseif (str_contains($mimeType, 'pdf'))
            @include('partials.pdf', ['id' => $id, 'routeName' => $routeName])
        @elseif (str_contains($mimeType, 'epub'))
            @include('partials.epub', ['id' => $id, 'routeName' => $routeName])
        @elseif (str_contains($mimeType, 'video'))
            @include('partials.video', ['id' => $id, 'mimeType' => $mimeType, 'routeName' => $routeName])
        @elseif (str_contains($mimeType, 'youtube'))
            @include('partials.youtube', ['link' => $link ])
        @elseif (str_contains($mimeType, 'csv'))
            @include('partials.csv', ['id' => $id, 'routeName' => $routeName])
        @elseif (str_contains($mimeType, 'spreadsheetml') || str_contains($mimeType, 'excel'))
            @include('partials.xlsx', ['id' => $id, 'routeName' => $routeName])
        @elseif (str_contains($mimeType, 'text'))
            @include('partials.text', ['id' => $id, 'value' => $value, 'routeName' => $routeName])
        @elseif (str_contains($mimeType, 'msword') || str_contains($mimeType, 'wordprocessingml'))
            @include('partials.doc', ['id' => $id, 'routeName' => $routeName])
        @elseif (str_contains($mimeType, 'audio'))
            @include('partials.audio', ['id' => $id, 'mimeType' => $mimeType, 'fileName' => $fileName, 'routeName' => $routeName])
        @else
            <p class="mt-3">This file type cannot be viewed directly in the browser.</p>
        @endif
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
