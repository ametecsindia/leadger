<?php

/*
|--------------------------------------------------------------------------
| Role permissions: the one list of LeadGER menus and their actions
|--------------------------------------------------------------------------
| Groups and items follow the sidebar (resources/views/dashboard/layouts/menu.blade.php) in the same order.
| A permission key is "<group>.<item>.<action>", for example "reports.call_report.view".
|
|   label          shown in the Create Role popup
|   icon           icon of the group card (the sidebar icon: home, leads, megaphone, headset, settings, database, people, chart, mic)
|   actions        the actions this item has (only these are shown as checkboxes)
|   admin_only     the whole item is for full-access roles only (never granted to another role)
|   admin_only_actions  single actions that are for full-access roles only
|   sidebar        sidebar route names that this item shows (tests keep the sidebar and this list in step)
|   routes         "Controller@method" => the action of this item it needs, or a list of full keys of which any one
|                  is enough. Every request to these controller methods is checked (App\Http\Middleware\CheckPermission);
|                  methods not listed here keep their own checks only.
|
| Rules: an item's other actions need its "view" (when it has one). Full-access roles (below) have every
| permission and can never lose them. Who sees which rows (own leads, team leads...) is not part of this list:
| that still follows the role (Telecaller, Sales Person, Manager, Team Leader...).
*/

return [

    'cache_seconds' => 600,

    // Roles with every permission (the system roles; they cannot be edited or deleted)
    'full_access_roles' => ['Admin', 'Webmaster', 'Super Admin'],

    'actions' => [
        'view' => 'View', 'create' => 'Create', 'edit' => 'Edit', 'delete' => 'Delete', 'export' => 'Export',
        'play' => 'Play', 'download' => 'Download', 'transcribe' => 'Transcribe',
    ],

    'groups' => [

        'dashboard' => ['label' => 'Dashboard', 'icon' => 'home', 'items' => [
            'home' => ['label' => 'Dashboard', 'actions' => ['view', 'export'], 'action_labels' => ['export' => 'Export shift log'],
                'sidebar' => ['adminHome'],
                'routes' => ['Dashboard@index' => 'view', 'Dashboard@dashboard' => 'view', 'Reports@exportShiftLog' => 'export']],
        ]],

        'leads' => ['label' => 'Leads', 'icon' => 'leads', 'items' => [
            'lead_list' => ['label' => 'Lead List', 'actions' => ['view', 'create', 'edit', 'export'],
                'sidebar' => ['leads'],
                'routes' => [
                    'Leads@index' => 'view', 'Campaigns@leadsList' => 'view', 'Leads@view' => 'view', 'Leads@data' => 'view',
                    'Leads@create' => ['leads.lead_list.create', 'leads.import_leads.view'],
                    'Leads@store' => ['leads.lead_list.create', 'leads.import_leads.create'],
                    'Leads@edit' => 'edit', 'Leads@update' => 'edit', 'Leads@reassignLead' => 'edit', 'Leads@scheduleFollowup' => 'edit',
                    'Leads@leadAction' => ['leads.lead_list.edit', 'calling.dialing.view'],
                    'Leads@logCall' => ['leads.lead_list.edit', 'calling.dialing.view'],
                ]],
            'import_leads' => ['label' => 'Upload Leads', 'actions' => ['view', 'create'],
                'sidebar' => ['leadsCreate'],
                'routes' => ['Leads@importLeads' => 'create', 'Leads@downloadSampleFile' => 'view']],
        ]],

        'campaigns' => ['label' => 'Campaign Hub', 'icon' => 'megaphone', 'items' => [
            'campaign_list' => ['label' => 'Campaign List', 'actions' => ['view', 'edit', 'delete', 'export'],
                'sidebar' => ['campaigns'],
                'routes' => [
                    'Campaigns@index' => 'view', 'Campaigns@campaignsData' => 'view', 'Campaigns@view' => 'view', 'Campaigns@performance' => 'view',
                    'Campaigns@edit' => 'edit', 'Campaigns@update' => 'edit', 'Campaigns@toggleStatus' => 'edit',
                    'Campaigns@delete' => 'delete', 'Campaigns@destroyAjax' => 'delete',
                    'Campaigns@exportLeads' => 'export',
                ]],
            'create_campaign' => ['label' => 'Create Campaign', 'actions' => ['create'],
                'sidebar' => ['campaignsCreate'],
                'routes' => ['Campaigns@create' => 'create', 'Campaigns@store' => 'create', 'Campaigns@downloadSampleFile' => 'create',
                    'Campaigns@duplicate' => 'create', 'Campaigns@dupStore' => 'create']],
            'transfer_leads' => ['label' => 'Transfer Leads', 'actions' => ['view', 'edit'],
                'sidebar' => ['campaignsTransferAccounts'],
                'routes' => ['Campaigns@transferAccountsPage' => 'view', 'Campaigns@transferAccountsData' => 'view',
                    'Campaigns@transferAccountsFilterOptions' => 'view', 'Campaigns@transfer' => 'view', 'Campaigns@transferList' => 'view',
                    'Campaigns@transferAction' => 'edit', 'Campaigns@transferUnassign' => 'edit']],
            'auto_distribute' => ['label' => 'Auto Distribute', 'actions' => ['view', 'edit'],
                'sidebar' => ['campaignsAutoDistribute'],
                'routes' => ['Campaigns@autoDistributePage' => 'view', 'Campaigns@getCampaignDistributionDetails' => 'view',
                    'Campaigns@executeAutoDistribution' => 'edit', 'Campaigns@randomAllocation' => 'edit']],
            'add_voucher' => ['label' => 'Add Voucher', 'actions' => ['view', 'create', 'edit', 'delete'],
                'sidebar' => ['obdDid'],
                'routes' => ['Obd@did' => 'view', 'Obd@did_add' => 'create', 'Obd@did_store' => 'create',
                    'Obd@edit_did' => 'edit', 'Obd@did_update' => 'edit', 'Obd@did_bulk_update' => 'edit', 'Obd@did_delete' => 'delete']],
        ]],

        'calling' => ['label' => 'Calling', 'icon' => 'headset', 'items' => [
            'dialing' => ['label' => 'Dialing', 'actions' => ['view'], 'hint' => 'Manual Dial, Campaign Queue, Auto Dialer and saving call outcomes',
                'sidebar' => ['dialer'],
                'routes' => [
                    'Dialer@dialer' => 'view', 'Dialer@click2call' => 'view', 'Dialer@detectChannel' => 'view', 'Dialer@callStatus' => 'view',
                    'Dialer@savelog' => 'view', 'Dialer@getChannelConfig' => 'view', 'Dialer@getTodayPerformance' => 'view',
                    'Dialer@getRecentActivity' => 'view', 'Dialer@quickNote' => 'view', 'Dialer@quickFollowup' => 'view',
                    'Dialer@quickTag' => 'view', 'Dialer@phoneHistory' => 'view', 'Dialer@updateCallState' => 'view',
                    'AutoDialer@status' => 'view', 'AutoDialer@start' => 'view', 'AutoDialer@next' => 'view', 'AutoDialer@event' => 'view',
                    'AutoDialer@pause' => 'view', 'AutoDialer@resume' => 'view', 'AutoDialer@stop' => 'view',
                    'Campaigns@leadCallingView' => 'view', 'Campaigns@getLeadCallingData' => 'view',
                    'Campaigns@getCampaignQueueLeads' => 'view', 'Campaigns@saveLeadCallingDisposition' => 'view',
                ]],
        ]],

        'configuration' => ['label' => 'Configuration', 'icon' => 'settings', 'items' => [
            'call_setup' => ['label' => 'Call Setup', 'actions' => ['view', 'edit'],
                'sidebar' => ['callingSettings'],
                'routes' => ['Click2Call@callingSettings' => 'view', 'Click2Call@updateCallingSettings' => 'edit']],
            // the own profile (Users@edit / Users@update for the signed-in user) is checked in UsersController
            'profile' => ['label' => 'Profile', 'actions' => ['view', 'edit'],
                'sidebar' => ['usersEdit']],
            'company_settings' => ['label' => 'Company Settings', 'actions' => ['view', 'edit'], 'admin_only' => true,
                'routes' => ['Users@updateCompanySettings' => 'edit']],
            'work_configuration' => ['label' => 'Work Configuration', 'actions' => ['view', 'edit'], 'admin_only' => true,
                'routes' => ['Users@updateWorkConfig' => 'edit']],
            'roles_permissions' => ['label' => 'Roles & Permissions', 'actions' => ['view', 'create', 'edit', 'delete'], 'admin_only' => true,
                'routes' => [
                    'Roles@index' => 'view', 'Roles@show' => 'view', 'Roles@store' => 'create', 'Roles@update' => 'edit', 'Roles@destroy' => 'delete',
                    'Users@quickStoreRole' => 'create', 'Users@ajaxUpdateRoleName' => 'edit', 'Users@ajaxDeleteRole' => 'delete',
                    'Users@ajaxGetRolesList' => 'view', 'Users@userGroups' => 'view', 'Users@permissions_create' => 'create',
                    'Users@permissions_store' => 'create', 'Users@permissions_edit' => 'edit', 'Users@permissions_update' => 'edit',
                    'Users@update_custom_home' => 'edit', 'Users@permissions_destroy' => 'delete',
                ]],
            'license_info' => ['label' => 'License Info', 'actions' => ['view', 'edit'], 'admin_only' => true,
                'routes' => ['Users@getLicenseInfo' => 'view', 'Users@syncDid' => 'edit', 'Users@deleteLicense' => 'edit']],
            // Super Admin only (central company): also checked by App\Support\Tenancy::isSuperAdmin in CompaniesController
            'companies' => ['label' => 'Companies', 'actions' => ['view'], 'admin_only' => true,
                'sidebar' => ['companies']],
        ]],

        'master_data' => ['label' => 'Master Data', 'icon' => 'database', 'items' => [
            'products' => ['label' => 'Products', 'actions' => ['view', 'create', 'edit', 'delete'],
                'sidebar' => ['productServices'],
                'routes' => ['ProductsServices@index' => 'view', 'ProductsServices@productServicesData' => 'view',
                    'ProductsServices@create' => 'create', 'ProductsServices@store' => 'create',
                    'ProductsServices@edit' => 'edit', 'ProductsServices@update' => 'edit', 'ProductsServices@destroy' => 'delete']],
            'sources' => ['label' => 'Sources', 'actions' => ['view', 'create', 'edit', 'delete'],
                'sidebar' => ['sources'],
                'routes' => ['Sources@index' => 'view', 'Sources@create' => 'create', 'Sources@store' => 'create',
                    'Sources@edit' => 'edit', 'Sources@update' => 'edit', 'Sources@destroy' => 'delete']],
            'segments' => ['label' => 'Segments', 'actions' => ['view', 'create', 'edit', 'delete'],
                'sidebar' => ['segments'],
                'routes' => ['Segments@index' => 'view', 'Segments@create' => 'create', 'Segments@store' => 'create',
                    'Segments@edit' => 'edit', 'Segments@update' => 'edit', 'Segments@destroy' => 'delete']],
            'statuses' => ['label' => 'Lead Status', 'actions' => ['view', 'create', 'edit', 'delete'],
                'sidebar' => ['statuses'],
                'routes' => ['Statuses@index' => 'view', 'Statuses@dispositionSettings' => 'view', 'Substatuses@index' => 'view',
                    'Statuses@create' => 'create', 'Statuses@store' => 'create', 'Statuses@ajaxStoreSubstatus' => 'create',
                    'Substatuses@create' => 'create', 'Substatuses@store' => 'create',
                    'Statuses@edit' => 'edit', 'Statuses@update' => 'edit', 'Statuses@ajaxToggleFollowup' => 'edit',
                    'Statuses@ajaxToggleConnected' => 'edit', 'Substatuses@edit' => 'edit', 'Substatuses@update' => 'edit',
                    'Statuses@ajaxDeleteSubstatus' => 'delete']],
        ]],

        'people' => ['label' => 'People', 'icon' => 'people', 'items' => [
            // editing another user (Users@edit / Users@update) is checked in UsersController
            'staff' => ['label' => 'Staff', 'actions' => ['view', 'create', 'edit', 'delete'],
                'sidebar' => ['users'],
                'routes' => ['Users@index' => 'view', 'Users@create' => 'create', 'Users@store' => 'create',
                    'Users@updateAll' => 'edit', 'Users@updateRole' => 'edit', 'Users@destroy' => 'delete']],
            'teams' => ['label' => 'Teams', 'actions' => ['view', 'create', 'edit', 'delete'],
                'sidebar' => ['teams'],
                'routes' => ['Users@teams' => 'view', 'Users@createTeam' => 'create', 'Users@storeTeam' => 'create',
                    'Users@editTeam' => 'edit', 'Users@updateTeam' => 'edit', 'Users@destroyTeam' => 'delete']],
            'webrtc' => ['label' => 'WebRTC', 'actions' => ['view', 'create', 'edit', 'delete'],
                'sidebar' => ['webrtc'],
                'routes' => ['WebRTC@index' => 'view', 'WebRTC@webrtcData' => 'view', 'WebRTC@create' => 'create', 'WebRTC@store' => 'create',
                    'WebRTC@edit' => 'edit', 'WebRTC@webrtcToggle' => 'edit', 'WebRTC@smart31GroupEdit' => 'edit',
                    'WebRTC@smart31GroupUpdate' => 'edit', 'WebRTC@delete' => 'delete',
                    'CallChannels@index' => ['people.webrtc.view', 'people.click2call.view'],
                    'CallChannels@toggle' => ['people.webrtc.edit', 'people.click2call.edit']]],
            'click2call' => ['label' => 'Click2Call', 'actions' => ['view', 'create', 'edit', 'delete'],
                'sidebar' => ['click2Call'],
                'routes' => ['Click2Call@index' => 'view', 'Click2Call@click2CallData' => 'view', 'Click2Call@create' => 'create',
                    'Click2Call@store' => 'create', 'Click2Call@edit' => 'edit', 'Click2Call@delete' => 'delete']],
        ]],

        'reports' => ['label' => 'Reports', 'icon' => 'chart', 'items' => [
            'call_report' => ['label' => 'Call Report', 'actions' => ['view', 'export'],
                'sidebar' => ['callReport'],
                'routes' => ['Reports@callReport' => 'view', 'Reports@callReportData' => 'view', 'Reports@callReportExport' => 'export']],
            'agent_performance' => ['label' => 'Agent Performance', 'actions' => ['view', 'export'],
                'sidebar' => ['agentPerformance'],
                'routes' => ['Reports@agentPerformance' => 'view', 'Reports@agentPerformanceData' => 'view',
                    'Reports@userLogs' => 'view', 'Reports@userLogsData' => 'view', 'Reports@agentPerformanceExport' => 'export']],
            'campaigns' => ['label' => 'Campaigns', 'actions' => ['view', 'export'],
                'sidebar' => ['campaignStatus'],
                'routes' => ['Reports@campaignStatus' => 'view', 'Reports@campaignStatusData' => 'view']],
            'inbound_calls' => ['label' => 'Inbound Calls', 'actions' => ['view', 'export'],
                'sidebar' => ['inboundCalls'],
                'routes' => ['InboundCalls@index' => 'view', 'InboundCalls@data' => 'view', 'InboundCalls@open' => 'view',
                    'InboundCalls@export' => 'export']],
            'transcript_report' => ['label' => 'Transcript Report', 'actions' => ['view', 'export'],
                'sidebar' => ['transcriptReport'],
                'routes' => ['TranscriptReport@index' => 'view', 'TranscriptReport@data' => 'view', 'TranscriptReport@show' => 'view',
                    'TranscriptReport@download' => 'export']],
        ]],

        'recordings' => ['label' => 'Recordings', 'icon' => 'mic', 'items' => [
            // Delete: Admin only, and only when Delete Permission is on in Company Settings (Helper::canDeleteRecordings)
            'call_recordings' => ['label' => 'Call Recordings', 'actions' => ['view', 'play', 'download', 'transcribe', 'delete'],
                'admin_only_actions' => ['delete'],
                'sidebar' => ['recordings'],
                'routes' => [
                    'Recordings@recordings' => 'view', 'Recordings@recordingData' => 'view', 'Recordings@getTranscript' => 'view',
                    'Recordings@transcriptOptions' => 'view', 'Recordings@transcriptOptionStatus' => 'view',
                    'Recordings@getBatchStatus' => 'view',
                    // the audio link is also played from the Call Report, RMI Analysis and the calling screen
                    'Reports@downloadRecordings' => ['recordings.call_recordings.play', 'recordings.call_recordings.download',
                        'reports.call_report.view', 'recordings.rmi_analysis.view', 'calling.dialing.view'],
                    'Recordings@bulkDownload' => 'download', 'Recordings@bulkDownloadFile' => 'download',
                    'Recordings@transcriptOptionDownload' => 'view',
                    'Recordings@estimateTranscriptionCost' => 'transcribe', 'Recordings@startTranscriptionJobs' => 'transcribe',
                    'Recordings@retryTranscript' => 'transcribe', 'Recordings@saveSpeakerRoles' => 'view',
                    'Recordings@destroy' => 'delete', 'Recordings@bulkDestroy' => 'delete',
                ]],
            'rmi_analysis' => ['label' => 'RMI Analysis', 'actions' => ['view', 'export'],
                'sidebar' => ['rmiAnalysis'],
                'routes' => ['Reports@rmiAnalysisPage' => 'view', 'Reports@rmiReport' => 'view', 'Reports@rmiAnalysisData' => 'view',
                    'Reports@rmiAnalysisDetail' => 'view', 'Reports@rmiAnalysisCalls' => 'view', 'Reports@rmiAnalysisCallsData' => 'view',
                    'Reports@rmiAnalysisExport' => 'export', 'Reports@rmiAnalysisCallsExport' => 'export']],
        ]],
    ],
];
