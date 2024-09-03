<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ListController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ConceptController;
use App\Http\Controllers\InitialController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ChatbotsController;
use App\Http\Controllers\HolidaysController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SubjectsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntentionsController;
use App\Http\Controllers\CombinationController;
use App\Http\Controllers\CityCouncilsController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\ChatbotSettingController;
use App\Http\Controllers\DefaultSettingController;
use App\Http\Controllers\ManualTrainingController;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;
use App\Http\Controllers\cityCouncilSettingController;
use App\Http\Controllers\SupervisedTrainingController;
use App\Http\Controllers\ImportExportIntention;

/*

|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Auth::routes(['register' => false]);
Route::get('/', [App\Http\Controllers\HomeController::class, 'index']);


//view routes
Route::group(['middleware' => ['auth']], function () {
    Route::view('/dashboard', 'home')->name('dashboard');
    Route::view('/conversations', 'home')->name('conversations');
    Route::view('/builder', 'home')->name('builder');
    Route::view('/chatbotSettings', 'home')->name('chatbotSettings');
    Route::view('/users', 'home')->name('users');
    Route::view('/customers', 'home')->name('customers');
    Route::view('/settings', 'home')->name('settings');
    Route::view('/roles', 'home')->name('roles');
    Route::view('/chatbots/{idCustomer?}', 'home')->name('chatbots');
    Route::view('/supervised_training', 'home')->name('supervised_training');
    Route::view('/profile/{idUser}', 'home')->name('profile');
    Route::view('/editConfiguration', 'home')->name('editConfiguration');
    Route::view('/supervised_manual', 'home')->name('supervised_manual');
    Route::view('/scriptTester', 'home')->name('scriptTester');
    Route::get('logs', [LogViewerController::class, 'index']);
});

//ROLES
Route::group(['middleware' => ['web', 'permission:manage_roles', 'auth']], function () {
    Route::get('getRoleData', [RoleController::class, 'getRoleData'])->name('getRoleData');
    Route::get('getPermissionData', [RoleController::class, 'getPermissionData'])->name('getPermissionData');
    Route::post('createRole', [RoleController::class, 'createRole'])->name('createRole');
    Route::get('getRoleId/{roleId}', [RoleController::class, 'getRoleId'])->name('getRoleId');
    Route::post('updateRole', [RoleController::class, 'updateRole'])->name('updateRole');
    Route::delete('deleteRole/{roleId}', [RoleController::class, 'deleteRole'])->name('deleteRole');
});

//Intentions
Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('getIntentions', [IntentionsController::class, 'index'])->name('getIntentions');
    Route::get('getIntentionsBuilder', [IntentionsController::class, 'getIntentionsBuilder'])->name('getIntentionsBuilder');
    Route::get('getHistoryIntentions/{id}', [IntentionsController::class, 'getHistoryIntentions'])->name('getHistoryIntentions');
    Route::post('saveIntentions', [IntentionsController::class, 'store'])->name('saveIntentions');
    Route::get('getDetailIntention', [IntentionsController::class, 'getDetailIntention'])->name('getDetailIntention');
    Route::delete('deleteIntentions/{id}', [IntentionsController::class, 'delete'])->name('deleteIntentions');
    Route::get('getConcepts', [ConceptController::class, 'getConcepts'])->name('getConcepts');
    Route::post('createConcepts', [ConceptController::class, 'store'])->name('createConcepts');
    Route::post('updateConcepts/{id}', [ConceptController::class, 'update'])->name('updateConcepts');
    Route::delete('deleteConcepts/{id}', [ConceptController::class, 'destroy'])->name('deleteConcepts');
    Route::get('getLists', [ListController::class, 'getLists'])->name('getLists');
    Route::post('createLists', [ListController::class, 'store'])->name('createLists');
    Route::post('updateLists/{id}', [ListController::class, 'update'])->name('updateLists');
    Route::delete('deleteLists/{id}', [ListController::class, 'destroy'])->name('deleteLists');
    Route::get('create-combinations/{intencion}/{chatbot}', [CombinationController::class, 'createCombinations']);
    Route::get('combinations/{intencion}', [CombinationController::class, 'getCombinations']);
    Route::post('storeCombinations', [CombinationController::class, 'storeCombinations'])->name('storeCombinations');
    Route::post('importIntentionsXlsx', [ImportExportIntention::class, 'importIntentionsXlsx'])->name('importIntentionsXlsx');
    Route::get('exportIntentionsXlsx/{data}', [ImportExportIntention::class, 'exportIntentionsXlsx'])->name('exportIntentionsXlsx');
});

//subjects
Route::group(['middleware' => ['web', 'permission:manage_thematic', 'auth']], function () {
    Route::get('getAllSubjects', [SubjectsController::class, 'getAllSubjects'])->name('getAllSubjects');
    Route::post('saveSubjects', [SubjectsController::class, 'store'])->name('saveSubjects');
    Route::get('editSubjects/{id}', [SubjectsController::class, 'edit'])->name('editSubjects');
    Route::put('updateSubjects/{id}', [SubjectsController::class, 'update'])->name('updateSubjects');
    Route::delete('deleteSubjects/{id}', [SubjectsController::class, 'destroy'])->name('deleteSubjects');
    Route::get('exportIntentions/{id}', [SubjectsController::class, 'exportIntentions'])->name('exportIntentions');
    Route::post('exportIntentionSelect', [SubjectsController::class, 'exportIntentionSelect'])->name('exportIntentionSelect');
    Route::post('importIntentions', [SubjectsController::class, 'importIntentions'])->name('importIntentions');
});

//Bot settings
Route::group(['middleware' => ['web', 'permission:chatbots_settings', 'auth']], function () {
    Route::get('/getModuleAgent/{id}', [ChatbotSettingController::class, 'getModuleAgent']);
    Route::get('/getBotSchedule/{id}', [ScheduleController::class, 'getSchedule']);
    Route::post('/updateBotSchedule/{id}', [ScheduleController::class, 'updateSchedule']);
    Route::get('/getChatbotHolidays/{id}', [HolidaysController::class, 'getChatbotHolidays']);
    Route::post('/createChatbotHoliday/{id}', [HolidaysController::class, 'store'])->name('createChatbotHolidays');
    Route::post('/updateChatbotHoliday/{id}', [HolidaysController::class, 'update'])->name('updateChatbotHolidays');
    Route::delete('/deleteChatbotHoliday/{id}', [HolidaysController::class, 'destroy'])->name('deleteChatbotHolidays');
    Route::get('getOneChatbotSettings/{id}', [ChatbotSettingController::class, 'getOneChatbotSettings']);
    Route::post('updateChatbotSetting/{id}', [ChatbotSettingController::class, 'updateChatbotSetting']);
});

//CITY_COUNCILS
Route::group(['middleware' => ['web', 'permission:manage_clients', 'auth']], function () {
    Route::get('getAllCityCouncils', [CityCouncilsController::class, 'index'])->name('getAllCityCouncils');
    Route::post('saveCityCouncils', [CityCouncilsController::class, 'store'])->name('saveCityCouncils');
    Route::delete('deleteCityCouncils/{id}', [CityCouncilsController::class, 'destroy'])->name('deleteCityCouncils');
    Route::get('editCityCouncils/{id}', [CityCouncilsController::class, 'edit'])->name('editCityCouncils');
    Route::put('updateCityCouncils/{id}', [CityCouncilsController::class, 'update'])->name('updateCityCouncils');
    Route::get('/loggedInUser', function () {
        return getLoggedInUser();
    });
    Route::get('getDataAdmin', [UserController::class, 'getDataAdmin'])->name('getDataAdmin');
    Route::get('getAdminClient/{id}', [CityCouncilsController::class, 'getAdminClient'])->name('getAdminClient');
});

//GENERAL SETTINGS
Route::group(['middleware' => ['web', 'permission:manage_settings', 'auth']], function () {
    Route::get('getSettings', [SettingController::class, 'getSettings'])->name('getSettings');
    Route::get('getSettingsDta', [SettingController::class, 'index'])->name('getSettingsDta');
    Route::put('updateSettings', [SettingController::class, 'updateSettings'])->name('updateSettings');
    //Defaults Settings
    Route::get('getDefaults', [DefaultSettingController::class, 'getDefaults'])->name('getDefaults');
    Route::post('storeDefault', [DefaultSettingController::class, 'store'])->name('store');
    Route::put('updateDefault/{id}', [DefaultSettingController::class, 'update'])->name('update');

    //CITY_COUNCILS
    Route::get('getAllCity', [CityCouncilsController::class, 'getAllCity'])->name('getAllCity');
    Route::post('saveSettings', [cityCouncilSettingController::class, 'saveSettings'])->name('saveSettings');
    Route::post('saveOrUpdateSettings', [cityCouncilSettingController::class, 'saveOrUpdateSettings'])->name('saveOrUpdateSettings');
    Route::get('editCityCouncilSetting/{id}', [cityCouncilSettingController::class, 'edit'])->name('editCityCouncilSetting');
    Route::post('updateSettingsCityCouncil', [cityCouncilSettingController::class, 'updateSettingsCityCouncil'])->name('updateSettingsCityCouncil');
    Route::delete('deleteSettings/{cityCouncilId}', [CityCouncilSettingController::class, 'deleteSettings'])->name('deleteSettings');
});

//DATA Helpers
Route::get('/getColorSetting', function () {
    $color = getColorSetting();
    return response()->json(['color' => $color]);
});


//CHATBOTS
Route::group(['middleware' => ['web', 'permission:manage_chatbots', 'auth']], function () {
    Route::get('getChatbots/{idCustomer?}', [ChatbotsController::class, 'index'])->name('getChatbots');
    Route::post('saveChatbot', [ChatbotsController::class, 'store'])->name('saveChatbot');
    Route::get('getIdChatbot/{id}', [ChatbotsController::class, 'getIdChatbot'])->name('getIdChatbot');
    Route::post('setEditChatbot', [ChatbotsController::class, 'setEditChatbot'])->name('setEditChatbot');
    Route::post('updateChatbot/{id}', [ChatbotsController::class, 'update'])->name('updateChatbot');
    Route::delete('deleteChatbot/{id}', [ChatbotsController::class, 'destroy'])->name('deleteChatbot');
    Route::post('updateStateChatbot', [ChatbotsController::class, 'updateStateChatbot'])->name('updateStateChatbot');
    Route::post('getLogBuilder', [ChatbotsController::class, 'getLogBuilder'])->name('getLogBuilder');
    Route::get('getHistoryChatbots/{id}', [ChatbotsController::class, 'getHistoryChatbots'])->name('getHistoryChatbots');
    Route::post('trainingChatbot', [ChatbotsController::class, 'trainingChatbot'])->name('trainingChatbot');
    Route::get('recoverChatbot/{id}', [ChatbotsController::class, 'recoverChatbot'])->name('recoverChatbot');
    Route::get('stateChatbot/{id}/{state}', [ChatbotsController::class, 'stateChatbot'])->name('stateChatbot');
});

//Users
Route::group(['middleware' => ['web', 'permission:manage_users', 'auth']], function () {
    Route::get('getUsers', [UserController::class, 'index'])->name('getUsers');
    Route::post('updateState', [UserController::class, 'updateState'])->name('updateState');
    Route::get('getRoles', [RoleController::class, 'getRoles'])->name('getRoles');
    Route::post('saveUser', [UserController::class, 'store'])->name('saveUser');
    Route::delete('deleteUser/{id}', [UserController::class, 'destroy'])->name('deleteUser');
    Route::get('getUser/{id}', [UserController::class, 'getUser'])->name('getUser');
    Route::post('updateUser/{id}', [UserController::class, 'updateUser'])->name('updateUser');
    Route::post('newPassword/{id}', [UserController::class, 'newPassword'])->name('newPassword');
    Route::get('getAccessHistory/{id}', [UserController::class, 'getAccessHistory'])->name('getAccessHistory');
    Route::get('getUsersId/{id}', [UserController::class, 'edit'])->name('getUsersId');
    Route::post('updateUserData', [UserController::class, 'update'])->name('updateUserData');
    Route::get('getClients', [UserController::class, 'getClients'])->name('getClients');
    Route::get('getClientUser/{id}', [UserController::class, 'getClientUser'])->name('getClientUser');
});
//Conversaciones
Route::group(['middleware' => ['web', 'permission:manage_conversations', 'auth']], function () {
    Route::get('getConversation', [ConversationController::class, 'index'])->name('getConversation');
    Route::get('getConversationDetail/{id}', [ConversationController::class, 'show'])->name('getConversationDetail');
    Route::get('getConversationStatus', [ConversationController::class, 'getConversationStatus'])->name('getConversationStatus');
});
//Entrenamiento supervisado
Route::group(['middleware' => ['web', 'permission:manage_supervised_training', 'auth']], function () {
    Route::get('resourceSupervisedTraining', [SupervisedTrainingController::class, 'index'])->name('resourceSupervisedTraining');
    Route::post('setRating', [SupervisedTrainingController::class, 'store'])->name('setRating');
    Route::post('descartRating', [SupervisedTrainingController::class, 'update'])->name('descartRating');
});
//Entrenamiento manual
Route::group(['middleware' => ['web', 'permission:manage_manual_training', 'auth']], function () {
    Route::get('resourceManualTraining', [ManualTrainingController::class, 'index'])->name('resourceManualTraining');
    Route::post('importXlxs', [ManualTrainingController::class, 'store'])->name('importXlxs');
    Route::post('descartRatingManual', [ManualTrainingController::class, 'update'])->name('descartRatingManual');
    Route::post('setRatingManual', [ManualTrainingController::class, 'setRatingManual'])->name('setRatingManual');
});
//CHAT CUSTOMER --> Publicas para poder hacer uso de ellas en cualquier pagina
Route::view('/chatbot-customer/{chatbotId}/{lang?}', 'chat-customer')->name('chatbot-customer');
Route::get('getOneChatbotCustomerSettings/{id}', [ChatbotSettingController::class, 'getOneChatbotCustomerSettings']);
//Dashboard
Route::group(['middleware' => ['web', 'permission:manage_dashboard', 'auth']], function () {
    Route::get('getMetrics', [DashboardController::class, 'getMetrics'])->name('getMetrics');
    Route::get('getChatbotsPerCustomer/{id}', [DashboardController::class, 'getChatbotsPerCustomer'])->name('getChatbotsPerCustomer');
    Route::get('getCustomers', [DashboardController::class, 'getCustomers'])->name('getCustomers');
});
Route::get('getInitialRedirectPath', [DashboardController::class, 'getInitialRedirectPath'])->name('getInitialRedirectPath');

Route::get('chatbot', function () {
    return response()->file(public_path('js/generate-iframe.js'));
});

Route::post('saveSelection', [InitialController::class, 'saveSelection'])->name('saveSelection');
