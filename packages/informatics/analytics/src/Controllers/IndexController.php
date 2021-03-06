<?php

namespace Informatics\Analytics\Controllers;

use App\Helpers\BasicHelper;
use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Informatics\Base\Models\Fee;
use Informatics\Tool\Models\Tool;
use Sentinel;
use Informatics\Key\Models\Key;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index(Request $request)
    {

        $userId = isset($request->user_id) ? $request->user_id : -1;
        $action = isset($request->action) ? $request->action : -1;
        $tool = isset($request->tool) ? $request->tool : -1;
        $fromDate = isset($request->from_date) ? $request->from_date : '';
        $toDate = isset($request->to_date) ? $request->to_date : '';
        $userLoginId = BasicHelper::getUserDetails()->id;
        $tools = Tool::all();

        // role 2 ( agency )
        $role = Sentinel::findRoleById(2);
        $users = $role->users()->with('roles')->get();

        $query = app(Fee::class)->newQuery()->with(['tool', 'user']);

        $query = $query->where(function ($query) use ($userId, $action, $tool, $fromDate, $toDate) {
            if ($userId != -1) {
                $query->where('user_id', '=', $userId);
            }
            if ($action != -1) {
                $query->where('action', '=', $action);
            }
            if ($tool != -1) {
                $query->where('tool_id', '=', $tool);
            }
            if ($fromDate != '') {
                $query->where('created_at', '>=', $fromDate);
            }
            if ($toDate != '') {
                $query->where('created_at', '<=', $toDate);
            }
        });
        $fees = $query->select('fees.*')->get();

        $totalValue = 0;
        if(count($fees) > 0){
            foreach ($fees as $fee){
                $totalValue += $fee['value'];
            }
        }
        return view('analytics::index.index', compact('fees', 'users', 'userId', 'action', 'tools', 'fromDate', 'toDate', 'totalValue'));
    }

    public function edit($id)
    {
        $fee = Fee::where('id', $id)->first();
        return view('analytics::create.create', compact('fee'));
    }

    public function update(Request $request, $id)
    {
        try {
            $fee = $request->only('value');
            $fee = Fee::where('id', $id)->limit(1)->update($fee);
            if ($fee) {
                return Redirect::back()
                    ->withMessage('Cập nhật thông tin thành công');
            }
        } catch (\Exception $ex) {
            return redirect('manager/analytics')
                ->with('error_message', $ex->getMessage());
        }
    }
}
