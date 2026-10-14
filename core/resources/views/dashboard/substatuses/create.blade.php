@php
    $createModal = true;
    if (!isset($Substatuses)) {
        $Substatuses = \App\Models\Substatus::select(['substatuses.*', 'statuses.name as status_name'])
            ->join('statuses','statuses.id', 'substatuses.status_id')
            ->orderby("substatuses.seq_order", "asc")
            ->paginate(env('BACKEND_PAGINATION', 10));
    }
@endphp
@include('dashboard.substatuses.list')
