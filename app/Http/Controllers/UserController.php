<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Validator;

use App\User;

use Illuminate\Support\Facades\File;
use Auth;

use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function getLogin()
    {
        if(Auth::check()) {
            // Why the heck would you want to login again?
            return redirect('/');
        }

        $page = new \stdClass();
        $page->title = 'Login';

        return view('user.login', compact('page'));
    }

    public function postLogin(Request $request)
    {
        $success = false;

        // Convert "on" / "off" to true/false
        $request->remember = ($request->remember === "on") ? true : false;

        // First determine if the user used his name or his email.
        if(!filter_var($request->name, FILTER_VALIDATE_EMAIL) === false) {
            // We have an email address
            $success = Auth::attempt(['email' => $request->name, 'password' => $request->password], $request->remember);
        } else {
            // We have a user name
            $success = Auth::attempt(['name' => $request->name, 'password' => $request->password], $request->remember);
        }

        if($success) {
            return redirect()->intended(); //redirect('/');
        } else {
            return redirect('/login')->withInput()->withErrors(['auth' => 'We could not find a user with these credentials. Please double check.']);
        }
    }

    public function getRegistration()
    {
        $page = new \stdClass();
        $page->title = 'Register';

        // Show the form
        return view('user.register', compact('page'));
    }

    public function postRegistration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        if($validator->fails()) {
            return redirect('/register')->withInput()->withErrors($validator);
        }

        if(!env('AUTH_REGISTER')) {
            $tokenfile = storage_path() . '/app/token.txt';
            if(!$request->has('register_token')) {
                return redirect('/register')->withInput()->withErrors(['register_token' => 'Please provide your unique token to register!']);
            }
            $tokenfound = false;
            // Tokens are saved in a file under storage/app/token.txt
            if(!File::exists($tokenfile)) {
                return redirect('/register')->withInput()->withErrors(['register_token' => 'There is no token available for registration.']);
            }

            $token = [];
            foreach(preg_split("/((\r?\n)|(\r\n?))/", File::get($tokenfile)) as $line) {
                if(strpos($line, '=') <= 0) {
                    continue;
                }

                $line = explode('=', $line);

                if($line[0] == $request->register_token && intval($line[1]) > 0) {
                    $tokenfound = true;
                    // Decrease the uses by one
                    $token[] = ['token' => $line[0], 'uses' => intval($line[1]) - 1];
                } else {
                    $token[] = ['token' => $line[0], 'uses' => intval($line[1])];
                }
            }

            // Now check expired tokens.
            foreach($token as $key => $t) {
                if($t['uses'] <= 0) {
                    unset($token[$key]);
                }
            }

            // And re-save the file.
            foreach($token as $key => $t) {
                $token[$key] = $t['token'] . '=' . $t['uses'];
            }
            File::put($tokenfile, implode("\n", $token));

            if(!$tokenfound) {
                return redirect('/register')->withInput()->withErrors(['register_token' => 'We could not confirm your token.']);
            }
        }

        // Create a new user
        $user = new User();
        $user->name = $request->name;
        $user->password = Hash::make($request->password);
        $user->email = $request->email;
        $user->api_token = Hash::make(random_bytes(32)); // For now only a simple token
        $user->save();

        // Now redirect to the login page
        return redirect('/login');
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->intended(); // redirect('/login');
    }
}
