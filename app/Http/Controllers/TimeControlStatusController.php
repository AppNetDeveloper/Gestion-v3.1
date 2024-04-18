<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\TimeControlStatus;

class TimeControlStatusController extends Controller
{
   
    public function index(Request $request)
    {
        $breadcrumbsItems = [
            [
                'name' => 'Settings',
                'url' => '/controltime',
                'active' => false
            ],
            [
                'name' => 'ControlTime',
                'url' => route('controltime.index'),
                'active' => true
            ],
        ];

        $q = $request->get('q');
        $perPage = $request->get('per_page', 10);
        $sort = $request->get('sort');

        $timeControlStatus = QueryBuilder::for(TimeControlStatus::class)
            ->allowedSorts(['table_name', 'created_at'])
            ->where('table_name', 'like', "%$q%")
            ->latest()
            ->paginate($perPage)
            ->appends(['per_page' => $perPage, 'q' => $q, 'sort' => $sort]);

        return view('controltime.index', [
            'breadcrumbItems' => $breadcrumbsItems,
            'timeControlStatus' => $timeControlStatus,
            'pageTitle' => 'timeControlStatus'
        ]);
        //$timeControlStatus = TimeControlStatus::all();
           // return view('controltime.index', compact('timeControlStatus'));
    }

}
