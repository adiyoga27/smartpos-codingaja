<?php

namespace App\Http\Controllers\Api;

use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Journal::with(['entries.account', 'creator'])->latest();

        if ($request->filled('from')) {
            $query->whereDate('journal_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('journal_date', '<=', $request->to);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('search')) {
            $query->where('journal_number', 'like', '%'.$request->search.'%');
        }

        return $this->paginate($query);
    }

    public function store(Request $request): JsonResponse
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

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($validated['entries'] as $entry) {
            $totalDebit += $entry['debit'] ?? 0;
            $totalCredit += $entry['credit'] ?? 0;
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return $this->error('Total debit dan kredit harus seimbang.', 422, [
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'difference' => abs($totalDebit - $totalCredit),
            ]);
        }

        $journal = null;

        DB::transaction(function () use ($validated, $totalDebit, $totalCredit, &$journal) {
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

        return $this->created($journal->load(['entries.account', 'creator']), 'Jurnal berhasil dibuat.');
    }

    public function show(Journal $journal): JsonResponse
    {
        return $this->success($journal->load(['entries.account', 'creator']));
    }
}
