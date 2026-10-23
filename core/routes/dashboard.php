<?php
use App\Http\Controllers\Dashboard\PayoutController;
use App\Http\Controllers\Dashboard\CallChannelsController;
use App\Http\Controllers\Dashboard\WebRTCController;
use App\Http\Controllers\Dashboard\AttendanceController;
use App\Http\Controllers\Dashboard\AuditController;
use App\Http\Controllers\Dashboard\AgreementController;
use App\Http\Controllers\Dashboard\CampaignsController;
use App\Http\Controllers\Dashboard\Click2CallController;
use App\Http\Controllers\Dashboard\CustomersController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\DialerController;
use App\Http\Controllers\Dashboard\AutoDialerController;
use App\Http\Controllers\Dashboard\EmailController;
use App\Http\Controllers\Dashboard\ImapController;
use App\Http\Controllers\Dashboard\FlexiDialerController;
use App\Http\Controllers\Dashboard\LeadsController;
use App\Http\Controllers\Dashboard\MailboxController;
use App\Http\Controllers\Dashboard\MobileDevicesController;
use App\Http\Controllers\Dashboard\NotificationsController;
use App\Http\Controllers\Dashboard\ObdController;
use App\Http\Controllers\Dashboard\ClientTypesController;
use App\Http\Controllers\Dashboard\ProductsServicesController;
use App\Http\Controllers\Dashboard\ReportsController;
use App\Http\Controllers\Dashboard\RecordingsController;
use App\Http\Controllers\Dashboard\SegmentsController;
use App\Http\Controllers\Dashboard\ShiftsController;
use App\Http\Controllers\Dashboard\SmsController;
use App\Http\Controllers\Dashboard\SourcesController;
use App\Http\Controllers\Dashboard\SubstatusesController;
use App\Http\Controllers\Dashboard\StatusesController;
use App\Http\Controllers\Dashboard\UsersController;
use App\Http\Controllers\Dashboard\WebmasterSettingsController;
use App\Http\Controllers\Dashboard\WebmasterBannersController;
use App\Http\Controllers\Dashboard\WebmasterSectionsController;
use App\Http\Controllers\Dashboard\WorkLocationsController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\Dashboard\AiCallIntelligenceController;
use App\Http\Controllers\Dashboard\BannersController;
use App\Http\Controllers\Dashboard\CategoriesController;
use App\Http\Controllers\Dashboard\TopicsController;
use App\Http\Controllers\Dashboard\ContactsController;
use App\Http\Controllers\Dashboard\WebmailsController;
use App\Http\Controllers\Dashboard\EventsController;
use App\Http\Controllers\Dashboard\AnalyticsController;
use App\Http\Controllers\Dashboard\MenusController;
use App\Http\Controllers\Dashboard\FileManagerController;
use Illuminate\Support\Facades\Route;

// Admin Home
Route::middleware(['auth','web'])->group(function () {
Route::get('/', [DashboardController::class, 'index'])->name('adminHome');
Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('adminDashboard');
//Search
Route::get('/search', [DashboardController::class, 'search'])->name('adminSearch');
Route::post('/find', [DashboardController::class, 'find'])->name('adminFind');

// Webmaster
Route::get('/webmaster', [WebmasterSettingsController::class, 'edit'])->name('webmasterSettings');
Route::post('/webmaster', [WebmasterSettingsController::class, 'update'])->name('webmasterSettingsUpdate');
Route::post('/webmaster/languages/store', [WebmasterSettingsController::class, 'language_store'])->name('webmasterLanguageStore');
Route::post('/webmaster/languages/store', [WebmasterSettingsController::class, 'language_store'])->name('webmasterLanguageStore');
Route::post('/webmaster/languages/update', [WebmasterSettingsController::class, 'language_update'])->name('webmasterLanguageUpdate');
Route::get('/webmaster/languages/destroy/{id}', [WebmasterSettingsController::class, 'language_destroy'])->name('webmasterLanguageDestroy');
Route::get('/webmaster/seo/repair', [WebmasterSettingsController::class, 'seo_repair'])->name('webmasterSEORepair');

Route::post('/webmaster/mail/smtp', [WebmasterSettingsController::class, 'mail_smtp_check'])->name('mailSMTPCheck');
Route::post('/webmaster/mail/test', [WebmasterSettingsController::class, 'mail_test'])->name('mailTest');

// Webmaster Banners
Route::get('/webmaster/banners', [WebmasterBannersController::class, 'index'])->name('WebmasterBanners');
Route::get('/webmaster/banners/create', [WebmasterBannersController::class, 'create'])->name('WebmasterBannersCreate');
Route::post('/webmaster/banners/store', [WebmasterBannersController::class, 'store'])->name('WebmasterBannersStore');
Route::get('/webmaster/banners/{id}/edit', [WebmasterBannersController::class, 'edit'])->name('WebmasterBannersEdit');
Route::post('/webmaster/banners/{id}/update', [WebmasterBannersController::class, 'update'])->name('WebmasterBannersUpdate');
Route::get('/webmaster/banners/destroy/{id}',
    [WebmasterBannersController::class, 'destroy'])->name('WebmasterBannersDestroy');
Route::post('/webmaster/banners/updateAll',
    [WebmasterBannersController::class, 'updateAll'])->name('WebmasterBannersUpdateAll');

// Webmaster Sections
Route::get('/webmaster/sections', [WebmasterSectionsController::class, 'index'])->name('WebmasterSections');
Route::get('/webmaster/sections/create', [WebmasterSectionsController::class, 'create'])->name('WebmasterSectionsCreate');
Route::post('/webmaster/sections/store', [WebmasterSectionsController::class, 'store'])->name('WebmasterSectionsStore');
Route::get('/webmaster/sections/{id}/edit', [WebmasterSectionsController::class, 'edit'])->name('WebmasterSectionsEdit');
Route::post('/webmaster/sections/{id}/update',
    [WebmasterSectionsController::class, 'update'])->name('WebmasterSectionsUpdate');

Route::post('/webmaster/sections/{id}/seo', [WebmasterSectionsController::class, 'seo'])->name('WebmasterSectionsSEOUpdate');

Route::get('/webmaster/sections/destroy/{id}',
    [WebmasterSectionsController::class, 'destroy'])->name('WebmasterSectionsDestroy');
Route::post('/webmaster/sections/updateAll',
    [WebmasterSectionsController::class, 'updateAll'])->name('WebmasterSectionsUpdateAll');

// Webmaster Sections :Custom Fields
Route::get('/webmaster/{webmasterId}/fields', [WebmasterSectionsController::class, 'webmasterFields'])->name('webmasterFields');
Route::get('/{webmasterId}/fields/create', [WebmasterSectionsController::class, 'fieldsCreate'])->name('webmasterFieldsCreate');
Route::post('/webmaster/{webmasterId}/fields/store', [WebmasterSectionsController::class, 'fieldsStore'])->name('webmasterFieldsStore');
Route::get('/webmaster/{webmasterId}/fields/{field_id}/edit', [WebmasterSectionsController::class, 'fieldsEdit'])->name('webmasterFieldsEdit');
Route::post('/webmaster/{webmasterId}/fields/{field_id}/update', [WebmasterSectionsController::class, 'fieldsUpdate'])->name('webmasterFieldsUpdate');
Route::get('/webmaster/{webmasterId}/fields/destroy/{field_id}', [WebmasterSectionsController::class, 'fieldsDestroy'])->name('webmasterFieldsDestroy');
Route::post('/webmaster/{webmasterId}/fields/updateAll', [WebmasterSectionsController::class, 'fieldsUpdateAll'])->name('webmasterFieldsUpdateAll');

// Settings
Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
Route::post('/settings', [SettingsController::class, 'updateSiteInfo'])->name('settingsUpdateSiteInfo');

// Ad. Banners
Route::get('/banners', [BannersController::class, 'index'])->name('Banners');
Route::get('/banners/create/{sectionId}', [BannersController::class, 'create'])->name('BannersCreate');
Route::post('/banners/store', [BannersController::class, 'store'])->name('BannersStore');
Route::get('/banners/{id}/edit', [BannersController::class, 'edit'])->name('BannersEdit');
Route::post('/banners/{id}/update', [BannersController::class, 'update'])->name('BannersUpdate');
Route::get('/banners/destroy/{id}', [BannersController::class, 'destroy'])->name('BannersDestroy');
Route::post('/banners/updateAll', [BannersController::class, 'updateAll'])->name('BannersUpdateAll');

// Sections
Route::get('/{webmasterId}/categories', [CategoriesController::class, 'index'])->name('categories');
Route::get('/{webmasterId}/categories/create', [CategoriesController::class, 'create'])->name('categoriesCreate');
Route::post('/{webmasterId}/categories/store', [CategoriesController::class, 'store'])->name('categoriesStore');
Route::get('/{webmasterId}/categories/{id}/edit', [CategoriesController::class, 'edit'])->name('categoriesEdit');
Route::post('/{webmasterId}/categories/{id}/update', [CategoriesController::class, 'update'])->name('categoriesUpdate');
Route::post('/{webmasterId}/categories/{id}/seo', [CategoriesController::class, 'seo'])->name('categoriesSEOUpdate');
Route::get('/{webmasterId}/categories/destroy/{id?}', [CategoriesController::class, 'destroy'])->name('categoriesDestroy');
Route::post('/{webmasterId}/categories/updateAll', [CategoriesController::class, 'updateAll'])->name('categoriesUpdateAll');

// Topics
Route::get('/{webmasterId}/topics', [TopicsController::class, 'index'])->name('topics');
Route::post('/topics-list', [TopicsController::class, 'list'])->name('topicsList');
Route::get('/{webmasterId}/view/{id}', [TopicsController::class, 'view'])->name('topicView');
Route::get('/{webmasterId}/topics/create', [TopicsController::class, 'create'])->name('topicsCreate');
Route::post('/{webmasterId}/topics/store', [TopicsController::class, 'store'])->name('topicsStore');
Route::get('/{webmasterId}/topics/{id}/edit', [TopicsController::class, 'edit'])->name('topicsEdit');
Route::post('/{webmasterId}/topics/{id}/update', [TopicsController::class, 'update'])->name('topicsUpdate');
Route::get('/{webmasterId}/topics/destroy/{id?}', [TopicsController::class, 'destroy'])->name('topicsDestroy');
Route::post('/{webmasterId}/topics/updateAll', [TopicsController::class, 'updateAll'])->name('topicsUpdateAll');
Route::get('/{webmasterId}/print', [TopicsController::class, 'print'])->name('topicsPrint');
// Topics :SEO
Route::post('/{webmasterId}/topics/{id}/seo', [TopicsController::class, 'seo'])->name('topicsSEOUpdate');
// Topics :Photos
Route::post('/topics/upload', [TopicsController::class, 'upload'])->name('topicsPhotosUpload');
Route::post('/{webmasterId}/topics/{id}/photos', [TopicsController::class, 'photos'])->name('topicsPhotosEdit');
Route::get('/{webmasterId}/topics/{id}/photos/{photo_id}/destroy',
    [TopicsController::class, 'photosDestroy'])->name('topicsPhotosDestroy');
Route::post('/{webmasterId}/topics/{id}/photos/updateAll',
    [TopicsController::class, 'photosUpdateAll'])->name('topicsPhotosUpdateAll');

// Topics :Files
Route::get('/{webmasterId}/topics/{id}/files', [TopicsController::class, 'topicsFiles'])->name('topicsFiles');
Route::get('/{webmasterId}/topics/{id}/files/create',
    [TopicsController::class, 'filesCreate'])->name('topicsFilesCreate');
Route::post('/{webmasterId}/topics/{id}/files/store',
    [TopicsController::class, 'filesStore'])->name('topicsFilesStore');
Route::get('/{webmasterId}/topics/{id}/files/{file_id}/edit',
    [TopicsController::class, 'filesEdit'])->name('topicsFilesEdit');
Route::post('/{webmasterId}/topics/{id}/files/{file_id}/update',
    [TopicsController::class, 'filesUpdate'])->name('topicsFilesUpdate');
Route::get('/{webmasterId}/topics/{id}/files/destroy/{file_id}',
    [TopicsController::class, 'filesDestroy'])->name('topicsFilesDestroy');
Route::post('/{webmasterId}/topics/{id}/files/updateAll',
    [TopicsController::class, 'filesUpdateAll'])->name('topicsFilesUpdateAll');


// Topics :Related
Route::get('/{webmasterId}/topics/{id}/related', [TopicsController::class, 'topicsRelated'])->name('topicsRelated');
Route::get('/relatedLoad/{id}', [TopicsController::class, 'topicsRelatedLoad'])->name('topicsRelatedLoad');
Route::get('/{webmasterId}/topics/{id}/related/create',
    [TopicsController::class, 'relatedCreate'])->name('topicsRelatedCreate');
Route::post('/{webmasterId}/topics/{id}/related/store',
    [TopicsController::class, 'relatedStore'])->name('topicsRelatedStore');
Route::get('/{webmasterId}/topics/{id}/related/destroy/{related_id}',
    [TopicsController::class, 'relatedDestroy'])->name('topicsRelatedDestroy');
Route::post('/{webmasterId}/topics/{id}/related/updateAll',
    [TopicsController::class, 'relatedUpdateAll'])->name('topicsRelatedUpdateAll');
// Topics :Comments
Route::get('/{webmasterId}/topics/{id}/comments', [TopicsController::class, 'topicsComments'])->name('topicsComments');
Route::get('/{webmasterId}/topics/{id}/comments/create',
    [TopicsController::class, 'commentsCreate'])->name('topicsCommentsCreate');
Route::post('/{webmasterId}/topics/{id}/comments/store',
    [TopicsController::class, 'commentsStore'])->name('topicsCommentsStore');
Route::get('/{webmasterId}/topics/{id}/comments/{comment_id}/edit',
    [TopicsController::class, 'commentsEdit'])->name('topicsCommentsEdit');
Route::post('/{webmasterId}/topics/{id}/comments/{comment_id}/update',
    [TopicsController::class, 'commentsUpdate'])->name('topicsCommentsUpdate');
Route::get('/{webmasterId}/topics/{id}/comments/destroy/{comment_id}',
    [TopicsController::class, 'commentsDestroy'])->name('topicsCommentsDestroy');
Route::post('/{webmasterId}/topics/{id}/comments/updateAll',
    [TopicsController::class, 'commentsUpdateAll'])->name('topicsCommentsUpdateAll');
// Topics :Maps
Route::get('/{webmasterId}/topics/{id}/maps', [TopicsController::class, 'topicsMaps'])->name('topicsMaps');
Route::get('/{webmasterId}/topics/{id}/maps/create', [TopicsController::class, 'mapsCreate'])->name('topicsMapsCreate');
Route::post('/{webmasterId}/topics/{id}/maps/store', [TopicsController::class, 'mapsStore'])->name('topicsMapsStore');
Route::get('/{webmasterId}/topics/{id}/maps/{map_id}/edit', [TopicsController::class, 'mapsEdit'])->name('topicsMapsEdit');
Route::post('/{webmasterId}/topics/{id}/maps/{map_id}/update',
    [TopicsController::class, 'mapsUpdate'])->name('topicsMapsUpdate');
Route::get('/{webmasterId}/topics/{id}/maps/destroy/{map_id}',
    [TopicsController::class, 'mapsDestroy'])->name('topicsMapsDestroy');
Route::post('/{webmasterId}/topics/{id}/maps/updateAll',
    [TopicsController::class, 'mapsUpdateAll'])->name('topicsMapsUpdateAll');

// Contacts Groups
Route::post('/contacts/storeGroup', [ContactsController::class, 'storeGroup'])->name('contactsStoreGroup');
Route::get('/contacts/{id}/editGroup', [ContactsController::class, 'editGroup'])->name('contactsEditGroup');
Route::post('/contacts/{id}/updateGroup', [ContactsController::class, 'updateGroup'])->name('contactsUpdateGroup');
Route::get('/contacts/destroyGroup/{id}', [ContactsController::class, 'destroyGroup'])->name('contactsDestroyGroup');
// Contacts
Route::get('/contacts/{group_id?}', [ContactsController::class, 'index'])->name('contacts');
Route::post('/contacts/store', [ContactsController::class, 'store'])->name('contactsStore');
Route::post('/contacts/search', [ContactsController::class, 'search'])->name('contactsSearch');
Route::get('/contacts/{id}/edit', [ContactsController::class, 'edit'])->name('contactsEdit');
Route::post('/contacts/{id}/update', [ContactsController::class, 'update'])->name('contactsUpdate');
Route::get('/contacts/destroy/{id}', [ContactsController::class, 'destroy'])->name('contactsDestroy');
Route::post('/contacts/updateAll', [ContactsController::class, 'updateAll'])->name('contactsUpdateAll');

// WebMails Groups
Route::post('/webmails/storeGroup', [WebmailsController::class, 'storeGroup'])->name('webmailsStoreGroup');
Route::get('/webmails/{id}/editGroup', [WebmailsController::class, 'editGroup'])->name('webmailsEditGroup');
Route::post('/webmails/{id}/updateGroup', [WebmailsController::class, 'updateGroup'])->name('webmailsUpdateGroup');
Route::get('/webmails/destroyGroup/{id}', [WebmailsController::class, 'destroyGroup'])->name('webmailsDestroyGroup');
// WebMails
Route::post('/webmails/store', [WebmailsController::class, 'store'])->name('webmailsStore');
Route::post('/webmails/search', [WebmailsController::class, 'search'])->name('webmailsSearch');
Route::get('/webmails/{id}/edit', [WebmailsController::class, 'edit'])->name('webmailsEdit');
Route::get('/webmails/{group_id?}/{wid?}/{stat?}/{contact_email?}', [WebmailsController::class, 'index'])->name('webmails');
Route::post('/webmails/{id}/update', [WebmailsController::class, 'update'])->name('webmailsUpdate');
Route::get('/webmails/destroy/{id}', [WebmailsController::class, 'destroy'])->name('webmailsDestroy');
Route::post('/webmails/updateAll', [WebmailsController::class, 'updateAll'])->name('webmailsUpdateAll');

// Calendar
Route::get('/calendar', [EventsController::class, 'index'])->name('calendar');
Route::get('/calendar/create', [EventsController::class, 'create'])->name('calendarCreate');
Route::post('/calendar/store', [EventsController::class, 'store'])->name('calendarStore');
Route::get('/calendar/{id}/edit', [EventsController::class, 'edit'])->name('calendarEdit');
Route::post('/calendar/{id}/update', [EventsController::class, 'update'])->name('calendarUpdate');
Route::get('/calendar/destroy/{id}', [EventsController::class, 'destroy'])->name('calendarDestroy');
Route::get('/calendar/updateAll', [EventsController::class, 'updateAll'])->name('calendarUpdateAll');
Route::post('/calendar/{id}/extend', [EventsController::class, 'extend'])->name('calendarExtend');

// Analytics
Route::get('/ip/{ip_code?}', [AnalyticsController::class, 'ip'])->name('visitorsIP');
Route::post('/ip/search', [AnalyticsController::class, 'search'])->name('visitorsSearch');
Route::post('/analytics/{stat}', [AnalyticsController::class, 'filter'])->name('analyticsFilter');
Route::get('/analytics/{stat?}', [AnalyticsController::class, 'index'])->name('analytics');
Route::get('/visitors', [AnalyticsController::class, 'visitors'])->name('visitors');

// Users & Permissions
Route::get('/users', [UsersController::class, 'index'])->name('users');
Route::get('/users/create/', [UsersController::class, 'create'])->name('usersCreate');
Route::post('/users/store', [UsersController::class, 'store'])->name('usersStore');
Route::get('/users/{id}/edit', [UsersController::class, 'edit'])->name('usersEdit');
Route::post('/users/{id}/update', [UsersController::class, 'update'])->name('usersUpdate');
Route::post('/me/theme', [UsersController::class, 'saveTheme'])->name('userTheme');
Route::post('/company-settings/update', [UsersController::class, 'updateCompanySettings'])->name('companySettingsUpdate');
Route::post('/users/{id}/update-work-config', [UsersController::class, 'updateWorkConfig'])->name('usersUpdateWorkConfig');
Route::post('/users/{id}/update-role', [UsersController::class, 'updateRole'])->name('usersUpdateRole');
Route::post('/users/roles/quick-store', [UsersController::class, 'quickStoreRole'])->name('rolesQuickStore');
Route::post('/users/roles/{id}/ajax-update', [UsersController::class, 'ajaxUpdateRoleName'])->name('rolesAjaxUpdate');
Route::delete('/users/roles/{id}/ajax-delete', [UsersController::class, 'ajaxDeleteRole'])->name('rolesAjaxDelete');
Route::get('/users/roles/ajax-list', [UsersController::class, 'ajaxGetRolesList'])->name('rolesAjaxList');
Route::post('/users/license/sync-did', [UsersController::class, 'syncDid'])->name('usersSyncDid');
Route::post('/users/license/delete', [UsersController::class, 'deleteLicense'])->name('usersDeleteLicense');
Route::get('/users/license/info', [UsersController::class, 'getLicenseInfo'])->name('usersGetLicenseInfo');
Route::get('/users/destroy/{id}', [UsersController::class, 'destroy'])->name('usersDestroy');
Route::post('/users/updateAll', [UsersController::class, 'updateAll'])->name('usersUpdateAll');

Route::get('/users/user-groups', [UsersController::class, 'userGroups'])->name('userGroups');
Route::get('/users/permissions/create/', [UsersController::class, 'permissions_create'])->name('permissionsCreate');
Route::post('/users/permissions/store', [UsersController::class, 'permissions_store'])->name('permissionsStore');
Route::get('/users/permissions/{id}/edit', [UsersController::class, 'permissions_edit'])->name('permissionsEdit');
Route::post('/users/permissions/{id}/update', [UsersController::class, 'permissions_update'])->name('permissionsUpdate');
Route::post('/users/permissions/{id}/save', [UsersController::class, 'update_custom_home'])->name('permissionsHomePageUpdate');
Route::get('/users/permissions/destroy/{id}', [UsersController::class, 'permissions_destroy'])->name('permissionsDestroy');

Route::post('/permissions-links/store', [UsersController::class, 'links_store'])->name('customLinksStore');
Route::post('/permissions-links/update', [UsersController::class, 'links_update'])->name('customLinksUpdate');
Route::get('/permissions-links/edit/{id?}/{p_id?}', [UsersController::class, 'links_edit'])->name('customLinksEdit');
Route::get('/permissions-links/destroy/{id?}/{p_id?}', [UsersController::class, 'links_destroy'])->name('customLinksDestroy');
Route::get('/permissions-links/list/{p_id?}', [UsersController::class, 'links_list'])->name('customLinksList');

// Teams
Route::get('/teams', [UsersController::class, 'teams'])->name('teams');
Route::get('/teams/createTeam/', [UsersController::class, 'createTeam'])->name('teamsCreate');
Route::post('/teams/storeTeam', [UsersController::class, 'storeTeam'])->name('teamsStore');
Route::get('/teams/{id}/edit', [UsersController::class, 'editTeam'])->name('teamsEdit');
Route::post('/teams/{id}/update', [UsersController::class, 'updateTeam'])->name('teamsUpdate');
Route::get('/teams/{id}/destroy', [UsersController::class, 'destroyTeam'])->name('teamsDestroy');
Route::get('/teams/{id}/getTeamMembers', [UsersController::class, 'getTeamMembers'])->name('getTeamMembers');

// Campaigns
Route::get('/campaigns', [CampaignsController::class, 'index'])->name('campaigns');
Route::get('/campaigns/performance', [CampaignsController::class, 'performance'])->name('campaignPerformance');
Route::get('/campaign-performance', [CampaignsController::class, 'performance'])->name('campaignPerformanceAlias');
Route::post('/campaignsData', [CampaignsController::class, 'campaignsData'])->name('campaignsData');
Route::get('/campaigns/create', [CampaignsController::class, 'create'])->name('campaignsCreate');
Route::get('/campaigns/sample-file', [CampaignsController::class, 'downloadSampleFile'])->name('campaignsSampleFile');
Route::post('/campaigns/store', [CampaignsController::class, 'store'])->name('campaignsStore');

Route::post('campaign/{id}/random-allocation', [CampaignsController::class, 'randomAllocation'])->name('randomAllocation');
Route::get('/campaigns/{id}/edit', [CampaignsController::class, 'edit'])->name('campaignsEdit');
Route::post('/campaigns/{id}/update', [CampaignsController::class, 'update'])->name('campaignsUpdate');
Route::get('/campaigns/{id}/view', [CampaignsController::class, 'view'])->name('campaignsView');
Route::get('/campaigns/{campaignId}/lead/{leadId}/calling', [CampaignsController::class, 'leadCallingView'])->name('leadCallingView');
Route::post('/campaigns/{campaignId}/lead/{leadId}/save-disposition', [CampaignsController::class, 'saveLeadCallingDisposition'])->name('saveLeadCallingDisposition');
Route::get('/campaigns/{campaignId}/lead/{leadId}/calling-data', [CampaignsController::class, 'getLeadCallingData'])->name('getLeadCallingData');
Route::get('/campaigns/{campaignId}/queue-leads', [CampaignsController::class, 'getCampaignQueueLeads'])->name('campaignQueueLeads');
Route::post('/campaigns/{id}/toggle-status', [CampaignsController::class, 'toggleStatus'])->name('campaignsToggleStatus');
Route::get('/campaigns/{id}/delete', [CampaignsController::class, 'delete'])->name('campaignsDelete');
Route::post('/campaigns/{id}/destroy', [CampaignsController::class, 'destroyAjax'])->name('campaignsDestroyAjax');
Route::get('/campaigns/{id}/export-leads', [CampaignsController::class, 'exportLeads'])->name('campaignsExportLeads');
Route::post('/campaigns/leadsList/{id?}', [CampaignsController::class, 'leadsList'])->name('leadsList');
Route::post('/campaigns/transferList/{id?}', [CampaignsController::class, 'transferList'])->name('transferList');

Route::get('/campaigns/transfer-accounts', [CampaignsController::class, 'transferAccountsPage'])->name('campaignsTransferAccounts');
Route::post('/campaigns/transfer-accounts/data', [CampaignsController::class, 'transferAccountsData'])->name('campaignsTransferAccountsData');
Route::get('/campaigns/transfer-accounts/filter-options', [CampaignsController::class, 'transferAccountsFilterOptions'])->name('campaignsTransferAccountsFilterOptions');

Route::get('/campaigns/auto-distribute', [CampaignsController::class, 'autoDistributePage'])->name('campaignsAutoDistribute');
Route::get('/campaigns/{id}/auto-distribute-details', [CampaignsController::class, 'getCampaignDistributionDetails'])->name('campaignsAutoDistributeDetails');
Route::post('/campaigns/auto-distribute/execute', [CampaignsController::class, 'executeAutoDistribution'])->name('campaignsAutoDistributeExecute');

Route::get('/campaigns/{id}/transfer', [CampaignsController::class, 'transfer'])->name('leadsTransfer');
Route::post('/campaigns/transferAction', [CampaignsController::class, 'transferAction'])->name('leadsTransferAction');

Route::post('/campaigns/unassign', [CampaignsController::class, 'transferUnassign'])->name('leadsUnassignAction');
Route::get('/campaigns/inbound_campaigns', [CampaignsController::class, 'inbound'])->name('inboundCampaigns');
Route::post('/campaigns/inbound_campaigns/store', [CampaignsController::class, 'inboundStore'])->name('inboundCampaignsStore');
Route::post('/campaigns/inbound_campaigns_data', [CampaignsController::class, 'inboundData'])->name('inboundData');
Route::get('/campaigns/inbound_campaigns/download/{id}', [CampaignsController::class, 'downloadQrCode'])->name('inboundQrcode');
Route::get('/campaigns/duplicate', [CampaignsController::class, 'duplicate'])->name('campaignsDuplicate');
Route::post('/campaigns/dup_store', [CampaignsController::class, 'dupStore'])->name('campaignsDupStore');

// Campaigns
Route::get('/mobileDevices', [MobileDevicesController::class, 'index'])->name('mobileDevices');
Route::post('/mobileDevicesData', [MobileDevicesController::class, 'mobileDevicesData'])->name('mobileDevicesData');
Route::get('/updateMobileDevice/{id}/{status}', [MobileDevicesController::class, 'update'])->name('updateMobileDevice');




    Route::get('flexidial',  [FlexiDialerController::class,'flexidialView'])->name('admin.callmanager.flexidial');
    Route::post('flexidial-data', [FlexiDialerController::class,'flexidialData'])->name('admin.callmanager.flexidial-data');
    Route::get('create-flexidial',  [FlexiDialerController::class,'createFlexidial'])->name('admin.callmanager.create-flexidial');
    Route::post('save-flexidial', [FlexiDialerController::class,'saveFlexidial'])->name('admin.callmanager.save-flexidial');
    Route::post('flexidial-status',  [FlexiDialerController::class,'flexidialStatus'])->name('admin.callmanager.flexidial-status');
    Route::post('flexidial-clear',  [FlexiDialerController::class,'flexidialClear'])->name('admin.callmanager.flexidial-clear');
    Route::get('flexidial_running', [FlexiDialerController::class,'flexidial_sse'])->name('admin.callmanager.flexidial_running');
    Route::get('flexidial-options/{id}',  [FlexiDialerController::class,'flexidial_options'])->name('admin.flexidial-options');
    Route::post('active-flexidial',  [FlexiDialerController::class,'activeFlexidial'])->name('admin.callmanager.active-flexidial');
    Route::post('refresh-flexidial', [FlexiDialerController::class,'refreshFlexidial'])->name('admin.callmanager.refresh-flexidial');

    Route::post('save-webrtc-flexidial', [FlexiDialerController::class,'saveWebRTCFlexidial'])->name('admin.callmanager.save-webrtc-flexidial');
    Route::post('webrtc_flexi_fetch',  [FlexiDialerController::class,'webrtcFlexiFetch'])->name('admin.webrtc.flexi.fetch');
    Route::get('webrtc-flexidial', [FlexiDialerController::class,'webRTCFlexidialView'])->name('admin.callmanager.webrtc-flexidial');
    Route::post('webrtc-flexidial-status', [FlexiDialerController::class,'webrtcflexidialStatus'])->name('admin.callmanager.webrtc-flexidial-status');
    Route::post('webrtc-flexidial-clear', [FlexiDialerController::class,'webrtcflexidialClear'])->name('admin.callmanager.webrtc-flexidial-clear');
    Route::get('webrtc_flexidial_running', [FlexiDialerController::class,'webrtc_flexidial_sse'])->name('admin.callmanager.webrtc-flexidial_running');
    Route::get('webrtc-flexidial-options/{id}',  [FlexiDialerController::class,'webrtcflexidial_options'])->name('admin.webrtc-flexidial-options');
    Route::post('webrtc-active-flexidial',[FlexiDialerController::class,'webrtcactiveFlexidial'])->name('admin.callmanager.webrtc-active-flexidial');
    Route::post('webrtc-refresh-flexidial',  [FlexiDialerController::class,'webrtcrefreshFlexidial'])->name('admin.callmanager.webrtc-refresh-flexidial');
    Route::post('webrtc-flexidial-data',  [FlexiDialerController::class,'webrtcflexidialData'])->name('admin.callmanager.webrtc-flexidial-data');
    Route::post('webrtc/sync-connected-calls',  [FlexiDialerController::class,'syncConnectedCalls'])->name('admin.callmanager.webrtc-flexidial-connected');


    // Flexi Dialer Vouchers routes
    Route::get('flexi_dialer_vouchers',  [FlexiDialerController::class,'index'])->name('admin.flexidialer.flexi_dialer_vouchers');
    Route::post('flexi_dialer_voucher_add',  [FlexiDialerController::class,'store'])->name('admin.flexidialer.flexi_dialer_voucher_add');
    Route::get('edit_flexi_dialer_voucher/{id}', [FlexiDialerController::class,'index'])->name('admin.flexidialer.edit_flexi_dialer_voucher');
    Route::post('flexi_dialer_voucher_update/{id}',  [FlexiDialerController::class,'index'])->name('admin.flexidialer.update_flexi_dialer_voucher');
    Route::post('flexi_dialer_voucher_delete/{id}',[FlexiDialerController::class,'index'])->name('admin.flexidialer.flexi_dialer_voucher_delete');


// Sources
Route::get('/sources', [SourcesController::class, 'index'])->name('sources');
Route::get('/sources/create/', [SourcesController::class, 'create'])->name('sourcesCreate');
Route::post('/sources/store', [SourcesController::class, 'store'])->name('sourcesStore');
Route::get('/sources/{id}/edit', [SourcesController::class, 'edit'])->name('sourcesEdit');
Route::post('/sources/{id}/update', [SourcesController::class, 'update'])->name('sourcesUpdate');
Route::delete('/sources/{id}', [SourcesController::class, 'destroy'])->name('sourcesDestroy');

// Segments
Route::get('/segments', [SegmentsController::class, 'index'])->name('segments');
Route::get('/segments/create/', [SegmentsController::class, 'create'])->name('segmentsCreate');
Route::post('/segments/store', [SegmentsController::class, 'store'])->name('segmentsStore');
Route::get('/segments/{id}/edit', [SegmentsController::class, 'edit'])->name('segmentsEdit');
Route::post('/segments/{id}/update', [SegmentsController::class, 'update'])->name('segmentsUpdate');
Route::get('/segments/{id}/delete', [SegmentsController::class, 'destroy'])->name('segmentsDelete');
Route::delete('/segments/{id}', [SegmentsController::class, 'destroy'])->name('segmentsDestroy');


// SMS
Route::get('/sms', [SmsController::class, 'index'])->name('sms');
Route::get('/sms/create/', [SmsController::class, 'create'])->name('smsCreate');
Route::post('/sms/store', [SmsController::class, 'store'])->name('smsStore');
Route::get('/sms/{id}/edit', [SmsController::class, 'edit'])->name('smsEdit');
Route::post('/sms/{id}/update', [SmsController::class, 'update'])->name('smsUpdate');

// E-Mail
Route::get('/email', [EmailController::class, 'index'])->name('email');
Route::get('/email/create/', [EmailController::class, 'create'])->name('emailCreate');
Route::post('/email/store', [EmailController::class, 'store'])->name('emailStore');
Route::get('/email/{id}/edit', [EmailController::class, 'edit'])->name('emailEdit');
Route::post('/email/{id}/update', [EmailController::class, 'update'])->name('emailUpdate');

//IMAP
Route::get('/imap', [ImapController::class, 'index'])->name('imap');
Route::get('/imap/create/', [ImapController::class, 'create'])->name('imapCreate');
Route::post('/imap/store', [ImapController::class, 'store'])->name('imapStore');
Route::get('/imap/{id}/edit', [ImapController::class, 'edit'])->name('imapEdit');
Route::post('/imap/{id}/update', [ImapController::class, 'update'])->name('imapUpdate');
Route::post('/imap/fetchFolders', [ImapController::class, 'fetchFolders'])->name('imapFetchFolders');

//Mailbox
Route::get('/mailbox/{id}/edit', [MailboxController::class, 'edit'])->name('mailboxEdit');
Route::post('/mailbox/search', [MailboxController::class, 'search'])->name('mailboxSearch');
Route::post('/mailbox/store', [MailboxController::class, 'store'])->name('mailboxStore');
Route::get('/mailbox/{group_id?}/{wid?}/{stat?}/{contact_email?}', [MailboxController::class, 'index'])->name('mailbox');

// Statuses
Route::get('/disposition-settings', [StatusesController::class, 'dispositionSettings'])->name('dispositionSettings');
Route::get('/statuses', [StatusesController::class, 'index'])->name('statuses');
Route::get('/statuses/create/', [StatusesController::class, 'create'])->name('statusesCreate');
Route::post('/statuses/store', [StatusesController::class, 'store'])->name('statusesStore');
Route::get('/statuses/{id}/edit', [StatusesController::class, 'edit'])->name('statusesEdit');
Route::post('/statuses/{id}/update', [StatusesController::class, 'update'])->name('statusesUpdate');
Route::post('/statuses/substatus/store', [StatusesController::class, 'ajaxStoreSubstatus'])->name('statusesSubstatusStore');
Route::post('/statuses/substatus/delete', [StatusesController::class, 'ajaxDeleteSubstatus'])->name('statusesSubstatusDelete');
Route::post('/statuses/toggle-followup', [StatusesController::class, 'ajaxToggleFollowup'])->name('statusesToggleFollowup');


// Substatuses
Route::get('/substatuses', [SubstatusesController::class, 'index'])->name('substatuses');
Route::get('/substatuses/create/', [SubstatusesController::class, 'create'])->name('substatusesCreate');
Route::post('/substatuses/store', [SubstatusesController::class, 'store'])->name('substatusesStore');
Route::get('/substatuses/{id}/edit', [SubstatusesController::class, 'edit'])->name('substatusesEdit');
Route::post('/substatuses/{id}/update', [SubstatusesController::class, 'update'])->name('substatusesUpdate');

// Products & Services
Route::get('/productServices', [ProductsServicesController::class, 'index'])->name('productServices');
Route::post('/productServicesData', [ProductsServicesController::class, 'productServicesData'])->name('productServicesData');
Route::get('/productServices/create/', [ProductsServicesController::class, 'create'])->name('productServicesCreate');
Route::post('/productServices/store', [ProductsServicesController::class, 'store'])->name('productServicesStore');
Route::get('/productServices/{id}/edit', [ProductsServicesController::class, 'edit'])->name('productServicesEdit');
Route::post('/productServices/{id}/update', [ProductsServicesController::class, 'update'])->name('productServicesUpdate');
Route::delete('/productServices/{id}', [ProductsServicesController::class, 'destroy'])->name('productServicesDestroy');

//client-types
Route::get('/clientTypes', [ClientTypesController::class, 'index'])->name('clientTypes');
Route::post('/clientTypesData', [ClientTypesController::class, 'productServicesData'])->name('clientTypesData');
Route::get('/clientTypes/create/', [ClientTypesController::class, 'create'])->name('clientTypesCreate');
Route::post('/clientTypes/store', [ClientTypesController::class, 'store'])->name('clientTypesStore');
Route::get('/clientTypes/{id}/edit', [ClientTypesController::class, 'edit'])->name('clientTypesEdit');
Route::post('/clientTypes/{id}/update', [ClientTypesController::class, 'update'])->name('clientTypesUpdate');

// Call Channels Selection Page & Toggle
Route::get('/call-channels', [CallChannelsController::class, 'index'])->name('callChannels');
Route::post('/call-channels/toggle', [CallChannelsController::class, 'toggle'])->name('callChannelsToggle');

// Click2Call
Route::get('/click2Call', [Click2CallController::class, 'index'])->name('click2Call');
Route::get('/click2call', [Click2CallController::class, 'index']);
Route::post('/click2CallData', [Click2CallController::class, 'click2CallData'])->name('click2CallData');
Route::post('/click2callData', [Click2CallController::class, 'click2CallData']);
Route::get('/click2Call/create/', [Click2CallController::class, 'create'])->name('click2CallCreate');
Route::post('/click2Call/store', [Click2CallController::class, 'store'])->name('click2CallStore');
Route::get('/click2Call/{id}/edit', [Click2CallController::class, 'edit'])->name('click2CallEdit');
Route::get('/click2Call/{id}/delete', [Click2CallController::class, 'delete'])->name('click2CallDelete');

// Calling Settings
Route::get('/calling-settings', [Click2CallController::class, 'callingSettings'])->name('callingSettings');
Route::post('/calling-settings/update', [Click2CallController::class, 'updateCallingSettings'])->name('callingSettingsUpdate');

Route::get('/webrtc', [WebRTCController::class, 'index'])->name('webrtc');
Route::post('/webrtcData', [WebRTCController::class, 'webrtcData'])->name('webrtcData');
Route::get('/webrtc/create/', [WebRTCController::class, 'create'])->name('webrtcCreate');
Route::post('/webrtc/store', [WebRTCController::class, 'store'])->name('webrtcStore');
Route::get('/webrtc/{id}/edit', [WebRTCController::class, 'edit'])->name('webrtcEdit');
Route::get('/webrtc/{id}/delete', [WebRTCController::class, 'delete'])->name('webrtcDelete');
Route::post('/webrtc/toggle/{id}', [WebRTCController::class, 'webrtcToggle'])->name('webrtcToggle');
// Configuration > Companies (Super Admin): the companies list, each company with its own database
Route::get('/companies', [\App\Http\Controllers\Dashboard\CompaniesController::class, 'index'])->name('companies');
Route::get('/companies/create', [\App\Http\Controllers\Dashboard\CompaniesController::class, 'create'])->name('companiesCreate');
Route::post('/companies', [\App\Http\Controllers\Dashboard\CompaniesController::class, 'store'])->name('companiesStore');
Route::post('/companies/test-connection', [\App\Http\Controllers\Dashboard\CompaniesController::class, 'testConnection'])->middleware('throttle:30,1')->name('companiesTestConnection');
Route::get('/companies/{company}/edit', [\App\Http\Controllers\Dashboard\CompaniesController::class, 'edit'])->whereNumber('company')->name('companiesEdit');
Route::put('/companies/{company}', [\App\Http\Controllers\Dashboard\CompaniesController::class, 'update'])->whereNumber('company')->name('companiesUpdate');
Route::post('/companies/{company}/status', [\App\Http\Controllers\Dashboard\CompaniesController::class, 'status'])->whereNumber('company')->name('companiesStatus');
Route::get('/companies/{company}/check', [\App\Http\Controllers\Dashboard\CompaniesController::class, 'check'])->whereNumber('company')->middleware('throttle:120,1')->name('companiesCheck');

// Work Locations
Route::get('/workLocations', [WorkLocationsController::class, 'index'])->name('workLocations');
Route::get('/workLocations/create/', [WorkLocationsController::class, 'create'])->name('workLocationsCreate');
Route::post('/workLocations/store', [WorkLocationsController::class, 'store'])->name('workLocationsStore');
Route::get('/workLocations/{id}/edit', [WorkLocationsController::class, 'edit'])->name('workLocationsEdit');
Route::post('/workLocations/{id}/update', [WorkLocationsController::class, 'update'])->name('workLocationsUpdate');

// Shifts
Route::get('/shifts', [ShiftsController::class, 'index'])->name('shifts');
Route::get('/shifts/ajax-list', [ShiftsController::class, 'ajaxList'])->name('shiftsAjaxList');
Route::post('/shifts/ajax-store', [ShiftsController::class, 'ajaxStore'])->name('shiftsAjaxStore');
Route::post('/shifts/{id}/ajax-update', [ShiftsController::class, 'ajaxUpdate'])->name('shiftsAjaxUpdate');
Route::delete('/shifts/{id}/ajax-delete', [ShiftsController::class, 'ajaxDelete'])->name('shiftsAjaxDelete');
Route::get('/shifts/create/', [ShiftsController::class, 'create'])->name('shiftsCreate');
Route::post('/shifts/store', [ShiftsController::class, 'store'])->name('shiftsStore');
Route::get('/shifts/{id}/edit', [ShiftsController::class, 'edit'])->name('shiftsEdit');
Route::post('/shifts/{id}/update', [ShiftsController::class, 'update'])->name('shiftsUpdate');

// Notifications
Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications');
Route::get('/notifications/{id}/view', [NotificationsController::class, 'view'])->name('notificationView');
Route::get('/notifications/createAnnouncement', [NotificationsController::class, 'createAnnouncement'])->name('createAnnouncement');
Route::post('/notifications/getTeamMembers', [NotificationsController::class, 'getTeamMembers'])->name('notificationsGetTeamMembers');
Route::post('/notifications/sendAnnouncement', [NotificationsController::class, 'sendAnnouncement'])->name('sendAnnouncement');

//Attendance
Route::get('/attendance/punch', [AttendanceController::class, 'index'])->name('punch');
Route::get('/attendance/onDuty', [AttendanceController::class, 'onDuty'])->name('onDuty');
Route::post('/attendance/onDutyData', [AttendanceController::class, 'onDutyData'])->name('onDutyData');
Route::get('/attendance/offDuty', [AttendanceController::class, 'offDuty'])->name('offDuty');
Route::post('/attendance/offDutyData', [AttendanceController::class, 'offDutyData'])->name('offDutyData');
Route::get('/attendance/{id}/punchUser', [AttendanceController::class, 'punchUser'])->name('punchUser');
Route::get('/attendance/history', [AttendanceController::class, 'history'])->name('attendanceHistory');
Route::post('/attendance/historyData', [AttendanceController::class, 'historyData'])->name('historyData');
Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendanceReport');

Route::get('/deals', [CustomersController::class, 'index'])->name('deals');
Route::get('/dialer', [DialerController::class, 'dialer'])->name('dialer');
Route::post('/dialer/click2call', [DialerController::class, 'click2call'])->name('dialerClick2call');
Route::get('/dialer/detect-channel', [DialerController::class, 'detectChannel'])->name('dialerDetectChannel');
Route::get('/dialer/call-status', [DialerController::class, 'callStatus'])->name('dialerCallStatus');
Route::post('/dialer/savelog', [DialerController::class, 'savelog'])->name('dialerSaveLog');
Route::get('/dialer/channel-config', [DialerController::class, 'getChannelConfig'])->name('dialerChannelConfig');
// Auto Dialer (Calling Workspace, Campaign Queue): session only; calls go through the normal campaign call routes
Route::get('/dialer/auto/status', [AutoDialerController::class, 'status'])->name('autoDialerStatus');
Route::post('/dialer/auto/start', [AutoDialerController::class, 'start'])->name('autoDialerStart');
Route::post('/dialer/auto/next', [AutoDialerController::class, 'next'])->name('autoDialerNext');
Route::post('/dialer/auto/event', [AutoDialerController::class, 'event'])->name('autoDialerEvent');
Route::post('/dialer/auto/pause', [AutoDialerController::class, 'pause'])->name('autoDialerPause');
Route::post('/dialer/auto/resume', [AutoDialerController::class, 'resume'])->name('autoDialerResume');
Route::post('/dialer/auto/stop', [AutoDialerController::class, 'stop'])->name('autoDialerStop');
Route::get('/dialer/today-performance', [DialerController::class, 'getTodayPerformance'])->name('dialerTodayPerformance');
Route::get('/dialer/recent-activity', [DialerController::class, 'getRecentActivity'])->name('dialerRecentActivity');
Route::post('/dialer/quick-note', [DialerController::class, 'quickNote'])->name('dialerQuickNote');
Route::post('/dialer/quick-followup', [DialerController::class, 'quickFollowup'])->name('dialerQuickFollowup');
Route::post('/dialer/quick-tag', [DialerController::class, 'quickTag'])->name('dialerQuickTag');
Route::get('/dialer/phone-history', [DialerController::class, 'phoneHistory'])->name('dialerPhoneHistory');
Route::post('/dialer/update-call-state', [DialerController::class, 'updateCallState'])->name('dialerUpdateCallState');
 

Route::get('/agreements/create', [AgreementController::class, 'create'])->name('agreements.create'); 
Route::post('/agreements/store', [AgreementController::class, 'store'])->name('agreements.store'); 
Route::get('/agreements', [AgreementController::class, 'index'])->name('agreements.index');
Route::get('/agreements/{id}/edit', [AgreementController::class, 'edit'])->name('agreements.edit');
Route::put('/agreements/{id}', [AgreementController::class, 'update'])->name('agreements.update');
// Agreement duplication routes
Route::get('/agreements/{id}/duplicate', [AgreementController::class, 'duplicate'])->name('agreements.duplicate');
Route::post('/agreements/{id}/duplicate/store', [AgreementController::class, 'duplicateStore'])->name('agreements.duplicate.store');

Route::delete('/agreements/{id}', [AgreementController::class, 'destroy'])->name('agreements.destroy');

Route::get('/agreements/verify-otp', [AgreementController::class, 'showOtpForm'])->name('agreements.otp.verify');
Route::post('/agreements/verify-otpcode', [AgreementController::class, 'verifyOtp'])->name('agreements.otp.submit');
Route::post('/agreements/resend-otp', [AgreementController::class, 'resendOtp'])->name('agreements.otp.resend');



Route::middleware('auth')->group(function() {
    Route::get('/file/view/{path}', [AgreementController::class, 'showFile'])
         ->where('path', '.*')
         ->name('file.view');

    Route::get('/file/download/{path}', [AgreementController::class, 'downloadFile'])
         ->where('path', '.*')
         ->name('file.download');
});



//Leads
Route::get('/leads', [LeadsController::class, 'index'])->name('leads');
Route::get('/leads/create/{id?}', [LeadsController::class, 'create'])->name('leadsCreate');
Route::get('/leads/sample-file', [LeadsController::class, 'downloadSampleFile'])->name('leadsSampleFile');
Route::post('/leads/store', [LeadsController::class, 'store'])->name('leadsStore');
Route::post('/leads/import', [LeadsController::class, 'importLeads'])->name('leadsImport');
Route::get('/leads/{id}/edit', [LeadsController::class, 'edit'])->name('leadsEdit');
Route::post('/leads/{id}/update', [LeadsController::class, 'update'])->name('leadsUpdate');
Route::get('/leads/{id}/view', [LeadsController::class, 'view'])->name('leadDetail');
Route::post('/leads/{id}/schedule-followup', [LeadsController::class, 'scheduleFollowup'])->name('leadsScheduleFollowup');
Route::post('/leads/{id}/reassign', [LeadsController::class, 'reassignLead'])->name('leadsReassign');

Route::post('/leads/{id}/data/', [LeadsController::class, 'data'])->name('leadData');
Route::post('/leads/log_call/', [LeadsController::class, 'logCall'])->name('logCall');
Route::post('/leads/duplicate_number/', [LeadsController::class, 'duplicateNumber'])->name('duplicateNumber');
Route::post('/leads/leadAction/', [LeadsController::class, 'leadAction'])->name('leadAction');
Route::get('/leads/getSubStatus/', [LeadsController::class, 'getSubStatus'])->name('getSubStatus');
Route::post('/leads/callImageUploads', [LeadsController::class, 'callImageUpload'])->name('callImageUploads');
Route::post('/leads/saveReview', [LeadsController::class, 'saveReview'])->name('saveReview');
Route::post('/leads/saveAuditReport', [LeadsController::class, 'saveAuditReport'])->name('saveAuditReport');
Route::post('/leads/sendSms', [LeadsController::class, 'sendSms'])->name('sendSms');
Route::post('/leads/sendEmail', [LeadsController::class, 'sendEmail'])->name('sendEmail');
Route::get('/leads/getTeamMembersByCampaign/{id}', [LeadsController::class, 'getTeamMembersByCampaign'])->name('getTeamMembersByCampaign');

Route::get('/sso-login-lead', [WebRTCController::class, 'loginSmartPing'])->name('sso-login-lead');
Route::post('/leads/webrtc_call/', [WebRTCController::class, 'logCall'])->name('webrtcCall');
Route::post('/dialer/webrtc', [WebRTCController::class, 'webrtcdialer'])->name('dialerwebrtc');
Route::post('/dialer/webrtc-debug', [WebRTCController::class, 'debugLog'])->middleware('throttle:120,1')->name('dialerWebrtcDebug');

Route::post('/sso-logout', [WebRTCController::class, 'ssoLogout'])->name('sso_logout');

Route::get('/smart31-group/edit/{group}', [WebRTCController::class, 'smart31GroupEdit'])
    ->name('smart31GroupEdit');
Route::get('/get-smart31-vmn', [WebRTCController::class, 'getVmn'])
    ->name('getVmn');
Route::get('/get-smart31-wvmn', [WebRTCController::class, 'getwVmn'])
    ->name('getwVmn');
// routes/web.php
Route::get('/smart31-group/details/{group}', [WebRTCController::class, 'getSmart31Details'])
    ->name('smart31GroupDetails');

Route::post('/smart31-group/update', [WebRTCController::class, 'smart31GroupUpdate'])
    ->name('smart31GroupUpdate');

//Reports
Route::get('/reports/responsed', [ReportsController::class, 'notSaved'])->name('responsed');
Route::post('/reports/responsedData', [ReportsController::class, 'notSavedData'])->name('responsedData');
Route::get('/reports/call-report', [ReportsController::class, 'callReport'])->name('callReport');
Route::post('/reports/call-report-data', [ReportsController::class, 'callReportData'])->name('callReportData');
Route::get('/reports/call-report/export/{format}', [ReportsController::class, 'callReportExport'])->name('callReportExport')->whereIn('format', ['xlsx', 'csv']);
Route::get('/reports/rmi-report', [ReportsController::class, 'rmiReport'])->name('rmiReport');
Route::get('/reports/rmi', [ReportsController::class, 'rmiReport'])->name('rmi');
Route::get('/reports/rmi-analysis', [ReportsController::class, 'rmiAnalysisPage'])->name('rmiAnalysis');
Route::post('/reports/rmi-analysis/data', [ReportsController::class, 'rmiAnalysisData'])->name('rmiAnalysisData');
Route::get('/reports/rmi-analysis/detail/{agentId}', [ReportsController::class, 'rmiAnalysisDetail'])->name('rmiAnalysisDetail');
Route::get('/reports/rmi-analysis/export/{type}', [ReportsController::class, 'rmiAnalysisExport'])->name('rmiAnalysisExport');
Route::get('/reports/rmi-analysis/calls', [ReportsController::class, 'rmiAnalysisCalls'])->name('rmiAnalysisCalls');
Route::post('/reports/rmi-analysis/calls/data', [ReportsController::class, 'rmiAnalysisCallsData'])->name('rmiAnalysisCallsData');
Route::get('/reports/rmi-analysis/calls/export/{format}', [ReportsController::class, 'rmiAnalysisCallsExport'])->name('rmiAnalysisCallsExport')->whereIn('format', ['xlsx', 'csv']);
Route::get('/reports/callLogs', [ReportsController::class, 'callLogs'])->name('callLogs');
Route::get('/call-history', [ReportsController::class, 'callLogs'])->name('callHistory');
Route::post('/reports/callLogsData', [ReportsController::class, 'callLogsData'])->name('callLogsData');
Route::get('/reports/recordings', [RecordingsController::class, 'recordings'])->name('recordings');
Route::post('/reports/recordingsData', [RecordingsController::class, 'recordingData'])->name('recordingsData');
Route::get('/reports/download-recordings/{id}/{incoming?}', [ReportsController::class, 'downloadRecordings'])->name('downloadRecordings');
Route::delete('/reports/recordings/{id}/{source_code?}', [RecordingsController::class, 'destroy'])->name('recordingDelete');
Route::post('/reports/recordings/bulk-delete', [RecordingsController::class, 'bulkDestroy'])->name('recordingsBulkDelete');
Route::post('/reports/recordings/bulk-download', [RecordingsController::class, 'bulkDownload'])->name('recordingsBulkDownload');
Route::get('/reports/recordings/bulk-download/{token}', [RecordingsController::class, 'bulkDownloadFile'])->name('recordingsBulkDownloadFile');
Route::get('/reports/recordings/transcript/{id}/{source_code}', [RecordingsController::class, 'getTranscript'])->name('recordingTranscript');
Route::post('/reports/recordings/transcript/{id}/{source_code}/speakers', [RecordingsController::class, 'saveSpeakerRoles'])->name('recordingTranscriptSpeakers');
Route::post('/reports/recordings/transcript/retry/{id}/{source_code}', [RecordingsController::class, 'retryTranscript'])->name('recordingTranscriptRetry');
Route::post('/reports/recordings/transcription/estimate', [RecordingsController::class, 'estimateTranscriptionCost'])->name('recordingsTranscriptionEstimate');
Route::post('/reports/recordings/transcription/jobs', [RecordingsController::class, 'startTranscriptionJobs'])->name('recordingsTranscriptionStartJobs');
Route::get('/reports/recordings/transcription/status/{batch_id}', [RecordingsController::class, 'getBatchStatus'])->name('recordingsTranscriptionBatchStatus');
Route::get('/reports/allocations', [ReportsController::class, 'allocations'])->name('allocations');
Route::get('/lead-allocation', [ReportsController::class, 'allocations'])->name('lead_allocation');
Route::post('/reports/allocationsData', [ReportsController::class, 'allocationsData'])->name('allocationsData');
Route::get('/reports/visitHistory', [ReportsController::class, 'visitHistory'])->name('visitHistory');
Route::post('/reports/visitHistoryData', [ReportsController::class, 'visitHistoryData'])->name('visitHistoryData');
Route::get('/reports/campaignStatus', [ReportsController::class, 'campaignStatus'])->name('campaignStatus');
Route::post('/reports/campaignStatusData', [ReportsController::class, 'campaignStatusData'])->name('campaignStatusData');
Route::get('/reports/userStatus', [ReportsController::class, 'userStatus'])->name('userStatus');
Route::post('/reports/userStatusData', [ReportsController::class, 'userStatusData'])->name('userStatusData');
Route::get('/reports/agentPerformance', [ReportsController::class, 'agentPerformance'])->name('agentPerformance');
Route::post('/reports/agentPerformanceData', [ReportsController::class, 'agentPerformanceData'])->name('agentPerformanceData');
Route::get('/reports/agentPerformance/export/{format}', [ReportsController::class, 'agentPerformanceExport'])->name('agentPerformanceExport')->whereIn('format', ['xlsx', 'csv']);
Route::get('/reports/inbound-calls', [\App\Http\Controllers\Dashboard\InboundCallsController::class, 'index'])->name('inboundCalls');
Route::post('/reports/inbound-calls-data', [\App\Http\Controllers\Dashboard\InboundCallsController::class, 'data'])->name('inboundCallsData');
Route::get('/reports/inbound-calls/{inboundCall}/call', [\App\Http\Controllers\Dashboard\InboundCallsController::class, 'open'])->name('inboundCallOpen')->whereNumber('inboundCall');
Route::get('/reports/inbound-calls/export/{format}', [\App\Http\Controllers\Dashboard\InboundCallsController::class, 'export'])->name('inboundCallsExport')->whereIn('format', ['xlsx', 'csv']);
Route::get('/reports/export-shift-log', [ReportsController::class, 'exportShiftLog'])->name('exportShiftLog');
Route::get('/payout/payout', [PayoutController::class, 'payout'])->name('payout');
Route::post('/payout/payoutData', [PayoutController::class, 'payoutData'])->name('payoutData');


Route::get('/payout', [PayoutController::class, 'index'])->name('payout.index');
Route::get('/payout/create', [PayoutController::class, 'create'])->name('payout.create');
Route::post('/payout', [PayoutController::class, 'store'])->name('payout.store');
Route::get('/payout/{id}/edit', [PayoutController::class, 'edit'])->name('payoutEdit');
Route::put('/payout/{id}/update', [PayoutController::class, 'update'])->name('payout.update');
Route::delete('/payout/{id}/delete', [PayoutController::class, 'destroy'])->name('payoutDestroy');

Route::get('/reports/userLogs', [ReportsController::class, 'userLogs'])->name('userLogs');
Route::post('/reports/userLogsData', [ReportsController::class, 'userLogsData'])->name('userLogsData');
Route::get('/reports/pulse', [ReportsController::class, 'pulse'])->name('pulse');
Route::post('/reports/pulseData', [ReportsController::class, 'pulseData'])->name('pulseData');
Route::get('/reports/incomingVmn', [ReportsController::class, 'incomingVmn'])->name('incomingVmn');
Route::post('/reports/incomingVmnData', [ReportsController::class, 'incomingVmnData'])->name('incomingVmnData');
// The former Dialer Logs page is part of the Call Report (manual dial calls): old bookmarks land there
Route::get('/reports/dialerLog', fn () => redirect(route('callReport', ['source' => 'manual']), 301));
Route::get('/dialer/get-smart31-vmns', [DialerController::class, 'getSmart31Vmns'])->name('dialerSmart31Vmns');
Route::get('/dialer/get-1600-vmns', [DialerController::class, 'get1600Vmns'])->name('dialer1600Vmns');

//Audit
Route::get('/audit/portfolios', [AuditController::class, 'portfolios'])->name('auditPortfolios');
Route::post('/audit/assignAuditors', [AuditController::class, 'assignAuditors'])->name('auditAssignAuditors');
Route::get('/audit/report', [AuditController::class, 'report'])->name('auditReport');
Route::post('/audit/reportData', [AuditController::class, 'reportData'])->name('auditReportData');
Route::get('/audit/auditors', [AuditController::class, 'auditors'])->name('auditAuditors');

// Menus
Route::post('/menus/store/parent', [MenusController::class, 'storeMenu'])->name('parentMenusStore');
Route::get('/menus/parent/{id}/edit', [MenusController::class, 'editMenu'])->name('parentMenusEdit');
Route::post('/menus/{id}/update/{ParentMenuId}', [MenusController::class, 'updateMenu'])->name('parentMenusUpdate');
Route::get('/menus/parent/destroy/{id}', [MenusController::class, 'destroyMenu'])->name('parentMenusDestroy');

Route::get('/menus/{ParentMenuId?}', [MenusController::class, 'index'])->name('menus');
Route::get('/menus/create/{ParentMenuId?}', [MenusController::class, 'create'])->name('menusCreate');
Route::post('/menus/store/{ParentMenuId?}', [MenusController::class, 'store'])->name('menusStore');
Route::get('/menus/{id}/edit/{ParentMenuId?}', [MenusController::class, 'edit'])->name('menusEdit');
Route::post('/menus/{id}/update', [MenusController::class, 'update'])->name('menusUpdate');
Route::get('/menus/destroy/{id}', [MenusController::class, 'destroy'])->name('menusDestroy');
Route::post('/menus/updateAll', [MenusController::class, 'updateAll'])->name('menusUpdateAll');

Route::group(['prefix' => 'obd'], function () {
    Route::get('/did', [ObdController::class, 'did'])->name('obdDid');
    Route::post('/did_add', [ObdController::class, 'did_add'])->name('obdDidAdd');
    Route::get('/did/{id}/edit', [ObdController::class, 'edit_did'])->name('obdDidEdit');
    Route::post('/did_store', [ObdController::class, 'did_store'])->name('obdDidStore');
    Route::post('/did_update', [ObdController::class, 'did_update'])->name('obdDidUpdate');
    Route::post('/did_bulk_update', [ObdController::class, 'did_bulk_update'])->name('obdDidBulkUpdate');
    Route::get('/did_delete/{id}', [ObdController::class, 'did_delete'])->name('obdDidDelete');
    Route::get('/credits', [ObdController::class, 'credits'])->name('obdCredits');
    Route::post('/add_credits', [ObdController::class, 'add_credits'])->name('obdAddCredits');
    Route::get('/transfer', [ObdController::class, 'transfer'])->name('obdTransfer');
    Route::post('/transfer_data', [ObdController::class, 'transferData'])->name('obdTransferData');
    Route::post('/transfer_credits', [ObdController::class, 'transferCredits'])->name('obdTransferCredits');
    Route::get('/create', [ObdController::class, 'create'])->name('obdCampaignCreate');
    Route::post('/store', [ObdController::class, 'store'])->name('obdCampaignStore');
    Route::post('/get_phone_numbers', [ObdController::class, 'getPhoneNumbers'])->name('obdPhoneNumbers');
    Route::get('/view', [ObdController::class, 'viewCampaigns'])->name('obdViewCampaigns');
    Route::post('/campaigns_data', [ObdController::class, 'viewCampaignsData'])->name('obdViewCampaignsData');
    Route::get('/view_campaign_detail/{id}', [ObdController::class, 'viewCampaignDetail'])->name('obdViewCampaignDetail');
    Route::post('/campaign_detail_data/{id}', [ObdController::class, 'viewCampaignDetailData'])->name('obdViewCampaignDetailData');
    Route::get('/fetch_status/{id}', [ObdController::class, 'fetchCampaignStatus'])->name('obdFetchCampaignStatus');
    Route::get('/download_campaign_detail/{id}', [ObdController::class, 'downloadCampaignDetail'])->name('obdDownloadCampaignDetail');
});

Route::post('userActivity', [UsersController::class, 'userActivity'])->name('userActivity');

Route::get('file-manager', [FileManagerController::class, 'index'])->name('FileManager');
Route::get('files-manager', [FileManagerController::class, 'manager'])->name('FilesManager');

// AI & Call Intelligence Routes
Route::get('/settings/ai-call-intelligence', [AiCallIntelligenceController::class, 'aiSettings'])->name('aiSettings');
Route::post('/settings/ai-call-intelligence/update', [AiCallIntelligenceController::class, 'updateAiSettings'])->name('aiSettingsUpdate');
Route::post('/settings/ai-call-intelligence/test', [AiCallIntelligenceController::class, 'testSarvamConnection'])->name('testSarvamConnection');
// This company's own Sarvam AI key (License Info tab). Admin only (checked in the controller); rate limited.
Route::get('/settings/sarvam-key', [\App\Http\Controllers\Dashboard\SarvamKeyController::class, 'show'])->middleware('throttle:60,1')->name('sarvamKeyShow');
Route::post('/settings/sarvam-key/test', [\App\Http\Controllers\Dashboard\SarvamKeyController::class, 'test'])->middleware('throttle:10,1')->name('sarvamKeyTest');
Route::post('/settings/sarvam-key', [\App\Http\Controllers\Dashboard\SarvamKeyController::class, 'save'])->middleware('throttle:10,1')->name('sarvamKeySave');
Route::delete('/settings/sarvam-key', [\App\Http\Controllers\Dashboard\SarvamKeyController::class, 'destroy'])->middleware('throttle:10,1')->name('sarvamKeyDelete');
Route::get('/settings/ai-usage', [AiCallIntelligenceController::class, 'aiUsage'])->name('aiUsage');
Route::post('/settings/ai-usage/data', [AiCallIntelligenceController::class, 'aiUsageData'])->name('aiUsageData');

Route::get('/reports/ai-analysis', [AiCallIntelligenceController::class, 'aiAnalysisList'])->name('aiAnalysisList');
Route::post('/reports/ai-analysis/data', [AiCallIntelligenceController::class, 'aiAnalysisData'])->name('aiAnalysisData');
Route::get('/reports/ai-analysis/export', [AiCallIntelligenceController::class, 'aiAnalysisExport'])->name('aiAnalysisExport');
Route::get('/reports/ai-analysis/{id}', [AiCallIntelligenceController::class, 'aiAnalysisDetail'])->name('aiAnalysisDetail');
Route::get('/reports/ai-analysis/{id}/retry', [AiCallIntelligenceController::class, 'aiAnalysisRetry'])->name('aiAnalysisRetry');

Route::get('/reports/call-transcripts', [AiCallIntelligenceController::class, 'callTranscriptsList'])->name('callTranscriptsList');
Route::get('/reports/call-transcripts/export', [AiCallIntelligenceController::class, 'callTranscriptsExport'])->name('callTranscriptsExport');
Route::get('/reports/call-transcript/{id}', [AiCallIntelligenceController::class, 'callTranscriptDetail'])->name('callTranscriptDetail');

// Clear Cache
Route::get('/cache-clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    return redirect()->back()->with('doneMessage', __('backend.cashClearDone'));
})->name('cacheClear');
});



