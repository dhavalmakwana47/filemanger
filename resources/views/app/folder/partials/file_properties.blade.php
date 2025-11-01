<div class="properties-content">
    @if (isset($name))
        <p><strong>Name:</strong> {{ $name ?? 'N/A' }}</p>
    @endif
    @if (isset($dateModified))
        <p><strong>Modified:</strong> {{ $dateModified ?? 'N/A' }}</p>
    @endif
    @if (isset($owner))
        <p><strong>Role:</strong> {{ $owner ?? 'N/A' }}</p>
    @endif
    @if(isset($itemCount))
        <p><strong>Contains:</strong> {{ $itemCount ?? '0' }} items</p>
    @endif
    @if (isset($size))
        <p><strong>Size:</strong> {{ $size ?? 'N/A' }}</p>
    @endif
    @if (isset($created_by))
        <p><strong>Created By:</strong> {{ $created_by ?? 'N/A' }}</p>  
    @endif
</div>