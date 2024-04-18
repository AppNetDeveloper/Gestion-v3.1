<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\TimeControlStatusRules;

class TimeControlStatusRulesController extends Controller
{
   
    public function index(Request $request)
    {
        $breadcrumbsItems = [
            [
                'name' => 'Settings',
                'url' => '/controltimerules',
                'active' => false
            ],
            [
                'name' => 'ControlTime',
                'url' => route('controltimerules.index'),
                'active' => true
            ],
        ];

        $q = $request->get('q');
        $perPage = $request->get('per_page', 10);
        $sort = $request->get('sort');

        $timeControlStatusRules = QueryBuilder::for(TimeControlStatusRules::class)
            ->allowedSorts(['time_control_status_id', 'created_at'])
            ->where('time_control_status_id', 'like', "%$q%")
            ->latest()
            ->with(['timeControlStatus', 'permission']) // Cargar la relación
            ->paginate($perPage)
            ->appends(['per_page' => $perPage, 'q' => $q, 'sort' => $sort]);
    

        return view('controltimerules.index', [
            'breadcrumbItems' => $breadcrumbsItems,
            'timeControlStatusRules' => $timeControlStatusRules,
            'pageTitle' => 'timeControlStatusRules'
        ]);
        //$timeControlStatus = TimeControlStatus::all();
           // return view('controltimerules.index', compact('timeControlStatusRules'));
    }

}