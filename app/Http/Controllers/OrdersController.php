<?php

namespace App\Http\Controllers;

use App\Models\orders;         // Model untuk tabel orders
use App\Models\foods;          // Model untuk tabel foods
use App\Models\Order_Details;  // Model untuk tabel order_details
use App\Http\Requests\StoreordersRequest;
use App\Http\Requests\UpdateordersRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    // Menampilkan halaman utama pelanggan (katalog menu makanan)
    public function index()
    {
        // Mengambil semua data makanan dari database
        $foods = foods::all();

        // Mengembalikan tampilan halaman utama customer beserta data makanan
        return view('customer.index', compact('foods'));
    }

    // Memproses dan menyimpan pesanan baru dari pelanggan
    public function store(Request $request)
    {
        // 1. Validasi input dari form pemesanan
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'table_number'  => 'required|integer|min:1',
            'items'         => 'required|array',
            'items.*'       => 'nullable|integer|min:0',
        ]);

        // 2. Filter item yang dibeli saja (jumlah/quantity lebih dari 0)
        $orderedItems = array_filter($request->items, fn ($qty) => $qty > 0);

        // Jika tidak ada makanan yang dipilih, kembalikan dengan pesan error
        if (empty($orderedItems)) {
            return back()->with('error', 'Pilih minimal satu menu makanan!');
        }

        // 3. Memulai Database Transaction agar jika ada error, data tidak tersimpan setengah-setengah
        DB::beginTransaction();
        try {
            // Buat record pesanan utama terlebih dahulu dengan total harga awal 0
            $order = orders::create([
                'customer_name' => $request->customer_name,
                'table_number'  => $request->table_number,
                'total_price'   => 0,
                'status'        => 'pending',
            ]);

            $totalPrice = 0;

            // Loop setiap item makanan yang dipesan untuk menghitung subtotal dan menyimpan detailnya
            foreach ($orderedItems as $foodId => $quantity) {
                $food = foods::findOrFail($foodId);
                $subtotal = $food->price * $quantity;
                $totalPrice += $subtotal; // Menambahkan ke total keseluruhan

                // Simpan setiap item ke tabel detail pesanan (Order_Details)
                Order_Details::create([
                    'order_id' => $order->id,
                    'food_id'  => $food->id,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ]);
            }

            // Update total harga keseluruhan pada pesanan utama
            $order->update(['total_price' => $totalPrice]);

            // Simpan permanen perubahan ke database
            DB::commit();

            // Redirect kembali ke halaman customer dengan pesan sukses
            return redirect()->route('customer.index')->with('success', 'Pesanan berhasil dibuat! Nomor Meja: ' . $order->table_number);
        } catch (\Exception $e) {
            // Batalkan semua query ke DB jika terjadi kesalahan/error
            DB::rollBack();
            return back()->with('error', 'Gagal memproses pesanan: ' . $e->getMessage());
        }
    }

    // Menampilkan halaman dashboard admin untuk mengelola pesanan
    public function adminDashboard()
    {
        // Mengambil seluruh data pesanan berurutan dari yang terbaru
        $orders = orders::latest()->get(); 

        // Mengembalikan tampilan dashboard admin dengan data pesanan
        return view('dashboard', compact('orders'));
    }

    // Memperbarui status pesanan (misal: pending -> completed / canceled)
    public function updateStatus(Request $request, $id)
    {
        // 1. Validasi input status
        $request->validate([
            'status' => 'required|string'
        ]);

        // 2. Cari data pesanan berdasarkan ID
        $order = orders::findOrFail($id);
        
        // 3. Penyesuaian format status (mengubah 'cancelled' menjadi 'canceled' jika sesuai nilai enum DB)
        $status = $request->status;
        if ($status === 'cancelled') {
            $status = 'canceled';
        }

        // 4. Update status pesanan di database
        $order->update(['status' => $status]);

        // 5. Redirect ke dashboard admin dengan pesan sukses
        return redirect()->route('admin.dashboard')->with('success', 'Status pesanan #' . $order->id . ' berhasil diperbarui!');
    }
}