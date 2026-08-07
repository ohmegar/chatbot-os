<?php

// use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\GeminiAdminController;
use App\Http\Controllers\KeycloakController;
use App\Http\Controllers\User\GeminiUserController;
use Illuminate\Support\Facades\Route;


// หน้าแจ้งเตือน IE
Route::get('/63322a3f-aa6f-4068-b51b-d08dfec9901a', function () {
    return view('errors.ie');
})->name('ie');


Route::get('/3a7f99a7-7691-4181-bcd6-433828c26cad', function () {
    return view('errors.permission');
})->name('permission');


// หน้าแรกของเว็บไซต์ (แสดงหน้า Welcome สวยๆ พร้อมปุ่ม Login)
Route::get('/', function () {
    return view('welcome');
})->name('welcome');


// ROUTE สำหรับทำ SESSION หลัง LOGIN KEYCLOAK (ให้อยู่นอก Group ใหญ่)
Route::middleware(['web', 'keycloak-web'])->group(function () {
    Route::get('/keycloak/session', [KeycloakController::class, 'keycloakSession'])->name('keycloakSession');

    // Route สำหรับดึงรูปภาพพนักงานผ่าน Controller (เพื่อกัน HTTP ตรงไปที่ server ภายใน)
    // ใส่เครื่องหมาย ? หลัง filename และกำหนดค่า default เป็น null
    Route::get('system/employee-image/{filename?}', [KeycloakController::class, 'getEmployeeImage'])
        ->name('keycloak.employee.image');
});





// สำหรับ User ทั่วไป (ผ่าน Keycloak & เช็ค IE เรียบร้อย)
Route::middleware(['check.ie', 'keycloak-web'])->group(function () {
    Route::get('/chatbot', [GeminiUserController::class, 'index'])->name('chatbot.index');
    Route::post('/chatbot/ask', [GeminiUserController::class, 'askAi'])->name('chatbot.ask');
    Route::get('/chatbot/history', [GeminiUserController::class, 'myHistory'])->name('chatbot.history');
});

// สำหรับ Admin
Route::middleware(['check.ie', 'keycloak-web', 'can:admin_chat'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/chatbot/documents', [GeminiAdminController::class, 'index'])->name('chatbot.docs');
    Route::post('/chatbot/documents/upload', [GeminiAdminController::class, 'store'])->name('chatbot.upload');
    Route::delete('/chatbot/documents/{ch_did}', [GeminiAdminController::class, 'destroy'])->name('chatbot.destroy');
    Route::get('/chatbot/trash', [GeminiAdminController::class, 'trash'])->name('chatbot.trash');
    Route::patch('/chatbot/documents/{id}/restore', [GeminiAdminController::class, 'restore'])->name('chatbot.restore');
    Route::get('/chatbot/logs', [GeminiAdminController::class, 'logs'])->name('chatbot.logs');
});





// // ROUTE หน้าหลักของระบบ (ดักจับด้วย check.ie และ keycloak-web)
// Route::middleware(['check.ie', 'keycloak-web'])->group(function () {

//     // dashboard pages
//     Route::get('/dashboard', function () {
//         return view('pages.dashboard.ecommerce', ['title' => 'E-commerce Dashboard']);
//     })->name('dashboard');

//     // calender pages
//     Route::get('/calendar', function () {
//         return view('pages.calender', ['title' => 'Calendar']);
//     })->name('calendar');

//     // profile pages
//     Route::get('/profile', function () {
//         return view('pages.profile', ['title' => 'Profile']);
//     })->name('profile');

//     // form pages
//     Route::get('/form-elements', function () {
//         return view('pages.form.form-elements', ['title' => 'Form Elements']);
//     })->name('form-elements');

//     // tables pages
//     Route::get('/basic-tables', function () {
//         return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
//     })->name('basic-tables');

//     // pages

//     Route::get('/blank', function () {
//         return view('pages.blank', ['title' => 'Blank']);
//     })->name('blank');

//     // error pages
//     Route::get('/error-404', function () {
//         return view('pages.errors.error-404', ['title' => 'Error 404']);
//     })->name('error-404');

//     // chart pages
//     Route::get('/line-chart', function () {
//         return view('pages.chart.line-chart', ['title' => 'Line Chart']);
//     })->name('line-chart');

//     Route::get('/bar-chart', function () {
//         return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
//     })->name('bar-chart');


//     // authentication pages
//     Route::get('/signin', function () {
//         return view('pages.auth.signin', ['title' => 'Sign In']);
//     })->name('signin');

//     Route::get('/signup', function () {
//         return view('pages.auth.signup', ['title' => 'Sign Up']);
//     })->name('signup');

//     // ui elements pages
//     Route::get('/alerts', function () {
//         return view('pages.ui-elements.alerts', ['title' => 'Alerts']);
//     })->name('alerts');

//     Route::get('/avatars', function () {
//         return view('pages.ui-elements.avatars', ['title' => 'Avatars']);
//     })->name('avatars');

//     Route::get('/badge', function () {
//         return view('pages.ui-elements.badges', ['title' => 'Badges']);
//     })->name('badges');

//     Route::get('/buttons', function () {
//         return view('pages.ui-elements.buttons', ['title' => 'Buttons']);
//     })->name('buttons');

//     Route::get('/image', function () {
//         return view('pages.ui-elements.images', ['title' => 'Images']);
//     })->name('images');

//     Route::get('/videos', function () {
//         return view('pages.ui-elements.videos', ['title' => 'Videos']);
//     })->name('videos');
// });
