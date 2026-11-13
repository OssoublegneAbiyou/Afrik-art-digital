<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Illustration;
use App\Models\JoinRequest;
use App\Models\User;
use App\Support\AdminLogger;
use App\Support\MemberAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Membres (admins seulement) : recherche, rôles, désactivation, création et suppression de comptes.
 */
class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'role' => $request->query('role'),
            'type' => $request->query('type'),
            'etat' => $request->query('etat'),
        ];

        // Œuvres comptées sans le filtre « publié » (jointure directe) : brouillons et attentes inclus.
        $members = User::query()
            ->with(['artist', 'writer'])
            ->withCount([
                'artist as illustrations_count' => fn ($q) => $q->join('illustrations', 'illustrations.artist_id', '=', 'artists.id'),
                'writer as documents_count' => fn ($q) => $q->join('documents', 'documents.writer_id', '=', 'writers.id'),
                'artist as illustrations_pending_count' => fn ($q) => $q->join('illustrations', 'illustrations.artist_id', '=', 'artists.id')
                    ->where('illustrations.moderation_status', Illustration::MODERATION_PENDING)->whereNotNull('illustrations.published_at'),
                'writer as documents_pending_count' => fn ($q) => $q->join('documents', 'documents.writer_id', '=', 'writers.id')
                    ->where('documents.moderation_status', Document::MODERATION_PENDING)->whereNotNull('documents.published_at'),
            ])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($filters['q'])).'%';
                $query->where(fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(email) LIKE ?', [$term]));
            })
            ->when(array_key_exists($filters['role'] ?? '', User::ROLES), fn ($q) => $q->where('role', $filters['role']))
            ->when(array_key_exists($filters['type'] ?? '', MemberAccounts::ACCOUNT_TYPES), fn ($q) => $q->where('account_type', $filters['type']))
            ->when($filters['etat'] === 'desactives', fn ($q) => $q->whereNotNull('deactivated_at'))
            ->when($filters['etat'] === 'actifs', fn ($q) => $q->whereNull('deactivated_at'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.members.index', [
            'members' => $members,
            'filters' => $filters,
            'roles' => User::ROLES,
            'accountTypes' => MemberAccounts::ACCOUNT_TYPES,
            'activeAdmins' => MemberAccounts::activeAdminCount(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validateWithBag('create', [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'account_type' => ['required', Rule::in(array_keys(MemberAccounts::ACCOUNT_TYPES))],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ], [
            'name.required' => 'Indiquez le nom du membre.',
            'email.required' => 'Indiquez l’adresse e-mail du membre.',
            'email.email' => 'Cette adresse e-mail ne semble pas valide.',
            'email.unique' => 'Un compte existe déjà avec cette adresse e-mail.',
            'account_type.*' => 'Choisissez le type de compte.',
            'role.*' => 'Choisissez un rôle.',
        ]);

        $user = MemberAccounts::create($data['name'], $data['email'], $data['account_type'], $data['role']);
        $sent = MemberAccounts::invite($user);

        AdminLogger::log('compte.cree', $user, null, null, [
            'nom' => $user->name, 'email' => $user->email, 'type' => $user->account_type, 'role' => $user->role,
        ]);

        // Une demande d'adhésion encore ouverte pour cette adresse est désormais acceptée.
        JoinRequest::where('email', $user->email)->where('statut', JoinRequest::STATUS_NEW)->get()
            ->each(function (JoinRequest $joinRequest) use ($request, $user) {
                $joinRequest->update([
                    'statut' => JoinRequest::STATUS_ACCEPTED, 'traite_par' => $request->user()->id,
                    'traite_le' => now(), 'user_id' => $user->id,
                ]);
                AdminLogger::log('demande.acceptee', $joinRequest, null, ['statut' => JoinRequest::STATUS_NEW], ['statut' => JoinRequest::STATUS_ACCEPTED, 'compte' => $user->id]);
            });

        return redirect()->route('admin.members.index', ['q' => $user->email])->with('status', $sent
            ? 'Compte de '.$user->name.' créé. Un e-mail lui permet de choisir son mot de passe.'
            : 'Compte de '.$user->name.' créé, mais l’e-mail n’a pas pu partir : il peut utiliser « Mot de passe oublié ».');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(array_keys(User::ROLES))]], ['role.*' => 'Choisissez un rôle valide.']);

        if ($data['role'] === $user->role) {
            return back()->with('status', $user->name.' a déjà le rôle « '.$user->roleLabel().' ».');
        }

        if ($data['role'] !== User::ROLE_ADMIN && MemberAccounts::isLastActiveAdmin($user)) {
            return back()->with('error', $request->user()->is($user)
                ? 'Vous êtes le dernier administrateur actif : nommez d’abord un autre admin avant de changer votre rôle.'
                : $user->name.' est le dernier administrateur actif : nommez d’abord un autre admin.');
        }

        $before = $user->role;
        $user->update(['role' => $data['role']]);
        AdminLogger::log('role.modifie', $user, null, ['role' => $before], ['role' => $user->role]);

        return back()->with('status', $user->name.' est maintenant « '.$user->roleLabel().' ».');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        if (MemberAccounts::isLastActiveAdmin($user)) {
            return back()->with('error', $user->name.' est le dernier administrateur actif : impossible de désactiver ce compte.');
        }

        if (! $user->isDeactivated()) {
            $user->forceFill(['deactivated_at' => now()])->save();
            AdminLogger::log('compte.desactive', $user, null, ['desactive' => false], ['desactive' => true]);
        }

        return back()->with('status', 'Le compte de '.$user->name.' est désactivé : il ne peut plus se connecter.');
    }

    public function reactivate(User $user): RedirectResponse
    {
        if ($user->isDeactivated()) {
            $user->forceFill(['deactivated_at' => null])->save();
            AdminLogger::log('compte.reactive', $user, null, ['desactive' => true], ['desactive' => false]);
        }

        return back()->with('status', 'Le compte de '.$user->name.' est réactivé.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte depuis l’administration.');
        }

        if (MemberAccounts::isLastActiveAdmin($user)) {
            return back()->with('error', $user->name.' est le dernier administrateur actif : impossible de supprimer ce compte.');
        }

        $snapshot = ['nom' => $user->name, 'email' => $user->email, 'type' => $user->account_type, 'role' => $user->role];

        MemberAccounts::deleteFiles($user);
        $user->delete();

        AdminLogger::log('compte.supprime', 'user', $user->getKey(), $snapshot, null);

        return redirect()->route('admin.members.index')->with('status', 'Le compte de '.$snapshot['nom'].' est supprimé, avec ses œuvres.');
    }
}
