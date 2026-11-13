<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JoinRequest;
use App\Models\User;
use App\Notifications\JoinRequestAnswered;
use App\Support\AdminLogger;
use App\Support\MemberAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Demandes d'adhésion (modérateurs et admins) : liste par statut, fiche, accepter, refuser, note interne.
 */
class JoinRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('statut', JoinRequest::STATUS_NEW);
        $status = array_key_exists($status, JoinRequest::STATUSES) || $status === 'toutes' ? $status : JoinRequest::STATUS_NEW;

        $counts = JoinRequest::query()->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');

        return view('admin.join-requests.index', [
            'requests' => JoinRequest::query()
                ->with('handler')
                ->when($status !== 'toutes', fn ($q) => $q->where('statut', $status))
                ->when($status === JoinRequest::STATUS_NEW, fn ($q) => $q->oldest(), fn ($q) => $q->latest())
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'counts' => $counts,
            'total' => $counts->sum(),
        ]);
    }

    public function show(JoinRequest $joinRequest): View
    {
        $joinRequest->load(['handler', 'user']);

        return view('admin.join-requests.show', [
            'joinRequest' => $joinRequest,
            'existingUser' => $joinRequest->user ?? User::where('email', $joinRequest->email)->first(),
            'accountTypes' => array_diff_key(MemberAccounts::ACCOUNT_TYPES, ['visitor' => true]),
        ]);
    }

    public function accept(Request $request, JoinRequest $joinRequest): RedirectResponse
    {
        if (! $joinRequest->isNew()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $data = $request->validate([
            'account_type' => ['required', Rule::in(['artist', 'writer'])],
        ], ['account_type.*' => 'Choisissez le type de compte à créer.']);

        $email = strtolower(trim($joinRequest->email));
        $user = User::where('email', $email)->first();
        $created = $user === null;

        if ($created) {
            $user = MemberAccounts::create($joinRequest->name, $email, $data['account_type']);
            $sent = MemberAccounts::invite($user, true);
            AdminLogger::log('compte.cree', $user, null, null, [
                'nom' => $user->name, 'email' => $user->email, 'type' => $user->account_type, 'role' => $user->role, 'demande' => $joinRequest->id,
            ]);
        } else {
            // Le compte existe déjà : on le relie sans le recréer ; un simple visiteur reçoit son espace de création.
            if ($user->isVisitor()) {
                $user->update(['account_type' => $data['account_type'], 'account_type_selected' => true]);
                MemberAccounts::ensureProfile($user);
            }
            $sent = $this->mail($email, new JoinRequestAnswered($joinRequest, true));
        }

        $joinRequest->update([
            'statut' => JoinRequest::STATUS_ACCEPTED,
            'traite_par' => $request->user()->id,
            'traite_le' => now(),
            'user_id' => $user->id,
        ]);

        AdminLogger::log('demande.acceptee', $joinRequest, null, ['statut' => JoinRequest::STATUS_NEW], [
            'statut' => JoinRequest::STATUS_ACCEPTED, 'compte' => $user->id, 'compte_existant' => ! $created,
        ]);

        $message = $created
            ? 'Demande acceptée : le compte de '.$user->name.' est créé'.($sent ? ' et un e-mail lui permet de choisir son mot de passe.' : ', mais l’e-mail n’a pas pu partir (il peut utiliser « Mot de passe oublié »).')
            : 'Demande acceptée : un compte existait déjà avec cette adresse, il a été relié à la demande'.($sent ? ' et la personne est prévenue par e-mail.' : '.');

        return redirect()->route('admin.join-requests.show', $joinRequest)->with('status', $message);
    }

    public function refuse(Request $request, JoinRequest $joinRequest): RedirectResponse
    {
        if (! $joinRequest->isNew()) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $data = $request->validate([
            'motif_refus' => ['nullable', 'string', 'max:2000'],
            'prevenir' => ['nullable', 'boolean'],
        ], ['motif_refus.max' => 'Le message ne doit pas dépasser 2 000 caractères.']);

        $note = filled($data['motif_refus'] ?? null) ? trim($data['motif_refus']) : null;

        $joinRequest->update([
            'statut' => JoinRequest::STATUS_REFUSED,
            'traite_par' => $request->user()->id,
            'traite_le' => now(),
            'motif_refus' => $note,
        ]);

        $sent = $request->boolean('prevenir') && $this->mail($joinRequest->email, new JoinRequestAnswered($joinRequest, false, $note));

        AdminLogger::log('demande.refusee', $joinRequest, null, ['statut' => JoinRequest::STATUS_NEW], [
            'statut' => JoinRequest::STATUS_REFUSED, 'message' => $note, 'candidat_prevenu' => $sent,
        ]);

        return redirect()->route('admin.join-requests.show', $joinRequest)->with('status', $sent
            ? 'Demande refusée. Le candidat est prévenu par e-mail.'
            : 'Demande refusée.'.($request->boolean('prevenir') ? ' L’e-mail n’a pas pu partir.' : ' Le candidat n’a pas été prévenu.'));
    }

    public function updateNote(Request $request, JoinRequest $joinRequest): RedirectResponse
    {
        $data = $request->validate([
            'note_interne' => ['nullable', 'string', 'max:2000'],
        ], ['note_interne.max' => 'La note ne doit pas dépasser 2 000 caractères.']);

        $before = $joinRequest->note_interne;
        $joinRequest->update(['note_interne' => filled($data['note_interne'] ?? null) ? trim($data['note_interne']) : null]);

        if ($before !== $joinRequest->note_interne) {
            AdminLogger::log('demande.note', $joinRequest, null, ['note' => $before], ['note' => $joinRequest->note_interne]);
        }

        return redirect()->route('admin.join-requests.show', $joinRequest)->with('status', 'Note interne enregistrée.');
    }

    private function mail(string $email, $notification): bool
    {
        try {
            Notification::route('mail', $email)->notify($notification);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Join request answer failed', ['email' => $email, 'error' => $exception->getMessage()]);

            return false;
        }
    }
}
