@extends('layouts.staff')
@section('title','Reports')
@section('crumb','Reports')
@section('content')
<div class="page-head"><div><h1>Category reports</h1><p>Unique concern issues for the selected period.</p></div><div class="row gap"><button type="button" class="btn" onclick="window.print()">Print</button><button type="button" class="btn primary" id="exportCsv">Export CSV</button></div></div>
<form class="filters card" method="GET">
  <div><label for="rp">Date range</label><select id="rp" name="period"><option value="today" @selected($period==='today')>Today</option><option value="week" @selected($period==='week')>This week</option><option value="month" @selected($period==='month')>This month</option><option value="year" @selected($period==='year')>This year</option><option value="all" @selected($period==='all')>All time</option></select></div>
  <div class="btns"><button type="submit" class="btn primary">Apply</button></div>
</form>
<section class="card flush">
<table class="table" data-sortable id="reportsTable">
<caption>Concern report totals by category</caption>
<thead><tr><th scope="col">Category</th><th scope="col" data-sort-type="number">Total</th><th scope="col" data-sort-type="number">Matched</th><th scope="col" data-sort-type="number">Unmatched</th><th scope="col" data-sort-type="number">Reviewed</th><th scope="col" data-sort-type="number">Rejected</th><th scope="col" data-sort-type="number">Percent of all</th></tr></thead>
<tbody>
@forelse($rows as $row)<tr><td data-label="Category"><b>{{ $row['name'] }}</b></td><td data-label="Total" data-sort="{{ $row['total'] }}">{{ $row['total'] }}</td><td data-label="Matched" data-sort="{{ $row['matched'] }}">{{ $row['matched'] }}</td><td data-label="Unmatched" data-sort="{{ $row['unmatched'] }}">{{ $row['unmatched'] }}</td><td data-label="Reviewed" data-sort="{{ $row['reviewed'] }}">{{ $row['reviewed'] }}</td><td data-label="Rejected" data-sort="{{ $row['rejected'] }}">{{ $row['rejected'] }}</td><td data-label="Percent" data-sort="{{ $row['percent'] }}">{{ $row['percent'] }}%</td></tr>@empty<tr><td colspan="7" class="empty">No categories or concerns available.</td></tr>@endforelse
</tbody><tfoot><tr><td><b>Total</b></td><td>{{ $totals['total'] }}</td><td>{{ $totals['matched'] }}</td><td>{{ $totals['unmatched'] }}</td><td>{{ $totals['reviewed'] }}</td><td>{{ $totals['rejected'] }}</td><td>100%</td></tr></tfoot>
</table></section>
@endsection
@push('scripts')
<script>
document.getElementById("exportCsv").addEventListener("click",()=>{
 const table=document.getElementById("reportsTable");
 const lines=[...table.querySelectorAll("tr")].map(row=>[...row.cells].map(cell=>'"'+cell.innerText.replaceAll('"','""').replace(/[▲▼]/g,'')+'"').join(","));
 const blob=new Blob([lines.join("\n")],{type:"text/csv"});
 const link=document.createElement("a");link.href=URL.createObjectURL(blob);link.download="category-reports.csv";link.click();URL.revokeObjectURL(link.href);
 window.UI?.announce("Category report CSV exported.");
});
</script>
@endpush
