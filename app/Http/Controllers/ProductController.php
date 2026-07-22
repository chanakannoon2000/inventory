<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Support\CostCipher;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);

        $products = $query->orderBy('name')->paginate(20)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $productGroups = ProductGroup::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $settings = Setting::current();
        $showCost = session('show_cost', false) && auth()->user()?->canViewCost();

        return view('products.index', compact(
            'products',
            'categories',
            'productGroups',
            'units',
            'suppliers',
            'settings',
            'showCost'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $format = $request->input('format', 'csv');
        if (! in_array($format, ['csv', 'excel'], true)) {
            $format = 'csv';
        }

        $showCost = auth()->user()?->canViewCost() ?? false;
        $filename = 'products-'.now()->format('Y-m-d-His').($format === 'excel' ? '.xls' : '.csv');

        if ($format === 'excel') {
            return $this->exportExcel($request, $filename, $showCost);
        }

        return $this->exportCsv($request, $filename, $showCost);
    }

    private function filteredQuery(Request $request)
    {
        $query = Product::with(['category', 'unit', 'supplier', 'productGroup']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->type === 'service') {
            $query->services();
        } elseif ($request->type === 'product') {
            $query->goods();
        }

        if ($request->stock === 'low') {
            $query->lowStock();
        } elseif ($request->stock === 'over') {
            $query->overStock();
        }

        return $query;
    }

    private function exportHeaders(bool $showCost): array
    {
        $headers = ['ประเภท', 'ชื่อสินค้า', 'บาร์โค้ด', 'หมวดหมู่', 'หน่วย', 'ราคาขาย', 'คงเหลือ', 'Min', 'Max', 'ผู้จำหน่าย', 'สถานะสต๊อก'];
        if ($showCost) {
            array_splice($headers, 5, 0, ['ราคาทุน', 'รหัสทุน']);
        } else {
            array_splice($headers, 5, 0, ['รหัสทุน']);
        }

        return $headers;
    }

    private function exportRow(Product $p, bool $showCost): array
    {
        $status = $p->isService()
            ? 'บริการ'
            : ($p->isLowStock() ? 'ต่ำกว่า Min' : ($p->isOverStock() ? 'เกิน Max' : 'ปกติ'));
        $row = [
            $p->typeLabel(),
            $p->name,
            $p->barcode,
            $p->category?->name,
            $p->unit?->name,
        ];

        if ($showCost) {
            $row[] = (float) $p->cost_price;
            $row[] = CostCipher::encode((float) $p->cost_price);
        } else {
            $row[] = CostCipher::encode((float) $p->cost_price);
        }

        $row = array_merge($row, [
            (float) $p->sell_price,
            (float) $p->stock,
            (float) $p->min_stock,
            (float) $p->max_stock,
            $p->supplier?->name,
            $status,
        ]);

        return $row;
    }

    private function exportCsv(Request $request, string $filename, bool $showCost): StreamedResponse
    {
        $headers = $this->exportHeaders($showCost);

        return response()->streamDownload(function () use ($request, $headers, $showCost) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers);

            $this->filteredQuery($request)->orderBy('name')->chunk(200, function ($rows) use ($out, $showCost) {
                foreach ($rows as $p) {
                    fputcsv($out, $this->exportRow($p, $showCost));
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportExcel(Request $request, string $filename, bool $showCost): StreamedResponse
    {
        $headers = $this->exportHeaders($showCost);
        $rows = $this->filteredQuery($request)->orderBy('name')->get();

        return response()->streamDownload(function () use ($headers, $rows, $showCost) {
            echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            echo '<?mso-application progid="Excel.Sheet"?>'."\n";
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
                .'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'."\n";
            echo '<Worksheet ss:Name="Products"><Table>'."\n";

            echo '<Row>';
            foreach ($headers as $h) {
                echo '<Cell><Data ss:Type="String">'.$this->xml($h).'</Data></Cell>';
            }
            echo '</Row>'."\n";

            foreach ($rows as $p) {
                echo '<Row>';
                foreach ($this->exportRow($p, $showCost) as $cell) {
                    $type = is_numeric($cell) && $cell !== '' && ! is_string($cell) ? 'Number' : 'String';
                    if (is_float($cell) || is_int($cell)) {
                        $type = 'Number';
                        $val = $cell;
                    } else {
                        $type = 'String';
                        $val = $this->xml((string) $cell);
                    }
                    echo '<Cell><Data ss:Type="'.$type.'">'.$val.'</Data></Cell>';
                }
                echo '</Row>'."\n";
            }

            echo '</Table></Worksheet></Workbook>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    public function toggleCost()
    {
        abort_unless(auth()->user()?->canViewCost(), 403);
        session(['show_cost' => ! session('show_cost', false)]);

        return back();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data = $this->normalizeProductData($data);
        $data['barcode'] = $data['barcode'] ?: $this->generateBarcode(
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null
        );
        $data['image_url'] = ImageUploader::storeProductImage(
            $request->file('image'),
            $request->input('image_url_link')
        );

        $product = Product::create($data);

        if ($product->tracksStock() && (float) $product->stock > 0) {
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'IN',
                'qty' => $product->stock,
                'note' => 'ตั้งต้นสต๊อก',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => ($product->isService() ? 'เพิ่มบริการสำเร็จ' : 'เพิ่มสินค้าสำเร็จ')]);
        }

        return redirect()->route('products.index')->with('success', $product->isService() ? 'เพิ่มบริการสำเร็จ' : 'เพิ่มสินค้าสำเร็จ');
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product->id);
        $data = $this->normalizeProductData($data);
        $data['barcode'] = $data['barcode'] ?: $product->barcode;

        if ($request->boolean('clear_image')) {
            ImageUploader::clear($product->image_url);
            $data['image_url'] = null;
        } else {
            $data['image_url'] = ImageUploader::storeProductImage(
                $request->file('image'),
                $request->filled('image_url_link') ? $request->input('image_url_link') : null,
                $product->image_url
            );
        }

        $oldStock = (float) $product->stock;
        $product->update($data);
        $newStock = (float) $product->stock;

        if ($product->tracksStock() && $oldStock !== $newStock) {
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'ADJUST',
                'qty' => $newStock - $oldStock,
                'note' => 'ปรับสต๊อกจากการแก้ไขสินค้า',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => ($product->isService() ? 'แก้ไขบริการสำเร็จ' : 'แก้ไขสินค้าสำเร็จ')]);
        }

        return redirect()->route('products.index')->with('success', $product->isService() ? 'แก้ไขบริการสำเร็จ' : 'แก้ไขสินค้าสำเร็จ');
    }

    public function destroy(Product $product)
    {
        ImageUploader::clear($product->image_url);
        $product->delete();

        return redirect()->route('products.index')->with('success', 'ลบสินค้าแล้ว');
    }

    public function barcodePreview(Request $request)
    {
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        return response()->json([
            'barcode' => $this->generateBarcode(categoryId: $categoryId),
        ]);
    }

    public function cipherPreview(Request $request)
    {
        return response()->json([
            'code' => CostCipher::encode((float) $request->input('cost', 0)),
        ]);
    }

    private function normalizeProductData(array $data): array
    {
        unset($data['image'], $data['image_url_link'], $data['clear_image']);

        $data['type'] = ($data['type'] ?? Product::TYPE_PRODUCT) === Product::TYPE_SERVICE
            ? Product::TYPE_SERVICE
            : Product::TYPE_PRODUCT;
        $data['cost_price'] = (float) ($data['cost_price'] ?? 0);
        $data['sell_price'] = (float) ($data['sell_price'] ?? 0);

        if ($data['type'] === Product::TYPE_SERVICE) {
            $data['stock'] = 0;
            $data['min_stock'] = 0;
            $data['max_stock'] = 0;
            $data['product_group_id'] = null;
            $data['size_label'] = null;
        } else {
            $data['stock'] = (float) ($data['stock'] ?? 0);
            $data['min_stock'] = (float) ($data['min_stock'] ?? 0);
            $data['max_stock'] = (float) ($data['max_stock'] ?? 100);
            $data['size_label'] = isset($data['size_label']) ? trim((string) $data['size_label']) ?: null : null;
        }

        return $data;
    }

    private function validated(Request $request, ?int $id = null): array
    {
        if (! $request->filled('image_url_link')) {
            $request->merge(['image_url_link' => null]);
        }

        // ช่องไฟล์ว่างจาก multipart มักทำให้ rule file/uploaded พัง
        if (! $request->hasFile('image')) {
            $request->files->remove('image');
        }

        foreach (['category_id', 'unit_id', 'supplier_id', 'barcode', 'product_group_id', 'size_label'] as $field) {
            if ($request->input($field) === '' || $request->input($field) === null) {
                $request->merge([$field => null]);
            }
        }

        if (! $request->filled('type')) {
            $request->merge(['type' => Product::TYPE_PRODUCT]);
        }

        $barcodeRule = $id
            ? 'nullable|string|max:255|unique:products,barcode,'.$id
            : 'nullable|string|max:255|unique:products,barcode';

        return $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:product,service',
            'product_group_id' => 'nullable|exists:product_groups,id',
            'size_label' => 'nullable|string|max:50',
            'barcode' => $barcodeRule,
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'cost_price' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:8192',
            'image_url_link' => 'nullable|url|max:500',
            'clear_image' => 'nullable|boolean',
        ], [
            'image.uploaded' => 'อัปโหลดรูปไม่สำเร็จ ไฟล์อาจใหญ่เกินไปหรือรูปแบบไม่รองรับ',
            'image.file' => 'อัปโหลดรูปไม่สำเร็จ ไฟล์อาจใหญ่เกินไปหรือรูปแบบไม่รองรับ',
            'image.mimes' => 'รองรับเฉพาะไฟล์ JPG, PNG, GIF, WEBP',
            'image.max' => 'ขนาดรูปต้องไม่เกิน 8MB',
            'image_url_link.url' => 'ลิงก์รูปภาพไม่ถูกต้อง',
            'name.required' => 'กรุณากรอกชื่อสินค้า/บริการ',
            'barcode.unique' => 'บาร์โค้ดนี้มีอยู่แล้ว',
            'product_group_id.exists' => 'ไม่พบกลุ่มสินค้าที่เลือก',
            'type.in' => 'ประเภทต้องเป็นสินค้าหรือบริการ',
        ]);
    }

    private function generateBarcode(?int $categoryId = null): string
    {
        $prefix = '';
        if ($categoryId) {
            $category = Category::find($categoryId);
            $prefix = $category?->barcodePrefixLetter() ?: '';
        }

        do {
            $code = $prefix.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Product::where('barcode', $code)->exists());

        return $code;
    }
}
