<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\TenantSetup;
use App\Models\WebmasterSection;
use App\Services\CompanyDatabaseProbe;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Configuration > Companies (Super Admin, central company only): the companies list (tenants table - on
 * app.leadger.in the tenants_leadger database), each company with its own database. List with live counts, add a
 * company (its database must answer first), edit, activate / suspend. Every change is written to the log.
 * Company databases are never created, changed or deleted here.
 */
class CompaniesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Tenancy::isSuperAdmin(Auth::user())) {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Only a Super Admin can manage companies.'], 403)
                    : redirect()->route('NoPermission');
            }
            if (!Tenancy::hasTenantsTable()) {
                abort(503, 'The companies list (tenants table) is not available. Run php artisan migrate.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $companies = TenantSetup::query()->orderBy('id')->get();
        $manageOnly = Tenancy::manageOnly();
        $registry = (string)config('database.connections.' . Tenancy::registry() . '.database');
        return view('dashboard.companies.index', compact('GeneralWebmasterSections', 'companies', 'manageOnly', 'registry'));
    }

    /** GET: live state of one company's database (reachable, users / leads / calls, pending migrations). */
    public function check(int $company)
    {
        $t = TenantSetup::findOrFail($company);
        return response()->json(CompanyDatabaseProbe::probe($this->credentials($t)));
    }

    /** POST: test database details from the form before saving (blank password = the saved one). */
    public function testConnection(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'db_host' => ['required', 'string', 'max:191'],
            'db_name' => ['required', 'string', 'max:191'],
            'db_user' => ['required', 'string', 'max:191'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);
        $password = $data['db_password'] ?? null;
        if (($password === null || $password === '') && !empty($data['id'])) {
            $password = TenantSetup::find($data['id'])?->db_password;
        }
        $r = CompanyDatabaseProbe::probe(['host' => $data['db_host'], 'database' => $data['db_name'],
            'username' => $data['db_user'], 'password' => (string)$password], false);
        return response()->json($r);
    }

    public function create()
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $company = new TenantSetup(['status' => TenantSetup::STATUS_ACTIVE, 'db_host' => config('database.connections.mysql.host')]);
        return view('dashboard.companies.form', compact('GeneralWebmasterSections', 'company'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($error = $this->connectionError($data)) {
            return back()->withInput($request->except('db_password'))->withErrors(['db_name' => $error]);
        }
        $company = TenantSetup::create($data + ['slug' => $this->slug($data['subdomain'])]);
        $this->audit('created', $company, array_keys($data));
        return redirect()->route('companies')->with('doneMessage', 'Company "' . ($company->name ?: $company->subdomain) . '" added.');
    }

    public function edit(int $company)
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        $company = TenantSetup::findOrFail($company);
        return view('dashboard.companies.form', compact('GeneralWebmasterSections', 'company'));
    }

    public function update(Request $request, int $company)
    {
        $t = TenantSetup::findOrFail($company);
        $data = $this->validated($request, $t);
        if (($data['db_password'] ?? '') === '') {
            unset($data['db_password']);   // blank = keep the saved password
        }
        $dbChanged = collect(['db_host', 'db_name', 'db_user', 'db_password'])->contains(fn ($k) => array_key_exists($k, $data) && $data[$k] !== $t->{$k});
        if ($dbChanged && ($error = $this->connectionError($data + ['db_password' => $t->db_password]))) {
            return back()->withInput($request->except('db_password'))->withErrors(['db_name' => $error]);
        }
        $t->fill($data);
        $changed = array_keys($t->getDirty());
        $t->save();
        $this->audit('updated', $t, $changed);
        return redirect()->route('companies')->with('doneMessage', 'Company "' . ($t->name ?: $t->subdomain) . '" saved.');
    }

    /** POST: activate or suspend (a suspended company's users get "not active" instead of their CRM). */
    public function status(Request $request, int $company)
    {
        $t = TenantSetup::findOrFail($company);
        $status = $request->validate(['status' => ['required', Rule::in([TenantSetup::STATUS_ACTIVE, TenantSetup::STATUS_SUSPENDED])]])['status'];
        $t->status = $status;
        $t->save();
        $this->audit($status === TenantSetup::STATUS_ACTIVE ? 'activated' : 'suspended', $t, ['status']);
        return back()->with('doneMessage', 'Company "' . ($t->name ?: $t->subdomain) . '" is now ' . $status . '.');
    }

    /* ------------------------------------------------------------------ */

    private function validated(Request $request, ?TenantSetup $t = null): array
    {
        $request->merge(['subdomain' => strtolower(trim((string)$request->input('subdomain')))]);
        $table = Tenancy::registry() . '.tenants';
        return $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'subdomain' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+(:\d{2,5})?$/',
                Rule::unique($table, 'subdomain')->ignore($t?->id)],
            'db_host' => ['required', 'string', 'max:191'],
            'db_name' => ['required', 'string', 'max:191', 'regex:/^[A-Za-z0-9_]+$/'],
            'db_user' => ['required', 'string', 'max:191'],
            'db_password' => [$t ? 'nullable' : 'required', 'string', 'max:255'],
            'status' => ['required', Rule::in([TenantSetup::STATUS_ACTIVE, TenantSetup::STATUS_SUSPENDED])],
            'plan' => ['nullable', 'string', 'max:64'],
            'max_users' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'expires_at' => ['nullable', 'date'],
        ], [
            'subdomain.regex' => 'Enter the web address only, e.g. company-a.leadger.in',
            'subdomain.unique' => 'Another company already uses this address.',
            'db_name.regex' => 'Database names contain only letters, numbers and _.',
        ]);
    }

    /** The company's database must answer before it is saved. */
    private function connectionError(array $data): ?string
    {
        $r = CompanyDatabaseProbe::probe(['host' => $data['db_host'], 'database' => $data['db_name'],
            'username' => $data['db_user'], 'password' => (string)($data['db_password'] ?? '')], false);
        return $r['ok'] ? null : 'The company database does not answer: ' . $r['error'];
    }

    private function credentials(TenantSetup $t): array
    {
        return ['host' => $t->db_host, 'database' => $t->db_name, 'username' => $t->db_user, 'password' => (string)$t->db_password];
    }

    private function slug(string $host): ?string
    {
        $slug = Str::slug(explode('.', $host)[0]);
        return $slug !== '' && !TenantSetup::where('slug', $slug)->exists() ? $slug : null;
    }

    /** Audit trail: who changed which company, and which fields (never values of secrets). */
    private function audit(string $action, TenantSetup $t, array $fields): void
    {
        Log::channel('audit')->info('[Companies] Company ' . $action, [
            'by_user' => Auth::id(), 'by_email' => Auth::user()?->email, 'company_id' => $t->id, 'host' => $t->subdomain,
            'fields' => array_values(array_diff($fields, ['updated_at'])), 'ip' => request()->ip(),
        ]);
    }
}
