<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Auth\AuthService;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;
use App\Mail\PasswordResetEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = $this->authService->register($request->all());

        $token = $user->createToken('auth_token')->plainTextToken;

        // ENVOYER L'EMAIL DE BIENVENUE
        try {
            Mail::to($user->email)->send(new WelcomeEmail($user));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email de bienvenue: ' . $e->getMessage());
        }

        // CRÉER UNE NOTIFICATION DANS LA BASE DE DONNÉES
        Notification::create([
            'user_id' => $user->id,
            'type' => 'welcome',
            'message' => 'Bienvenue sur PER ANKH, ' . $user->name . ' ! 🎉',
            'is_read' => false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'utilisateur creer avec succes',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = $this->authService->login($request->all());

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'email ou mot de passe invalide'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'connexion avec succes',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'deconnexion avec succes'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'status' => true,
            'user' => $request->user()
        ]);
    }

    // AJOUTER LES MÉTHODES DE RÉCUPÉRATION DE MOT DE PASSE
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        // Générer un token de réinitialisation
        $token = Str::random(60);
        
        // Sauvegarder le token
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => $token,
                'created_at' => now()
            ]
        );

        // ENVOYER L'EMAIL DE RÉINITIALISATION
        try {
            Mail::to($user->email)->send(new PasswordResetEmail($user, $token));
        } catch (\Exception $e) {
            Log::error('Erreur envoi email de réinitialisation: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Un email de réinitialisation a été envoyé'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|min:6|confirmed'
        ]);

        // Vérifier le token
        $reset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$reset) {
            return response()->json([
                'status' => false,
                'message' => 'Token invalide ou expiré'
            ], 400);
        }

        // Vérifier si le token n'est pas expiré (60 minutes)
        if (now()->diffInMinutes($reset->created_at) > 60) {
            return response()->json([
                'status' => false,
                'message' => 'Token expiré'
            ], 400);
        }

        // Mettre à jour le mot de passe
        $user = \App\Models\User::where('email', $request->email)->first();
        $user->password = bcrypt($request->password);
        $user->save();

        // Supprimer le token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }
}