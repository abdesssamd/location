<x-layouts.admin title="Nouveau magasin">
    <div class="space-y-6">
    <div>
        <h1 class="page-title">Nouveau magasin</h1>
        <p class="page-subtitle">Créer un nouveau magasin sur la plateforme.</p>
    </div>

    <form method="POST" action="{{ route('admin.stores.store') }}" class="card card-pad max-w-2xl space-y-5">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="space-y-2">
                <label for="name" class="text-sm font-medium text-zinc-700">Nom du magasin</label>
                <input id="name" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                @error('name') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="space-y-2">
                <label for="slug" class="text-sm font-medium text-zinc-700">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug') }}" required placeholder="mon-magasin" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
                @error('slug') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="space-y-2">
                <label for="phone" class="text-sm font-medium text-zinc-700">Téléphone</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
            </div>
            <div class="space-y-2">
                <label for="email" class="text-sm font-medium text-zinc-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="space-y-2">
                <label for="wilaya" class="text-sm font-medium text-zinc-700">Wilaya</label>
                <input id="wilaya" name="wilaya" value="{{ old('wilaya') }}" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
            </div>
            <div class="space-y-2">
                <label for="commune" class="text-sm font-medium text-zinc-700">Commune</label>
                <input id="commune" name="commune" value="{{ old('commune') }}" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
            </div>
        </div>

        <div class="space-y-2">
            <label for="address" class="text-sm font-medium text-zinc-700">Adresse</label>
            <input id="address" name="address" value="{{ old('address') }}" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="space-y-2">
                <label for="manager_name" class="text-sm font-medium text-zinc-700">Responsable</label>
                <input id="manager_name" name="manager_name" value="{{ old('manager_name') }}" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
            </div>
            <div class="space-y-2">
                <label for="currency" class="text-sm font-medium text-zinc-700">Devise</label>
                <input id="currency" name="currency" value="{{ old('currency', 'DA') }}" maxlength="8" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
            </div>
        </div>

        <div class="space-y-2">
            <label for="color" class="text-sm font-medium text-zinc-700">Couleur de la marque</label>
            <div class="flex items-center gap-2">
                <input type="color" id="color_picker" value="{{ old('color', '#1e3a5f') }}" oninput="document.getElementById('color').value = this.value" class="h-10 w-12 cursor-pointer rounded-xl border border-zinc-300 bg-white p-1" />
                <input type="text" id="color" name="color" value="{{ old('color', '#1e3a5f') }}" maxlength="7" onchange="document.getElementById('color_picker').value = this.value" class="w-32 rounded-xl border border-zinc-300 px-3 py-2 font-mono text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 focus:outline-none" />
            </div>
            @error('color') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-xl bg-brand-800 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700">
                Créer le magasin
            </button>
            <a href="{{ route('admin.stores.index') }}" class="rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50" wire:navigate>
                Annuler
            </a>
        </div>
    </form>
</div>
</x-layouts.admin>