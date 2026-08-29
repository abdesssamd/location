<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function scan(Product $product): View
    {
        $this->authorize('view', $product);
        $product->load(['images', 'category']);

        return view('products.scan-result', compact('product'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);
        AuditLogger::deleted($product, 'product.deleted');
        $product->delete();

        return redirect()->route('products.index')->with('status', 'Article supprimé.');
    }
}