<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\WebmasterSection;
use App\Services\TranscriptReportQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Reports > Transcript Report: who requested call transcripts, how many recordings, what was produced, the status and
 * the credits used (App\Services\TranscriptReportQuery). View / Download / Copy read the saved transcripts: Sarvam is
 * never called again and nothing is charged again. Same roles as requesting a transcript.
 */
class TranscriptReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function query(Request $request): TranscriptReportQuery
    {
        return new TranscriptReportQuery([
            'from' => $request->input('from_date'), 'to' => $request->input('to_date'), 'user' => $request->input('user'),
            'role' => $request->input('role'), 'type' => $request->input('type'), 'status' => $request->input('status'),
            'search' => $request->input('q', $request->input('search.value')),
        ], Auth::user());
    }

    public function index(Request $request)
    {
        abort_unless(TranscriptReportQuery::canView(Auth::user()), 403);
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $options = $this->query($request)->filterOptions();
        return view('dashboard.reports.transcriptReport', compact('GeneralWebmasterSections', 'options'));
    }

    /** DataTables (server side): rows + KPI cards for the same filters. */
    public function data(Request $request)
    {
        abort_unless(TranscriptReportQuery::canView(Auth::user()), 403);
        $query = $this->query($request);
        $columns = $request->input('columns', []);
        $sort = (string)($columns[$request->input('order.0.column')]['data'] ?? 'date');
        $total = $query->count();
        return response()->json([
            'draw' => (int)$request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $query->rows((int)$request->input('start', 0), (int)$request->input('length', 25), $sort, (string)$request->input('order.0.dir', 'desc')),
            'kpis' => $query->kpis(),
            'filters' => $query->filters(),
        ]);
    }

    /** The saved transcripts of one request (View / Copy): read from the database, never regenerated. */
    public function show(Request $request, string $batch)
    {
        [$query, $meta] = $this->requestOrFail($request, $batch);
        $items = TranscriptReportQuery::transcripts($batch);
        return response()->json(['success' => true, 'meta' => $meta, 'items' => $items,
            'text' => $items ? TranscriptReportQuery::asText($batch, $meta) : '']);
    }

    /** Download the saved transcripts of one request as a text file. */
    public function download(Request $request, string $batch)
    {
        [$query, $meta] = $this->requestOrFail($request, $batch);
        abort_unless(TranscriptReportQuery::transcripts($batch), 404, 'No completed transcript in this request yet.');
        $name = 'transcript-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($meta['used_by'])) . '-' . str_replace(' ', '', $meta['date'] . '-' . $meta['time']) . '.txt';
        return response(TranscriptReportQuery::asText($batch, $meta), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . trim($name, '-') . '"',
        ]);
    }

    /** The request's row as the table shows it, only when this user may see it (same scope as the table). */
    private function requestOrFail(Request $request, string $batch): array
    {
        abort_unless(TranscriptReportQuery::canView(Auth::user()) && preg_match('/^[A-Za-z0-9_.-]{1,80}$/', $batch), 404);
        $query = new TranscriptReportQuery(['from' => '2000-01-01', 'to' => '2999-12-31'], Auth::user());
        $row = $query->rowFor($batch);
        abort_unless($row, 404);
        return [$query, $row];
    }
}
