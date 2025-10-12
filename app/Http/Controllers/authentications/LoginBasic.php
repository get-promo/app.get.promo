<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginBasic extends Controller
{
  /**
   * Wyświetl formularz logowania
   */
  public function index()
  {
    // Jeśli użytkownik jest już zalogowany, przekieruj do dashboardu
    if (Auth::check()) {
      return redirect('/');
    }

    $pageConfigs = ['myLayout' => 'blank'];
    return view('content.authentications.auth-login-basic', ['pageConfigs' => $pageConfigs]);
  }

  /**
   * Obsłuż próbę logowania
   */
  public function login(Request $request)
  {
    $credentials = $request->validate([
      'email' => ['required', 'email'],
      'password' => ['required'],
    ]);

    $remember = $request->has('remember');

    if (Auth::attempt($credentials, $remember)) {
      $request->session()->regenerate();

      return redirect()->intended('/')->with('success', 'Zalogowano pomyślnie!');
    }

    return back()->withErrors([
      'email' => 'Podane dane logowania są nieprawidłowe.',
    ])->onlyInput('email');
  }

  /**
   * Wyloguj użytkownika
   */
  public function logout(Request $request)
  {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login')->with('success', 'Wylogowano pomyślnie!');
  }
}
