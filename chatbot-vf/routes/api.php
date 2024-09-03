<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\ConceptController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ChatbotsController;
use App\Http\Controllers\HolidaysController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SubjectsController;
use App\Http\Controllers\IntentionsController;
use App\Http\Controllers\CityCouncilsController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\ChatbotSettingController;
use App\Http\Controllers\DefaultSettingController;
use App\Http\Controllers\ManualTrainingController;
use App\Http\Controllers\cityCouncilSettingController;
use App\Http\Controllers\SupervisedTrainingController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/loginApi', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('getActiveChatbots/', [ChatbotsController::class, 'getActiveChatbots'])->name('getActiveChatbots');

    Route::post('/startConversation', [AgentController::class, 'startConversation'])->name('startConversation');
    Route::post('/sendToAgent', [AgentController::class, 'toAgent'])->name('sendToAgent');
    Route::post('/closeConversation', [AgentController::class, 'closeConversation'])->name('closeConversation');
    Route::post('/getFromAgent', [AgentController::class, 'fromAgent'])->name('getFromAgent');
    //Users
    Route::get('getUsersApi', [UserController::class, 'index'])->name('getUsersApi');
    Route::post('saveUserApi', [UserController::class, 'store'])->name('saveUserApi');
    Route::get('/users/{id}/edit', [UserController::class, 'edit']);
    Route::post('updateUserApi', [UserController::class, 'update'])->name('updateUserApi');
    Route::delete('deleteUserApi/{id}', [UserController::class, 'destroy'])->name('deleteUserApi');
    Route::get('/userId/{id}', [UserController::class, 'getUser']);
    Route::put('/users/{id}/newPassword', [UserController::class, 'newPassword']);
    Route::put('/users/updateState', [UserController::class, 'updateState']);
    Route::get('/users/{id}/accessHistory', [UserController::class, 'getAccessHistory']);
    Route::get('/dataAdmin', [UserController::class, 'getDataAdmin']);
    Route::get('/getClientUser/{user_id}', [UserController::class, 'getClientUser']);
    Route::get('getClientsApi', [UserController::class, 'getClients'])->name('getClientsApi');

    //Roles
    Route::get('/getRoleData', [RoleController::class, 'getRoleData']);
    Route::get('/getRoles', [RoleController::class, 'getRoles']);
    Route::get('/getPermissionData', [RoleController::class, 'getPermissionData']);
    Route::post('/createRole', [RoleController::class, 'createRole']);
    Route::get('/getRoleId/{id}', [RoleController::class, 'getRoleId']);
    Route::post('/updateRole', [RoleController::class, 'updateRole']);
    Route::delete('/deleteRole/{roleId}', [RoleController::class, 'deleteRole']);

    //Setting
    Route::get('getSettingsDtaApi', [SettingController::class, 'index'])->name('getSettingsDtaApi');
    Route::post('updateSettingsCityCouncilApi', [cityCouncilSettingController::class, 'updateSettingsCityCouncil'])->name('updateSettingsCityCouncilApi');
    Route::delete('deleteSettingsApi/{cityCouncilId}', [CityCouncilSettingController::class, 'deleteSettings'])->name('deleteSettingsApi');
    Route::post('saveSettingsApi', [cityCouncilSettingController::class, 'saveSettings'])->name('saveSettingsApi');
    Route::get('getSettingsApi', [SettingController::class, 'getSettings'])->name('getSettingsApi');
    Route::put('updateSettingsApi', [SettingController::class, 'updateSettings'])->name('updateSettingsApi');
    Route::put('updateDefaultApi/{id}', [DefaultSettingController::class, 'update'])->name('updateDefaultApi');
    Route::post('storeDefaultApi', [DefaultSettingController::class, 'store'])->name('storeDefaultApi');
    Route::post('saveOrUpdateSettingsApi', [cityCouncilSettingController::class, 'saveOrUpdateSettings'])->name('saveOrUpdateSettingsApi');

    //Clients
    Route::post('saveClientApi', [CityCouncilsController::class, 'store'])->name('saveClientApi');
    Route::get('getAdminClientApi/{id}', [CityCouncilsController::class, 'getAdminClient'])->name('getAdminClientApi');
    Route::get('getClientId/{id}', [CityCouncilsController::class, 'edit'])->name('getClientId');
    Route::put('updateClientsApi/{id}', [CityCouncilsController::class, 'update'])->name('updateClientsApi');
    Route::delete('deleteClientApi/{id}', [CityCouncilsController::class, 'destroy'])->name('deleteClientApi');

    //Dashboard
    Route::post('getMetricsApi', [DashboardController::class, 'getMetrics'])->name('getMetricsApi');
    Route::get('getChatbotsPerCustomerApi/{id}', [DashboardController::class, 'getChatbotsPerCustomer'])->name('getChatbotsPerCustomerApi');


    //SupervisedTraining
    Route::get('resourceSupervisedTrainingApi', [SupervisedTrainingController::class, 'index'])->name('resourceSupervisedTrainingApi');
    Route::post('setRatingApi', [SupervisedTrainingController::class, 'store'])->name('setRatingApi');
    Route::post('descartRatingApi', [SupervisedTrainingController::class, 'update'])->name('descartRatingApi');

    //Chatbots
    Route::get('getChatbotsApi/{idCustomer?}', [ChatbotsController::class, 'index'])->name('getChatbotsApi');
    Route::post('saveChatbotApi', [ChatbotsController::class, 'store'])->name('saveChatbotApi');
    Route::post('setEditChatbotApi', [ChatbotsController::class, 'setEditChatbot'])->name('setEditChatbotApi');
    Route::get('getIdChatbotApi/{id}', [ChatbotsController::class, 'getIdChatbot'])->name('getIdChatbotApi');
    Route::post('getLogBuilderApi', [ChatbotsController::class, 'getLogBuilder'])->name('getLogBuilderApi');
    Route::get('getHistoryChatbotsApi/{id}', [ChatbotsController::class, 'getHistoryChatbots'])->name('getHistoryChatbotsApi');
    Route::delete('deleteChatbotApi/{id}', [ChatbotsController::class, 'destroy'])->name('deleteChatbotApi');
    //Chatbots setting

    Route::get('getOneChatbotSettingsApi/{id}', [ChatbotSettingController::class, 'getOneChatbotSettings'])->name('getOneChatbotSettingsApi');
    Route::post('updateChatbotSettingApi/{id}', [ChatbotSettingController::class, 'updateChatbotSetting']);
    Route::get('/getBotScheduleApi/{id}', [ScheduleController::class, 'getSchedule']);
    Route::post('/updateBotScheduleApi/{id}', [ScheduleController::class, 'updateSchedule']);
    Route::get('/getChatbotHolidaysApi/{id}', [HolidaysController::class, 'getChatbotHolidays']);
    Route::post('/createChatbotHolidayApi/{id}', [HolidaysController::class, 'store'])->name('createChatbotHolidayApi');
    Route::post('/updateChatbotHolidayApi/{id}', [HolidaysController::class, 'update'])->name('updateChatbotHolidayApi');
    Route::delete('/deleteChatbotHolidayApi/{id}', [HolidaysController::class, 'destroy'])->name('deleteChatbotHolidayApi');

    //Thematic
    Route::get('getAllSubjectsApi', [SubjectsController::class, 'getAllSubjects'])->name('getAllSubjectsApi');
    Route::post('saveSubjectsApi', [SubjectsController::class, 'store'])->name('saveSubjectsApi');
    Route::get('getSubjectIdApi/{id}', [SubjectsController::class, 'edit'])->name('getSubjectIdApi');
    Route::delete('deleteSubjectsApi/{id}', [SubjectsController::class, 'destroy'])->name('deleteSubjectsApi');
    Route::get('exportIntentionsApi/{id}', [SubjectsController::class, 'exportIntentions'])->name('exportIntentionsApi');
    Route::post('importIntentionsApi', [SubjectsController::class, 'importIntentions'])->name('importIntentionsApi');
    Route::put('updateSubjectsApi/{id}', [SubjectsController::class, 'update'])->name('updateSubjectsApi');
    //Intentions
    Route::get('getIntentionsApi', [IntentionsController::class, 'index'])->name('getIntentionsApi');
    Route::post('saveIntentionsApi', [IntentionsController::class, 'store'])->name('saveIntentionsApi');
    Route::get('getDetailIntentionApi', [IntentionsController::class, 'getDetailIntention'])->name('getDetailIntentionApi');
    Route::delete('deleteIntentionsApi/{id}', [IntentionsController::class, 'delete'])->name('deleteIntentionsApi');
    Route::get('getHistoryIntentionsApi/{id}', [IntentionsController::class, 'getHistoryIntentions'])->name('getHistoryIntentionsApi');

    //Concepts
    Route::get('getContextsApi', [ConceptController::class, 'getConcepts'])->name('getContextsApi');
    Route::post('createContextsApi', [ConceptController::class, 'store'])->name('createContextsApi');
    Route::post('updateContextsApi/{id}', [ConceptController::class, 'update'])->name('updateContextsApi');
    Route::delete('deleteContextsApi/{id}', [ConceptController::class, 'destroy'])->name('deleteContextsApi');

    //Lists
    Route::get('getListsApi', [ListController::class, 'getLists'])->name('getListsApi');
    Route::post('createListsApi', [ListController::class, 'store'])->name('createListsApi');
    Route::post('updateListsApi/{id}', [ListController::class, 'update'])->name('updateListsApi');
    Route::delete('deleteListsApi/{id}', [ListController::class, 'destroy'])->name('deleteListsApi');
    //supervisedManual
    Route::post('importCsvApi', [ManualTrainingController::class, 'store'])->name('importCsvApi');
    Route::get('resourceManualTrainingApi', [ManualTrainingController::class, 'index'])->name('resourceManualTrainingApi');
    Route::post('setRatingManualApi', [ManualTrainingController::class, 'setRatingManual'])->name('setRatingManualApi');
    Route::post('descartRatingManualApi', [ManualTrainingController::class, 'update'])->name('descartRatingManualApi');
});
//Endpoint Conversaciones
Route::post('conversation', [ConversationController::class, 'conversation'])->name('conversation');
Route::post('createConversation', [ConversationController::class, 'CreateConversation'])->name('createConversation');
Route::post('conversationHistory', [ConversationController::class, 'conversationHistory'])->name('conversationHistory');
Route::post('closeConversationAbandonment', [ConversationController::class, 'closeConversationAbandonment'])->name('closeConversationAbandonment');
Route::post('validateConversationStatus', [ConversationController::class, 'validateConversationStatus'])->name('validateConversationStatus');
Route::get('getConversationDetailApi/{id}', [ConversationController::class, 'show'])->name('getConversationDetailApi');
Route::get('getConversationStatusApi', [ConversationController::class, 'getConversationStatus'])->name('getConversationStatusApi');

