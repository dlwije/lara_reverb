<?php

namespace App\Http\Controllers\Installer;

use App\Services\Install;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallController extends Controller
{
    public function __construct()
    {
        // If the 'APP_INSTALLED' true it will redirect
        if (env('APP_INSTALLED', false)) {
            return redirect('/');
        }
    }

    public function index()
    {
        if (! File::exists(base_path('.env'))) {
            Install::createEnv();
        }

        return view('installer::index');
    }

    public function demo(Request $request)
    {
        if (! $request->done) {
            return response()->json(['success' => false, 'message' => 'Unable to create demo data!'], 422);
        }
        Install::createDemoData();

        return response()->json(['success' => true, 'message' => 'Demo data has been created.'], 200);
    }

    public function save(Request $request)
    {
        $v = $request->validate([
            'license.username'              => 'required',
            'license.code'                  => 'required|uuid',
            'database.host'                 => 'required',
            'database.port'                 => 'required|numeric',
            'database.user'                 => 'required',
            'database.password'             => 'nullable',
            'database.name'                 => 'required',
            'database.socket'               => 'nullable',
            'mail.driver'                   => 'nullable|required|in:log,smtp,sendmail',
            'mail.host'                     => 'nullable|required_if:mail.driver,smtp',
            'mail.port'                     => 'nullable|required_if:mail.driver,smtp|numeric',
            'mail.username'                 => 'nullable',
            'mail.password'                 => 'nullable',
            'mail.path'                     => 'nullable|required_if:mail.driver,sendmail',
            'account.name'                  => 'required',
            'account.username'              => 'required|alpha_num',
            'account.email'                 => 'required|email',
            'account.password'              => 'required|confirmed',
            'account.password_confirmation' => 'required',
        ]);

        $v['license']['ip'] = $request->ip();
        $data = Install::registerLicense($request, $v['license']);
        if ($data && isset($data['success']) && $data['success'] == true) {
            $res = Install::createTables($request, $v, $data['installation_id'] ?? null);

            if (! $res || !$res['success']) {
                $error = null;
                if (isset($res['0']) && ! empty($res['0'])) {
                    $error = Str::before($res['0'], '(SQL:');
                }

                return response()->json(['success' => false, 'message' => $error ?? $res['message'] ?? 'Could not connect to the database! Please make sure the details are correct.'], 422);
            }

            // Install::updateMailSettings($v);
            Install::createUser($v['account']);

            return response()->json(['success' => true, 'message' => $res['message'] ? $res['message'] : 'Database tables created and settings has been saved.'], 200);
        }

        return response()->json(['success' => false, 'message' => $data['message'] ?? 'Could not verify your purchase! Please make sure the details are correct.'], 422);
    }

    public function show()
    {
        $requirements = Install::requirements();
        if (! File::exists(base_path('.env'))) {
            Install::createEnv();
        }

        if (! empty($requirements)) {
            return response()->json(['success' => false, 'message' => 'Please check the following server requirement', 'errors' => $requirements]);
        }

        return response()->json(['success' => true, 'message' => 'Your server php version and extensions are fine. Please proceed to next step!']);
    }

    public function finalize(Request $request)
    {
        if (! $request->done) {
            return response()->json(['success' => false, 'message' => 'Installation can not be finalized!'], 422);
        }
        Install::finalize();
        Artisan::call('storage:link --force');
        Artisan::call('key:generate --force');

        return response()->json(['success' => true, 'message' => 'Installation has been finalized.'], 200);
    }
}
