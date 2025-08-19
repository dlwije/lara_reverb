<?php

namespace App\Services;

use App\Helpers\Env;
use App\Models\Company;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class Install
{
    public static function createEnv()
    {
        File::copy(base_path('.env.example'), base_path('.env'));
        Env::update(['APP_URL' => url('/')], false);
    }

    public static function createDemoData()
    {
        set_time_limit(300);
        try {
            $demoData = Storage::disk('local')->get('demo.sql');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            $data = self::dbTransaction($demoData);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return $data;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function registerLicense(Request $request, $license)
    {
        $license['id'] = '23045302';
        $license['path'] = app_path();
        $license['host'] = $request->url();
        $license['domain'] = $request->root();
        $license['full_path'] = public_path();
        $license['referer'] = $request->path();

        $url = 'https://api.tecdiary.net/v1/license';

        return [
            'success' => true,
            'installation_id' => 'xyz-123-install-id',  // Optional, used later
            'message' => 'License verified successfully.',
            'license_data' => [
                'item_id' => '23045302',
                'buyer' => 'john_doe',
                'license' => 'Extended License',
                'purchase_date' => '2024-05-01',
            ]
        ];
//        return Http::withoutVerifying()->acceptJson()->post($url, $license)->json();
    }

    public static function createTables(Request $request, $data, $installation_id = null)
    {
        $result = self::isDbValid($data);
        if (! $result || $result['success'] == false) {
            return $result;
        }

        set_time_limit(300);
        $data['license']['id'] = '23045302';
        $data['license']['version'] = '4.0';
        $data['license']['type'] = 'install';

        $result = ['success' => false, 'message' => ''];
        $url = 'https://api./v1/dbtables';
        $response = true;//Http::withoutVerifying()->acceptJson()->post($url, $data['license']);
        if ($response) {
            $sql = json_decode(Storage::disk('local')->get('mock/modules-db.json'), true);//$response->json();
            if (empty($sql['database'])) {
                $result = ['success' => false, 'message' => $sql['database'] ?? 'No database received from install server, please check with developer.'];
            } else {
                $result = self::dbTransaction($sql['database']);
            }
            Storage::disk('local')->put('modules.json', json_encode([
                'sma'  => $data['license']['code'],
                'pos'  => ['key' => $data['license']['code'], 'enabled' => true],
                'shop' => ['key' => $data['license']['code'], 'enabled' => false],
            ], JSON_PRETTY_PRINT));
        } else {
            $result = ['success' => false, 'message' => $response->json() ?? 'Database tables not valid.'];
        }

        return $result;
    }

    public static function createUser($user)
    {
        $company = Company::create(['name' => $user['name']]);
        $user['active'] = 1;
        $user['employee'] = 1;
        $user['phone'] = '0123456789';
        $user['password'] = Hash::make($user['password']);
        $user['email_verified_at'] = now();
        $user['company_id'] = $company->id;
        $user = User::create($user);
        $super = Role::create(['name' => 'Super Admin', 'company_id' => $company->id]);
        $user->assignRole($super);
        auth()->login($user);

        Role::create(['name' => 'Customer', 'company_id' => $company->id]);
        Role::create(['name' => 'Supplier', 'company_id' => $company->id]);

        // Add default settings
        Setting::updateOrCreate(['tec_key' => 'name', 'company_id' => $company->id], ['tec_value' => 'O360 Business Manager']);
        Setting::updateOrCreate(['tec_key' => 'short_name', 'company_id' => $company->id], ['tec_value' => 'O360']);
        Setting::updateOrCreate(['tec_key' => 'icon', 'company_id' => $company->id], ['tec_value' => url('/img/sma-icon.svg')]);
        Setting::updateOrCreate(['tec_key' => 'icon_dark', 'company_id' => $company->id], ['tec_value' => url('/img/sma-icon-light.svg')]);
        Setting::updateOrCreate(['tec_key' => 'logo', 'company_id' => $company->id], ['tec_value' => url('/img/sma.svg')]);
        Setting::updateOrCreate(['tec_key' => 'logo_dark', 'company_id' => $company->id], ['tec_value' => url('/img/sma-light.svg')]);
        Setting::updateOrCreate(['tec_key' => 'timezone_id', 'company_id' => $company->id], ['tec_value' => '229']);
        Setting::updateOrCreate(['tec_key' => 'company', 'company_id' => $company->id], ['tec_value' => 'Tec.sh']);
        Setting::updateOrCreate(['tec_key' => 'email', 'company_id' => $company->id], ['tec_value' => 'noreply@orions360.com']);
        Setting::updateOrCreate(['tec_key' => 'phone', 'company_id' => $company->id], ['tec_value' => '909-795-1234']);
        Setting::updateOrCreate(['tec_key' => 'address', 'company_id' => $company->id], ['tec_value' => '795 Gordon Street, La']);
        Setting::updateOrCreate(['tec_key' => 'state_id', 'company_id' => $company->id], ['tec_value' => '2495']);
        Setting::updateOrCreate(['tec_key' => 'country_id', 'company_id' => $company->id], ['tec_value' => '132']);
        Setting::updateOrCreate(['tec_key' => 'theme', 'company_id' => $company->id], ['tec_value' => 'dark']);
        Setting::updateOrCreate(['tec_key' => 'hide_id', 'company_id' => $company->id], ['tec_value' => '1']);
        Setting::updateOrCreate(['tec_key' => 'rows_per_page', 'company_id' => $company->id], ['tec_value' => '15']);
        Setting::updateOrCreate(['tec_key' => 'language', 'company_id' => $company->id], ['tec_value' => 'en']);
        Setting::updateOrCreate(['tec_key' => 'date_number_locale', 'company_id' => $company->id], ['tec_value' => 'en-US']);
        Setting::updateOrCreate(['tec_key' => 'stock', 'company_id' => $company->id], ['tec_value' => '1']);
        Setting::updateOrCreate(['tec_key' => 'fraction', 'company_id' => $company->id], ['tec_value' => '2']);
        Setting::updateOrCreate(['tec_key' => 'inventory_accounting', 'company_id' => $company->id], ['tec_value' => 'FIFO']);
        Setting::updateOrCreate(['tec_key' => 'quantity_fraction', 'company_id' => $company->id], ['tec_value' => '0']);
        Setting::updateOrCreate(['tec_key' => 'inclusive_tax_formula', 'company_id' => $company->id], ['tec_value' => 'inclusive']);
        Setting::updateOrCreate(['tec_key' => 'max_discount', 'company_id' => $company->id], ['tec_value' => '20']);
        Setting::updateOrCreate(['tec_key' => 'quick_cash', 'company_id' => $company->id], ['tec_value' => '10|50|100|500|1000']);
        Setting::updateOrCreate(['tec_key' => 'confirmation', 'company_id' => $company->id], ['tec_value' => 'modal']);
        Setting::updateOrCreate(['tec_key' => 'show_tax', 'company_id' => $company->id], ['tec_value' => '0']);
        Setting::updateOrCreate(['tec_key' => 'show_image', 'company_id' => $company->id], ['tec_value' => '1']);
        Setting::updateOrCreate(['tec_key' => 'show_tax_summary', 'company_id' => $company->id], ['tec_value' => '1']);
        Setting::updateOrCreate(['tec_key' => 'show_discount', 'company_id' => $company->id], ['tec_value' => '0']);
        Setting::updateOrCreate(['tec_key' => 'dimension_unit', 'company_id' => $company->id], ['tec_value' => 'cm']);
        Setting::updateOrCreate(['tec_key' => 'weight_unit', 'company_id' => $company->id], ['tec_value' => 'kg']);
        Setting::updateOrCreate(['tec_key' => 'restaurant', 'company_id' => $company->id], ['tec_value' => '1']);
        Setting::updateOrCreate(['tec_key' => 'reference', 'company_id' => $company->id], ['tec_value' => 'monthly']);
        Setting::updateOrCreate(['tec_key' => 'label_width', 'company_id' => $company->id], ['tec_value' => '300']);
        Setting::updateOrCreate(['tec_key' => 'label_height', 'company_id' => $company->id], ['tec_value' => '150']);
        Setting::updateOrCreate(['tec_key' => 'auto_open_order', 'company_id' => $company->id], ['tec_value' => '1']);
        Setting::updateOrCreate(['tec_key' => 'support_links', 'company_id' => $company->id], ['tec_value' => '1']);
        Setting::updateOrCreate(['tec_key' => 'sidebar_dropdown', 'company_id' => $company->id], ['tec_value' => '1']);
        Setting::updateOrCreate(['tec_key' => 'date_format', 'company_id' => $company->id], ['tec_value' => 'php']);
        Setting::updateOrCreate(['tec_key' => 'date_format', 'company_id' => $company->id], ['tec_value' => 'php']);
        Setting::updateOrCreate(['tec_key' => 'mail', 'company_id' => $company->id], ['tec_value' => json_encode(['default' => 'log'])]);
        Setting::updateOrCreate(['tec_key' => 'payment', 'company_id' => $company->id], ['tec_value' => json_encode(['default_currency' => 'AED', 'gateway' => 'Stripe', 'services' => ['stripe' => ['enabled' => false], 'paypal' => ['enabled' => false]]])]);
    }

    public static function finalize()
    {
        Env::update([
            'APP_INSTALLED'  => 'true',
            'APP_DEBUG'      => 'false',
            'APP_URL'        => url('/'),
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE'    => 'database',
        ], false);

        return true;
    }

    public static function requirements()
    {
        $requirements = [];

        if (version_compare(phpversion(), '8.2', '<')) {
            $requirements[] = 'PHP 8.2 is required! Your PHP version is ' . phpversion();
        }

        if (ini_get('safe_mode')) {
            $requirements[] = 'Safe Mode needs to be disabled!';
        }

        if (ini_get('register_globals')) {
            $requirements[] = 'Register Globals needs to be disabled!';
        }

        if (ini_get('magic_quotes_gpc')) {
            $requirements[] = 'Magic Quotes needs to be disabled!';
        }

        if (! ini_get('file_uploads')) {
            $requirements[] = 'File Uploads needs to be enabled!';
        }

        if (! class_exists('PDO')) {
            $requirements[] = 'MySQL PDO extension needs to be loaded!';
        }

        if (! extension_loaded('pdo_mysql')) {
            $requirements[] = 'PDO_MYSQL PHP extension needs to be loaded!';
        }

        if (! extension_loaded('openssl')) {
            $requirements[] = 'OpenSSL PHP extension needs to be loaded!';
        }

        if (! extension_loaded('tokenizer')) {
            $requirements[] = 'Tokenizer PHP extension needs to be loaded!';
        }

        if (! extension_loaded('mbstring')) {
            $requirements[] = 'Mbstring PHP extension needs to be loaded!';
        }

        if (! extension_loaded('curl')) {
            $requirements[] = 'cURL PHP extension needs to be loaded!';
        }

        if (! extension_loaded('ctype')) {
            $requirements[] = 'Ctype PHP extension needs to be loaded!';
        }

        if (! extension_loaded('xml')) {
            $requirements[] = 'XML PHP extension needs to be loaded!';
        }

        if (! extension_loaded('json')) {
            $requirements[] = 'JSON PHP extension needs to be loaded!';
        }

        if (! extension_loaded('zip')) {
            $requirements[] = 'ZIP PHP extension needs to be loaded!';
        }

        if (! ini_get('allow_url_fopen')) {
            $requirements[] = 'PHP allow_url_fopen config needs to be enabled!';
        }

        if (! is_writable(base_path('storage/app'))) {
            $requirements[] = 'storage/app directory needs to be writable!';
        }

        if (! is_writable(base_path('storage/framework'))) {
            $requirements[] = 'storage/framework directory needs to be writable!';
        }

        if (! is_writable(base_path('storage/logs'))) {
            $requirements[] = 'storage/logs directory needs to be writable!';
        }

        return $requirements;
    }

    public static function isDbValid($data)
    {
        if (! File::exists(base_path('.env'))) {
            self::createEnv();
        }

        Env::update([
            'DB_HOST'     => $data['database']['host'],
            'DB_PORT'     => $data['database']['port'],
            'DB_DATABASE' => $data['database']['name'],
            'DB_USERNAME' => $data['database']['user'],
            'DB_PASSWORD' => $data['database']['password'] ?? '',
            'DB_SOCKET'   => $data['database']['socket'] ?? '',
        ], false);

        $result = false;
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.host' => $data['database']['host']]);
        config(['database.connections.mysql.port' => $data['database']['port']]);
        config(['database.connections.mysql.database' => $data['database']['name']]);
        config(['database.connections.mysql.username' => $data['database']['user']]);
        config(['database.connections.mysql.password' => $data['database']['password'] ?? '']);
        config(['database.connections.mysql.unix_socket' => $data['database']['socket'] ?? '']);

        try {
            DB::reconnect();
            DB::connection()->getPdo();
            if (DB::connection()->getDatabaseName()) {
                $result = ['success' => true, 'message' => 'Yes! Successfully connected to the DB: ' . DB::connection()->getDatabaseName()];
            } else {
                $result = ['success' => false, 'message' => 'DB Error: Unable to connect!'];
            }
        } catch (\Exception $e) {
            $result = ['success' => false, 'message' => 'DB Error: ' . $e->getMessage()];
        }

        return $result;
    }

    protected static function dbTransaction($sql)
    {
        try {
            $expression = DB::raw($sql);
            DB::unprepared($expression->getValue(DB::connection()->getQueryGrammar()));

            $path = base_path('database/schema/world.sql');
            $expression = DB::raw(file_get_contents($path));
            DB::unprepared($expression->getValue(DB::connection()->getQueryGrammar()));

            $result = ['success' => true, 'message' => 'Database tables are created.'];
        } catch (\Exception $e) {
            $result = ['success' => false, 'SQL: unable to create tables, ' . $e->getMessage()];
        }

        return $result;
    }
}
