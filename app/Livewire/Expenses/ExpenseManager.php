<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use App\Services\StoreContext;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ExpenseManager extends Component
{
    use WithFileUploads, WithPagination;

    public ?int $expense_category_id = null;
    public string $label = '';
    public $amount = 0;
    public string $payment_method = 'cash';
    public string $date = '';
    public string $notes = '';
    public bool $is_recurring = false;
    public $proof;

    public string $search = '';
    public string $filterCategory = '';
    public string $from = '';
    public string $to = '';

    public bool $showNewCategory = false;
    public string $newCategoryName = '';
    public string $newCategoryColor = '#71717a';

    public function mount(): void
    {
        $this->authorize('viewAny', Expense::class);
        $this->date = now()->toDateString();

        // Catégories par défaut, créées une fois par magasin : le magasin ne
        // repart jamais d'une liste vide, mais reste libre de la personnaliser.
        $storeId = StoreContext::id();

        if ($storeId && ! ExpenseCategory::where('store_id', $storeId)->exists()) {
            foreach (ExpenseCategory::defaultNames() as $name) {
                ExpenseCategory::create(['store_id' => $storeId, 'name' => $name, 'is_default' => true]);
            }
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    public function quickAddCategory(): void
    {
        $this->authorize('create', Expense::class);

        $this->validate([
            'newCategoryName' => ['required', 'string', 'max:255'],
        ]);

        $category = ExpenseCategory::firstOrCreate(
            ['store_id' => StoreContext::id(), 'name' => trim($this->newCategoryName)],
            ['color' => $this->newCategoryColor ?: null]
        );

        $this->expense_category_id = $category->id;
        $this->newCategoryName = '';
        $this->showNewCategory = false;

        session()->flash('status', "Catégorie « {$category->name} » créée et sélectionnée.");
    }

    public function save(): void
    {
        $this->authorize('create', Expense::class);

        $this->validate([
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,card,transfer,check'],
            'date' => ['required', 'date'],
            'expense_category_id' => ['nullable', 'exists:expense_categories,id'],
            'proof' => ['nullable', 'image', 'max:5120'],
        ]);

        $storeId = StoreContext::id();

        abort_if($storeId === null, 403, 'Aucun magasin associé à votre compte.');

        $proofPath = null;
        if ($this->proof) {
            // Disque privé, comme les autres justificatifs (paiements, abonnement).
            $proofPath = $this->proof->store('expenses/'.$storeId, 'local');
        }

        $expense = Expense::create([
            'store_id' => $storeId,
            'expense_category_id' => $this->expense_category_id,
            'user_id' => auth()->id(),
            'reference' => ReferenceGenerator::reference('DEP', Expense::class),
            'label' => $this->label,
            'amount' => (int) $this->amount,
            'payment_method' => $this->payment_method,
            'date' => $this->date,
            'notes' => $this->notes ?: null,
            'proof_path' => $proofPath,
            'is_recurring' => $this->is_recurring,
        ]);

        AuditLogger::created($expense, 'expense.created');

        $this->reset(['label', 'amount', 'notes', 'proof', 'is_recurring', 'expense_category_id']);
        $this->payment_method = 'cash';
        $this->date = now()->toDateString();

        session()->flash('status', 'Dépense « '.$expense->label.' » enregistrée.');
    }

    public function delete(int $expenseId): void
    {
        $expense = Expense::findOrFail($expenseId);
        $this->authorize('delete', $expense);

        if ($expense->proof_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($expense->proof_path);
        }

        AuditLogger::deleted($expense, 'expense.deleted');
        $expense->delete();

        session()->flash('status', 'Dépense supprimée.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $storeId = StoreContext::id();

        $expenses = Expense::query()
            ->with(['category', 'user'])
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('label', 'like', '%'.$this->search.'%')
                    ->orWhere('reference', 'like', '%'.$this->search.'%');
            }))
            ->when($this->filterCategory, fn ($q) => $q->where('expense_category_id', $this->filterCategory))
            ->when($this->from, fn ($q) => $q->whereDate('date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('date', '<=', $this->to))
            ->latest('date')
            ->latest('id')
            ->paginate(15);

        $categories = ExpenseCategory::query()
            ->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))
            ->orderBy('name')
            ->get();

        $today = (int) Expense::query()->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->whereDate('date', now()->toDateString())->sum('amount');
        $month = (int) Expense::query()->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->whereYear('date', now()->year)->whereMonth('date', now()->month)->sum('amount');
        $total = (int) Expense::query()->when($storeId, fn ($q, $sid) => $q->where('store_id', $sid))->sum('amount');

        return view('livewire.expenses.expense-manager', compact('expenses', 'categories', 'today', 'month', 'total'));
    }
}
