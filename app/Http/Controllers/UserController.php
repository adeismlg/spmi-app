<?php

namespace App\Http\Controllers;

use App\Enums\Jabatan;
use App\Models\Unit;
use App\Models\User;
use App\Services\WorkflowNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends CrudController
{
    public function __construct(private WorkflowNotificationService $notifications) {}

    protected string $model = User::class;

    protected string $routeName = 'users';

    protected string $title = 'Pengguna';

    protected array $with = ['unit', 'roles'];

    protected function columns(): array
    {
        return [
            ['label' => 'Nama', 'value' => fn ($r) => $r->name],
            ['label' => 'Email', 'value' => fn ($r) => $r->email],
            ['label' => 'Unit', 'value' => fn ($r) => $r->unit?->nama ?? '-'],
            ['label' => 'Jabatan', 'value' => fn ($r) => $r->jabatan?->label() ?? '-'],
            ['label' => 'Peran', 'value' => fn ($r) => $r->roles->pluck('name')->implode(', ')],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'name' => ['label' => 'Nama', 'rules' => 'required|max:255'],
            'email' => ['label' => 'Email', 'type' => 'email',
                'rules' => ['required', 'email', Rule::unique('users', 'email')->ignore($record?->id)]],
            'password' => ['label' => 'Password', 'type' => 'password',
                'rules' => $record ? 'nullable|min:8' : 'required|min:8',
                'help' => $record ? 'Kosongkan jika tidak diganti.' : null],
            'unit_id' => ['label' => 'Unit', 'type' => 'select', 'placeholder' => '— tidak ada —',
                'options' => Unit::orderBy('nama')->pluck('nama', 'id')->all(), 'rules' => 'nullable|exists:units,id'],
            'jabatan' => ['label' => 'Jabatan', 'type' => 'select', 'placeholder' => '— tidak ada —',
                'options' => $this->enumOptions(Jabatan::class), 'rules' => ['nullable', Rule::enum(Jabatan::class)],
                'help' => 'Pengguna dengan jabatan hanya mengisi pernyataan yang PIC-nya sama dengan jabatannya di unitnya. Wadir/Direktur: pilih unit "Institusi".'],
            'role' => ['label' => 'Peran', 'type' => 'select', 'virtual' => true,
                'options' => Role::orderBy('name')->pluck('name', 'name')->all(),
                'value' => fn ($r) => $r?->getRoleNames()->first(),
                'rules' => ['required', Rule::exists('roles', 'name')]],
        ];
    }

    protected function beforeSave(array $data, Request $request, ?Model $record): array
    {
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }

    protected function afterSave(Model $record, Request $request): void
    {
        $record->syncRoles([$request->input('role')]);

        if ($record->wasRecentlyCreated) {
            $this->notifications->notifyUserAccountCreated($record);
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        abort_if((int) $id === auth()->id(), 422, 'Tidak dapat menghapus akun sendiri.');

        return parent::destroy($id);
    }

    protected function rowActions(): array
    {
        return auth()->user()->hasRole('admin_spmi') ? [
            [
                'label' => new HtmlString('<i class="bi bi-person-check"></i> Login As'),
                'route' => fn ($user) => route('users.impersonate', $user->id),
                'method' => 'post',
                'visible' => fn ($user) => $user->canBeImpersonated(),
                'class' => 'btn-warning btn-sm',
                'confirm' => fn ($user) => 'Login as '.$user->name.'?',
            ],
        ] : [];
    }
}
