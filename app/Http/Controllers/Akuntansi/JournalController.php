<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CompanySetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Journal::with('creator')->latest();
            if ($request->filled('from')) {
                $query->whereDate('journal_date', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('journal_date', '<=', $request->to);
            }
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where('journal_number', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            }
            $filtered = $query->count();
            $data = $query->skip($start)->take($length)->get()->map(function ($item) {
                return [
                    $item->journal_number,
                    $item->journal_date->format('d/m/Y'),
                    \Str::limit($item->description, 50),
                    ucfirst($item->source),
                    formatRupiah($item->total_debit),
                    formatRupiah($item->total_credit),
                    '<a href="'.route('akuntansi.journals.show', $item).'" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.akuntansi.journals.index');
    }

    public function create()
    {
        $accounts = Account::active()->get();
        $prefix = CompanySetting::first()->doc_prefix_journal ?? 'JUR';
        $journalNumber = $prefix.'-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT);

        return view('pages.akuntansi.journals.create', compact('accounts', 'journalNumber'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'journal_number' => 'required|string|unique:journals',
            'journal_date' => 'required|date',
            'description' => 'nullable|string',
            'entries' => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:accounts,id',
            'entries.*.debit' => 'nullable|numeric|min:0',
            'entries.*.credit' => 'nullable|numeric|min:0',
            'entries.*.description' => 'nullable|string',
        ]);

        $totalDebit = collect($validated['entries'])->sum('debit');
        $totalCredit = collect($validated['entries'])->sum('credit');
        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->with('error', 'Total debit harus sama dengan total kredit.')->withInput();
        }

        DB::transaction(function () use ($validated, $totalDebit, $totalCredit) {
            $journal = Journal::create([
                'journal_number' => $validated['journal_number'],
                'journal_date' => $validated['journal_date'],
                'description' => $validated['description'] ?? null,
                'source' => 'manual',
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'created_by' => auth()->id(),
            ]);
            foreach ($validated['entries'] as $entry) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $entry['account_id'],
                    'description' => $entry['description'] ?? null,
                    'debit' => $entry['debit'] ?? 0,
                    'credit' => $entry['credit'] ?? 0,
                ]);
            }
        });

        return redirect()->route('akuntansi.journals.index')->with('success', 'Jurnal berhasil dicatat.');
    }

    public function show(Journal $journal)
    {
        $journal->load('entries.account');

        return view('pages.akuntansi.journals.show', compact('journal'));
    }
}
