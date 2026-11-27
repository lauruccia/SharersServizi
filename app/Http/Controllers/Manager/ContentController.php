<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Faq;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Materiali (moduli, grafiche, video) e FAQ mostrati nelle aree di agenti e dealer. */
class ContentController extends Controller
{
    public function index()
    {
        return view('manager.content', [
            'resources' => Resource::query()->orderBy('kind')->orderBy('position')->orderBy('id')->get(),
            'faqs' => Faq::query()->orderBy('position')->orderBy('id')->get(),
        ]);
    }

    public function storeResource(Request $request)
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(Resource::KINDS))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'url' => ['nullable', 'url', 'max:500', 'required_without:file'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip', 'max:'.config('effepi.upload_max_kb'), 'required_without:url'],
        ], [
            'url.required_without' => 'Carica un file oppure inserisci un link.',
            'file.required_without' => 'Carica un file oppure inserisci un link.',
        ]);

        $resource = Resource::query()->create([
            'kind' => $data['kind'], 'title' => $data['title'], 'description' => $data['description'] ?? null,
            'url' => $request->hasFile('file') ? null : ($data['url'] ?? null),
            'file_path' => $request->file('file')?->store('materiali', 'uploads'),
            'position' => (int) Resource::query()->where('kind', $data['kind'])->max('position') + 1,
        ]);
        ActivityLog::record('resource.created', $resource);

        return back()->with('status', 'Materiale pubblicato: è già visibile ad agenti e dealer.');
    }

    public function destroyResource(Resource $resource)
    {
        if ($resource->file_path) {
            Storage::disk('uploads')->delete($resource->file_path);
        }
        $resource->delete();

        return back()->with('status', 'Materiale rimosso.');
    }

    public function storeFaq(Request $request)
    {
        $faq = Faq::query()->create($this->faqData($request) + ['position' => (int) Faq::query()->max('position') + 1]);
        ActivityLog::record('faq.created', $faq);

        return back()->with('status', 'Domanda aggiunta.');
    }

    public function updateFaq(Request $request, Faq $faq)
    {
        $faq->update(['active' => $request->boolean('active')] + $this->faqData($request));

        return back()->with('status', 'Domanda aggiornata.');
    }

    public function destroyFaq(Faq $faq)
    {
        $faq->delete();

        return back()->with('status', 'Domanda rimossa.');
    }

    private function faqData(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'keywords' => ['nullable', 'string', 'max:255'],
        ]);
        $data['keywords'] = $data['keywords'] ?? null;

        return $data;
    }
}
