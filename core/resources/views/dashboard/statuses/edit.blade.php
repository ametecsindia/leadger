@php
    $editStatus = $Status ?? null;
    if (!isset($Statuses)) {
        $Statuses = \App\Models\Status::with('substatuses')->orderby("seq_order", "asc")->orderby("id", "asc")->get();
    }
@endphp
@include('dashboard.statuses.list')
