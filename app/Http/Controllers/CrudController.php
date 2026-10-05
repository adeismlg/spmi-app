<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Controller CRUD generik untuk modul master. Turunannya cukup mendefinisikan
 * $model, $routeName, $title, columns() dan fields(); view `crud.index` & `crud.form`
 * dipakai bersama. Modul dengan alur khusus (evaluasi diri, daftar tilik) tidak memakainya.
 */
abstract class CrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    protected string $routeName;

    protected string $title;

    protected array $with = [];

    /** [ ['label' => 'Nama', 'value' => fn ($row) => $row->nama], ... ] */
    abstract protected function columns(): array;

    /**
     * [ 'nama' => ['label' => 'Nama', 'type' => 'text', 'rules' => 'required', 'options' => [...],
     *              'virtual' => false, 'value' => fn ($record) => ..., 'default' => ..., 'help' => '...'] ]
     * type: text|email|password|number|date|textarea|select|multiselect|file|checkbox
     */
    abstract protected function fields(?Model $record = null): array;

    protected function query(): Builder
    {
        return $this->model::query()->with($this->with)->latest('id');
    }

    /** Filter dropdown pada halaman daftar: ['standard_id' => ['label' => 'Standar', 'options' => [id => label]]] */
    protected function filters(): array
    {
        return [];
    }

    /** Tombol tambah/ubah/hapus tampil hanya bila true. */
    protected function canManage(): bool
    {
        return true;
    }

    /** Aksi tambahan per baris: [['label'=>..., 'route'=>fn($r)=>url, 'method'=>'get|post', 'visible'=>fn($r)=>bool, 'class'=>..., 'confirm'=>...]] */
    protected function rowActions(): array
    {
        return [];
    }

    /** Aturan validasi tambahan (mis. 'findings.*'). */
    protected function extraRules(?Model $record): array
    {
        return [];
    }

    protected function beforeSave(array $data, Request $request, ?Model $record): array
    {
        return $data;
    }

    protected function afterSave(Model $record, Request $request): void {}

    public function index(): View
    {
        return view('crud.index', [
            'title' => $this->title,
            'routeName' => $this->routeName,
            'columns' => $this->columns(),
            'rows' => $this->query()->paginate(15)->withQueryString(),
            'filters' => $this->filters(),
            'actions' => $this->rowActions(),
            'canManage' => $this->canManage(),
        ]);
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function edit(string $id): View
    {
        return $this->form($this->query()->findOrFail($id));
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, null);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        return $this->save($request, $this->query()->findOrFail($id));
    }

    public function destroy(string $id): RedirectResponse
    {
        $record = $this->query()->findOrFail($id);

        try {
            $record->delete();
        } catch (QueryException) {
            return back()->with('error', 'Data tidak dapat dihapus karena masih dipakai data lain.');
        }

        return redirect()->route($this->routeName.'.index')->with('success', 'Data berhasil dihapus.');
    }

    protected function form(?Model $record): View
    {
        return view('crud.form', [
            'title' => $this->title,
            'routeName' => $this->routeName,
            'record' => $record,
            'fields' => $this->fields($record),
        ]);
    }

    protected function save(Request $request, ?Model $record): RedirectResponse
    {
        $fields = $this->fields($record);

        $request->validate(array_merge(
            collect($fields)->map(fn ($f) => $f['rules'] ?? 'nullable')->all(),
            $this->extraRules($record),
        ));

        $data = [];
        foreach ($fields as $name => $f) {
            if (! empty($f['virtual'])) {
                continue;
            }

            $type = $f['type'] ?? 'text';

            if ($type === 'file') {
                if ($request->hasFile($name)) {
                    if ($record && $record->{$name}) {
                        Storage::disk('public')->delete($record->{$name});
                    }
                    $data[$name] = $request->file($name)->store($this->routeName, 'public');
                }
            } elseif ($type === 'checkbox') {
                $data[$name] = $request->boolean($name);
            } else {
                $data[$name] = $request->input($name);
            }
        }

        $data = $this->beforeSave($data, $request, $record);

        if ($record) {
            $record->update($data);
        } else {
            $record = $this->model::create($data);
        }

        $this->afterSave($record, $request);

        return redirect()->route($this->routeName.'.index')->with('success', 'Data berhasil disimpan.');
    }
}
