<?php

namespace Modules\Workorder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ServiceStation\CustomerSMA;
use App\Models\ServiceStation\Vehicle;
use App\Models\ServiceStation\WorkOrder;
use App\Services\ControllerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Yajra\DataTables\DataTables;

class WorkorderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('workorder/index');
    }

    public function getDataTableList(Request $request)
    {

        if ($request->ajax()) {
            $wo_tbl_name = (new WorkOrder())->getTable();
            $cus_tbl_name = (new CustomerSMA())->getTable();
            $vehi_tbl_name = (new Vehicle())->getTable();

            $data = WorkOrder::query()
                ->select($wo_tbl_name . '.*', $cus_tbl_name . '.name', $vehi_tbl_name . '.vehicle_no', $vehi_tbl_name . '.engine_no', DB::raw($vehi_tbl_name . '.engine_no as vinNo'))
                ->leftJoin($cus_tbl_name, $cus_tbl_name . '.id', '=', $wo_tbl_name . '.customer_id')
                ->leftJoin($vehi_tbl_name, $vehi_tbl_name . '.id', '=', $wo_tbl_name . '.vehicle_id')
                ->where($wo_tbl_name . '.wo_type', '=', 0)
                ->where($wo_tbl_name . '.is_active', 1);

            try {
                return Datatables::of($data)
                    ->editColumn('updated_at', function ($user) {
                        return $user->updated_at->format('Y-m-d');
                    })
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {

                        $actionBtn = '';
//                        if (auth()->user()->hasPermissionTo('deposit'))
//                        $actionBtn .= '<span onclick="workOrderList.ivm.showDepositView(' . $row->id . ')" class="btn btn-success btn-sm mr-1" data-toggle="tooltip" data-placement="top" title="Add deposits."><i class="fas fa-dollar-sign"></i></span>';
//                        if(auth()->user()->hasPermissionTo('workorder-view') )
//                            $actionBtn .= '<span onclick="workOrderList.ivm.showDetails('.$row->id.')" class="btn btn-success btn-sm mr-1" data-toggle="tooltip" data-placement="top" title="View workorder."><i class="fas fa-eye"></i></span>';
//                        if (auth()->user()->hasPermissionTo('workorder-trans-est'))
//                        $actionBtn .= '<span onclick="workOrderList.ivm.transferToEstimate(' . $row->id . ')" class="btn btn-warning btn-sm mr-1" data-toggle="tooltip" data-placement="top" title="Transfer workorder to estimate."><i class="fas fa-exchange-alt"></i></span>';
//                        if (auth()->user()->hasPermissionTo('update-workorders'))
//                            $actionBtn .= '<a href="' . route('workorder.edit', $row->id) . '" class="delete btn btn-primary btn-sm mr-1" data-toggle="tooltip" data-placement="top" title="Edit workorder."><i class="fas fa-edit"></i></a>';
//                        if (auth()->user()->hasPermissionTo('delete-workorders'))
//                            $actionBtn .= '<span onclick="workOrderList.ivm.deleteRecord(' . $row->id . ')" class="btn btn-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Delete workorder."><i class="fas fa-trash-alt"></i></span>';

                        return $actionBtn;
                    })
                    ->addColumn('woStatus', function ($row) {
                        if ($row->wo_status == 0)
                            $state = 'Pending';// Just started WO by collecting data from customer
                        elseif ($row->wo_status == 1)
                            $state = 'APRVDEST';// Approved WO estimate before work commencement
                        elseif ($row->wo_status == 2)
                            $state = 'APRVEDCTMR';// Estimate approved by the customer
                        elseif ($row->wo_status == 3)
                            $state = 'Ongoing';// WO assigned to labor
                        elseif ($row->wo_status == 4)
                            $state = 'Halted';// WO halt after assigned to labor lack of parts etc...
                        elseif ($row->wo_status == 5)
                            $state = 'CMLTDLBR';// WO completed by labor
                        elseif ($row->wo_status == 6)
                            $state = 'APRVEDMNGR';// WO approved by service manager after completed by labor
                        elseif ($row->wo_status == 7)
                            $state = 'DAPRVEDMNGR';// WO invoice paid by the customer
                        else
                            $state = '-';// no=8, WO has no state, and it is an estimate
                        return $state;
                    })
                    ->addColumn('woType', function ($row) {
                        if ($row->wo_type == 0)
                            $state = '<span class="text-black badge badge-secondary" data-toggle="tooltip" data-placement="top" title="Workorder">WO</span>';// Just WO
                        elseif ($row->wo_type == 1)
                            $state = '<span class="text-black badge badge-primary" data-toggle="tooltip" data-placement="top" title="Warranty Workorder">WWO</span>';// Warranty WO
                        elseif ($row->wo_type == 2)
                            $state = '<span class="text-black badge badge-light badge-success" data-toggle="tooltip" data-placement="top" title="Estimate">EST</span>';// Estimate
                        else
                            $state = '<span class="text-white badge badge-secondary">-</span>';// WO has no state
                        return $state;
                    })
                    ->rawColumns(['action', 'woStatus', 'woType'])
                    ->removeColumn('created_at')
                    ->filterColumn('updated_at', function ($query, $keyword) {
                        $query->whereRaw("DATE_FORMAT(" . (new WorkOrder())->getTable() . ".updated_at, '%Y-%m-%d') like ?", ["%$keyword%"]);
                    })
                    ->filterColumn('name', function ($query, $keyword) {
                        $query->whereRaw((new CustomerSMA())->getTable() . ".name like ?", ["%$keyword%"]);
                    })
                    ->filterColumn('vehicle_no', function ($query, $keyword) {
                        $query->whereRaw((new Vehicle())->getTable() . ".vehicle_no like ?", ["%$keyword%"]);
                    })
                    ->filterColumn('engine_no', function ($query, $keyword) {
                        $query->whereRaw((new Vehicle())->getTable() . ".engine_no like ?", ["%$keyword%"]);
                    })
                    ->make(true);
            } catch (\Exception $e) {
                Log::error("getWorkOrderError:" . $e);
                return (new ControllerService())->responseDataJson(false, ['Somethings went wrong'], 500);
            }
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('workorder::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('workorder::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('workorder::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
