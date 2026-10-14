
<div class="tab-pane {{  ( Session::get('active_tab') == 'statusTab') ? 'active' : '' }}"
     id="tab-8">
    <div class="p-a-md"><h5><i class="material-icons">&#xe8c6;</i>
            &nbsp; Crons</h5></div>
    <div class="p-a-md col-md-12">

        <table class="table table-bordered m-a-0">
            <thead>
            <tr>
                <th>Name</th>
                <th>URL</th>
            </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Absent Notification to Managers</td>
                    <td>{{ route('api.absent-notifications') }}</td>
                </tr>
                <tr>
                    <td>Login Notifications</td>
                    <td>{{route('api.login-notifications')}}</td>
                </tr>
                <tr>
                    <td>Reminder Notifications</td>
                    <td>{{route('api.reminder-notifications')}}</td>
                </tr>
                <tr>
                    <td>Sync Emails to Mailbox</td>
                    <td>{{route('api.emails-sync')}}</td>
                </tr>
                <tr>
                    <td>Click2call Status</td>
                    <td>{{route('api.click2call.fetch')}}</td>
                </tr>
                <tr>
                    <td>OBD Campaigns Execution</td>
                    <td>{{route('api.obd.execute')}}</td>
                </tr>
                <tr>
                    <td>click2call responses</td>
                    <td>{{route('updateAllClick2CallResponses')}}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
