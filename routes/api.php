<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Yantrana\Components\Auth\Controllers\ApiUserController;
use App\Yantrana\Components\Contact\Controllers\ContactController;
use App\Yantrana\Components\WhatsAppService\Controllers\WhatsAppServiceController;

//agregados
use App\Yantrana\Components\Vendor\Controllers\VendorSettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// base url
Route::any('/', function () {
    return 'api endpoint';
})->name('api.base_url');
Route::group([
    'middleware' => 'api.vendor.authenticate',
    'prefix' => '{vendorUid}/',
], function () {
    Route::post('/contact/send-message', [
        WhatsAppServiceController::class,
        'apiSendChatMessage',
    ])->name('api.vendor.chat_message.send.process');
    // send media message
    Route::post('/contact/send-media-message', [
        WhatsAppServiceController::class,
        'apiSendMediaChatMessage',
    ])->name('api.vendor.chat_message_media.send.process');
    // send media message
    Route::post('/contact/send-template-message', [
        WhatsAppServiceController::class,
        'apiSendTemplateChatMessage',
    ])->name('api.vendor.chat_template_message.send.process');
	//get contact (using _uid)
    Route::get('/contact/get/{contactUid}', [
        ContactController::class,
        'apiShowContact',
    ])->name('api.vendor.contact.show');
    // create new contact
    Route::post('/contact/create', [
        ContactController::class,
        'apiProcessContactCreate',
    ])->name('api.vendor.contact.create.process');
    // update contact
    Route::post('/contact/update/{phoneNumber}', [
        ContactController::class,
        'apiProcessContactUpdate',
    ])->name('api.vendor.contact.update.process');
	
	// Send Image Generation Message FUNCION CREADA
    Route::post('/contact/send-image-generation', [
        WhatsAppServiceController::class,
        'apiSendImageGeneration',
    ])->name('api.vendor.image_generation.send.process');
	
	/*
	// Ruta para obtener settings dinámicos
	Route::get('/vendor-settings-automate', [
		VendorSettingsController::class,
		'getSettings'
	])->name('api.vendor.settings.get');
	*/
	
	
});


// Ruta pública para obtener vendor-settings sin autenticación.
// Se utiliza el vendorUid como parámetro en la URL y se pueden pasar los keys deseados vía query string.
Route::get('{vendorUid}/vendor-settings-automate', [
    VendorSettingsController::class,
    'getSettings'
])->name('api.vendor.settings.get');


// Mobile app apis

/*
    User Components Public Section Related Routes
    ----------------------------------------------------------------------- */
Route::group(['middleware' => 'guest'], function () {
    Route::group([
        // 'namespace' => 'User\ApiControllers',
        'prefix' => 'user',
    ], function () {
        // login process
        Route::post('/login-process', [
            ApiUserController::class,
            'loginProcess'
        ])->name('api.user.login.process');

        // User Registration prepare data
        Route::get('/prepare-sign-up', [
            'as' => 'api.user.sign_up.prepare',
            'uses' => 'ApiUserController@prepareSignUp',
        ]);

        // User Registration
        Route::post('/process-sign-up', [
            'as' => 'api.user.sign_up.process',
            'uses' => 'ApiUserController@processSignUp',
        ]);
		
    });
});
Route::group([
    'middleware' => 'app_api.vendor.authenticate',
    // 'prefix' => '{vendorUid}/',
], function () {
    Route::group([
        'prefix' => 'vendor/',
    ], function () {
        Route::get('/contact/contacts-data/{contactUid?}', [
            WhatsAppServiceController::class,
            'getContactsData',
        ])->name('app_api.vendor.contacts.data.read');
    });
    // logout
    Route::post('/user/logout', [
        ApiUserController::class,
        'logout'
    ])->name('api.user.logout');
});

//agregados para ver usuario


Route::get('/legal', [VendorSettingsController::class, 'getLegalContent']);

