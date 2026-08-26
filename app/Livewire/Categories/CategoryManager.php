<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CategoryManager extends Component
{
    public string $name = '';
    public ?int $parent_id = null;
    public string $color = '#14213f';
    public ?int $store_id = null;
    public ?int $filterStoreId = null;

    public ?int $editingId = null;

    public function save(): void
    {
        $needsStore = StoreContext::id() === null;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'store_id' => $needsStore ? ['required', 'exists:stores,id'] : ['nullable', 'exists:stores,id'],
        ]);

        $data = [
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'color' => $this->color,
        ];

        if ($this->editingId) {
            $category = Category::findOrFail($this->editingId);
            $old = $category->getAttributes();
            $category->update($data);
            AuditLogger::updated($category, $old, 'category.updated');
        } else {
            $data['store_id'] = StoreContext::id() ?? $this->store_id;
            $category = Category::create($data);
            AuditLogger::created($category, 'category.created');
        }

        $this->reset(['name', 'parent_id', 'color', 'editingId', 'store_id']);
        session()->flash('status', 'Catégorie enregistrée.');
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->parent_id = $category->parent_id;
        $this->color = $category->color ?? '#14213f';
        $this->store_id = $category->store_id;
    }

    public function delete(int $id): void
    {
        $category = Category::findOrFail($id);
        AuditLogger::deleted($category, 'category.deleted');
        $category->delete();
        session()->flash('status', 'Catégorie supprimée.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $needsStore = StoreContext::id() === null;
        $stores = $needsStore ? Store::where('status', 'active')->orderBy('name')->get() : collect();

        $categories = Category::with('children', 'products')
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($needsStore && $this->filterStoreId, fn ($q) => $q->where('store_id', $this->filterStoreId))
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $allCategories = Category::query()
            ->when(StoreContext::id(), fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($needsStore && $this->filterStoreId, fn ($q) => $q->where('store_id', $this->filterStoreId))
            ->orderBy('name')
            ->get();

        $currentStoreName = $needsStore ? null : \App\Models\Store::find(StoreContext::id())?->name;

        return view('livewire.categories.category-manager', compact('categories', 'allCategories', 'needsStore', 'stores', 'currentStoreName'));
    }
}