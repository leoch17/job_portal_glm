<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordEmail;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobType;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;


class AccountController extends Controller
{
    //Este método mostrará la página de registro de usuario
    public function registration() {
        return view('frontend.account.registration');
    }

    //Este método guardará un usuario
    public function processRegistration(Request $request) {
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:5',
            'confirm_password' => 'required|same:password',
        ]);

        if($validator->passes()) {

            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();

            session()->flash('success', 'Te has registrado satisfactoriamente');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    //Este método mostrará la página de inicio de sesión de usuario
    public function login() {
        return view('frontend.account.login');
    }

    public function authenticate(Request $request) {
        $validator = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if($validator->passes()){

            if(Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return redirect()->route('account.profile');
            } else {
                return redirect()->route('account.login')->with('error','El correo electrónico o la contraseña son incorrectos');
            }

        } else {
            return redirect()->route('account.login')
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }
    }

    public function profile() {

        $id = Auth::user()->id;

        $user = User::where('id',$id)->first();

        return view('frontend.account.profile', [
            'user' => $user
        ]);
    }

    public function updateProfile(Request $request) {

        $id = Auth::user()->id;

        $validator = Validator::make($request->all(),[
            'name' => 'required|min:5|max:30',
            'email' => 'required|email|unique:users,email,'.$id.',id'
        ]);

        if ($validator->passes()) {

            $user = User::find($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->designation = $request->designation;
            $user->mobile = $request->mobile;
            $user->save();

            session()->flash('success','Perfil actualizado satisfactoriamente');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

    }

    public function logout() {
        Auth::logout();
        return redirect()->route('account.login');
    }

    public function updateProfilePic(Request $request) {

        $id = Auth::user()->id;

        $validator = Validator::make($request->all(),[
            'image' => 'required|image'
        ]);

        if($validator->passes()) {

            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = $id.'-'.time().'.'.$ext;
            $image->move(public_path('/profile_pic/'), $imageName);


            // Crear una pequeña miniatura

            // crear una nueva imagen (800 x 600)
            $sourcePath = public_path('/profile_pic/'.$imageName);
            $manager = new ImageManager(Driver::class);
            $image = $manager->read($sourcePath);

            // recortar el mejor ajuste 5:3 (600x360) y cambiar el tamaño a 600x360 píxeles
            $image->cover(300, 300);
            $image->toPng()->save(public_path('/profile_pic/thumb/'.$imageName));

            //Eliminar fotos de perfil antiguas
            File::delete(public_path('/profile_pic/thumb/'.Auth::user()->image));
            File::delete(public_path('/profile_pic/'.Auth::user()->image));

            User::where('id',$id)->update(['image' => $imageName]);

            session()->flash('success','Foto de perfil actualizada satisfactoriamente');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function createJob() {

        $categories = Category::orderBy('name','ASC')->where('status',1)->get();

        $jobTypes = JobType::orderBy('name','ASC')->where('status',1)->get();

        return view('frontend.account.job.create',[
            'categories' => $categories,
            'jobTypes' => $jobTypes,
        ]);
    }

    public function saveJob(Request $request) {

        $rules = [
            'title' => 'required|min:5|max:200',
            'category' => 'required',
            'jobType' => 'required',
            'vacancy' => 'required|integer',
            'location' => 'required|max:70',
            'description' => 'required',
            'company_name' => 'required|min:3|max:80',
        ];

        $validator = Validator::make($request->all(),$rules);

        if ($validator->passes()) {

            $job = new Job();
            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->job_type_id = $request->jobType;
            $job->user_id = Auth::user()->id;
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualifications = $request->qualifications;
            $job->keywords = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->website;
            $job->save();

            session()->flash('success','Trabajo añadido satisfactoriamente');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function myJobs() {
        $jobs = Job::where('user_id',Auth::user()->id)->with('jobType')->orderBy('created_at','DESC')->paginate(10);
        return view('frontend.account.job.my-jobs',[
            'jobs' => $jobs
        ]);
    }

    public function editJob(Request $request, $id) {
        $categories = Category::orderBy('name','ASC')->where('status',1)->get();
        $jobTypes = JobType::orderBy('name','ASC')->where('status',1)->get();

        $job = Job::where([
            'user_id' => Auth::user()->id,
            'id' => $id
        ])->first();

        if($job == null) {
            abort(404);
        }

        return view('frontend.account.job.edit', [
            'categories' => $categories,
            'jobTypes' => $jobTypes,
            'job' => $job,
        ]);
    }

    public function updateJob(Request $request, $id) {

        $rules = [
            'title' => 'required|min:5|max:200',
            'category' => 'required',
            'jobType' => 'required',
            'vacancy' => 'required|integer',
            'location' => 'required|max:70',
            'description' => 'required',
            'company_name' => 'required|min:3|max:80',
        ];

        $validator = Validator::make($request->all(),$rules);

        if ($validator->passes()) {

            $job = Job::find($id);
            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->job_type_id = $request->jobType;
            $job->user_id = Auth::user()->id;
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualifications = $request->qualifications;
            $job->keywords = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->website;
            $job->save();

            session()->flash('success','Trabajo actualizado satisfactoriamente');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function deleteJob(Request $request) {

        $job = Job::where([
            'user_id' => Auth::user()->id,
            'id' => $request->jobId
        ])->first();

        if ($job == null) {
            session()->flash('error','Trabajo eliminado o no encontrado');
            return response()->json([
                'status' => true
            ]);
        }

        Job::where('id', $request->jobId)->delete();
        session()->flash('success','Trabajo eliminado satisfactoriamente');
            return response()->json([
                'status' => true
            ]);
    }

    public function myJobApplications() {
        $jobApplications = JobApplication::where('user_id',Auth::user()->id)
                ->with(['job','job.jobType','job.applications'])
                ->orderBy('created_at','DESC')
                ->paginate(10);

        return view('frontend.account.job.my-job-applications',[
            'jobApplications' => $jobApplications
        ]);
    }

    public function removeJobs(Request $request) {
        $jobApplication = JobApplication::where([
                                'id' => $request->id,
                                'user_id' => Auth::user()->id]
                            )->first();

        if ($jobApplication == null) {
            session()->flash('error','Solicitud de empleo no encontrado');
            return response()->json([
                'status' => false,
            ]);
        }

        JobApplication::find($request->id)->delete();

        session()->flash('success','Solicitud de empleo removida satisfactoriamente');
        return response()->json([
            'status' => true,
        ]);
    }


    public function savedJobs() {
        // $jobApplications = JobApplication::where('user_id',Auth::user()->id)
        //         ->with(['job','job.jobType','job.applications'])
        //         ->paginate(10);

        $savedJobs = SavedJob::where([
            'user_id' => Auth::user()->id,
        ])->with(['job','job.jobType','job.applications'])
        ->orderBy('created_at','DESC')
        ->paginate(10);

        return view('frontend.account.job.saved-jobs',[
            'savedJobs' => $savedJobs
        ]);
    }

    public function removeSavedJob(Request $request) {
        $savedJob = SavedJob::where([
                                'id' => $request->id,
                                'user_id' => Auth::user()->id]
                            )->first();

        if ($savedJob == null) {
            session()->flash('error','Empleo no encontrado');
            return response()->json([
                'status' => false,
            ]);
        }

        SavedJob::find($request->id)->delete();

        session()->flash('success','Empleo removido satisfactoriamente');
        return response()->json([
            'status' => true,
        ]);
    }

    public function updatePassword(Request $request) {
        $validator = Validator::make($request->all(),[
            'old_password' => 'required',
            'new_password' => 'required|min:5',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ]);
        }

        if (Hash::check($request->old_password, Auth::user()->password) == false) {
            session()->flash('error','Tu antigua contraseña es incorrecta');
            return response()->json([
                'status' => true,
            ]);
        }

        $user = User::find(Auth::user()->id);
        $user->password = Hash::make($request->new_password);
        $user->save();

        session()->flash('success','Contraseña actualizada satisfactoriamente');
        return response()->json([
            'status' => true,
        ]);
    }

    public function forgotPassword() {
        return view('frontend.account.forgot-password');
    }

    public function processForgotPassword(Request $request) {
        $validator = Validator::make($request->all(),[
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return redirect()->route('account.forgotPassword')->withInput()->withErrors($validator);
        }

        $token = Str::random(60);

        \DB::table('password_reset_tokens')->where('email',$request->email)->delete();

        \DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => now()
        ]);

        // Enviar Correo electronico aquí
        $user = User::where('email',$request->email)->first();
        $mailData = [
            'token' => $token,
            'user' => $user,
            'subject' => 'Has solicitado cambiar tu contraseña',
        ];
        Mail::to($request->email)->send(new ResetPasswordEmail($mailData));

        return redirect()->route('account.forgotPassword')->with('success','Se ha enviado a tu bandeja de entrada un correo electrónico para restablecer la contraseña');
    }

    public function resetPassword($tokenString) {
        $token = \DB::table('password_reset_tokens')->where('token',$tokenString)->first();

        if ($token == null) {
            return redirect()->route('account.forgotPassword')->with('error','Token Inválido');
        }

        return view('frontend.account.reset-password',[
            'tokenString' => $tokenString,
        ]);
    }

    public function processResetPassword(Request $request) {

        $token = \DB::table('password_reset_tokens')->where('token',$request->token)->first();

        if ($token == null) {
            return redirect()->route('account.forgotPassword')->with('error','Token Inválido');
        }

        $validator = Validator::make($request->all(),[
            'new_password' => 'required|min:5',
            'confirm_password' => 'required|same:new_password'
        ]);

        if ($validator->fails()) {
            return redirect()->route('account.resetPassword',$request->token)->withErrors($validator);
        }

        User::where('email',$token->email)->update([
            'password' => Hash::make($request->new_password)
        ]);

        return redirect()->route('account.login')->with('success','Has cambiado tu contraseña satisfactoriamente');
    }
}
