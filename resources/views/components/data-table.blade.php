<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title">{{ $extraOptions['title'] ?? 'Data Table' }}</h3>
    </div>
    <div class="card-body">
        <table id="{{ $id }}" class="table table-bordered table-hover  table-responsive-sm" style="width:100%">
            <thead class="thead-dark">
                <tr>
                    @foreach ($columns as $column)
                        @if ($column['data'] === 'select')
                            <th>    
                                <input type="checkbox" id="select-all">
                            </th>
                        @else
                            <th>{{ $column['title'] }}</th>
                        @endif
                    @endforeach
                </tr>
            </thead>
        </table>
    </div>
</div>
