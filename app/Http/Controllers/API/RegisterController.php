<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Models\User;
use App\Models\Wallet;


class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */

     public function index()
    {
        $users = User::with(['user_type',
                            'basic_info_jobseeker',
                            'basic_info_recruiter',
                            'job_seeker_link',
                            'recruiter_link',
                            'about_job_seeker',
                            'about_recruiter',
                            'more_about_recruiter',
                            'upload_job',
                            'wallet',
                            'work_experiences',
                            'skills',
                            'education',
    ])->paginate();
        return $this->sendResponse($users, "Users Fetched Successfully");
    }


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_type_id' => 'nullable',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('MyApp')->plainTextToken;
        $success['email'] =  $user->email;
        $user->user_type;
        $success['user'] =  $user;

        // create user wallet
        if ($user->user_type->name == "recruiter")
        {

            $wallet_data = [
                'user_id' => $user->id,
                'amount' => 0,
            ];

            $wallet = Wallet::create($wallet_data);
            $success['wallet'] = $wallet;
       
        }
        
        return $this->sendResponse($success, 'User registered successfully.');
    }
   
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            $user = Auth::user(); 
            $success['token'] =  $user->createToken('MyApp')->plainTextToken; 
            $success['email'] =  $user->email;
            $user->user_type;
            $success['user'] =  $user;
            // $success['user_type'] =  $user->user_type;

   
            return $this->sendResponse($success, 'User login successfull.');
        } 
        else{ 
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised']);
        } 
    }

     /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $user = User::with(['user_type',
                            'basic_info_jobseeker',
                            'basic_info_recruiter',
                            'job_seeker_link',
                            'recruiter_link',
                            'about_job_seeker',
                            'about_recruiter',
                            'more_about_recruiter',
                            'upload_job',
                            'wallet',
                            'work_experiences',
                            'skills',
                            'education',
        ])->find($id);

        return $this->sendResponse($user, "User Found Successfully" );

    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $user = User::find($id);

        $input = $request->all();
        $result = $user->update($input);

        return $this->sendResponse($user, "User Updated Successfully" );



    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $user = User::find($id);

        if($user)
        {

            $user->delete();
            $result = [];
            $message = "User Deleted Successfully";
        }

        else
        {
            $result = [];
            $message = "User Does Not Exist";
        }

        return $this->sendResponse([], $message );

    }

    // Forgot Password Api
    public function forgot_password(Request $request)
{
    $input = $request->all();
    $rules = array(
        'email' => "required|email",
    );
    $validator = Validator::make($input, $rules);
    if ($validator->fails()) {
        $arr = array("status" => 400, "message" => $validator->errors()->first(), "data" => array());
    } else {
        try {
            $response = Password::sendResetLink($request->only('email'), function (Message $message) {
                $message->subject($this->getEmailSubject());
            });
            switch ($response) {
                case Password::RESET_LINK_SENT:
                    return \Response::json(array("status" => 200, "message" => trans($response), "data" => array()));
                case Password::INVALID_USER:
                    return \Response::json(array("status" => 400, "message" => trans($response), "data" => array()));
            }
        } catch (\Swift_TransportException $ex) {
            $arr = array("status" => 400, "message" => $ex->getMessage(), "data" => []);
        } catch (Exception $ex) {
            $arr = array("status" => 400, "message" => $ex->getMessage(), "data" => []);
        }
    }
    return \Response::json($arr);
}

}
