<?php

namespace App\Http\Controllers\Installer;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

class ModuleController extends Controller
{
    public function index()
    {
        cache()->forget('sma_modules');
        $modules_status = get_module();

        $modules = [
            'pos' => [
                'enabled' => $modules_status['pos']['enabled'] ?? false,
            ],
            'shop' => [
                'enabled' => $modules_status['shop']['enabled'] ?? false,
            ],
        ];
        $shop_module = File::exists(base_path('modules/Shop'));

        return view('installer::modules.index', compact('modules', 'shop_module'));
    }

    public function disable(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|in:pos,shop',
        ]);

        try {
            disable_module($v['name']);

            return back()->with('message', 'Module has successfully disabled!');
        } catch (\Exception $e) {
            throw new Exception('Failed to disable module: ' . $e->getMessage());
        }
    }

    public function enable(Request $request)
    {
        $v = $request->validate([
            'code' => 'required|uuid',
            'name' => 'required|in:pos,shop',
        ]);

        try {
            enable_module($v['name'], $v['code']);

            return back()->withInput($request->only('code', 'name'))
                ->with('message', 'Module has successfully enabled!');
        } catch (\Exception $e) {
            return back()->withInput($request->only('code', 'name'))
                ->with('error', $e->getMessage());
        }
    }
}
