<?php
namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\WebmasterSection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Auth;
use Illuminate\Http\Request;
use App\Models\Agreement;
use App\Mail\OtpVerification;

// Assuming you have a Contract model

class AgreementController extends Controller
{
    // For viewing files
public function showFile($path)
{
    return response()->file(storage_path('app/public/'.$path));
}

// For downloads
public function downloadFile($path)
{
    $fullPath = storage_path('app/public/' . $path);
    $originalName = basename($fullPath); // default fallback

    // Try to get original name from database if possible
    $agreement = Agreement::where('script_file', $path)
                    ->orWhere('uploaded_file', $path)
                    ->orWhere('pdf_path', $path)
                    ->first();

    if ($agreement) {
        if ($agreement->script_file === $path && !empty($agreement->script_original_name)) {
            $originalName = $agreement->script_original_name;
        } elseif ($agreement->uploaded_file === $path && !empty($agreement->uploaded_original_name)) {
            $originalName = $agreement->uploaded_original_name;
        } elseif ($agreement->pdf_path === $path) {
            $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $agreement->advertiser_name);
            $originalName = $safeName . '.pdf'; // or pull something dynamic
        }
    }

    return response()->download($fullPath, $originalName);
}

    public function create()
    {
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        return view('dashboard.Agreements.create', compact( "GeneralWebmasterSections"));
    }
    public function index()
    {
        $user = Auth::user();
    
        // Fetch user role by joining permissions table
        $userRole = DB::table('users')
            ->leftJoin('permissions', 'users.permissions_id', '=', 'permissions.id')
            ->where('users.id', $user->id)
            ->value('permissions.name');
        
        // Get agreements based on role
        if ($userRole === 'Manager') {
            $agreements = Agreement::where('emp_id', $user->id)->get(); // Only manager's agreements
        } else {
            $agreements = Agreement::all(); // Admin sees all
        }
        //dd($userRole);
        $GeneralWebmasterSections = WebmasterSection::where('status', 1)->get();
    
        return view('dashboard.Agreements.index', compact('agreements', 'userRole', 'GeneralWebmasterSections'));
    }
    public function store(Request $request)
{
    // Validate input
    $request->validate([
        'advertiser_name' => 'required|string|max:255',
        'calls_per_week' => 'required|integer',
        'calls_per_month' => 'required|integer',
        'preferred_calling_hours' => 'nullable|string|max:255',
        // 'script_type' => 'required|in:text,document',
        // 'script_text' => 'nullable|string',
        'script_file' => 'nullable|file|mimes:txt,doc,docx,pdf',
        'support_person_name' => 'required|string|max:255',
        'contact_details' => 'required|string|max:255',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'uploaded_file' => 'nullable|file|mimes:csv,xlsx,xls',
        // 'signature' => 'required|string',
        'agree_terms' => 'required|accepted',
    ]);

    
    $tempAgreement = $request->all();
    // Handle script (text or uploaded file)
    $scriptFilePath=null;
    if ($request->hasFile('script_file')) {
        $extension = $request->file('script_file')->getClientOriginalExtension();
        if (!in_array($extension, ['txt', 'doc', 'docx', 'pdf'])) {
            return back()->with('error', 'Invalid script file format.');
        }
        $originalScriptName = $request->file('script_file')->getClientOriginalName();
        $uniqueScriptName = pathinfo($originalScriptName, PATHINFO_FILENAME) . '_' . time() . '.' . $request->file('script_file')->getClientOriginalExtension();
        $scriptFilePath = $request->file('script_file')->storeAs(
            'scripts', $uniqueScriptName, 'public'
        );
        

    }
    
    $tempAgreement['script_file'] = $scriptFilePath;
    // Handle data file
    $originalUploadedName = $request->file('uploaded_file')->getClientOriginalName();
    $uniqueUploadedName = pathinfo($originalUploadedName, PATHINFO_FILENAME) . '_' . time() . '.' . $request->file('uploaded_file')->getClientOriginalExtension();
    $dataFilePath = $request->file('uploaded_file')->storeAs(
        'uploads', $uniqueUploadedName, 'public'
    );
    
    $tempAgreement['uploaded_file'] = $dataFilePath;

    Session::put('agreement_data', $tempAgreement);

    // Generate OTP
    $otp = rand(100000, 999999);
    Session::put('agreement_otp', $otp);

    // Send email
    $user = Auth::user();
    $email = $user->email;
    Mail::to($email)->send(new OtpVerification($otp));

    return redirect()->route('agreements.otp.verify');


    // Handle signature
    // $signaturePath = null;
    // if (preg_match('/^data:image\/(\w+);base64,/', $request->signature, $matches)) {
    //     $imageType = $matches[1];
    //     $signatureData = substr($request->signature, strpos($request->signature, ',') + 1);
    //     $signatureDecoded = base64_decode($signatureData);
    //     if ($signatureDecoded === false) {
    //         return response()->json(['error' => 'Invalid signature format'], 422);
    //     }
    //     $signaturePath = 'signatures/' . uniqid() . '.' . $imageType;
    //     Storage::disk('public')->put($signaturePath, $signatureDecoded);
    // } else {
    //     return response()->json(['error' => 'Invalid signature format'], 422);
    // }


}
public function showOtpForm()
{
    $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
    
    // Passing $errors explicitly to the view
    return view('dashboard.Agreements.verify_otp', compact('GeneralWebmasterSections'))
        ->withErrors(session('errors')); // This makes $errors available
}

public function verifyOtp(Request $request)
{
    $request->validate(['otp' => 'required|numeric']);

    if ($request->otp != Session::get('agreement_otp')) {
        return back()->with('error', 'Invalid OTP. Please try again.');
    }

    $data = Session::get('agreement_data');



    $agreement = Agreement::create([
        'emp_id' => Auth::id(),
        'advertiser_name' => $data['advertiser_name'],
        'calls_per_week' =>$data['calls_per_week'],
        'calls_per_month' => $data['calls_per_month'],
        'preferred_calling_hours' => $data['preferred_calling_hours'],

        'script_file' => $data['script_file'],
        'support_person_name' => $data['support_person_name'],
        'contact_details' => $data['contact_details'],
        'start_date' => $data['start_date'],
        'end_date' => $data['end_date'],
        'uploaded_file' => $data['uploaded_file'],
        

         // ✅ Full relative path to PDF
        'agree_terms' => 1,
    ]);
    $pdfData = [
        'advertiser_name' => $data['advertiser_name'],
        'calls_per_week' => $data['calls_per_week'],
        'calls_per_month' => $data['calls_per_month'],
        'preferred_calling_hours' => $data['preferred_calling_hours'] ?? 'N/A',
        'script_document' =>$data['script_file'],
        'support_person_name' => $data['support_person_name'],
        'contact_details' =>  $data['contact_details'],
        'start_date' => $data['start_date'],
        'end_date' => $data['end_date'],
        'uploaded_file' => $data['uploaded_file'],
       
    ];

    $pdf = Pdf::loadView('dashboard.Agreements.pdf', ['agreement' => (object) $pdfData]);

    $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['uploaded_file']);
    $pdfFilename = $sanitizedName . '_' . $agreement->id . '.pdf';
    $pdfPath = 'agreements/pdfs/' . $pdfFilename;
    Storage::disk('public')->put($pdfPath, $pdf->output());

    // Save to DB

    $agreement->pdf_path = $pdfPath;
    $agreement->save();

    Session::forget(['agreement_data', 'agreement_otp']);

    return redirect()->route('agreements.index')->with('success', 'Agreement stored successfully after OTP verification!');
}

public function resendOtp()
{
    $otp = rand(100000, 999999);
    Session::put('agreement_otp', $otp);

    $email = Auth::user()->email;

    Mail::to($email)->send(new OtpVerification($otp));

    return back()->with('success', 'OTP resent successfully!');
}
public function edit($id)
{
    $agreement = Agreement::findOrFail($id);
    $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
    return view('dashboard.Agreements.edit', compact('agreement','GeneralWebmasterSections'));
}
public function update(Request $request, $id)
{
    $agreement = Agreement::findOrFail($id);

    // Handle new file uploads (optional)
    if ($request->hasFile('script_file')) {
        $extension = $request->file('script_file')->getClientOriginalExtension();
        if (!in_array($extension, ['txt', 'doc', 'docx', 'pdf'])) {
            return back()->with('error', 'Invalid script file format.');
        }
        $originalScriptName = $request->file('script_file')->getClientOriginalName();
        $scriptFilePath = $request->file('script_file')->storeAs(
        'scripts', $originalScriptName, 'public'
        );

        $agreement->script_file = $scriptFilePath;
    }

    if ($request->hasFile('uploaded_file')) {
        $originalUploadedName = $request->file('uploaded_file')->getClientOriginalName();
        $uploadedFilePath = $request->file('uploaded_file')->storeAs(
            'uploads', $originalUploadedName, 'public'
        );
        $agreement->uploaded_file = $uploadedFilePath;
    }

    // Update basic details (use old value if not submitted)
    $agreement->advertiser_name = $request->input('advertiser_name', $agreement->advertiser_name);
    $agreement->calls_per_week = $request->input('calls_per_week', $agreement->calls_per_week);
    $agreement->calls_per_month = $request->input('calls_per_month', $agreement->calls_per_month);
    $agreement->preferred_calling_hours = $request->input('preferred_calling_hours', $agreement->preferred_calling_hours);
    $agreement->support_person_name = $request->input('support_person_name', $agreement->support_person_name);
    $agreement->contact_details = $request->input('contact_details', $agreement->contact_details);
    $agreement->start_date = $request->input('start_date', $agreement->start_date);
    $agreement->end_date = $request->input('end_date', $agreement->end_date);

    // Save changes
    $agreement->save();

    // Regenerate PDF
    $pdfData = [
        'advertiser_name' => $agreement->advertiser_name,
        'calls_per_week' => $agreement->calls_per_week,
        'calls_per_month' => $agreement->calls_per_month,
        'preferred_calling_hours' => $agreement->preferred_calling_hours ?? 'N/A',
        'script_document' => $agreement->script_file,
        'support_person_name' => $agreement->support_person_name,
        'contact_details' => $agreement->contact_details,
        'start_date' => $agreement->start_date,
        'end_date' => $agreement->end_date,
        'uploaded_file' => $agreement->uploaded_file,
    ];

    $pdf = Pdf::loadView('dashboard.Agreements.pdf', ['agreement' => (object) $pdfData]);
    $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->advertiser_name);
    $pdfFilename = $sanitizedName . '_' . $agreement->id . '.pdf';
    $pdfPath = 'agreements/pdfs/' . $pdfFilename;
    Storage::disk('public')->put($pdfPath, $pdf->output());

    // Save new PDF path
    $agreement->pdf_path = $pdfPath;
    $agreement->save();

    return redirect()->route('agreements.index')->with('success', 'Agreement updated and PDF regenerated successfully!');
}
public function duplicate($id)
{
    $agreement = Agreement::findOrFail($id);
    $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();

    return view('dashboard.Agreements.duplicate', compact('agreement', 'GeneralWebmasterSections'));
}

public function duplicateStore(Request $request, $id)
{
    $original = Agreement::findOrFail($id);

    $request->validate([
        'advertiser_name' => 'required|string|max:255',
        'calls_per_week' => 'required|integer',
        'calls_per_month' => 'required|integer',
        'preferred_calling_hours' => 'nullable|string|max:255',
        'support_person_name' => 'required|string|max:255',
        'contact_details' => 'required|string|max:255',
        'start_date' => 'required|date',
        'end_date' => 'nullable|date',
        'script_file' => 'nullable|file|mimes:txt,doc,docx,pdf',
        'uploaded_file' => 'nullable|file|mimes:csv,xlsx,xls',
        'agree_terms' => 'required|accepted',
    ]);

    // Handle script file upload
    $scriptFilePath = null;
    if ($request->hasFile('script_file')) {
        $originalScriptName = $request->file('script_file')->getClientOriginalName();
        $scriptFilePath = $request->file('script_file')->storeAs('scripts', $originalScriptName, 'public');
    } else {
        $scriptFilePath = $original->script_file; // Use the original file if not uploaded
    }

    // Handle uploaded data file
    $uploadedFilePath = null;
    if ($request->hasFile('uploaded_file')) {
        $originalUploadedName = $request->file('uploaded_file')->getClientOriginalName();
        $uploadedFilePath = $request->file('uploaded_file')->storeAs('uploads', $originalUploadedName, 'public');
    } else {
        $uploadedFilePath = $original->uploaded_file; // Use the original file if not uploaded
    }

    // Prepare data for OTP generation and email
    $tempAgreement = $request->all();
    $tempAgreement['script_file'] = $scriptFilePath;
    $tempAgreement['uploaded_file'] = $uploadedFilePath;

    Session::put('agreement_data', $tempAgreement);

    // Generate OTP
    $otp = rand(100000, 999999);
    Session::put('agreement_otp', $otp);

    // Send OTP email
    $user = Auth::user();
    $email = $user->email;
    Mail::to($email)->send(new OtpVerification($otp));

    return redirect()->route('agreements.otp.verify');
}

public function destroy($id)
{
    if (!\App\Helpers\Helper::canDelete()) {
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['error' => 'Unauthorized: Delete operations are disabled or restricted to Admin.'], 403);
        }
        return redirect()->route('NoPermission');
    }
    $agreement = Agreement::findOrFail($id);

    // Optional: Delete associated files if needed
    if ($agreement->script_file && Storage::disk('public')->exists($agreement->script_file)) {
        Storage::disk('public')->delete($agreement->script_file);
    }

    if ($agreement->uploaded_file && Storage::disk('public')->exists($agreement->uploaded_file)) {
        Storage::disk('public')->delete($agreement->uploaded_file);
    }

    if ($agreement->pdf_path && Storage::disk('public')->exists($agreement->pdf_path)) {
        Storage::disk('public')->delete($agreement->pdf_path);
    }

    // Delete the agreement
    $agreement->delete();

    return redirect()->route('agreements.index')->with('success', 'Agreement deleted successfully!');
}


}
