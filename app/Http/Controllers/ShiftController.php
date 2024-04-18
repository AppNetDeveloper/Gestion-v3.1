<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Shift;

class ShiftController extends Controller
{
   
    public function index(Request $request)
    {
        $breadcrumbsItems = [
            [
                'name' => 'Settings',
                'url' => '/shift',
                'active' => false
            ],
            [
                'name' => 'Shift',
                'url' => route('shift.index'),
                'active' => true
            ],
        ];

        $q = $request->get('q');
        $perPage = $request->get('per_page', 10);
        $sort = $request->get('sort');

        $shift = QueryBuilder::for(Shift::class)
            ->allowedSorts(['name', 'created_at'])
            ->where('name', 'like', "%$q%")
            ->latest()
            ->paginate($perPage)
            ->appends(['per_page' => $perPage, 'q' => $q, 'sort' => $sort]);

        return view('shift.index', [
            'breadcrumbItems' => $breadcrumbsItems,
            'shift' => $shift,
            'pageTitle' => 'shift'
        ]);
        //$timeControlStatus = TimeControlStatus::all();
           // return view('controltime.index', compact('timeControlStatus'));
    }

}