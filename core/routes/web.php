<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\SiteMapController;
use App\Http\Controllers\Dashboard\LeadsController;
use App\Http\Controllers\Dashboard\DialerController;

use Illuminate\Support\Facades\Route;
use App\Models\User;
use Google\Auth\ApplicationDefaultCredentials;



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

Route::get('/test-google-auth', function () {
    dd(ApplicationDefaultCredentials::class);
});

    
Route::post('/save-fcm-token', function (Illuminate\Http\Request $request) {
    $user = auth()->user();
    if ($user) {
        $user->fcm_key = $request->token;
        $user->save();

        return response()->json(['status' => 'Token saved']);
    }
    return response()->json(['success' => false], 401);
});

Route::get('/admin/license', [LoginController::class, 'license'])->name('adminLicense');
Route::post('/admin/license_activate', [LoginController::class, 'activateLicense'])->name('adminLicenseActivate');
Route::post('/admin/custom_login', [LoginController::class, 'customLogin'])->name('adminCustomLogin');
Route::post('/admin/validate_otp', [LoginController::class, 'validateOtp'])->name('adminValidateOtp');

//Recordings upload
Route::any('/admin/upload', [LeadsController::class, 'downloadFile'])->name('recordingsUpload');
Route::post('/admin/mobile_upload', [LeadsController::class, 'mobileCallRecordingUpload'])->name('mobileCallRecordingUpload');
Route::any('/admin/endcall', [LeadsController::class, 'sipEndCall'])->name('sipEndCall');
Route::match(['get', 'post'], '/admin/recording/callback', [\App\Http\Controllers\APIs\TelephonyWebhookController::class, 'handleWebhook'])->name('admin.recording.callback');


// Language Route
Route::post('/lang', [LanguageController::class, 'index'])->middleware('LanguageSwitcher')->name('lang');
// For Language direct URL link
Route::get('/lang/{lang}', [LanguageController::class, 'change'])->middleware('LanguageSwitcher')->name('langChange');
Route::get('/locale/{lang}', [LanguageController::class, 'locale'])->middleware('LanguageSwitcher')->name('localeChange');
// .. End of Language Route

// No Permission
Route::get('/403', function () {
    return response()->view('errors.403', [], 403);
})->name('NoPermission');

Route::get('/check-loop', function () {
    return 'No redirect loop.';
});

// Not Found
Route::get('/404', function () {
    return view('errors.404');
})->name('NotFound');

// Backend Routes
Route::get('/login', function () {
    return redirect('/');
})->name('login');

Route::get('/register', function () {
    return redirect('/');
});

// RSS Feed Routes
if (env("RSS_STATUS", 0)) {
    Route::feeds();
}

// Social Auth
Route::get('/oauth/{driver}', [SocialAuthController::class, 'redirectToProvider'])->name('social.oauth');
Route::get('/oauth/{driver}/callback', [SocialAuthController::class, 'handleProviderCallback'])->name('social.callback');

Route::Group(['prefix' => env('BACKEND_PATH')], function () {
    Auth::routes();
});
//Route::get('/logout', function () {
//    Auth::logout();
//    return redirect('/');
//})->name('logout');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/sso-logout', [LoginController::class, 'ssoLogout'])->name('sso_logout');

// Start of Frontend Routes
// ../site map
Route::get('/sitemap.xml', [SiteMapController::class, 'siteMap'])->name('siteMap');
Route::get('/{lang}/sitemap', [SiteMapController::class, 'siteMap'])->name('siteMapByLang');

Route::get('/', [HomeController::class, 'HomePage'])->name('Home');

Route::post('/form-submit', [HomeController::class, 'formSubmit'])->name('formSubmit');

// ../home url
Route::get('/home', [HomeController::class, 'HomePage'])->name('HomePage');
Route::get('/{lang?}/home', [HomeController::class, 'HomePageByLang'])->name('HomePageByLang');
// ../subscribe to newsletter submit  (ajax url)
Route::post('/subscribe', [HomeController::class, 'subscribeSubmit'])->name('subscribeSubmit');
// ../Comment submit  (ajax url)
Route::post('/comment', [HomeController::class, 'commentSubmit'])->name('commentSubmit');
// ../Order submit  (ajax url)
Route::post('/order', [HomeController::class, 'orderSubmit'])->name('orderSubmit');
// ..Custom URL for contact us page ( www.site.com/contact )
Route::get('/contact', [HomeController::class, 'ContactPage'])->name('contactPage');
Route::get('/register-leads/{id}', [HomeController::class, 'RegisterLeadsPage'])->name('registerLeadsPage');
Route::post('/register-leads/submit', [HomeController::class, 'RegisterLeadsPageSubmit'])->name('registerLeadsPageSubmit');
Route::get('/{lang?}/contact', [HomeController::class, 'ContactPageByLang'])->name('contactPageByLang');
// ../contact message submit  (ajax url)
Route::post('/contact/submit', [HomeController::class, 'ContactPageSubmit'])->name('contactPageSubmit');
// ..if page by name ( ex: www.site.com/about )
Route::get('/topic/{id}', [HomeController::class, 'topic'])->name('FrontendPage');
// ..if page by user id ( ex: www.site.com/user )
Route::get('/user/{id}', [HomeController::class, 'userTopics'])->name('FrontendUserTopics');
Route::get('/{lang?}/user/{id}', [HomeController::class, 'userTopicsByLang'])->name('FrontendUserTopicsByLang');
// ../search
Route::get('/search', [HomeController::class, 'searchTopics'])->name('searchTopics');

// ..Topics url  ( ex: www.site.com/news/topic/32 )
Route::get('/{section}/topic/{id}', [HomeController::class, 'topic'])->name('FrontendTopic');
Route::get('/{lang?}/{section}/topic/{id}', [HomeController::class, 'topicByLang'])->name('FrontendTopicByLang');

// ..Sub category url for Section  ( ex: www.site.com/products/2 )
Route::get('/{section}/{cat}', [HomeController::class, 'topics'])->name('FrontendTopicsByCat');
Route::get('/{lang?}/{section}/{cat}', [HomeController::class, 'topicsByLang'])->name('FrontendTopicsByCatWithLang');

// ..Section url by name  ( ex: www.site.com/news )
Route::get('/{section}', [HomeController::class, 'topics'])->name('FrontendTopics');
Route::get('/{lang?}/{section}', [HomeController::class, 'topicsByLang'])->name('FrontendTopicsByLang');

// ..SEO url  ( ex: www.site.com/title-here )
Route::get('/{seo_url_slug}', [HomeController::class, 'SEO'])->name('FrontendSEO');
Route::get('/{lang?}/{seo_url_slug}', [HomeController::class, 'SEOByLang'])->name('FrontendSEOByLang');

// ..if page by name and language( ex: www.site.com/ar/about )
Route::get('/{lang?}/topic/{id}', [HomeController::class, 'topicByLang'])->name('FrontendPageByLang');
// .. End of Frontend Route

