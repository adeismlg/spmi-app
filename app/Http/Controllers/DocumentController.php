<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends CrudController
{
    protected string $model = Document::class;

    protected string $routeName = 'documents';

    protected string $title = 'Dokumen Mutu';

    protected array $with = ['unit'];

    protected function canManage(): bool
    {
        return auth()->user()->hasRole('admin_spmi');
    }

    protected function query(): Builder
    {
        $q = Document::query()->with($this->with)->latest('id');

        // Non-admin hanya melihat dokumen yang sudah valid.
        if (! $this->canManage()) {
            $q->where('status', DocumentStatus::Valid->value);
        }

        return $q;
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Jenis', 'value' => fn ($r) => $r->jenis->label()],
            ['label' => 'Nomor', 'value' => fn ($r) => $r->nomor ?? '-'],
            ['label' => 'Judul', 'value' => fn ($r) => $r->judul],
            ['label' => 'Versi', 'value' => fn ($r) => $r->versi],
            ['label' => 'Status', 'value' => fn ($r) => $r->status->label()],
            ['label' => 'Unit', 'value' => fn ($r) => $r->unit?->nama ?? 'Institusi'],
        ];
    }

    protected function rowActions(): array
    {
        return [[
            'label' => 'Unduh / Buka',
            'route' => fn ($r) => route('documents.download', $r->id),
            'method' => 'get',
            'visible' => fn ($r) => $r->file_path || $r->url,
            'class' => 'btn-outline-primary',
        ]];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'jenis' => ['label' => 'Jenis Dokumen', 'type' => 'select', 'options' => $this->enumOptions(DocumentType::class),
                'rules' => ['required', Rule::enum(DocumentType::class)]],
            'nomor' => ['label' => 'Nomor Dokumen', 'rules' => 'nullable|max:255'],
            'judul' => ['label' => 'Judul', 'rules' => 'required|max:255'],
            'versi' => ['label' => 'Versi', 'default' => '1.0', 'rules' => 'required|max:20'],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => $this->enumOptions(DocumentStatus::class),
                'rules' => ['required', Rule::enum(DocumentStatus::class)]],
            'file_path' => ['label' => 'Berkas', 'type' => 'file', 'rules' => 'nullable|file|max:10240',
                'help' => 'Maks 10 MB. Atau isi tautan di bawah.'],
            'url' => ['label' => 'Tautan', 'rules' => 'nullable|url|max:255'],
            'tanggal_berlaku' => ['label' => 'Tanggal Berlaku', 'type' => 'date', 'rules' => 'nullable|date'],
            'unit_id' => ['label' => 'Unit Pemilik', 'type' => 'select', 'placeholder' => '— Institusi —',
                'options' => Unit::orderBy('nama')->pluck('nama', 'id')->all(), 'rules' => 'nullable|exists:units,id'],
        ];
    }

    protected function beforeSave(array $data, Request $request, ?Model $record): array
    {
        if (! $record) {
            $data['uploaded_by'] = $request->user()->id;
        }

        return $data;
    }

    public function download(string $id)
    {
        $doc = $this->query()->findOrFail($id);

        if ($doc->file_path) {
            return Storage::disk('public')->download($doc->file_path, str($doc->judul)->slug().'.'.pathinfo($doc->file_path, PATHINFO_EXTENSION));
        }

        abort_unless($doc->url, 404);

        return redirect()->away($doc->url);
    }
}
