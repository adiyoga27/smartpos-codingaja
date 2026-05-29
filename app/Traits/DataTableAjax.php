<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait DataTableAjax
{
    public function dataTableAjax(Request $request, $query, array $columns): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $search = $request->input('search.value', '');
        $order = $request->input('order', []);

        $total = $query->count();

        if ($search) {
            $query->where(function ($q) use ($columns, $search) {
                $first = true;
                foreach ($columns as $col) {
                    if (str_contains($col, '.') || str_contains($col, '(')) {
                        continue;
                    }
                    if ($first) {
                        $q->where($col, 'like', '%'.$search.'%');
                        $first = false;
                    } else {
                        $q->orWhere($col, 'like', '%'.$search.'%');
                    }
                }
            });
        }

        $filtered = $query->count();

        if (! empty($order)) {
            $orderCol = $columns[$order[0]['column']] ?? $columns[0];
            $orderDir = $order[0]['dir'] ?? 'desc';
            if (! str_contains($orderCol, '.') && ! str_contains($orderCol, '(')) {
                $query->orderBy($orderCol, $orderDir);
            }
        }

        $data = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    public function dataTableAjaxPaginate(Request $request, $query, array $columns, string $resourceRoute = '', array $actions = []): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $search = $request->input('search.value', '');
        $order = $request->input('order', []);

        $total = $query->count();

        if ($search) {
            $query->where(function ($q) use ($columns, $search) {
                $first = true;
                foreach ($columns as $col) {
                    if (str_contains($col, '.') || str_contains($col, '(') || str_contains($col, '?')) {
                        continue;
                    }
                    if ($first) {
                        $q->where($col, 'like', '%'.$search.'%');
                        $first = false;
                    } else {
                        $q->orWhere($col, 'like', '%'.$search.'%');
                    }
                }
            });
        }

        $filtered = $query->count();

        if (! empty($order)) {
            $orderCol = $columns[$order[0]['column']] ?? $columns[0];
            $orderDir = $order[0]['dir'] ?? 'desc';
            if (! str_contains($orderCol, '.') && ! str_contains($orderCol, '(')) {
                $query->orderBy($orderCol, $orderDir);
            }
        }

        $items = $query->skip($start)->take($length)->get();

        $data = [];
        foreach ($items as $item) {
            $row = [];
            foreach ($columns as $col) {
                if (str_starts_with($col, 'fn:')) {
                    $fnName = substr($col, 3);
                    $row[] = $actions[$fnName]($item) ?? '';
                } elseif ($col === 'id') {
                    $row[] = $item->id;
                } elseif ($col === 'DT_RowId') {
                    $row[] = '';
                } else {
                    $row[] = data_get($item, $col) ?? '';
                }
            }
            $data[] = $row;
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }
}
