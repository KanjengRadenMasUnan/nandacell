<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException; 

class ApiController extends Controller
{
    // 1. Tambah Barang
    public function addProduct(Request $request) {
        // 1. Logika Request (Sama seperti sebelumnya)
        $request->validate([
            'name'  => 'required',
            'price' => 'required|numeric',
            'stock' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction(); // Mulai Transaksi Database (Biar aman)

            // Logika Barcode
            if ($request->filled('code')) {
                if (Product::where('code', trim($request->code))->exists()) {
                    return response()->json(['message' => 'Barcode sudah ada!'], 400);
                }
                $finalCode = trim($request->code);
            } else {
                $lastProduct = Product::where('code', 'like', 'BRG-%')->orderBy('id', 'desc')->first();
                $number = $lastProduct ? ((int)substr($lastProduct->code, 4) + 1) : 1;
                $finalCode = 'BRG-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }

            // 2. Simpan ke Database
            $product = new Product();
            $product->code = $finalCode;
            $product->name = $request->name;
            $product->price = $request->price;
            $product->stock = $request->stock;
            
            // Cek apakah berhasil save
            if (!$product->save()) {
                DB::rollBack();
                return response()->json(['message' => 'Gagal menyimpan ke DB'], 500);
            }

            DB::commit(); // Simpan permanen

            // 3. RETURN RESPONSE (INI YANG TADI HILANG/KOSONG)
            return response()->json([
                'message' => 'Success',
                'data' => $product
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            // Kembalikan error 500 dengan pesan jelas
            return response()->json([
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // 2. Scan Barang (By Code)
    public function getProduct($code) {
        $product = Product::where('code', $code)->first();
        if(!$product) return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        return response()->json($product);
    }

    // 3. Checkout (Bayar)
    public function checkout(Request $request) {
        try {
            DB::beginTransaction(); // Memulai transaksi database

            if(empty($request->items)) {
                throw new \Exception("Keranjang kosong!");
            }

            // Buat Header Transaksi
            $trx = Transaction::create([
                'invoice_no' => 'INV-' . time(),
                'transaction_date' => now(),
                'total_amount' => $request->total_amount,
            ]);

            // Loop barang yang dibeli
            foreach($request->items as $item) {
                $product = Product::find($item['product_id']);
                
                if(!$product) {
                    throw new \Exception("Barang ID " . $item['product_id'] . " hilang.");
                }

                if($product->stock < $item['qty']) {
                    throw new \Exception("Stok " . $product->name . " kurang!");
                }
                
                // Kurangi Stok
                $product->decrement('stock', $item['qty']);

                // Simpan Detail
                TransactionDetail::create([
                    'transaction_id' => $trx->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'subtotal' => $item['price'] * $item['qty'],
                ]);
            }

            DB::commit(); 

            // --- PERBAIKAN UTAMA DI SINI ---
            return response()->json([
                'message' => 'Transaksi Sukses', 
                'invoice_number' => $trx->invoice_no // Key harus 'invoice_number'
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); 
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    
    // 4. Cari Barang (Search Manual)
    public function searchProduct(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json([]);
        }

        // Search tanpa filter stok (agar barang kosong tetap bisa dicari utk di-edit)
        $products = Product::where('name', 'like', '%' . $query . '%')
                           ->limit(20)
                           ->get();

        return response()->json($products);
    }
    
    // 5. Ambil Semua Barang (Untuk Cetak Massal)
    public function getAllProducts() {
        return response()->json(Product::all()->sortByDesc('created_at')->values());
    }

    public function updateProduct(Request $request, $id) {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Barang tidak ditemukan'], 404);
        }

        $product->update([
            'name'  => $request->name,
            'price' => $request->price,
            'stock' => $request->stock,
            'category' => $request->category,
        ]);

        return response()->json(['message' => 'Berhasil diupdate', 'data' => $product]);
    }

    public function deleteProduct($id) {
        $product = Product::find($id);
        if ($product) {
            $product->delete();
            return response()->json(['message' => 'Berhasil dihapus']);
        }
        return response()->json(['message' => 'Barang tidak ditemukan'], 404);
    }
    
    // 6. Riwayat (Opsional)
    public function history() {
        $transactions = Transaction::with('details')->orderBy('created_at', 'desc')->get();
        return response()->json($transactions);
    }
}