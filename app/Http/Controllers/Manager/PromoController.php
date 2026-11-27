<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Promo;
use App\Models\ShowcaseProduct;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromoController extends Controller
{
    public function index()
    {
        return view('manager.promos', [
            'active' => Promo::query()->active()->orderBy('ends_at')->get(),
            'expired' => Promo::query()->where('ends_at', '<=', now())->latest('ends_at')->limit(10)->get(),
            'showcase' => $this->showcase(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'duration_hours' => ['required', Rule::in(array_keys(config('effepi.promo_durations')))],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
        ]);
        $start = now();
        $promo = Promo::query()->create([
            'name' => $data['name'],
            'price' => $data['price'],
            'duration_hours' => (int) $data['duration_hours'],
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours((int) $data['duration_hours']),
            'photo_path' => $request->file('photo')?->store('promos', 'uploads'),
            'created_by' => auth()->id(),
        ]);
        ActivityLog::record('promo.published', $promo);

        return back()->with('status', 'Offerta pubblicata: è già visibile ad agenti e dealer fino a '.$promo->ends_at->isoFormat('dddd D MMMM [alle] HH:mm').'.');
    }

    /** Chiude subito un'offerta in corso. */
    public function close(Promo $promo)
    {
        if ($promo->isActive()) {
            $promo->update(['ends_at' => now()]);
            ActivityLog::record('promo.closed', $promo);
        }

        return back()->with('status', 'Offerta chiusa.');
    }

    public function saveShowcase(Request $request)
    {
        $data = $request->validate([
            'products' => ['required', 'array', 'size:3'],
            'products.*.name' => ['nullable', 'string', 'max:255'],
            'products.*.url' => ['nullable', 'url', 'max:500', 'starts_with:https://www.sharers.it,https://sharers.it'],
            'products.*.photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
        ], [], ['products.*.url' => 'link su sharers.it', 'products.*.name' => 'nome del prodotto', 'products.*.photo' => 'foto']);

        foreach ([1, 2, 3] as $position) {
            $row = $data['products'][$position] ?? [];
            $product = ShowcaseProduct::query()->firstOrNew(['position' => $position]);
            $product->fill(['name' => $row['name'] ?? null, 'url' => $row['url'] ?? null, 'updated_by' => auth()->id()]);
            if ($file = $request->file("products.$position.photo")) {
                $product->photo_path = $file->store('promos', 'uploads');
            }
            $product->save();
        }
        ActivityLog::record('showcase.saved');

        return back()->with('status', 'Prodotti del catalogo Sharers salvati.');
    }

    private function showcase()
    {
        $saved = ShowcaseProduct::query()->get()->keyBy('position');

        return collect([1, 2, 3])->mapWithKeys(fn ($p) => [$p => $saved[$p] ?? new ShowcaseProduct(['position' => $p])]);
    }
}
