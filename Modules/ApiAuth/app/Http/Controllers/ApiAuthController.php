<?php

namespace Modules\ApiAuth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\HelperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Modules\ApiAuth\Actions\SaveUser;
use Modules\ApiAuth\Enums\RoleEnum;
use Modules\ApiAuth\Events\UserRegisteredEvent;
use Modules\ApiAuth\Http\Requests\UserRequest;
use Modules\ApiAuth\Services\AuthTokenService;
use Spatie\Permission\PermissionRegistrar;
use Yajra\DataTables\DataTables;

class ApiAuthController extends Controller
{
    public function __construct(public AuthTokenService $tokenService){}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('users/index');
    }

    public function userList()
    {
        try {
            $query = User::select([
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'users.user_type',
                'users.updated_at',
                DB::raw('GROUP_CONCAT(DISTINCT roles.name SEPARATOR ", ") as role_name')
            ])
                ->leftJoin('model_has_roles', function ($join) {
                    $join->on('users.id', '=', 'model_has_roles.model_id')
                        ->where('model_has_roles.model_type', '=', User::class);
                })
                ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('users.id', '!=', auth('api')->id()) // 👈 Exclude logged-in user
                ->groupBy('users.id');

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('updated_at', function ($user) {
                    return \Carbon\Carbon::parse($user->updated_at)->format('Y-m-d');
                })

                ->addColumn('role', function ($user) {
                    return $user->role_name ?? '-';
                })

                ->filterColumn('updated_at', function ($query, $keyword) {
                    $query->whereRaw("DATE_FORMAT(users.updated_at, '%Y-%m-%d') like ?", ["%$keyword%"]);
                })

                ->removeColumn('email_verified_at', 'created_at', 'role_name')
                ->make(true);

        } catch (\Exception $e) {
            Log::error('User List Getting Error:', ['error' => $e]);
            return self::error(__('messages.something_went_wrong'), 500);
        }
    }

    public function login(Request $request)
    {
        // Validate request input (lightweight in-controller; move to FormRequest if preferred)
        $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        // Fetch user first so we can detect social-created accounts
        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();

        if (! $user) {

            return self::error(__('messages.invalid_credentials'), 401);
        }

        // Example: Set the current team (team_id = 1)
        app(PermissionRegistrar::class)->setPermissionsTeamId((new HelperService())->system_user_team_id);;

        // **Role Check** (adjust roles as needed)
        $allowedRoles = [RoleEnum::sys_admin->name, RoleEnum::sys_subadmin->name]; // allowed roles
        if (! $user->hasAnyRole($allowedRoles)) {

            HelperService::logLogin($request, null, 'email', false);
            return self::error(__('messages.invalid_credentials'), 401);
        }

        // If user was created via social login and has NO password stored
        // (null, empty string, or length < 4 hashed? depending on legacy data)
        if (empty($user->password)) {
            return self::error([
                'requires_password_setup' => true,
                'message' => __('messages.password_required_for_email_login'), // add to lang file
                'email' => $user->email,
            ], 409); // Conflict: cannot login yet
        }

        // Quick local password check before hitting Auth::attempt() (faster, clearer errors)
        if (! Hash::check($request->password, $user->password)) {
            return self::error(__('messages.invalid_credentials'), 401);
        }

        // Attempt to authenticate through the guard (optional; we already validated password)
        if (! Auth::attempt(['email' => $request->email, 'password' => $request->password])) {

            HelperService::logLogin($request, null, 'email', false);
            // Guard rejected (status inactive? locked? etc.) — treat as invalid
            return self::error(__('messages.invalid_credentials'), 401);
        }

        try {
            // Issue Passport token through your existing service
            $tokenData = $this->tokenService->issueTokenViaPasswordGrant(
                $request->email,
                $request->password
            );

            HelperService::logLogin($request, $user);
            return $this->tokenService->respondWithToken($tokenData, null, $user);
        } catch (\Throwable $e) {
            // Log if desired: Log::error('Login token issue', ['error' => $e->getMessage()]);
            return self::error(__('messages.validation_error'), 500, $e->getMessage());
        }
    }

    public function register(UserRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        // Ensure roles are provided or fall back to ['customer']
        $data['roles'] = $request->input('roles', [RoleEnum::customer->name]);;
        $data['team_id'] = $data['team_id'] ?? 1; // or get team ID dynamically
        $data['login_type'] = 'email';

        DB::beginTransaction();
        try {
            $user = (new SaveUser)->execute($data);
//            $this->createGallery($user);

            /** @var User $user */
            $user = User::where('email', $request->email)->first();

            // Load roles & permissions using Spatie
            $user->load('roles', 'permissions');

            $user->sendEmailVerificationNotification();

            $tokenData = $this->tokenService->issueTokenViaPasswordGrant($request->email, $request->password);

            event(new UserRegisteredEvent($user));

            HelperService::logLogin($request, $user);

            DB::commit();
            return $this->tokenService->respondWithToken($tokenData, 'register', $user);
        }catch (\Exception $e) {
            HelperService::logLogin($request, null, 'email', false);
            DB::rollBack();
            return self::error(__('messages.validation_error'), 500, $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('apiauth::create');
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
        return view('apiauth::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('apiauth::edit');
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
